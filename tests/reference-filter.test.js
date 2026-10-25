import { test } from 'node:test';
import assert from 'node:assert/strict';
import { matchesReference } from '../src/scripts/reference-filter.js';
import { referenceDirectory, getReferenceArchive, getReferenceCategoryCards } from '../src/data/reference-categories.js';
import { withCmsRequest } from '../src/data/cms-request.js';

test('title search ignores accents/case and combines with an exact channel', () => {
  assert.equal(matchesReference('Diseño con WordPress', ['medium', 'podcast'], ' DISENO ', 'medium'), true);
  assert.equal(matchesReference('Diseño con WordPress', ['medium'], 'wordpress', 'podcast'), false);
  assert.equal(matchesReference('Diseño con WordPress', ['medium'], 'not present', ''), false);
  assert.equal(matchesReference('Diseño con WordPress', ['medium'], '', ''), true);
  assert.equal(matchesReference('Design', ['podcast-extra'], '', 'podcast'), false);
});

test('archive and channel cards share the existing inventory and taxonomy reads', async t => {
  const info = { hasNextPage: false, endCursor: null };
  const fetch = t.mock.method(globalThis, 'fetch', async (_url, init) => {
    const { query } = JSON.parse(init.body);
    if (query.includes('getArticleCategories')) return Response.json({data:{categories:{
      nodes:[{slug:'medium',name:'Medium'}],pageInfo:info,
    }}});
    assert.match(query, /SitemapReferences/);
    return Response.json({data:{articles:{nodes:[{status:'publish',hasPassword:false,isRestricted:false,
      slug:'entry',title:'Public reference',categories:{nodes:[{slug:'medium'}],pageInfo:info}}],pageInfo:info}}});
  });
  const response = await withCmsRequest(async () => {
    const [archive, cards] = await Promise.all([getReferenceArchive(),getReferenceCategoryCards()]);
    assert.equal(archive.entries[0].title, 'Public reference');
    assert.deepEqual(archive.channels, cards);
    return new Response('ok');
  });
  assert.equal(response.status,200);
  assert.equal(fetch.mock.callCount(),2);
});

test('directory deduplicates references, retains real channels and preserves existing paths', () => {
  const categories = [{ slug: 'medium', name: 'Medium' }, { slug: 'podcast', name: 'Podcast' }];
  const reference = { slug: 'entry', title: 'Real title', categories: { nodes: [
    {slug:'podcast'}, {slug:'medium'}, {slug:'medium'}, {slug:'unknown'},
  ] } };
  assert.deepEqual(referenceDirectory(categories, [reference, reference]), [{ title: 'Real title',
    path:'/articles/category/medium/entry', channels:[{slug:'medium',name:'Medium'}, {slug:'podcast',name:'Podcast'}] }]);
  assert.deepEqual(referenceDirectory(categories, [{...reference,title:null}, {...reference,title:' '},
    {...reference,categories:{nodes:[{slug:'unknown'}]}}]), []);
});
