import { test } from 'node:test';
import assert from 'node:assert/strict';
import { getLabProjects, getLabProject, hasLabProjects, getHomeLabProjects, labStatus, labLink } from '../src/data/lab-content.js';
import { withCmsRequest } from '../src/data/cms-request.js';
const node = (code = 'EN', changes = {}) => ({ status: 'publish', isRestricted: false, hasPassword: false,
  title: 'Training lab', slug: 'training-lab', content: '<p>Personal project</p>', language: { code }, translations: [],
  labProjectFields: { status: ['in_progress'], purpose: 'Learning', displayOrder: 0 }, ...changes });
const page = (nodes, next = false, cursor = null) => ({ labProjects: { nodes, pageInfo: { hasNextPage: next, endCursor: cursor } } });
function harness(t, resolve) {
  t.mock.method(console, 'error', () => {});
  return t.mock.method(globalThis, 'fetch', async (_url, init) => Response.json({ data: resolve(JSON.parse(init.body)) }));
}

test('LAB uses its own paginated type and sorts only after completing the inventory', async t => {
  const calls = [];
  harness(t, ({ query, variables }) => {
    assert.match(query, /labProjects\(first: 100/);
    assert.match(query, /language: ES/);
    assert.doesNotMatch(query, /\bposts\(|\barticles\(|\bprojects\(/);
    calls.push(variables.after);
    return variables.after === null ? page([node('ES', { labProjectFields: { displayOrder: 4 } })], true, 'next')
      : page([node('ES', { slug: 'second', labProjectFields: { displayOrder: 1 } })]);
  });
  assert.deepEqual((await getLabProjects('es')).map(item => item.slug), ['second', 'training-lab']);
  assert.deepEqual(calls, [null, 'next']);
});

test('Home LAB uses one bounded localized preview and projects only public overview fields', async t => {
  const fetch = harness(t, ({ query }) => {
    assert.match(query, /labProjects\(first: 3,/); assert.match(query, /language: ES/);
    assert.doesNotMatch(query, /\$after|\bcontent\b|repositoryUrl|demoUrl/);
    return page([node('ES', { content: 'DO_NOT_EXPORT', privateExtra: 'DO_NOT_EXPORT' })], true, 'not-followed');
  });
  const items = await getHomeLabProjects('es');
  assert.equal(fetch.mock.callCount(), 1); assert.equal(items[0].state.label, 'En desarrollo');
  assert.doesNotMatch(JSON.stringify(items), /DO_NOT_EXPORT|repositoryUrl|content/);
});

test('Home LAB rejects private, protected, wrong-language and oversized previews', async t => {
  let nodes = [];
  harness(t, () => page(nodes));
  assert.deepEqual(await getHomeLabProjects('en'), []);
  for (const invalid of [[node('ES')], [node('EN', { status: 'draft' })], [node('EN', { hasPassword: true })],
    [node('EN', { isRestricted: true })], [node(), node(), node(), node()]]) {
    nodes = invalid; await assert.rejects(getHomeLabProjects('en'), { code: 'CMS_LAB_CONTRACT' });
  }
});

test('Home LAB shares only its public projection with60s cache and locale/credential partitions', async t => {
  t.mock.method(console, 'info', () => {});
  const entries = new Map();
  const edgeCache = { match: async key => entries.get(key.url)?.clone(),
    put: async (key, response) => entries.set(key.url, response.clone()) };
  const fetch = harness(t, ({ query }) => page([node(query.includes('language: ES') ? 'ES' : 'EN', {
    privateExtra: 'DO_NOT_CACHE', content: 'DO_NOT_CACHE', labProjectFields: { status: ['beta'], purpose: 'Public purpose', privateExtra: 'DO_NOT_CACHE' },
  })]));
  const call = async (locale, password = 'synthetic') => {
    const response = await withCmsRequest(async () => Response.json(await getHomeLabProjects(locale)), {
      edgeCache, cacheOrigin: 'https://example.test', cmsConfig: { WPGRAPHQL_AUTH_ENABLED: 'true',
        WPGRAPHQL_USERNAME: 'reader', WPGRAPHQL_APPLICATION_PASSWORD: password },
    });
    assert.equal(response.status, 200); return response.json();
  };
  assert.deepEqual(await call('en'), await call('en')); assert.equal(fetch.mock.callCount(), 1);
  await call('es'); await call('en', 'rotated'); assert.equal(fetch.mock.callCount(), 3);
  for (const [key, response] of entries) {
    assert.doesNotMatch(key, /synthetic|reader/); assert.equal(response.headers.get('cache-control'), 'public, max-age=60');
    assert.doesNotMatch(await response.clone().text(), /DO_NOT_CACHE|privateExtra|content/);
  }
});

for (const changes of [{ status: 'draft' }, { hasPassword: true }, { isRestricted: true },
  { language: { code: 'ES' } }, { title: null }, { slug: '../private' }]) {
  test(`LAB archive rejects invalid public inventory: ${JSON.stringify(changes)}`, async t => {
    harness(t, () => page([node('EN', changes)]));
    await assert.rejects(getLabProjects('en'), { code: 'CMS_LAB_CONTRACT' });
  });
}

test('LAB distinguishes missing and wrong-language records from CMS/private failures', async t => {
  let result = { labProject: null };
  harness(t, () => result);
  assert.equal(await getLabProject('missing', 'en'), null);
  result = { labProject: node('ES') };
  assert.equal(await getLabProject('training-lab', 'en'), null);
  result = { labProject: node('EN', { status: 'private' }) };
  const response = await withCmsRequest(async () => Response.json(await getLabProject('training-lab', 'en')));
  assert.equal(response.status, 503);
  assert.equal(response.headers.get('cache-control'), 'no-store');
});

test('LAB projects only allowed fields and public translation links', async t => {
  harness(t, () => ({ labProject: node('EN', { privateExtra: 'SECRET', labProjectFields: { privateExtra: 'SECRET',
    repositoryUrl: 'javascript:SECRET', demoUrl: 'https://example.test/demo', status: ['in_progress'] },
  translations: [node('ES', { databaseId: 2, slug: 'laboratorio', privateExtra: 'SECRET' }),
    node('ES', { databaseId: 3, slug: 'draft', status: 'draft' })] }) }));
  const result = await getLabProject('training-lab', 'en');
  assert.deepEqual(result.translations, [{ databaseId: 2, slug: 'laboratorio', locale: 'es' }]);
  assert.equal(result.repositoryUrl, null);
  assert.equal(result.demoUrl, 'https://example.test/demo');
  assert.doesNotMatch(JSON.stringify(result), /SECRET|privateExtra|draft/);
});

test('LAB availability checks one public localized entry and does not load editorial fields', async t => {
  let nodes = [];
  harness(t, ({ query }) => {
    assert.match(query, /labProjects\(first: 1,/);
    assert.doesNotMatch(query, /labProjectFields|\bcontent\b/);
    return { labProjects: { nodes } };
  });
  assert.equal(await hasLabProjects('en'), false);
  nodes = [node()];
  assert.equal(await hasLabProjects('en'), true);
  nodes = [node('ES')];
  await assert.rejects(hasLabProjects('en'), { code: 'CMS_LAB_CONTRACT' });
});

test('invalid LAB routes and locales cannot reach GraphQL', async t => {
  const fetch = harness(t, () => assert.fail('no request expected'));
  for (const slug of ['a/b', '%2fprivate', '..', '', undefined]) assert.equal(await getLabProject(slug, 'en'), null);
  await assert.rejects(getLabProjects('ALL'), TypeError);
  await assert.rejects(hasLabProjects('fr'), TypeError);
  assert.equal(fetch.mock.callCount(), 0);
});

test('LAB never returns a partial archive when a cursor repeats', async t => {
  harness(t, () => page([node()], true, 'repeat'));
  await assert.rejects(getLabProjects('en'), { code: 'CMS_PAGINATION' });
});

test('LAB state labels use actual choices and optional external links reject unsafe destinations', () => {
  assert.deepEqual(labStatus(['in_progress'], 'es'), { value: 'in_progress', label: 'En desarrollo' });
  for (const state of [null, [], ['idea', 'beta'], 'unknown']) assert.equal(labStatus(state, 'en'), null);
  assert.equal(labLink('https://example.test/source'), 'https://example.test/source');
  for (const url of ['', null, 'javascript:alert(1)', 'https://user:secret@example.test', '/private']) assert.equal(labLink(url), null);
});
