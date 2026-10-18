import { test } from 'node:test';
import assert from 'node:assert/strict';
import { allowsPublicCmsCache, publicCacheKey, retryAfterSeconds } from '../src/data/cms-cache.js';
import { request, withCmsRequest } from '../src/data/cms-request.js';
import { getHomeInfo, getProjectsInfo } from '../src/data/client.js';
import { publicProjects } from '../src/data/public-projects.js';

const endpoint = 'https://content.example.com/graphql';
const query = 'query PublishedHome { pages { nodes { title } } }';
const policy = { publicCache: true, validatePublicData: data => Array.isArray(data?.pages?.nodes) };
const data = { pages: { nodes: [{ title: 'Published content' }] } };

function setup(t, payload = { data }, status = 200, headers = {}) {
  t.mock.method(console, 'info', () => {});
  t.mock.method(console, 'error', () => {});
  let now = 0;
  const entries = new Map();
  const cache = {
    async match(key) {
      const entry = entries.get(key.url);
      return entry && entry.until > now ? entry.response.clone() : undefined;
    },
    async put(key, response) {
      assert.equal(response.status, 200);
      assert.equal(response.headers.get('cache-control'), 'public, max-age=60');
      entries.set(key.url, { response: response.clone(), until: now + 60000 });
    },
  };
  const fetchMock = t.mock.method(globalThis, 'fetch', async () => new Response(JSON.stringify(payload), {
    status, headers: { 'content-type': 'application/json', ...headers },
  }));
  const options = { edgeCache: cache, cacheOrigin: 'https://staging.example.com', requestId: 'test-request' };
  const run = async (operation = () => request(endpoint, query, undefined, policy), config = options) => {
    let result;
    const response = await withCmsRequest(async () => { result = await operation(); return new Response('ok'); }, config);
    return { result, response };
  };
  return { run, fetchMock, entries, options, advance: ms => { now += ms; } };
}

test('reuses public data between requests and refreshes after TTL', async t => {
  const ctx = setup(t);
  assert.deepEqual((await ctx.run()).result, data);
  assert.deepEqual((await ctx.run()).result, data);
  assert.equal(ctx.fetchMock.mock.callCount(), 1);
  ctx.advance(60001);
  await ctx.run();
  assert.equal(ctx.fetchMock.mock.callCount(), 2);
});

const serviceConfig = { WPGRAPHQL_AUTH_ENABLED: 'true', WPGRAPHQL_USERNAME: 'reader',
  WPGRAPHQL_APPLICATION_PASSWORD: 'private-password' };
const publicNode = { status: 'publish', isRestricted: false, hasPassword: false,
  title: 'Project', slug: 'project', content: '<p>Public</p>', secret: 'DO-NOT-STORE',
  projects: { projectTagline: 'Public tagline', internalNotes: 'DO-NOT-STORE' },
  featuredImage: { node: { sourceUrl: 'https://example.com/image.png', privateMeta: 'DO-NOT-STORE' } } };

test('service project cache stores only public fields and reuses projected data', async t => {
  const ctx = setup(t, { data: { viewer: { databaseId: 99 }, projects: { nodes: [publicNode] } } });
  const config = { ...ctx.options, cmsConfig: serviceConfig };
  const first = await ctx.run(getProjectsInfo, config);
  const second = await ctx.run(getProjectsInfo, config);
  assert.equal(first.response.status, 200);
  assert.deepEqual(first.result, second.result);
  assert.equal(ctx.fetchMock.mock.callCount(), 1);
  assert.doesNotMatch(JSON.stringify(first.result), /DO-NOT-STORE|databaseId|viewer/);
  const entry = [...ctx.entries.values()][0];
  assert.doesNotMatch(await entry.response.clone().text(), /DO-NOT-STORE|databaseId|viewer/);
  assert.ok([...ctx.entries.keys()].every(key => !key.includes('private-password')));
  await ctx.run(getProjectsInfo, { ...config, cmsConfig: { ...serviceConfig, WPGRAPHQL_APPLICATION_PASSWORD: 'rotated' } });
  assert.equal(ctx.fetchMock.mock.callCount(), 2);
  await ctx.run(getProjectsInfo, ctx.options);
  assert.equal(ctx.fetchMock.mock.callCount(), 3, 'anonymous request cannot read service cache');
});

for (const changes of [{ status: 'draft' }, { status: 'private' }, { status: 'future' },
  { hasPassword: true }, { isRestricted: true }, { status: undefined }, { hasPassword: undefined }]) {
  test(`rejects non-public service project without caching: ${JSON.stringify(changes)}`, async t => {
    const ctx = setup(t, { data: { projects: { nodes: [{ ...publicNode, ...changes }] } } });
    assert.equal(publicProjects({ projects: { nodes: [{ ...publicNode, ...changes }] } }), undefined);
    const { response } = await ctx.run(getProjectsInfo, { ...ctx.options, cmsConfig: serviceConfig });
    assert.equal(response.status, 503);
    assert.equal(ctx.entries.size, 0);
  });
}

