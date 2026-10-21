import { test } from 'node:test';
import assert from 'node:assert/strict';
import { getBlogPosts, getBlogPost, hasBlogPosts, blogSummary, blogDate } from '../src/data/blog-content.js';
import { withCmsRequest } from '../src/data/cms-request.js';
const post = (code = 'EN', changes = {}) => ({ status: 'publish', isRestricted: false, hasPassword: false,
  title: 'Real post', slug: 'real-post', content: '<p>Body</p>', excerpt: '<p>Summary</p>',
  language: { code }, translations: [], ...changes });
const page = (nodes, next = false, cursor = null) => ({ posts: { nodes, pageInfo: { hasNextPage: next, endCursor: cursor } } });
function harness(t, resolve) {
  t.mock.method(console, 'error', () => {});
  t.mock.method(console, 'info', () => {});
  return t.mock.method(globalThis, 'fetch', async (_url, init) => Response.json({ data: resolve(JSON.parse(init.body)) }));
}

test('blog navigation checks a single public localized post without retrieving its body', async t => {
  let nodes = [];
  harness(t, ({ query }) => {
    assert.match(query, /posts\(first: 1,/);
    assert.match(query, /language: ES/);
    assert.doesNotMatch(query, /\bcontent\b|\bexcerpt\b/);
    return { posts: { nodes } };
  });
  assert.equal(await hasBlogPosts('es'), false);
  nodes = [post('ES')];
  assert.equal(await hasBlogPosts('es'), true);
  nodes = [post('ES', { status: 'private' })];
  await assert.rejects(hasBlogPosts('es'), { code: 'CMS_BLOG_CONTRACT' });
  nodes = [post('EN')];
  await assert.rejects(hasBlogPosts('es'), { code: 'CMS_BLOG_CONTRACT' });
});

test('native blog reader paginates in the requested language without querying articles or projects', async t => {
  const fetch = harness(t, ({ query, variables }) => {
    assert.match(query, /posts\(first: 100/);
    assert.match(query, /language: ES/);
    assert.doesNotMatch(query, /articles\(|projects\(/);
    return variables.after === null ? page([post('ES')], true, 'next') : page([post('ES', { slug: 'second' })]);
  });
  assert.deepEqual((await getBlogPosts('es')).map(item => item.slug), ['real-post', 'second']);
  assert.equal(fetch.mock.callCount(), 2);
});

for (const changes of [{ status: 'draft' }, { isRestricted: true }, { hasPassword: true }, { language: { code: 'ES' } }, { title: null }, { slug: null }]) {
  test(`blog collection rejects invalid or nonpublic nodes: ${JSON.stringify(changes)}`, async t => {
    harness(t, () => page([post('EN', changes)]));
    await assert.rejects(getBlogPosts('en'), { code: 'CMS_BLOG_CONTRACT' });
  });
}

test('blog metadata contains only public unambiguous translations and no private extras', async t => {
  harness(t, () => ({ post: post('EN', { extraPrivate: 'SECRET', translations: [post('ES', { databaseId: 2, slug: 'es-post', content: 'SECRET' }),
    post('ES', { databaseId: 3, slug: 'private', status: 'private' })] }) }));
  const result = await getBlogPost('real-post', 'en');
  assert.deepEqual(result.translations, [{ databaseId: 2, slug: 'es-post', locale: 'es' }]);
  assert.doesNotMatch(JSON.stringify(result), /SECRET|private/);
});

test('empty, missing and other-language posts stay distinct from CMS failures', async t => {
  let result = page([]);
  harness(t, () => result);
  assert.deepEqual(await getBlogPosts('en'), []);
  result = { post: null };
  assert.equal(await getBlogPost('missing', 'en'), null);
  result = { post: post('ES') };
  assert.equal(await getBlogPost('real-post', 'en'), null);
  result = { post: post('EN', { isRestricted: true }) };
  const response = await withCmsRequest(async () => Response.json(await getBlogPost('real-post', 'en')));
  assert.equal(response.status, 503);
  assert.equal(response.headers.get('cache-control'), 'no-store');
});

test('invalid locales and path segments do not reach the CMS', async t => {
  const fetch = harness(t, () => assert.fail('no query'));
  await assert.rejects(getBlogPosts('ALL'), TypeError);
  assert.equal(await getBlogPost('a/b', 'en'), null);
  assert.equal(fetch.mock.callCount(), 0);
});

test('excerpt summaries remove unsafe HTML and preserve readable entities', () => {
  assert.equal(blogSummary('<p>WordPress &#8217; &amp; &lt;code&gt;</p><script>PRIVATE</script>'), 'WordPress ’ & <code>');
  assert.equal(blogSummary(null), '');
});

test('dates use real GMT values only, reject invalid calendar values and preserve UTC date', () => {
  assert.equal(blogDate('2026-02-30T00:00:00', 'en'), null);
  assert.equal(blogDate(null, 'es'), null);
  assert.equal(blogDate('2026-10-19T00:00:00', 'en').iso, '2026-10-19T00:00:00Z');
  assert.match(blogDate('2026-10-19T00:00:00', 'es').label, /19.*octubre.*2026/);
});
