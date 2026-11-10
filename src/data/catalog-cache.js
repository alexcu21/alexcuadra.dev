export const catalogTtlMs = 60_000;
export const catalogExpiryHeader = 'X-Catalog-Expires-At';
export const catalogCacheTag = namespace => 'alexcuadra-catalog-' + namespace;

export function canStoreCatalog(response) {
  const type = response.headers.get('content-type')?.split(';', 1)[0];
  const policy = response.headers.get('cache-control') ?? '';
  const vary = response.headers.get('vary');
  return response.status === 200
    && ['text/html', 'application/xml'].includes(type)
    && !response.headers.has('set-cookie')
    && !/(?:private|no-store|no-cache)/i.test(policy)
    && (!vary || vary.toLowerCase() === 'accept-encoding');
}

export function catalogCacheKey(request, namespace) {
  if (request.method !== 'GET' || !/^[a-z0-9-]{1,64}$/.test(namespace ?? '')) return null;
  if (['cookie', 'range', 'if-match', 'if-none-match', 'if-modified-since',
    'if-unmodified-since', 'if-range'].some(name => request.headers.has(name))) return null;
  const url = new URL(request.url);
  if (url.search || url.pathname.startsWith('/_') || url.pathname.startsWith('/api/')) return null;
  if (url.pathname !== '/sitemap.xml' && !url.pathname.endsWith('/')) return null;
  url.pathname = '/__catalog-cache/emdash-v1/' + namespace + url.pathname;
  return new Request(url);
}

export async function readCatalogCache(cache, key, render, context, now = Date.now, tag, fence) {
  const startedAt = now();
  const hit = await cache.match(key).catch(() => undefined);
  const expiresAt = Number(hit?.headers.get(catalogExpiryHeader));
  if (hit && expiresAt > now()) {
    const headers = new Headers(hit.headers);
    headers.delete(catalogExpiryHeader);
    headers.set('X-Catalog-Cache', 'HIT');
    return new Response(hit.body, { status: hit.status, headers });
  }
  if (hit?.body) context.waitUntil(hit.body.cancel().catch(() => {}));

  const lease = fence ? await fence.begin().catch(() => null) : null;
  if (fence && !lease) return render(false);
  const release = () => lease ? fence.finish(lease.id).catch(() => {}) : Promise.resolve();
  let storing = false;
  try {
    const response = await render(true);
    const upstreamDeadline = Number(response.headers.get(catalogExpiryHeader));
    const deadline = Math.min(startedAt + catalogTtlMs, upstreamDeadline || Infinity, lease?.expiresAt ?? Infinity);
    const remaining = Math.floor((deadline - now()) / 1000);
    if (remaining > 0 && canStoreCatalog(response)) {
      const copy = response.clone();
      const headers = new Headers(copy.headers);
      headers.set(catalogExpiryHeader, String(deadline));
      headers.set('Cache-Control', 'public, max-age=' + remaining);
      if (tag) headers.set('Cache-Tag', tag);
      const write = cache.put(key, new Response(copy.body, { status: copy.status, headers }));
      context.waitUntil(write.catch(() => {}).finally(release));
      storing = true;
    }
    return response;
  } finally {
    if (lease && !storing) context.waitUntil(release());
  }
}
