// Bump this namespace to invalidate cached data after query/schema changes.
const CACHE_VERSION = 'public-cms-v1';
export const PUBLIC_CMS_TTL = 60;

export function allowsPublicCmsCache(request) {
  const url = new URL(request.url);
  return ['GET', 'HEAD'].includes(request.method)
    && !request.headers.has('authorization')
    && !request.headers.has('cookie')
    && ![...url.searchParams.keys()].some(key => /preview|draft|nonce|token/i.test(key));
}

export async function publicCacheKey(origin, endpoint, document, variables, partition = 'anonymous') {
  // Include the entire query and variables; never expose them in the cache URL.
  const bytes = new TextEncoder().encode(JSON.stringify([endpoint, document, variables ?? null, partition]));
  const digest = await crypto.subtle.digest('SHA-256', bytes);
  const hash = [...new Uint8Array(digest)].map(byte => byte.toString(16).padStart(2, '0')).join('');
  return new Request(new URL(`/__internal-cache/${CACHE_VERSION}/${hash}`, origin));
}

export function retryAfterSeconds(value, now = Date.now()) {
  if (!value) return undefined;
  if (/^\d+$/.test(value.trim())) {
    const seconds = Number(value);
    return Number.isSafeInteger(seconds) ? Math.max(1, seconds) : undefined;
  }
  const timestamp = Date.parse(value);
  if (!Number.isFinite(timestamp)) return undefined;
  return Math.max(1, Math.ceil((timestamp - now) / 1000));
}
