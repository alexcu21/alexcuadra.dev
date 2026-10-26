import { defineMiddleware } from 'astro:middleware';
import { withCmsRequest } from '../../../../src/data/cms-request.js';
import { scenario } from '../scenario.mjs';
import { presentDocumentError } from '../../../../src/data/error-document.js';
export const onRequest = defineMiddleware((context, next) => scenario.run(context.url.searchParams.get('mode'), async () =>
  presentDocumentError(context.request, await withCmsRequest(next, { bufferHtml: true, cmsConfig: {
    WPGRAPHQL_AUTH_ENABLED: 'true', WPGRAPHQL_USERNAME: 'fixture', WPGRAPHQL_APPLICATION_PASSWORD: 'synthetic',
  } }))));
