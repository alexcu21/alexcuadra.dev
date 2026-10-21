import { test } from 'node:test';
import assert from 'node:assert/strict';
import { contentLocale, graphqlLanguage, publicTranslationLinks, publicLocalizedCollection } from '../src/data/content-language.js';
import { publicCacheKey } from '../src/data/cms-cache.js';

const node = (code, overrides = {}) => ({ status: 'publish', isRestricted: false, hasPassword: false,
  databaseId: 10, slug: 'example', language: { code }, ...overrides });

test('locale comes from the CMS enum, with no slug or default-language fallback', () => {
  assert.equal(contentLocale(node('ES', { slug: 'english-looking' })), 'es');
  for (const value of [null, {}, node('FR'), node('en')]) assert.equal(contentLocale(value), undefined);
  assert.equal(graphqlLanguage('en'), 'EN');
  assert.equal(graphqlLanguage('es'), 'ES');
  for (const value of [undefined, 'ALL', 'EN', 'fr']) assert.throws(() => graphqlLanguage(value), TypeError);
});

test('translation links discard unpublished, protected, restricted and incomplete targets', () => {
  const valid = node('ES', { content: 'PRIVATE_EXTRA', acf: { secret: 'SECRET' } });
  const invalid = [{ status: 'draft' }, { status: 'private' }, { status: 'future' }, { hasPassword: true },
    { isRestricted: true }, { status: undefined }, { language: null }, { databaseId: null }, { slug: '../path' }];
  const source = node('EN', { translations: [valid, ...invalid.map(change => node('ES', change)), node('EN'), null] });
  assert.deepEqual(publicTranslationLinks(source), [{ databaseId: 10, slug: 'example', locale: 'es' }]);
  assert.doesNotMatch(JSON.stringify(publicTranslationLinks(source)), /PRIVATE_EXTRA|SECRET|acf|content/);
  assert.deepEqual(publicTranslationLinks({ ...source, hasPassword: true }), []);
  assert.deepEqual(publicTranslationLinks(node('EN')), []);
});

test('ambiguous translation relationships fail closed', () => {
  assert.deepEqual(publicTranslationLinks(node('EN', { translations: [node('ES'), node('ES', { databaseId: 11 })] })), []);
});

test('localized lists reject mixed languages and unpublished records', () => {
  assert.equal(publicLocalizedCollection([node('EN')], 'en'), true);
  for (const values of [[node('ES')], [node('EN'), node('ES')], [node('EN', { hasPassword: true })], [{}], null]) {
    assert.equal(publicLocalizedCollection(values, 'en'), false);
  }
});


test('GraphQL language variables isolate cache entries across locales', async () => {
  const key = locale => publicCacheKey('https://example.com', 'https://cms.example/graphql',
    'query Projects($language: LanguageCodeFilterEnum!) { projects(where: {language: $language}) { nodes { slug } } }',
    { language: graphqlLanguage(locale) }, 'service-public-test');
  assert.notEqual((await key('en')).url, (await key('es')).url);
  assert.equal((await key('es')).url, (await key('es')).url);
});
