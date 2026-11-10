import assert from 'node:assert/strict';
import test from 'node:test';
import { getBlogPost, getBlogPosts, hasBlogPosts } from '../src/data/blog-content.js';
import { read, withStagingRequest } from '../src/data/emdash-provider.js';
import { getLabProjects, getHomeLabProjects } from '../src/data/lab-content.js';
import { getPrivacyPage } from '../src/data/privacy-page.js';
import { referencePage } from '../src/data/reference-preview.js';
import { stagingSitemap } from '../src/data/staging-sitemap.js';

const record = (locale, slug, extra = {}) => ({
  id: slug, locale, slug, title: slug, publicationStatus: 'published',
  contentHtml: '<p>Catalog content</p>', translations: [], ...extra,
});
const payload = (data, overrides = {}) => Response.json({
  version: 1, mode: 'published', contentVersion: 'fixture', data, ...overrides,
});
const inCatalog = (task, fetchCatalog) => withStagingRequest(task, fetchCatalog, 'published');

test('catalog reads deduplicate concurrent calls within one request and isolate locales and requests', async () => {
  const calls = [];
  const fetchCatalog = query => {
    const locale = query.get('locale');
    calls.push(locale);
    return payload([record(locale, locale)]);
  };
  const list = locale => read('list', 'posts', locale, { limit: '20' });
  await inCatalog(async () => {
    const [a, b, es] = await Promise.all([list('en'), list('en'), list('es')]);
    assert.deepEqual(a, b);
    assert.equal(es[0].locale, 'es');
  }, fetchCatalog);
  await inCatalog(() => list('en'), fetchCatalog);
  assert.deepEqual(calls, ['en', 'es', 'en']);
});

test('public catalog refuses preview data, drafts, foreign-language rows and partial lists', async () => {
  const list = () => read('list', 'posts', 'en');
  const scenarios = [
    [() => payload([], { mode: 'preview' }), 'INVALID_CONTRACT'],
    [() => payload([], { version: 2 }), 'INVALID_CONTRACT'],
    [() => payload([], { pageInfo: { nextCursor: 'another-page' } }), 'INVALID_CONTRACT'],
    [() => payload([record('en', 'draft', { publicationStatus: 'draft' })]), 'INVALID_PUBLICATION_STATUS'],
    [() => payload([record('es', 'entrada')]), 'LOCALE_MISMATCH'],
  ];
  for (const [fetchCatalog, code] of scenarios) {
    await assert.rejects(inCatalog(list, fetchCatalog), { message: code, status: 503 });
  }
});

test('a missing post is distinct from an unavailable or unauthorized catalog', async () => {
  const detail = () => getBlogPost('missing', 'en');
  assert.equal(await inCatalog(detail, () => new Response(null, { status: 404 })), null);
  await assert.rejects(inCatalog(detail, () => new Response('private error', { status: 503 })),
    { message: 'CMS_UNAVAILABLE', status: 503 });
  await assert.rejects(inCatalog(detail, () => new Response('private error', { status: 401 })),
    { message: 'PREVIEW_AUTH_REQUIRED', status: 401 });
  await assert.rejects(getBlogPosts('fr'), TypeError);
});

test('blog availability reads metadata and the Spanish archive orders by publication date', async () => {
  const calls = [];
  await inCatalog(async () => {
    assert.equal(await hasBlogPosts('es'), true);
    assert.deepEqual((await getBlogPosts('es')).map(post => post.slug), ['nuevo', 'anterior']);
  }, query => {
    assert.equal(query.get('collection'), 'posts');
    assert.equal(query.get('locale'), 'es');
    const operation = query.get('operation');
    calls.push(operation);
    if (operation === 'availability') return payload({ available: true });
    return payload([record('es', 'anterior', { originalPublishedAt: '2025-01-01T00:00:00Z' }),
      record('es', 'nuevo', { originalPublishedAt: '2026-01-01T00:00:00Z' })]);
  });
  assert.deepEqual(calls, ['availability', 'list']);
});

test('LAB preserves display order and localized labels and selects featured entries', async () => {
  const project = (slug, fields) => record('es', slug, { fields: { projectStatus: 'released', ...fields } });
  await inCatalog(async () => {
    const list = await getLabProjects('es');
    assert.deepEqual(list.map(item => item.slug), ['primero', 'segundo']);
    assert.equal(list[0].state.label, 'Publicado');
    assert.deepEqual((await getHomeLabProjects('es')).map(item => item.slug), ['segundo']);
  }, query => {
    assert.equal(query.get('collection'), 'lab_projects');
    return payload([project('segundo', { featured: true, displayOrder: 2 }),
      project('primero', { featured: false, displayOrder: 1 })]);
  });
});

test('privacy uses the explicit locale and keeps an absent Spanish translation absent', async () => {
  await inCatalog(async () => {
    assert.equal((await getPrivacyPage(undefined, 'en')).slug, 'privacy');
    assert.equal(await getPrivacyPage(undefined, 'es'), null);
  }, query => {
    assert.equal(query.get('operation'), 'page');
    assert.equal(query.get('pageKey'), 'privacy');
    if (query.get('locale') === 'es') return new Response(null, { status: 404 });
    return payload(record('en', 'privacy', { fields: {} }));
  });
});

test('a historical English reference redirects to the published Spanish path', async () => {
  const oldPath = '/articles/category/medium/historia/';
  const route = '/es/articulos/categoria/medium/historia/';
  const mention = record('es', 'historia', { route, legacyRoutes: [oldPath],
    terms: { reference_category: [{ slug: 'medium', name: 'Medium' }] } });
  const fetchCatalog = query => payload(query.get('locale') === 'es' ? [mention] : []);
  assert.deepEqual(await inCatalog(() => referencePage('en', { category: 'medium', slug: 'historia' }), fetchCatalog), { redirect: route });
  assert.equal((await inCatalog(() => referencePage('es', { category: 'medium', slug: 'historia' }), fetchCatalog)).mode, 'detail');
});

test('public sitemap uses actual catalog paths and rejects a failed inventory', async () => {
  const fetchCatalog = query => {
    if (query.get('locale') !== 'es' || query.get('collection') !== 'references') return payload([]);
    return payload([record('es', 'historia', { route: '/es/articulos/categoria/medium/historia/',
      terms: { reference_category: [{ slug: 'medium' }] } })]);
  };
  const xml = await inCatalog(stagingSitemap, fetchCatalog);
  assert.match(xml, /<loc>https:\/\/alexcuadra.dev\/es\/articulos\/categoria\/medium\/historia\/<\/loc>/);
  assert.match(xml, /<loc>https:\/\/alexcuadra.dev\/es\/contacto\/<\/loc>/);
  assert.doesNotMatch(xml, /<loc>https:\/\/alexcuadra.dev\/articles\/<\/loc>/);
  await assert.rejects(inCatalog(stagingSitemap, () => new Response(null, { status: 503 })), { status: 503 });
});
