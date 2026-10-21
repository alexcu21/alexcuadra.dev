import { test } from 'node:test';
import assert from 'node:assert/strict';
import { getLabProjects, getLabProject, hasLabProjects, labStatus, labLink } from '../src/data/lab-content.js';
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
