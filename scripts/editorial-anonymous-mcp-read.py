#!/usr/bin/env python3
"""Anonymous, bounded read-only inspection attempts; no credentials, redirects or retries."""
import json
import ssl
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT/'docs/qa/MCP_OPERATING_ANONYMOUS_HTTP_2026-10-24.json'
ENDPOINTS = [
    'https://content.alexcuadra.dev/wp-json/mcp/mcp-adapter-default-server',
    'https://content.alexcuadra.dev/?rest_route=/mcp/mcp-adapter-default-server',
]


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def has_document_keys(value):
    if isinstance(value, dict):
        return bool(set(value) & {'document', 'before', 'after', 'patch', 'body_html', 'project_tagline'}) or any(has_document_keys(v) for v in value.values())
    return isinstance(value, list) and any(has_document_keys(v) for v in value)


def probe(endpoint):
    # No cookie processor, password manager, Authorization, nonce or MCP session.
    # Python.org macOS builds may lack their optional local CA symlink.
    # Use an existing system CA bundle in that case; never disable TLS checks.
    ca = '/etc/ssl/cert.pem' if ssl.get_default_verify_paths().cafile is None and Path('/etc/ssl/cert.pem').is_file() else None
    context = ssl.create_default_context(cafile=ca)
    opener = urllib.request.build_opener(NoRedirect(), urllib.request.HTTPSHandler(context=context))
    payload = {'jsonrpc': '2.0', 'id': 1, 'method': 'tools/call', 'params': {
        'name': 'mcp-adapter-execute-ability', 'arguments': {
            'ability_name': 'alexcuadra/inspect-project-review',
            'parameters': {'review_id': 1, 'locale': 'en'}}}}
    request = urllib.request.Request(endpoint, data=json.dumps(payload).encode(), method='POST', headers={
        'Content-Type': 'application/json', 'Accept': 'application/json, text/event-stream',
        'User-Agent': 'AlexCuadra-Editorial-Anonymous-Read-Check/1'})
    try:
        response = opener.open(request, timeout=15)
    except urllib.error.HTTPError as error:
        response = error
    except (urllib.error.URLError, TimeoutError, OSError) as error:
        raise RuntimeError('ANONYMOUS_PROBE_NETWORK_FAILED') from error
    with response:
        status = response.code
        raw = response.read(65537)
    within_limit = len(raw) <= 65536
    try:
        body = json.loads(raw) if within_limit else None
    except (ValueError, UnicodeError):
        body = None
    error_code = body.get('code') if isinstance(body, dict) else None
    error_status = body.get('data', {}).get('status') if isinstance(body, dict) and isinstance(body.get('data'), dict) else None
    private_keys = has_document_keys(body)
    blocked = status in [401, 403] and error_code == 'rest_forbidden' and error_status == status and not private_keys
    return {'endpoint': endpoint, 'httpStatus': status, 'responseBytesRead': len(raw),
            'responseWithin64KiB': within_limit, 'wordpressPermissionErrorVerified': blocked,
            'wordpressErrorCode': error_code if error_code in ['rest_forbidden', 'rest_not_logged_in'] else None,
            'wordpressErrorStatus': error_status if isinstance(error_status, int) else None,
            'privateDocumentKeyDetected': private_keys, 'rawResponseStored': False,
            'authorizationHeaderSent': False, 'cookiesSent': False,
            'mcpSessionHeaderSent': False, 'redirectFollowed': False,
            'tlsCertificateVerificationEnabled': context.verify_mode == ssl.CERT_REQUIRED,
            'tlsHostnameVerificationEnabled': context.check_hostname,
            'caSource': 'system-ca-bundle' if ca else 'python-default-trust'}


def main():
    if OUTPUT.exists():
        raise FileExistsError('PRESERVE_EXISTING_ANONYMOUS_EVIDENCE')
    results = [probe(endpoint) for endpoint in ENDPOINTS]
    report = {'format': 'editorial-operating-anonymous-http-v1', 'dateLocal': '2026-10-24',
              'requests': results, 'allProbesBlockedByWordPressPermission': all(r['wordpressPermissionErrorVerified'] for r in results),
              'requestedAbility': 'alexcuadra/inspect-project-review', 'requestedReviewId': 1, 'locale': 'en',
              'editorialMutationsRequested': False, 'initializeOrSessionCreationRequested': False,
              'authenticatedOtherOwnerPrivacyVerified': False, 'otherRestOrGraphqlSurfacesVerified': False,
              'sshExecuted': False, 'dockerStarted': False, 'backupCreated': False,
              'postmetaIncidentResolved': False}
    with OUTPUT.open('x') as stream:
        json.dump(report, stream, indent=2)
        stream.write('\n')
    print(json.dumps(report))
    if not report['allProbesBlockedByWordPressPermission']:
        raise SystemExit('ANONYMOUS_HTTP_RESULT_REQUIRES_REVIEW')


if __name__ == '__main__':
    main()