test('cache hits record attempt zero without contacting origin', async t => {
  const ctx = setup(t);
  await ctx.run(); await ctx.run();
  const events = console.info.mock.calls.map(call => JSON.parse(call.arguments[0]));
  assert.deepEqual(events.map(event => [event.cache, event.attempt]), [['MISS', 1], ['HIT', 0]]);
  assert.equal(events[1].requestId, 'test-request');
});

test('cache keys separate queries, variables, CMS and staging/production hosts', async () => {
  const keys = await Promise.all([
    publicCacheKey('https://one.test', endpoint, query, { slug: 'one' }),
    publicCacheKey('https://one.test', endpoint, query, { slug: 'two' }),
    publicCacheKey('https://two.test', endpoint, query, { slug: 'one' }),
    publicCacheKey('https://one.test', endpoint, query + ' ', { slug: 'one' }),
    publicCacheKey('https://one.test', 'https://other.test/graphql', query, { slug: 'one' }),
  ]);
  assert.equal(new Set(keys.map(key => key.url)).size, 5);
  assert.ok(keys.every(key => !key.url.includes('PublishedHome') && !key.url.includes('slug')));
});

for (const headers of [{ Authorization: 'Bearer PRIVATE' }, { Cookie: 'session=PRIVATE' }, { 'X-API-Key': 'PRIVATE' }]) {
  test('outgoing custom headers bypass shared cache even when explicitly opted in', async t => {
    const ctx = setup(t);
    await ctx.run();
    await ctx.run(() => request(endpoint, query, undefined, { ...policy, requestHeaders: headers }));
    assert.equal(ctx.fetchMock.mock.callCount(), 2);
    assert.ok(!JSON.stringify(console.info.mock.calls).includes('PRIVATE'));
  });
}

test('queries without explicit public policy never use shared cache', async t => {
  const ctx = setup(t);
  await ctx.run(() => request(endpoint, query));
  await ctx.run(() => request(endpoint, query));
  assert.equal(ctx.fetchMock.mock.callCount(), 2);
  assert.equal(ctx.entries.size, 0);
});

for (const [payload, status] of [[{}, 429], [{ errors: [{ message: 'Failure' }] }, 200]]) {
  test('does not cache failures or automatically retry them', async t => {
    const ctx = setup(t, payload, status);
    const { response } = await ctx.run();
    assert.equal(response.status, 503);
    assert.equal(ctx.fetchMock.mock.callCount(), 1);
    assert.equal(ctx.entries.size, 0);
  });
}

test('does not store missing Home as valid public data', async t => {
  const ctx = setup(t, { data: { pages: { nodes: [] } } });
  assert.equal((await ctx.run(getHomeInfo)).response.status, 503);
  assert.equal(ctx.entries.size, 0);
});

test('preserves Retry-After in diagnostics and the visitor response', async t => {
  const ctx = setup(t, {}, 429, { 'retry-after': '120' });
  assert.equal((await ctx.run()).response.headers.get('retry-after'), '120');
  assert.equal(JSON.parse(console.error.mock.calls[0].arguments[0]).retryAfter, 120);
});

test('cache infrastructure failure does not break an otherwise healthy query', async t => {
  const ctx = setup(t);
  const broken = { match: async () => { throw new Error('cache unavailable'); } };
  assert.equal((await ctx.run(undefined, { ...ctx.options, edgeCache: broken })).response.status, 200);
});

test('outbound requests have correlation ID and descriptive User-Agent', async t => {
  const ctx = setup(t);
  await ctx.run();
  const headers = new Headers(ctx.fetchMock.mock.calls[0].arguments[1].headers);
  assert.equal(headers.get('x-request-id'), 'test-request');
  assert.match(headers.get('user-agent'), /AlexCuadraPortfolio/);
});

test('incoming cookies, authentication, previews and non-read methods disable cache', () => {
  assert.equal(allowsPublicCmsCache(new Request('https://example.com/')), true);
  for (const url of ['?preview=true', '?preview_id=12', '?draft=1', '?token=secret']) {
    assert.equal(allowsPublicCmsCache(new Request(`https://example.com/${url}`)), false);
  }
  for (const headers of [{ Cookie: 'session=x' }, { Authorization: 'Bearer x' }]) {
    assert.equal(allowsPublicCmsCache(new Request('https://example.com/', { headers })), false);
  }
  assert.equal(allowsPublicCmsCache(new Request('https://example.com/', { method: 'POST' })), false);
});

test('Retry-After parses seconds/dates and ignores malformed values', () => {
  assert.equal(retryAfterSeconds('90'), 90);
  assert.equal(retryAfterSeconds('Wed, 01 Jan 2025 00:02:00 GMT', Date.parse('2025-01-01T00:00:00Z')), 120);
  assert.equal(retryAfterSeconds('bad-value'), undefined);
  assert.equal(retryAfterSeconds('999999999999999999999999999'), undefined);
});
