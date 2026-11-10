import { handle } from '@astrojs/cloudflare/handler';
import { DurableObject } from 'cloudflare:workers';
import { timingSafeEqual } from 'node:crypto';
import { withStagingRequest } from './data/emdash-provider.js';
import { stagingSitemap } from './data/staging-sitemap.js';
import { catalogCacheKey, catalogCacheTag, catalogExpiryHeader, readCatalogCache } from './data/catalog-cache.js';
import { CatalogFill } from './data/catalog-fill.js';
import { CatalogWriteFence } from './data/catalog-write-fence.js';

export { CmsOriginCoordinator } from './data/cms-origin-coordinator.js';
export { ContactCoordinator } from './data/contact-coordinator.js';

const readHeaders = { 'Cache-Control': 'private, no-store', 'X-Robots-Tag': 'noindex, nofollow' };
const coordinatedRoutes = new Set(['/','/es/','/work/','/es/proyectos/','/blog/','/es/blog/','/articles/','/es/articulos/','/sitemap.xml']);

function restrict(response) {
  const headers = new Headers(response.headers);
  headers.delete(catalogExpiryHeader);
  headers.delete('Cache-Tag');
  headers.set('X-Content-Type-Options', 'nosniff');
  for (const [name, value] of Object.entries(readHeaders)) headers.set(name, value);
  return new Response(response.body, { status: response.status, headers });
}

function cmsRequest(env, origin, path, method = 'GET') {
  return env.CMS.fetch(new URL(path, origin).href, {
    method,
    headers: { Authorization: 'Bearer ' + (path.startsWith('/api/content-v1.json?') ? env.CMS_CONTENT_READ_KEY : env.STAGING_CMS_KEY) },
    redirect: 'manual',
  });
}

function renderCatalog(request, env, context, published) {
  const url = new URL(request.url);
  return withStagingRequest(async () => {
    if (url.pathname === '/sitemap.xml') {
      const xml = await stagingSitemap();
      return new Response(request.method === 'HEAD' ? null : xml, { headers: { 'Content-Type': 'application/xml; charset=utf-8' } });
    }
    const response = await handle(request, env, context);
    if (response.status === 200 && response.headers.get('content-type')?.startsWith('text/html')
      && !url.pathname.endsWith('/')) {
      await response.body?.cancel();
      return new Response(null, { status: 301, headers: { Location: url.pathname + '/' + url.search } });
    }
    return response;
  }, query => cmsRequest(env, url.origin, (published ? '/api/content-v1.json?' : '/api/staging-v1.json?') + query),
  published ? 'published' : 'preview');
}

export class CatalogFillCoordinator extends DurableObject {
  #fill = new CatalogFill();

  async fetch(request) {
    if (!coordinatedRoutes.has(new URL(request.url).pathname)) return new Response(null, { status: 404 });
    const response = await this.#fill.get(() => renderCatalog(request, this.env, this.ctx, true));
    return response ?? new Response(null, { status: 409 });
  }

  invalidate() {
    this.#fill.invalidate();
  }
}

function matchesAuthorization(request, value) {
  const expected = Buffer.from(value);
  const received = Buffer.from(request.headers.get('authorization') ?? '');
  return received.length === expected.length && timingSafeEqual(received, expected);
}

function authorized(request, secret) {
  return typeof secret === 'string' && secret.length >= 32
    && matchesAuthorization(request, 'Basic ' + Buffer.from('reviewer:' + secret).toString('base64'));
}

async function invalidateCatalog(request, env) {
  const secret = env.CATALOG_INVALIDATION_KEY;
  if (request.method !== 'POST' || typeof secret !== 'string' || secret.length < 32
    || !matchesAuthorization(request, 'Bearer ' + secret)) return new Response(null, { status: 404 });
  const namespace = env.CATALOG_CACHE_NAMESPACE;
  const origin = env.CATALOG_PUBLIC_ORIGIN;
  if (env.CATALOG_MODE !== 'published' || !/^[a-z0-9-]{1,64}$/.test(namespace ?? '')
    || typeof origin !== 'string' || !/^https:\/\/[a-z0-9.-]+$/.test(origin)
    || !/^[a-f0-9]{32}$/.test(env.CATALOG_PURGE_ZONE ?? '') || !env.CATALOG_PURGE_TOKEN
    || !env.CATALOG_WRITE_FENCE) {
    return new Response(null, { status: 503 });
  }
  try {
    const gate = env.CATALOG_WRITE_FENCE.get(env.CATALOG_WRITE_FENCE.idFromName(namespace));
    if (await gate.purge()) return new Response(null, { status: 204 });
  } catch {
    // Expiration still bounds stale content if the purge endpoint is unavailable.
  }
  return new Response(null, { status: 502 });
}

