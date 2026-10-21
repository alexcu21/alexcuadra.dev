import { defineMiddleware } from 'astro:middleware';
import { withCmsRequest } from '../../../../src/data/cms-request.js';
import { scenario } from '../scenario.mjs';
export const onRequest = defineMiddleware((context, next) => scenario.run(context.url.searchParams.get('mode'), () =>
  withCmsRequest(next, { bufferHtml: true, cmsConfig: {
    WPGRAPHQL_AUTH_ENABLED: 'true', WPGRAPHQL_USERNAME: 'fixture', WPGRAPHQL_APPLICATION_PASSWORD: 'synthetic',
  } })));
