import { test } from 'node:test';
import assert from 'node:assert/strict';
import { referenceCategoryCards, getReferenceCategoryCards } from '../src/data/reference-categories.js';
import { withCmsRequest } from '../src/data/cms-request.js';

test('reference cards exclude blog-only terms and count distinct references rather than shared category totals', () => {
  const categories = [{ slug: 'medium', count: 99 }, { slug: 'blog-only', count: 5 }];
  const reference = { slug: 'a', categories: { nodes: [{ slug: 'medium' }, { slug: 'medium' }] } };
  const result = referenceCategoryCards(categories, [reference, reference, { slug: 'b', categories: { nodes: [{ slug: 'medium' }] } }]);
  assert.deepEqual(result, [{ slug: 'medium', count: 2 }]);
  assert.equal(categories[0].count, 99);
});

test('a failed reference inventory does not leave successful cards based on post counts', async t => {
  t.mock.method(console, 'error', () => {});
  t.mock.method(globalThis, 'fetch', async (_url, init) => {
    const { query } = JSON.parse(init.body);
    return query.includes('SitemapReferences') ? new Response('', { status: 429 }) : Response.json({ data: {
      categories: { nodes: [{ slug: 'blog-only', count: 1 }], pageInfo: { hasNextPage: false } },
    } });
  });
  const response = await withCmsRequest(async () => Response.json(await getReferenceCategoryCards()));
  assert.equal(response.status, 503);
  assert.doesNotMatch(await response.text(), /blog-only/);
});
