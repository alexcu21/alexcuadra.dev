import { test } from 'node:test';
import assert from 'node:assert/strict';
import { hasPageTranslation } from '../src/data/page-navigation.js';
import { withCmsRequest } from '../src/data/cms-request.js';

const visible = code => ({ status: 'publish', isRestricted: false, hasPassword: false, language: { code } });
function setup(t, getPage) {
  t.mock.method(console, 'info', () => {});
  t.mock.method(console, 'error', () => {});
  const entries = new Map();
  const edgeCache = { match: async key => entries.get(key.url)?.clone(),
    put: async (key, response) => entries.set(key.url, response.clone()) };
  const fetch = t.mock.method(globalThis, 'fetch', async (_url, init) => {
    const body = JSON.parse(init.body);
    assert.deepEqual(body.variables, { id: '68' });
    assert.doesNotMatch(body.query, /\bcontent\b|aboutPage|homePageFields|featuredImage/);
    return Response.json({ data: { page: getPage() } });
  });
  const run = async () => {
    let result;
    const response = await withCmsRequest(async () => {
      result = await hasPageTranslation(68, 'es'); return Response.json(result);
    }, { edgeCache, cacheOrigin: 'https://example.test', cmsConfig: { WPGRAPHQL_AUTH_ENABLED: 'true',
      WPGRAPHQL_USERNAME: 'reader', WPGRAPHQL_APPLICATION_PASSWORD: 'synthetic' } });
    return { result, response };
  };
  return { run, entries, fetch };
}

test('page navigation uses only a real public translation and safely reuses metadata', async t => {
  const ctx = setup(t, () => ({ ...visible('EN'), content: 'DO_NOT_CACHE', aboutPage: { private: 'DO_NOT_CACHE' },
    translations: [{ ...visible('ES'), databaseId: 191, slug: 'sobre-mi', content: 'DO_NOT_CACHE' }] }));
  assert.equal((await ctx.run()).result, true);
  assert.equal((await ctx.run()).result, true);
  assert.equal(ctx.fetch.mock.callCount(), 1);
  for (const entry of ctx.entries.values()) {
    assert.equal(entry.headers.get('cache-control'), 'public, max-age=60');
    assert.doesNotMatch(await entry.clone().text(), /DO_NOT_CACHE|aboutPage|content/);
  }
});

for (const target of [null, { ...visible('ES'), status: 'draft' }, { ...visible('ES'), hasPassword: true },
  { ...visible('ES'), isRestricted: true }, { ...visible('EN') }]) {
  test(`navigation never announces a missing or unavailable Spanish page: ${JSON.stringify(target)}`, async t => {
    const ctx = setup(t, () => ({ ...visible('EN'), translations: target ? [{ databaseId: 191, slug: 'sobre-mi', ...target }] : [] }));
    assert.equal((await ctx.run()).result, false);
    assert.equal(ctx.fetch.mock.callCount(), 1, 'Never query a translated body or ACF');
  });
}

test('private sources fail without caching; an absent source has no translation', async t => {
  let page = { ...visible('EN'), status: 'private' };
  const ctx = setup(t, () => page);
  assert.equal((await ctx.run()).response.status, 503);
  assert.equal(ctx.entries.size, 0);
  page = null;
  assert.equal((await ctx.run()).result, false);
});

test('invalid navigation inputs do not reach WordPress', async t => {
  const fetch = t.mock.method(globalThis, 'fetch', () => assert.fail('no request'));
  await assert.rejects(hasPageTranslation(-1, 'es'), TypeError);
  await assert.rejects(hasPageTranslation(68, 'ALL'), TypeError);
  assert.equal(fetch.mock.callCount(), 0);
});
