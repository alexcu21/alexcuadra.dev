import { test } from 'node:test';
import assert from 'node:assert/strict';
import { referenceBreadcrumbs } from '../src/data/reference-seo.js';

test('reference breadcrumbs preserve legacy category and detail URLs without inferring authorship', () => {
  const schema = JSON.parse(referenceBreadcrumbs({ slug: 'medium', name: 'Medium' }, { slug: 'reference', title: 'Real title' }));
  assert.equal(schema['@type'], 'BreadcrumbList');
  assert.deepEqual(schema.itemListElement.map(item => [item.position, item.name, item.item]), [
    [1, 'Articles & Mentions', 'https://alexcuadra.dev/articles/'],
    [2, 'Medium', 'https://alexcuadra.dev/articles/category/medium'],
    [3, 'Real title', 'https://alexcuadra.dev/articles/category/medium/reference'],
  ]);
  assert.ok(!('author' in schema));
  assert.equal(JSON.parse(referenceBreadcrumbs({ slug: 'medium', name: 'Medium' })).itemListElement.length, 2);
});

test('CMS labels cannot terminate the JSON-LD script', () => {
  const title = '</script><script>alert(1)</script>';
  const value = referenceBreadcrumbs({ slug: 'medium', name: title }, { slug: 'reference', title });
  assert.ok(!value.includes('<'));
  assert.equal(JSON.parse(value).itemListElement[2].name, title);
});
