import { test } from 'node:test';
import assert from 'node:assert/strict';
import * as api from '../src/data/client.js';
import { withCmsRequest } from '../src/data/cms-request.js';

function respond(t, payload, status = 200) {
  t.mock.method(console, 'error', () => {});
  return t.mock.method(globalThis, 'fetch', async () => new Response(JSON.stringify(payload), {
    status, headers: { 'content-type': 'application/json' },
  }));
}

for (const [method, payload] of [
  ['getHomeInfo', { pages: { nodes: [] } }],
  ['getHomeInfo', { pages: { nodes: [{ homePageFields: null }] } }],
]) {
  test('missing Home fields fail before templates access homePageFields', async t => {
    respond(t, { data: payload });
    await assert.rejects(api[method](), { code: 'CMS_HOME_MISSING' });
  });
}

for (const method of ['getHomeInfo', 'getProjectsInfo', 'getArticlesCategories']) {
  test(`${method} does not hide GraphQL failure as an empty collection`, async t => {
    respond(t, { errors: [{ message: 'Upstream query failed' }] });
    await assert.rejects(api[method](), { code: 'CMS_GRAPHQL', status: 200 });
  });
}

test('HTTP rejection is logged as structured status without upstream body or secrets', async t => {
  respond(t, { secret: 'PRIVATE-UPSTREAM-BODY' }, 503);
  await assert.rejects(api.getHomeInfo(), { code: 'CMS_HTTP', status: 503 });
  const calls = console.error.mock.calls;
  assert.deepEqual(JSON.parse(calls[0].arguments[0]), {
    event: 'cms_failure', code: 'CMS_HTTP', operation: 'getHomeFields', httpStatus: 503,
  });
  assert.ok(!JSON.stringify(calls).includes('PRIVATE-UPSTREAM-BODY'));
});

test('timeout is classified without exposing raw error messages', async t => {
  t.mock.method(console, 'error', () => {});
  t.mock.method(globalThis, 'fetch', async () => { throw new DOMException('PRIVATE', 'TimeoutError'); });
  await assert.rejects(api.getHomeInfo(), { code: 'CMS_TIMEOUT' });
  assert.ok(!JSON.stringify(console.error.mock.calls).includes('PRIVATE'));
});

test('CMS failure produces non-cacheable 503 without internal details', async t => {
  respond(t, {}, 403);
  const response = await withCmsRequest(async () => { await api.getHomeInfo(); return new Response('ok'); });
  assert.equal(response.status, 503);
  assert.equal(response.headers.get('cache-control'), 'no-store');
  assert.equal(response.headers.get('retry-after'), '60');
  assert.equal(response.headers.get('x-robots-tag'), 'noindex');
  assert.doesNotMatch(await response.text(), /403|CMS_AUTHORIZATION|graphql|stack/i);
});

test('CMS failure is mapped even if framework catches it and returns HTTP 500', async t => {
  respond(t, {}, 502);
  const response = await withCmsRequest(async () => {
    try { await api.getHomeInfo(); } catch { return new Response('framework error', { status: 500 }); }
  });
  assert.equal(response.status, 503);
});

test('deduplicates global content within a request and isolates separate requests', async t => {
  const fetchMock = respond(t, { data: { pages: { nodes: [{ homePageFields: { name_field: 'Alex' } }] } } });
  await withCmsRequest(async () => {
    await Promise.all([api.getHomeInfo(), api.getHomeInfo()]);
    await api.getHomeInfo();
    return new Response('ok');
  });
  assert.equal(fetchMock.mock.callCount(), 1);
  await withCmsRequest(async () => { await api.getHomeInfo(); return new Response('ok'); });
  assert.equal(fetchMock.mock.callCount(), 2);
});

test('does not disguise unrelated programming exceptions as CMS failures', async () => {
  await assert.rejects(withCmsRequest(async () => { throw new TypeError('bug'); }), /bug/);
});
