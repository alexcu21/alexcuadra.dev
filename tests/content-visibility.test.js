import { test } from 'node:test';
import assert from 'node:assert/strict';
import { isPublicContent } from '../src/data/content-visibility.js';

const publicNode = { status: 'publish', isRestricted: false, hasPassword: false,
  homePageFields: { name_field: 'Alex' }, content: 'Public', slug: 'public' };
test('visibility predicate fails closed for absent or incomplete content', () => {
  for (const node of [null, undefined, {}, { status: 'publish' }]) assert.equal(isPublicContent(node), false);
  assert.equal(isPublicContent(publicNode), true);
});
