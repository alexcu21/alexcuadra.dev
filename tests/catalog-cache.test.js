import assert from 'node:assert/strict';
import test from 'node:test';
import { catalogCacheKey, readCatalogCache } from '../src/data/catalog-cache.js';

const origin = 'https://alexcuadra.dev';
const html = (body, headers = {}, status = 200) =>
  new Response(body, { status, headers: { 'Content-Type': 'text/html', ...headers } });

function fixture() {
  const store = new Map();
  const writes = [];
  const cache = {
    async match(request) { return store.get(request.url)?.clone(); },
    async put(request, response) { store.set(request.url, response); },
  };
  const context = { waitUntil(promise) { writes.push(promise); } };
  return { cache, context, store, writes };
}

test('cache keys isolate namespaces, hosts and languages and exclude stateful requests', () => {
  const key = (path, options) => catalogCacheKey(new Request(origin + path, options), 'release-1');
  assert.notEqual(key('/es/blog/').url, key('/blog/').url);
  assert.notEqual(key('/blog/').url, catalogCacheKey(new Request(origin + '/blog/'), 'release-2').url);
  assert.notEqual(key('/blog/').url, catalogCacheKey(new Request('https://other.example/blog/'), 'release-1').url);
  for (const path of ['/blog/?q=a', '/_emdash/admin/', '/api/contact/', '/blog']) assert.equal(key(path), null);
  for (const headers of [{ Cookie: 'session=x' }, { Range: 'bytes=0-5' }, { 'If-None-Match': 'tag' }]) {
    assert.equal(key('/blog/', { headers }), null);
  }
  assert.equal(key('/blog/', { method: 'HEAD' }), null);
  assert.equal(catalogCacheKey(new Request(origin), undefined), null);
});

test('cache hit reuses SSR output and expiration is measured from the start of render', async () => {
  const { cache, context, writes, store } = fixture();
  const key = catalogCacheKey(new Request(origin + '/blog/'), 'release-1');
  let time = 0;
  let renders = 0;
  const render = () => {
    renders++;
    time += 4000;
    return html('published ' + renders);
  };
  const first = await readCatalogCache(cache, key, render, context, () => time);
  await Promise.all(writes);
  assert.equal(await first.text(), 'published 1');
  assert.equal(store.get(key.url).headers.get('cache-control'), 'public, max-age=56');
  time = 59_999;
  const hit = await readCatalogCache(cache, key, render, context, () => time);
  assert.equal(hit.headers.get('X-Catalog-Cache'), 'HIT');
  assert.equal(hit.headers.get('X-Catalog-Expires-At'), null);
  assert.equal(await hit.text(), 'published 1');
  assert.equal(renders, 1);
  time = 60_000;
  const next = await readCatalogCache(cache, key, render, context, () => time);
  assert.equal(await next.text(), 'published 2');
});

test('errors, private content, session responses and late renders are never stored', async () => {
  const { cache, context, store } = fixture();
  const key = catalogCacheKey(new Request(origin), 'release-1');
  let time = 0;
  const responses = [
    html('missing', {}, 404),
    html('unavailable', {}, 503),
    html('preview', { 'Cache-Control': 'private, no-store' }),
    html('session', { 'Set-Cookie': 'session=x' }),
    html('variant', { Vary: 'Cookie' }),
  ];
  for (const response of responses) {
    await readCatalogCache(cache, key, () => response, context, () => time);
  }
  await readCatalogCache(cache, key, () => { time = 60_000; return html('late'); }, context, () => time);
  assert.equal(store.size, 0);
});

test('an edge fill cannot extend the coordinated response lifetime', async () => {
  const { cache, context, store, writes } = fixture();
  const key = catalogCacheKey(new Request(origin), 'release-1');
  await readCatalogCache(cache, key, () => html('coordinated', {
    'X-Catalog-Expires-At': '60000',
  }), context, () => 45_000);
  await Promise.all(writes);
  assert.equal(store.get(key.url).headers.get('Cache-Control'), 'public, max-age=15');
});