export class CatalogCacheFence extends DurableObject {
  #fence = new CatalogWriteFence(this.ctx.storage);

  begin() {
    return this.#fence.begin();
  }

  finish(id) {
    return this.#fence.finish(id);
  }

  purge() {
    const env = this.env;
    const namespace = env.CATALOG_CACHE_NAMESPACE;
    return this.#fence.purge(async () => {
      if (!env.CATALOG_FILL) return;
      await Promise.all([...coordinatedRoutes].map(path => {
        const key = catalogCacheKey(new Request(env.CATALOG_PUBLIC_ORIGIN + path), namespace);
        return env.CATALOG_FILL.get(env.CATALOG_FILL.idFromName(key.url)).invalidate();
      }));
    }, async () => {
      const response = await fetch('https://api.cloudflare.com/client/v4/zones/' + env.CATALOG_PURGE_ZONE + '/purge_cache', {
        method: 'POST',
        headers: { Authorization: 'Bearer ' + env.CATALOG_PURGE_TOKEN, 'Content-Type': 'application/json' },
        body: JSON.stringify({ tags: [catalogCacheTag(namespace)] }),
        signal: AbortSignal.timeout(3000),
      });
      return response.ok && (await response.json()).success === true;
    });
  }
}

export default {
  async fetch(request, env, context) {
    const url = new URL(request.url);
    if (url.pathname === '/__catalog-cache/invalidate') {
      return restrict(await (url.hostname === 'catalog-internal.invalid'
        ? invalidateCatalog(request, env) : new Response(null, { status: 404 })));
    }
    if (/^\/_emdash\/api\/admin\/transfer\/imports(?:\/|$)/.test(url.pathname)) {
      const token = env.STAGING_TRANSFER_TOKEN;
      if (typeof token !== 'string' || token.length < 32) return restrict(new Response(null, { status: 404 }));
      const expected = Buffer.from('Bearer ' + token);
      const received = Buffer.from(request.headers.get('authorization') ?? '');
      if (received.length !== expected.length || !timingSafeEqual(received, expected)) {
        return restrict(new Response(null, { status: 401 }));
      }
      return restrict(await env.CMS.fetch(request));
    }
    if (!authorized(request, env.STAGING_REVIEW_PASSWORD)) {
      return restrict(new Response('Acceso restringido', {
        status: 401, headers: { 'WWW-Authenticate': 'Basic realm="Catalog staging", charset="UTF-8"' },
      }));
    }
    if (!['GET', 'HEAD'].includes(request.method)) return restrict(new Response(null, { status: 405 }));
    if (url.pathname === '/robots.txt') return restrict(new Response('User-agent: *\nDisallow: /\n'));
    const published = env.CATALOG_MODE === 'published';
    try {
      if (url.pathname.startsWith('/_emdash/api/media/file/')) {
        const media = await cmsRequest(env, url.origin, url.pathname, request.method);
        if (media.status !== 200) return restrict(new Response(null, { status: media.status === 404 ? 404 : 503 }));
        const mimeType = media.headers.get('content-type')?.split(';', 1)[0];
        if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'].includes(mimeType)) {
          return restrict(new Response(null, { status: 415 }));
        }
        return restrict(media);
      }
      const render = () => renderCatalog(request, env, context, published);
      const key = published && catalogCacheKey(request, env.CATALOG_CACHE_NAMESPACE);
      if (!key) return restrict(await render());
      const fill = async coordinated => {
        if (!coordinated || !env.CATALOG_FILL || !coordinatedRoutes.has(url.pathname)) return render();
        const object = env.CATALOG_FILL.get(env.CATALOG_FILL.idFromName(key.url));
        const response = await object.fetch(new Request(url));
        return response.status === 409 ? render() : response;
      };
      const namespace = env.CATALOG_CACHE_NAMESPACE;
      const fence = env.CATALOG_WRITE_FENCE?.get(env.CATALOG_WRITE_FENCE.idFromName(namespace));
      return restrict(await readCatalogCache(caches.default, key, fill, context, Date.now, catalogCacheTag(namespace), fence));
    } catch {
      return restrict(new Response('Catálogo temporalmente inaccesible', { status: 503 }));
    }
  },
};
