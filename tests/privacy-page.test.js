import { test } from 'node:test';
import assert from 'node:assert/strict';
import { getPrivacyPage } from '../src/data/privacy-page.js';

const page = { databaseId: 123, title: 'Privacy', content: '<p>Approved text</p>',
  status: 'publish', isRestricted: false, hasPassword: false, language: { code: 'EN' } };
test('privacy stays unavailable without a valid configured source and performs no CMS query', async t => {
  const fetch = t.mock.method(globalThis, 'fetch', () => { throw Error('Must not query'); });
  for (const id of [undefined, '', '0', '-1', '123/injected', 123]) assert.equal(await getPrivacyPage(id), null);
  assert.equal(fetch.mock.callCount(), 0);
});
test('privacy projects only published English content from the configured page', async t => {
  t.mock.method(globalThis, 'fetch', async (_url, options) => {
    assert.deepEqual(JSON.parse(options.body).variables, { id: '123' });
    return Response.json({ data: { page: { ...page, privateMetadata: 'DO_NOT_COPY' } } });
  });
  assert.deepEqual(await getPrivacyPage('123'), { title: page.title, content: page.content });
});
test('privacy excludes drafts, protected pages, other languages, mismatched IDs and empty content', async t => {
  let changes;
  t.mock.method(globalThis, 'fetch', async () => Response.json({ data: { page: changes === null ? null : { ...page, ...changes } } }));
  for (changes of [null, { status: 'draft' }, { status: 'private' }, { hasPassword: true },
    { isRestricted: true }, { language: { code: 'ES' } }, { language: null }, { databaseId: 124 }, { title: '' }, { content: '' }]) {
    assert.equal(await getPrivacyPage('123'), null);
  }
});

test('Spanish privacy requires its explicit published Spanish source without English fallback', async t => {
  let target = { ...page, databaseId: 182, language: { code: 'ES' } };
  const fetch = t.mock.method(globalThis, 'fetch', async (_url, options) => {
    assert.deepEqual(JSON.parse(options.body).variables, { id: '182' });
    return Response.json({ data: { page: target } });
  });
  assert.deepEqual(await getPrivacyPage('182', 'es'), { title: page.title, content: page.content });
  for (const change of [{ language: { code: 'EN' } }, { status: 'draft' }, { hasPassword: true }, { databaseId: 180 }]) {
    target = { ...page, databaseId: 182, language: { code: 'ES' }, ...change };
    assert.equal(await getPrivacyPage('182', 'es'), null);
  }
  const before = fetch.mock.callCount();
  assert.equal(await getPrivacyPage('182', 'fr'), null);
  assert.equal(await getPrivacyPage(undefined, 'es'), null);
  assert.equal(fetch.mock.callCount(), before);
});
