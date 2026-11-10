import { test } from 'node:test';
import assert from 'node:assert/strict';
import { getArticlesCategories, getArticlesByCategory, getAllArticleSlugs } from '../src/data/client.js';
import { referenceCategoryPage, referenceDetailPage, referenceNotFound } from '../src/data/reference-pages.js';
import { collectCmsPages } from '../src/data/cms-pagination.js';
import { withCmsRequest } from '../src/data/cms-request.js';

const visible = { status: 'publish', isRestricted: false, hasPassword: false };
const connection = (nodes, hasNextPage = false, endCursor = null) => ({ nodes, pageInfo: { hasNextPage, endCursor } });
const config = { WPGRAPHQL_AUTH_ENABLED: 'true', WPGRAPHQL_USERNAME: 'fixture', WPGRAPHQL_APPLICATION_PASSWORD: 'synthetic' };
function harness(t, resolve) {
  t.mock.method(console, 'error', () => {});
  t.mock.method(console, 'info', () => {});
  return t.mock.method(globalThis, 'fetch', async (_url, init) => {
    const result = resolve(JSON.parse(init.body));
    return result instanceof Response ? result : Response.json({ data: result });
  });
}

for (const [operation, run, type] of [
  ['categories', () => getArticlesCategories(), 'categories'],
  ['category references', () => getArticlesByCategory('medium'), 'articles'],
  ['reference slugs', () => getAllArticleSlugs(), 'articles'],
]) {
  test(`legacy ${operation} reads all pages without filtering editorial language`, async t => {
    const fetch = harness(t, ({ query, variables }) => {
      assert.doesNotMatch(query, /language\s*:/);
      assert.match(query, /first: 100, after: \$after/);
      if (operation === 'category references') assert.equal(variables.slug, 'medium');
      const slug = variables.after === null ? 'first' : 'second';
      assert.ok(variables.after === null || variables.after === 'next');
      return { [type]: connection([{ ...visible, slug }], variables.after === null, variables.after === null ? 'next' : null) };
    });
    const result = await run();
    assert.deepEqual(result.map(item => typeof item === 'string' ? item : item.slug), ['first', 'second']);
    assert.equal(fetch.mock.callCount(), 2);
  });
}

for (const malformed of [undefined, { nodes: [] }, connection([], true, null), connection([], true, ''),
  { nodes: null, pageInfo: { hasNextPage: false } }, { nodes: [], pageInfo: { hasNextPage: 'false' } }]) {
  test(`pagination rejects malformed connections: ${JSON.stringify(malformed)}`, async t => {
    t.mock.method(console, 'error', () => {});
    await assert.rejects(collectCmsPages(async () => malformed, 'Fixture'), { code: 'CMS_PAGINATION' });
  });
}

test('pagination bounds repeated cursors and unbounded inventories', async t => {
  t.mock.method(console, 'error', () => {});
  let calls = 0;
  await assert.rejects(collectCmsPages(async () => { calls++; return connection([], true, 'same'); }, 'Fixture'), { code: 'CMS_PAGINATION' });
  assert.equal(calls, 2);
  calls = 0;
  await assert.rejects(collectCmsPages(async () => connection([], true, String(++calls)), 'Fixture'), { code: 'CMS_PAGINATION' });
  assert.equal(calls, 100);
});

test('later private reference fails closed instead of exposing a partial category', async t => {
  harness(t, ({ variables }) => ({ articles: variables.after === null
    ? connection([{ ...visible, slug: 'safe' }], true, 'next')
    : connection([{ ...visible, status: 'private', slug: 'SECRET' }]) }));
  const response = await withCmsRequest(async () => Response.json(await getArticlesByCategory('medium')), { cmsConfig: config });
  assert.equal(response.status, 503);
  assert.equal(response.headers.get('cache-control'), 'no-store');
  assert.doesNotMatch(await response.text(), /safe|SECRET/);
});

