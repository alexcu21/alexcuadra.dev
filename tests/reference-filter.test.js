import { test } from 'node:test';
import assert from 'node:assert/strict';
import { matchesReference, referenceFilterState, referenceFilterUrl } from '../src/scripts/reference-filter.js';
import { referenceDirectory, getReferenceArchive, getReferenceCategoryCards } from '../src/data/reference-categories.js';
import { withCmsRequest } from '../src/data/cms-request.js';

test('title search ignores accents/case and combines with an exact channel', () => {
  assert.equal(matchesReference('Diseño con WordPress', ['medium', 'podcast'], ' DISENO ', 'medium'), true);
  assert.equal(matchesReference('Diseño con WordPress', ['medium'], 'wordpress', 'podcast'), false);
  assert.equal(matchesReference('Diseño con WordPress', ['medium'], 'not present', ''), false);
  assert.equal(matchesReference('Diseño con WordPress', ['medium'], '', ''), true);
  assert.equal(matchesReference('Design', ['podcast-extra'], '', 'podcast'), false);
});

test('shared reference filters accept only an exact published channel and bound title queries', () => {
  assert.deepEqual(referenceFilterState(new URLSearchParams('q=Dise%C3%B1o&channel=medium'),['medium']),{query:'Diseño',channel:'medium'});
  assert.equal(referenceFilterState(new URLSearchParams('channel=unknown'),['medium']).channel,'');
  assert.equal(referenceFilterState(new URLSearchParams({q:'x'.repeat(400)}),[]).query.length,200);
});
test('ambiguous reference filter parameters are not guessed', () => {
  assert.deepEqual(referenceFilterState(new URLSearchParams('q=one&q=two&channel=medium&channel=podcast'),['medium','podcast']),{query:'',channel:''});
});
test('reference filter URLs encode text and retain unrelated parameters and fragments', () => {
  const query='<script>Diseño & React</script>';
  const url=referenceFilterUrl('https://example.test/articles/?keep=yes#reference-directory-title',{query,channel:'medium'});
  assert.equal(url.pathname,'/articles/');assert.equal(url.searchParams.get('q'),query);assert.equal(url.searchParams.get('channel'),'medium');
  assert.equal(url.searchParams.get('keep'),'yes');assert.equal(url.hash,'#reference-directory-title');assert.ok(!url.href.includes('<script>'));
});
test('clearing reference filters removes only their own URL parameters', () => {
  const url=referenceFilterUrl('https://example.test/articles/?q=title&channel=medium&keep=yes#reference-directory-title',{query:'',channel:''});
  assert.equal(url.search,'?keep=yes');assert.equal(url.hash,'#reference-directory-title');
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
