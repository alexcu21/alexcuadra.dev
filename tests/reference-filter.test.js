import { test } from 'node:test';
import assert from 'node:assert/strict';
import { matchesReference, referenceFilterState, referenceFilterUrl } from '../src/scripts/reference-filter.js';

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
