import { defineMiddleware } from 'astro:middleware';
import { withCmsRequest } from './data/cms-request.js';

export const onRequest = defineMiddleware(async (context, next) => {
  const response = await withCmsRequest(next);
  // Staging uses workers.dev; this is an indexing policy, not access control.
  if (context.url.hostname.endsWith('.workers.dev')) {
    const headers = new Headers(response.headers);
    headers.set('X-Robots-Tag', 'noindex, nofollow');
    return new Response(response.body, { status: response.status, statusText: response.statusText, headers });
  }
  return response;
});
