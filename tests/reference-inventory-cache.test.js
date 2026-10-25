import { test } from 'node:test';
import assert from 'node:assert/strict';
import { publicReferenceInventory, referenceInventory } from '../src/data/reference-inventory.js';
import { withCmsRequest } from '../src/data/cms-request.js';
const node = { status: 'publish', isRestricted: false, hasPassword: false, slug: 'reference',
  secret: 'PRIVATE', categories: { nodes: [{ slug: 'medium', secret: 'PRIVATE' }], pageInfo: { hasNextPage: false } } };
const data = { viewer: { databaseId: 123 }, articles: { nodes: [node], pageInfo: { hasNextPage: false } } };
test('reference cache projection is idempotent and strips identity and unrequested fields', () => {
  const projected = publicReferenceInventory(data);
  assert.deepEqual(publicReferenceInventory(projected), projected);
  assert.doesNotMatch(JSON.stringify(projected), /PRIVATE|viewer|databaseId/);
});
for (const changes of [{status:'draft'}, {status:'private'}, {hasPassword:true}, {isRestricted:true},
  {status:undefined}, {slug:'../private'}, {categories:{ nodes:[{slug:'bad/path'}], pageInfo:{hasNextPage:false} }},
  {categories:{ nodes:[], pageInfo:{hasNextPage:true} }}]) {
  test(`reference projection rejects unsafe inventory ${JSON.stringify(changes)}`, () => {
    assert.equal(publicReferenceInventory({ ...data, articles: { ...data.articles, nodes: [{ ...node, ...changes }] } }), undefined);
  });
}
test('reference projection rejects incomplete pagination', () => {
  assert.equal(publicReferenceInventory({ articles: { nodes: [node], pageInfo: {hasNextPage:true} } }), undefined);
});
test('authenticated reference inventory shares only projected data and partitions rotated credentials', async t => {
  t.mock.method(console, 'info', () => {});
  const fetch = t.mock.method(globalThis, 'fetch', async () => Response.json({data}));
  const entries = new Map();
  const edgeCache = { match: async key => entries.get(key.url)?.clone(),
    put: async (key, response) => { assert.equal(response.headers.get('cache-control'), 'public, max-age=60'); entries.set(key.url, response.clone()); } };
  const config = { edgeCache, cacheOrigin:'https://staging.test', cmsConfig:{ WPGRAPHQL_AUTH_ENABLED:'true',
    WPGRAPHQL_USERNAME:'reader', WPGRAPHQL_APPLICATION_PASSWORD:'secret' } };
  const run = options => withCmsRequest(async () => Response.json(await referenceInventory()), options);
  const first = await run(config), second = await run(config);
  assert.equal(first.status, 200);
  assert.deepEqual(await first.json(), await second.json());
  assert.equal(fetch.mock.callCount(), 1);
  for (const entry of entries.values()) assert.doesNotMatch(await entry.clone().text(), /PRIVATE|viewer|databaseId/);
  await run({...config, cmsConfig:{...config.cmsConfig, WPGRAPHQL_APPLICATION_PASSWORD:'rotated'}});
  await run({...config, cmsConfig:undefined});
  assert.equal(fetch.mock.callCount(), 3);
});
