import { test } from 'node:test';
import assert from 'node:assert/strict';
import { getProjectsInfo, getArticlesByCategory, getArticleBySlug, getArticlesCategories } from '../src/data/client.js';
import { localizedProjection } from '../src/data/localized-content.js';
import { withCmsRequest } from '../src/data/cms-request.js';

const node = (code, overrides = {}) => ({ status: 'publish', isRestricted: false, hasPassword: false,
  databaseId: 1, id: 'public', slug: 'public', title: 'Public', content: 'Published body',
  language: { code }, translations: [],
  categories: { nodes: [], pageInfo: { hasNextPage: false } },
  tags: { nodes: [], pageInfo: { hasNextPage: false } }, ...overrides });
const page = (type, nodes, hasNextPage = false, endCursor = null) => ({
  [type]: { nodes, pageInfo: { hasNextPage, endCursor } },
});
const cmsConfig = { WPGRAPHQL_AUTH_ENABLED: 'true', WPGRAPHQL_USERNAME: 'reader', WPGRAPHQL_APPLICATION_PASSWORD: 'synthetic-test' };

function harness(t, resolve) {
  t.mock.method(console, 'info', () => {});
  t.mock.method(console, 'error', () => {});
  const entries = new Map();
  const edgeCache = {
    match: async key => entries.get(key.url)?.clone(),
    put: async (key, response) => { entries.set(key.url, response.clone()); },
  };
  const fetch = t.mock.method(globalThis, 'fetch', async (_url, options) => {
    const body = JSON.parse(options.body);
    return Response.json({ data: resolve(body) });
  });
  const run = async action => {
    let result;
    const response = await withCmsRequest(async () => {
      result = await action();
      return Response.json(result);
    }, { cmsConfig, edgeCache, cacheOrigin: 'https://example.test' });
    return { response, result };
  };
  return { run, fetch, entries };
}

test('localized project requests isolate EN/ES cache and reproject cache hits safely', async t => {
  const ctx = harness(t, ({ query }) => page('projects', [node(query.includes('language: ES') ? 'ES' : 'EN', {
    extraPrivateField: 'DO_NOT_STORE', translations: [node(query.includes('language: ES') ? 'EN' : 'ES', {
      databaseId: 2, content: 'DO_NOT_STORE', extraACF: 'DO_NOT_STORE',
    }), node('ES', { hasPassword: true, slug: 'protected' })],
  })]));
  const en = await ctx.run(() => getProjectsInfo('en'));
  const es = await ctx.run(() => getProjectsInfo('es'));
  const hit = await ctx.run(() => getProjectsInfo('en'));
  assert.equal(en.response.status, 200);
  assert.equal(es.result[0].language.code, 'ES');
  assert.deepEqual(hit.result, en.result);
  assert.equal(ctx.fetch.mock.callCount(), 2);
  assert.equal(ctx.entries.size, 2);
  for (const entry of ctx.entries.values()) assert.doesNotMatch(await entry.clone().text(), /DO_NOT_STORE|protected/);
});

test('localized articles paginate and keep category values in GraphQL variables', async t => {
  const category = 'name" } injected';
  const ctx = harness(t, ({ query, variables }) => {
    assert.doesNotMatch(query, /injected/);
    assert.equal(variables.category, category);
    assert.match(query, /language: ES/);
    return variables.after === null ? page('articles', [node('ES')], true, 'next')
      : page('articles', [node('ES', { slug: 'second' })]);
  });
  const { result } = await ctx.run(() => getArticlesByCategory(category, 'es'));
  assert.deepEqual(result.map(item => item.slug), ['public', 'second']);
  assert.equal(ctx.fetch.mock.callCount(), 2);
});

for (const changes of [{ language: { code: 'ES' } }, { language: null }, { status: 'draft' },
  { status: 'private' }, { hasPassword: true }, { isRestricted: true }]) {
  test(`localized response fails closed without caching: ${JSON.stringify(changes)}`, async t => {
    const ctx = harness(t, () => page('projects', [node('EN', changes)]));
    assert.equal((await ctx.run(() => getProjectsInfo('en'))).response.status, 503);
    assert.equal(ctx.entries.size, 0);
  });
}

test('invalid cached locale triggers origin reload rather than serving a mixed-language hit', async t => {
  const ctx = harness(t, () => page('projects', [node('EN')]));
  await ctx.run(() => getProjectsInfo('en'));
  const [key] = ctx.entries.keys();
  ctx.entries.set(key, Response.json(page('projects', [node('ES')])));
  assert.equal((await ctx.run(() => getProjectsInfo('en'))).result[0].language.code, 'EN');
  assert.equal(ctx.fetch.mock.callCount(), 2);
});

test('article details require an exact language and expose only public translation metadata', async t => {
  const ctx = harness(t, () => ({ article: node('ES', { translations: [node('EN', { content: 'DO_NOT_COPY' })] }) }));
  assert.equal((await ctx.run(() => getArticleBySlug('public', 'en'))).result, null);
  const { result } = await ctx.run(() => getArticleBySlug('public', 'es'));
  assert.equal(result.language.code, 'ES');
  assert.doesNotMatch(JSON.stringify(result), /DO_NOT_COPY/);
});

test('repeated pagination cursors fail instead of looping indefinitely', async t => {
  const ctx = harness(t, () => page('projects', [node('EN')], true, 'same'));
  assert.equal((await ctx.run(() => getProjectsInfo('en'))).response.status, 503);
  assert.equal(ctx.fetch.mock.callCount(), 2);
});

test('unsupported locales never reach the CMS and projections are idempotent', async t => {
  const ctx = harness(t, () => assert.fail('must not fetch'));
  await assert.rejects(() => getProjectsInfo('ALL'), TypeError);
  assert.equal(ctx.fetch.mock.callCount(), 0);
  const projected = localizedProjection(page('articles', [node('EN', { translations: [node('ES')] })]), 'articles', 'en');
  assert.deepEqual(localizedProjection(projected, 'articles', 'en'), projected);
});

test('localized categories reject mixed-language responses and omit unknown fields', async t => {
  let code = 'EN';
  const ctx = harness(t, () => page('categories', [{ databaseId: 1, slug: 'category',
    language: { code }, unknown: 'DO_NOT_RETURN' }]));
  assert.equal((await ctx.run(() => getArticlesCategories('es'))).response.status, 503);
  code = 'ES';
  const { result } = await ctx.run(() => getArticlesCategories('es'));
  assert.equal(result[0].language.code, 'ES');
  assert.doesNotMatch(JSON.stringify(result), /DO_NOT_RETURN/);
  assert.equal(ctx.entries.size, 0);
});
