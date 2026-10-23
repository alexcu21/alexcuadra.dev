import copy
import importlib.util
from pathlib import Path
import unittest

spec = importlib.util.spec_from_file_location('privacy', Path(__file__).resolve().parents[2] / 'scripts/editorial-graphql-privacy.py')
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)


def page(ids=(146,), more=False, cursor=None):
    return {'data': {'publicControl': {'databaseId': 146}, 'privateContent': None, 'privateNode': None,
                     'contentNodes': {'nodes': [{'databaseId': i} for i in ids],
                                      'pageInfo': {'hasNextPage': more, 'endCursor': cursor}}}}


class PrivacyTests(unittest.TestCase):
    def test_complete_pages_and_readonly_query(self):
        calls = []
        pages = iter([(200, page(more=True, cursor='a')), (200, page(ids=(196,)))])
        def fetch(body):
            calls.append(copy.deepcopy(body))
            return next(pages)
        result = m.run(227, fetch, lambda _: None)
        self.assertTrue(result['nodePrivacyVerified'])
        self.assertEqual(result['requests'], 2)
        self.assertEqual(calls[1]['variables']['after'], 'a')
        self.assertNotIn('mutation', calls[0]['query'])

    def test_denied_is_not_node_proof(self):
        result = m.run(227, lambda _: (403, None))
        self.assertFalse(result['nodePrivacyVerified'])
        self.assertFalse(result['collectionComplete'])

    def test_graphql_authentication_gate_is_not_node_proof(self):
        payload = {'errors': [{'message': f'The field "RootQuery.{field}" cannot be accessed without authentication.'}
                              for field in ('project','contentNode','node','contentNodes')]}
        result = m.run(227, lambda _: (200, payload))
        self.assertEqual(result['outcome'], 'anonymous-endpoint-access-denied')
        self.assertFalse(result['nodePrivacyVerified'])
        payload['errors'][0]['message'] = 'unrelated failure'
        with self.assertRaisesRegex(ValueError, 'PUBLIC_CONTROL_FAILED'):
            m.run(227, lambda _: (200, payload))

    def test_private_node_exposed_or_missing(self):
        for key in ['privateContent', 'privateNode']:
            for present in [True, False]:
                payload = page()
                if present: payload['data'][key] = {'id': 'private'}
                else: del payload['data'][key]
                with self.assertRaisesRegex(ValueError, 'PRIVATE_NODE_NOT_HIDDEN'):
                    m.inspect_page(200, payload, 227, None)

    def test_collection_exposure(self):
        with self.assertRaisesRegex(ValueError, 'PRIVATE_NODE_IN_COLLECTION'):
            m.inspect_page(200, page(ids=(146, 227)), 227, None)

    def test_public_control_and_global_errors(self):
        payload = page(); payload['data']['publicControl'] = None
        with self.assertRaisesRegex(ValueError, 'PUBLIC_CONTROL_FAILED'):
            m.inspect_page(200, payload, 227, None)
        payload = page(); payload['errors'] = [{'message': 'internal'}]
        with self.assertRaisesRegex(ValueError, 'UNEXPECTED_GRAPHQL_ERRORS'):
            m.inspect_page(200, payload, 227, None)

    def test_cycle_and_access_change(self):
        for pages, error in [([(200,page(more=True,cursor='a')),(403,None)], 'ACCESS_CHANGED'),
                             ([(200,page(more=True,cursor='a')),(200,page(more=True,cursor='b')),
                               (200,page(more=True,cursor='a'))], 'PAGINATION_CYCLE')]:
            it = iter(pages)
            with self.assertRaisesRegex(ValueError, error):
                m.run(227, lambda _: next(it), lambda _: None)

    def test_invalid_status_cursor_and_bound(self):
        with self.assertRaisesRegex(ValueError, 'HTTP_OR_JSON'):
            m.inspect_page(429, None, 227, None)
        with self.assertRaisesRegex(ValueError, 'PAGINATION_NOT_ADVANCING'):
            m.inspect_page(200, page(more=True,cursor='a'), 227, 'a')
        count = iter(range(11))
        with self.assertRaisesRegex(ValueError, 'COLLECTION_BOUND'):
            m.run(227, lambda _: (200,page(more=True,cursor=str(next(count)))), lambda _: None)
