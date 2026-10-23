#!/usr/bin/env python3
"""Read-only anonymous HTTP privacy check. Never accepts credentials or writes CMS data."""
import argparse
import base64
import json
import ssl
import sys
from pathlib import Path
import time
import urllib.error
import urllib.request

ENDPOINT = 'https://content.alexcuadra.dev/graphql'
MAX_BYTES = 1024 * 1024
QUERY = '''query EditorialPrivacy($private:ID!,$global:ID!,$after:String){
  publicControl:project(id:"146",idType:DATABASE_ID){databaseId}
  privateContent:contentNode(id:$private,idType:DATABASE_ID){databaseId}
  privateNode:node(id:$global){id}
  contentNodes(first:100,after:$after){nodes{databaseId}pageInfo{hasNextPage endCursor}}
}'''


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def inspect_page(status, payload, proposal_id, previous_cursor):
    # A blocked endpoint is reported separately; it does not prove node-level filtering.
    if status in (401, 403):
        return {'blocked': True}
    if status != 200 or not isinstance(payload, dict):
        raise ValueError('HTTP_OR_JSON_UNEXPECTED')
    data = payload.get('data')
    required = {f'The field "RootQuery.{field}" cannot be accessed without authentication.'
                for field in ('project', 'contentNode', 'node', 'contentNodes')}
    errors = payload.get('errors')
    if data is None and isinstance(errors, list) and len(errors) == 4 and all(isinstance(e, dict) for e in errors):
        if {e.get('message') for e in errors} == required and all(not e.get('path') for e in errors):
            return {'blocked': True}
    if not isinstance(data, dict) or data.get('publicControl') != {'databaseId': 146}:
        raise ValueError('PUBLIC_CONTROL_FAILED')
    for key in ('privateContent', 'privateNode'):
        if key not in data or data[key] is not None:
            raise ValueError('PRIVATE_NODE_NOT_HIDDEN')
    errors = payload.get('errors', [])
    if not isinstance(errors, list) or any(
        not isinstance(e, dict) or not isinstance(e.get('path'), list)
        or not e['path'] or e['path'][0] not in ('privateContent', 'privateNode') for e in errors
    ):
        raise ValueError('UNEXPECTED_GRAPHQL_ERRORS')
    connection = data.get('contentNodes')
    if not isinstance(connection, dict) or not isinstance(connection.get('nodes'), list):
        raise ValueError('COLLECTION_UNAVAILABLE')
    for node in connection['nodes']:
        if not isinstance(node, dict) or type(node.get('databaseId')) is not int:
            raise ValueError('COLLECTION_INVALID')
        if node['databaseId'] == proposal_id:
            raise ValueError('PRIVATE_NODE_IN_COLLECTION')
    info = connection.get('pageInfo')
    if not isinstance(info, dict) or type(info.get('hasNextPage')) is not bool:
        raise ValueError('PAGINATION_INVALID')
    cursor = info.get('endCursor')
    if info['hasNextPage'] and (not isinstance(cursor, str) or not cursor or cursor == previous_cursor):
        raise ValueError('PAGINATION_NOT_ADVANCING')
    return {'blocked': False, 'next': cursor if info['hasNextPage'] else None}


def run(proposal_id, fetch, pause=time.sleep):
    after = None
    seen = set()
    for page in range(1, 11):
        status, payload = fetch({'query': QUERY, 'variables': {
            'private': str(proposal_id),
            'global': base64.b64encode(f'post:{proposal_id}'.encode()).decode(), 'after': after}})
        result = inspect_page(status, payload, proposal_id, after)
        if result['blocked']:
            if page != 1:
                raise ValueError('ACCESS_CHANGED_DURING_PAGINATION')
            return {'outcome': 'anonymous-endpoint-access-denied', 'httpStatus': status,
                    'nodePrivacyVerified': False, 'collectionComplete': False, 'requests': page}
        after = result['next']
        if after is None:
            return {'outcome': 'anonymous-private-proposal-hidden', 'httpStatus': status,
                    'nodePrivacyVerified': True, 'collectionComplete': True, 'requests': page}
        if after in seen:
            raise ValueError('PAGINATION_CYCLE')
        seen.add(after)
        pause(2)
    raise ValueError('COLLECTION_BOUND_EXCEEDED')


def fetch(body):
    req = urllib.request.Request(ENDPOINT, data=json.dumps(body).encode(), headers={
        'Content-Type': 'application/json', 'Accept': 'application/json',
        'User-Agent': 'AlexCuadra-Editorial-Privacy/1.0'}, method='POST')
    context = ssl.create_default_context()
    # Framework Python on macOS may have no bundled default CA file. Use the
    # existing system trust bundle, preserving hostname and certificate checks.
    if sys.platform == "darwin" and ssl.get_default_verify_paths().cafile is None and Path("/etc/ssl/cert.pem").is_file():
        context.load_verify_locations(cafile="/etc/ssl/cert.pem")
    opener = urllib.request.build_opener(NoRedirect(), urllib.request.HTTPSHandler(context=context))
    try:
        response = opener.open(req, timeout=20)
    except urllib.error.HTTPError as exc:
        if exc.code in (401, 403):
            exc.close()
            return exc.code, None
        raise ValueError(f'HTTP_STATUS_{exc.code}') from None
    with response:
        data = response.read(MAX_BYTES + 1)
        if len(data) > MAX_BYTES:
            raise ValueError('RESPONSE_TOO_LARGE')
        try:
            return response.status, json.loads(data)
        except (ValueError, UnicodeError):
            raise ValueError('INVALID_JSON') from None


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.parse_args()
    report = {'format': 'editorial-graphql-http-privacy-v1', 'endpoint': ENDPOINT,
              'identity': 'anonymous', 'proposalId': 227, 'publicControlProjectId': 146,
              'readOnly': True, 'credentialsUsed': False, 'redirectsAllowed': False}
    try:
        report.update(run(227, fetch))
    except (ValueError, OSError) as exc:
        # Only our fixed error labels are printed, never response bodies or network details.
        report.update(outcome='verification-failed', error=str(exc) if isinstance(exc, ValueError) else 'NETWORK_ERROR')
        print(json.dumps(report, sort_keys=True))
        return 1
    print(json.dumps(report, sort_keys=True))
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
