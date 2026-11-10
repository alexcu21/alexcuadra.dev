import { test } from 'node:test';
import assert from 'node:assert/strict';
import { hasBlogPosts } from '../src/data/blog-content.js';
import { hasLabProjects } from '../src/data/lab-content.js';
import { publicAvailabilityOptions } from '../src/data/cms-public-availability.js';
import { withCmsRequest } from '../src/data/cms-request.js';

const visible = code => ({ status: 'publish', isRestricted: false, hasPassword: false, language: { code } });
for (const [field, run] of [['posts', hasBlogPosts], ['labProjects', hasLabProjects]]) {
  test(`${field} availability caches only public flags and isolates locales/credentials`, async t => {
    t.mock.method(console, 'info', () => {});
    const entries = new Map();
    const edgeCache = { match: async key => entries.get(key.url)?.clone(),
      put: async (key, response) => entries.set(key.url, response.clone()) };
    const fetch = t.mock.method(globalThis, 'fetch', async (_url, init) => {
      const { query } = JSON.parse(init.body);
      return Response.json({ data: { [field]: { nodes: [{ ...visible(query.includes('language: ES') ? 'ES' : 'EN'),
        privateExtra: 'DO_NOT_CACHE', content: 'DO_NOT_CACHE' }] } } });
    });
    const call = async (locale, password = 'synthetic') => {
      let result;
      const response = await withCmsRequest(async () => {
        result = await run(locale); return Response.json(result);
      }, { edgeCache, cacheOrigin: 'https://example.test', cmsConfig: { WPGRAPHQL_AUTH_ENABLED: 'true',
        WPGRAPHQL_USERNAME: 'reader', WPGRAPHQL_APPLICATION_PASSWORD: password } });
      assert.equal(response.status, 200);
      return result;
    };
    assert.equal(await call('en'), true);
    assert.equal(await call('en'), true);
    assert.equal(fetch.mock.callCount(), 1);
    await call('es');
    await call('en', 'rotated-synthetic');
    assert.equal(fetch.mock.callCount(), 3);
    for (const [key, entry] of entries) {
      assert.doesNotMatch(key, /synthetic|reader/);
      assert.equal(entry.headers.get('cache-control'), 'public, max-age=60');
      assert.doesNotMatch(await entry.clone().text(), /DO_NOT_CACHE|content|privateExtra/);
    }
  });
}

test('availability cache rejects private, protected, wrong-language and oversized inventories', () => {
  const options = publicAvailabilityOptions('posts', 'en');
  for (const nodes of [[{ ...visible('EN'), status: 'private' }], [{ ...visible('EN'), hasPassword: true }],
    [visible('ES')], [visible('EN'), visible('EN')], undefined]) {
    assert.equal(options.validatePublicData({ posts: { nodes } }), false);
    assert.equal(options.servicePublicProjection({ posts: { nodes } }), undefined);
  }
  assert.equal(options.validatePublicData({ posts: { nodes: [] } }), true);
});

test('availability projection is idempotent and rejects unsupported inputs', () => {
  const options = publicAvailabilityOptions('labProjects', 'es');
  const projected = options.servicePublicProjection({ labProjects: { nodes: [visible('ES')] } });
  assert.deepEqual(options.servicePublicProjection(projected), projected);
  assert.throws(() => publicAvailabilityOptions('users', 'en'), TypeError);
  assert.throws(() => publicAvailabilityOptions('posts', 'ALL'), TypeError);
});
