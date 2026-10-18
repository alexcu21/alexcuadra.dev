import { defineMiddleware } from 'astro:middleware';

export const onRequest = defineMiddleware(async (context, next) => {
  const response = await next();
  // Staging uses workers.dev; this is an indexing policy, not access control.
  if (context.url.hostname.endsWith('.workers.dev')) {
    response.headers.set('X-Robots-Tag', 'noindex, nofollow');
  }
  return response;
});
