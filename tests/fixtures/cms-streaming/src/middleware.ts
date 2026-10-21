import { defineMiddleware } from 'astro:middleware';
import { withCmsRequest } from '../../../../src/data/cms-request.js';
export const onRequest = defineMiddleware((context, next) => withCmsRequest(next,
  { bufferHtml: context.url.searchParams.get('guard') !== 'off' }));
