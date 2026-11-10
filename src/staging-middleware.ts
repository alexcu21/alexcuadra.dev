import { defineMiddleware } from 'astro:middleware';
import { isPreviewRoute } from './data/preview-route.js';

export const onRequest = defineMiddleware(({ url }, next) => {
  if (!isPreviewRoute(url.pathname)) return new Response('Ruta fuera del catálogo', { status: 404 });
  return next();
});
