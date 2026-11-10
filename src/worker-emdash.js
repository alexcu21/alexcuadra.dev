import { cmsRequest, renderCatalog } from './data/catalog-render.js';
import { catalogCacheKey, catalogExpiryHeader, readCatalogCache } from './data/catalog-cache.js';

export { CmsOriginCoordinator } from './data/cms-origin-coordinator.js';
export { ContactCoordinator } from './data/contact-coordinator.js';

const mediaPrefix = '/_emdash/api/media/file/';
const imageTypes = new Set(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif']);

function failure(status) {
  return new Response(null, { status, headers: { 'Cache-Control': 'no-store' } });
}

async function publishedMedia(request, env, url) {
  // Grant public access only to media approved for the published catalog.
  if (!Array.isArray(env.CATALOG_PUBLIC_MEDIA_PATHS)
    || !env.CATALOG_PUBLIC_MEDIA_PATHS.includes(url.pathname)) return failure(404);
  const key = env.CMS_MEDIA_READ_KEY;
  if (typeof key !== 'string' || !key) return failure(503);
  const upstream = await cmsRequest(env, url.origin, url.pathname, request.method, key);
  if (upstream.status !== 200) return failure(upstream.status === 404 ? 404 : 503);
  const contentType = upstream.headers.get('content-type');
  if (!imageTypes.has(contentType?.split(';', 1)[0])) return failure(415);
  return new Response(upstream.body, {
    headers: { 'Content-Type': contentType, 'X-Content-Type-Options': 'nosniff', 'Cache-Control': 'no-store' },
  });
}

export default {
  async fetch(request, env, context) {
    const url = new URL(request.url);
    const path = url.pathname;
    if (path.startsWith('/__catalog-cache') || path.startsWith('/_emdash') && !path.startsWith(mediaPrefix)
      || path.startsWith('/api/') && path !== '/api/contact' && path !== '/api/contact/') return failure(404);
    if (!['GET', 'HEAD'].includes(request.method)) return failure(405);

    try {
      if (path.startsWith(mediaPrefix)) return await publishedMedia(request, env, url);
      const render = () => renderCatalog(request, env, context, true);
      const key = request.headers.has('authorization') ? null : catalogCacheKey(request, env.CATALOG_CACHE_NAMESPACE);
      if (!key) return await render();
      const response = await readCatalogCache(caches.default, key, render, context);
      const headers = new Headers(response.headers);
      headers.delete(catalogExpiryHeader);
      headers.delete('Cache-Tag');
      if (headers.get('X-Catalog-Cache') === 'HIT') headers.set('Cache-Control', 'no-cache');
      return new Response(response.body, { status: response.status, headers });
    } catch {
      return failure(503);
    }
  },
};
