import { handle } from '@astrojs/cloudflare/handler';
import { withStagingRequest } from './emdash-provider.js';
import { stagingSitemap } from './staging-sitemap.js';

export function cmsRequest(env, origin, path, method = 'GET', mediaKey = env.STAGING_CMS_KEY) {
  return env.CMS.fetch(new URL(path, origin).href, {
    method,
    headers: { Authorization: 'Bearer ' + (path.startsWith('/api/content-v1.json?') ? env.CMS_CONTENT_READ_KEY : mediaKey) },
    redirect: 'manual',
  });
}

export function renderCatalog(request, env, context, published) {
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
