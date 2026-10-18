import { test } from 'node:test';
import assert from 'node:assert/strict';
import * as api from '../src/data/client.js';
import { withCmsRequest } from '../src/data/cms-request.js';
import { isPublicContent } from '../src/data/content-visibility.js';

const publicNode = { status: 'publish', isRestricted: false, hasPassword: false,
  homePageFields: { name_field: 'Alex' }, content: 'Public', slug: 'public' };
const cmsConfig = { WPGRAPHQL_AUTH_ENABLED: 'true', WPGRAPHQL_USERNAME: 'reader', WPGRAPHQL_APPLICATION_PASSWORD: 'password' };
const operations = [
  ['getHomeInfo', undefined, node => ({ viewer: { databaseId: 1 }, pages: { nodes: [node] } })],
  ['getAboutFields', undefined, node => ({ pages: { edges: [{ node }] } })],
  ['getArticlesByCategory', 'medium', node => ({ articles: { nodes: [node] } })],
  ['getArticleBySlug', 'public', node => ({ article: node })],
  ['getAllArticleSlugs', undefined, node => ({ articles: { nodes: [node] } })],
];
for (const [method, argument, payload] of operations) {
  test(`${method} accepts published content and rejects private/protected/missing visibility`, async t => {
    t.mock.method(console, 'error', () => {});
    t.mock.method(console, 'info', () => {});
    let node;
    t.mock.method(globalThis, 'fetch', async () => new Response(JSON.stringify({ data: payload(node) }),
      { headers: { 'content-type': 'application/json' } }));
    for (const change of [null, { status: 'draft' }, { status: 'private' }, { status: 'future' },
      { hasPassword: true }, { isRestricted: true }, { status: undefined }]) {
      node = { ...publicNode, ...change };
      const response = await withCmsRequest(async () => Response.json(await api[method](argument)), { cmsConfig });
      assert.equal(response.status, change ? 503 : 200);
      if (change) assert.doesNotMatch(await response.text(), /Public|name_field/);
    }
  });
}

test('visibility predicate fails closed for absent or incomplete content', () => {
  for (const node of [null, undefined, {}, { status: 'publish' }]) assert.equal(isPublicContent(node), false);
  assert.equal(isPublicContent(publicNode), true);
});
