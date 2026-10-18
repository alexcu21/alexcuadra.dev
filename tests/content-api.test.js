import { test } from 'node:test';
import assert from 'node:assert/strict';
import * as api from '../src/data/client.js';

function respond(t, payload, inspect = () => {}, status = 200) {
  t.mock.method(globalThis, 'fetch', async (url, init) => {
    assert.equal(new URL(url).pathname, '/graphql');
    assert.equal(init.method, 'POST');
    inspect(JSON.parse(init.body));
    return new Response(JSON.stringify(payload), {
      status, headers: { 'content-type': 'application/json' },
    });
  });
}

for (const [method, operation, payload, expected] of [
  ['getHomeInfo', 'getHomeFields', { pages: { nodes: [{ homePageFields: { name_field: 'Alex' } }] } }, [{ homePageFields: { name_field: 'Alex' } }]],
  ['getProjectsInfo', 'getProjectFields', { projects: { nodes: [{ slug: 'project' }] } }, [{ slug: 'project' }]],
  ['getArticlesCategories', 'getArticleCategories', { categories: { nodes: [{ slug: 'talks', count: 2 }] } }, [{ slug: 'talks', count: 2 }]],
  ['getAboutFields', 'getAboutFields', { pages: { edges: [{ node: { content: 'About' } }] } }, { content: 'About' }],
  ['getAllArticleSlugs', 'AllArticles', { articles: { nodes: [{ slug: 'one' }, { slug: 'two' }] } }, ['one', 'two']],
]) {
  test(`${method} reads its WordPress response`, async t => {
    respond(t, { data: payload }, body => assert.match(body.query, new RegExp(`query ${operation}`)));
    assert.deepEqual(await api[method](), expected);
  });
}

test('category slug is sent as a variable, never interpolated into the query', async t => {
  const slug = 'talks-"-test';
  respond(t, { data: { articles: { nodes: [] } } }, body => {
    assert.deepEqual(body.variables, { slug });
    assert.ok(!body.query.includes(slug));
  });
  assert.deepEqual(await api.getArticlesByCategory(slug), []);
});

test('article lookup preserves a missing article as null', async t => {
  respond(t, { data: { article: null } }, body => assert.deepEqual(body.variables, { slug: 'missing' }));
  assert.equal(await api.getArticleBySlug('missing'), null);
});

test('article lookup returns featured media and ACF quote', async t => {
  const article = { slug: 'one', featuredImage: { node: { sourceUrl: 'https://example.com/image.jpg' } }, articlesFields: { quote: 'Quote' } };
  respond(t, { data: { article } });
  assert.deepEqual(await api.getArticleBySlug('one'), article);
});

test('GraphQL authentication errors reject even when HTTP status is 200', async t => {
  respond(t, { errors: [{ message: 'Operation requires authentication' }] });
  await assert.rejects(api.getArticleBySlug('one'), { code: 'CMS_AUTHORIZATION', status: 200 });
});

test('HTTP authorization failure rejects category loading', async t => {
  respond(t, {}, () => {}, 403);
  await assert.rejects(api.getArticlesByCategory('talks'));
});

test('network failures reject article loading', async t => {
  t.mock.method(globalThis, 'fetch', async () => { throw new Error('CMS unavailable'); });
  await assert.rejects(api.getArticleBySlug('one'), { code: 'CMS_NETWORK' });
});