test('later upstream failure produces 503 with no partial inventory', async t => {
  harness(t, ({ variables }) => variables.after === null
    ? { articles: connection([{ ...visible, slug: 'PARTIAL' }], true, 'next') }
    : Response.json({ upstream: 'SECRET' }, { status: 429, headers: { 'Retry-After': '120' } }));
  const response = await withCmsRequest(async () => Response.json(await getArticlesByCategory('medium')), { cmsConfig: config });
  assert.equal(response.status, 503);
  assert.equal(response.headers.get('retry-after'), '120');
  assert.equal(response.headers.get('x-robots-tag'), 'noindex');
  assert.doesNotMatch(await response.text(), /PARTIAL|SECRET/);
});

test('category lookup finds later pages and distinguishes empty from missing categories', async t => {
  harness(t, ({ query, variables }) => query.includes('getArticleCategories')
    ? { categories: variables.after === null ? connection([{ slug: 'other' }], true, 'next') : connection([{ slug: 'medium', name: 'Medium' }]) }
    : { articles: connection([]) });
  assert.deepEqual(await referenceCategoryPage('medium'), { category: { slug: 'medium', name: 'Medium' }, articles: [] });
  assert.equal(await referenceCategoryPage('missing'), null);
});

test('detail requires category membership and tolerates optional quote/media', async t => {
  let article = { ...visible, slug: 'reference', title: 'Reference', categories: connection([{ slug: 'medium', name: 'Medium' }]) };
  harness(t, () => ({ article }));
  assert.equal((await referenceDetailPage('medium', 'reference')).article.title, 'Reference');
  assert.equal(await referenceDetailPage('podcast', 'reference'), null);
  article = null;
  assert.equal(await referenceDetailPage('medium', 'missing'), null);
});

test('not-found contract is non-cacheable and CMS outages stay 503', async t => {
  const absent = referenceNotFound();
  assert.equal(absent.status, 404);
  assert.equal(absent.headers.get('cache-control'), 'no-store');
  assert.equal(absent.headers.get('x-robots-tag'), 'noindex');
  harness(t, () => Response.json({}, { status: 502 }));
  const response = await withCmsRequest(async () => {
    const page = await referenceDetailPage('medium', 'reference');
    return page ? Response.json(page) : referenceNotFound();
  }, { cmsConfig: config });
  assert.equal(response.status, 503);
});

test('detail resolves a category present only on a later nested page', async t => {
  const fetch = harness(t, ({ query, variables }) => {
    if (query.includes('query getArticleBySlug')) return { article: { ...visible, slug: 'reference', title: 'Reference',
      categories: connection([{ slug: 'other' }], true, 'next-category') } };
    assert.match(query, /query ArticleCategoryPage/);
    assert.deepEqual(variables, { slug: 'reference', after: 'next-category' });
    return { article: { ...visible, categories: connection([{ slug: 'medium', name: 'Medium' }]) } };
  });
  assert.equal((await referenceDetailPage('medium', 'reference')).category.name, 'Medium');
  assert.equal(fetch.mock.callCount(), 2);
});

for (const failure of ['missing', 'private', 'outage', 'cursor']) {
  test(`nested category failure stays 503 rather than a false 404: ${failure}`, async t => {
    harness(t, ({ query }) => {
      if (query.includes('query getArticleBySlug')) return { article: { ...visible, slug: 'reference',
        categories: connection([{ slug: 'other' }], true, 'next') } };
      if (failure === 'outage') return Response.json({}, { status: 429 });
      if (failure === 'missing') return { article: null };
      return { article: { ...visible, ...(failure === 'private' ? { status: 'private' } : {}),
        categories: connection([], true, 'next') } };
    });
    const response = await withCmsRequest(async () => {
      const page = await referenceDetailPage('medium', 'reference');
      return page ? Response.json(page) : referenceNotFound();
    }, { cmsConfig: config });
    assert.equal(response.status, 503);
    assert.equal(response.headers.get('cache-control'), 'no-store');
  });
}
