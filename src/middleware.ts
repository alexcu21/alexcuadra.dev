import { defineMiddleware } from 'astro:middleware';
import { withCmsRequest } from './data/cms-request.js';
import { allowsPublicCmsCache } from './data/cms-cache.js';
import { env } from 'cloudflare:workers';
import { isWordPressRestProbe } from './data/request-policy.js';

export const onRequest = defineMiddleware(async (context, next) => {
  const requestId = crypto.randomUUID();
  if (isWordPressRestProbe(context.url)) {
    return new Response('Not found', { status: 404, headers: {
      'Content-Type': 'text/plain; charset=utf-8', 'X-Robots-Tag': 'noindex',
      'X-Request-ID': requestId,
    } });
  }
  let edgeCache;
  if (allowsPublicCmsCache(context.request)) {
    try { edgeCache = await caches.open('alexcuadra-public-cms'); } catch { /* Cache is optional. */ }
  }
  const response = await withCmsRequest(next, { requestId, edgeCache, cacheOrigin: context.url.origin,
    cmsConfig: env });
  const headers = new Headers(response.headers);
  headers.set('X-Request-ID', requestId);
  // Staging uses workers.dev; this is an indexing policy, not access control.
  if (context.url.hostname.endsWith('.workers.dev')) {
    headers.set('X-Robots-Tag', 'noindex, nofollow');
  }
  return new Response(response.body, { status: response.status, statusText: response.statusText, headers });
});
