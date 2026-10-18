import { AsyncLocalStorage } from 'node:async_hooks';
import { request as graphqlRequest } from 'graphql-request';

const requests = new AsyncLocalStorage();

export class CmsError extends Error {
  constructor(code, operation, status) {
    super(`CMS unavailable (${code})`);
    this.name = 'CmsError';
    this.code = code;
    this.operation = operation;
    this.status = status;
  }
}

export function cmsFailure(code, operation, status) {
  const error = new CmsError(code, operation, status);
  const scope = requests.getStore();
  if (scope) scope.failure = error;
  // Do not log raw GraphQL errors, queries, headers or response bodies.
  console.error(JSON.stringify({ event: 'cms_failure', code, operation, httpStatus: status }));
  return error;
}

export async function request(endpoint, document, variables) {
  const operation = /\bquery\s+(\w+)/.exec(document)?.[1] || 'unknown';
  const load = async () => {
    try {
      return await graphqlRequest({ url: endpoint, document, variables, signal: AbortSignal.timeout(10000) });
    } catch (error) {
      const status = error?.response?.status;
      const messages = error?.response?.errors?.map(item => item.message).join(' ') || '';
      const code = status === 401 || status === 403 || /authenticat|not authorized|unauthorized/i.test(messages)
        ? 'CMS_AUTHORIZATION'
        : status >= 400 ? 'CMS_HTTP'
        : error?.response ? 'CMS_GRAPHQL'
        : ['TimeoutError', 'AbortError'].includes(error?.name) ? 'CMS_TIMEOUT' : 'CMS_NETWORK';
      throw cmsFailure(code, operation, status);
    }
  };
  const scope = requests.getStore();
  if (!scope) return load();
  const key = JSON.stringify([endpoint, document, variables]);
  if (!scope.cache.has(key)) scope.cache.set(key, load());
  return scope.cache.get(key);
}

export function withCmsRequest(next) {
  const scope = { cache: new Map(), failure: null };
  return requests.run(scope, async () => {
    let response;
    try {
      response = await next();
    } catch (error) {
      if (!(error instanceof CmsError) && !scope.failure) throw error;
    }
    // Astro may turn a rendering exception into a response internally.
    if (scope.failure) {
      return new Response('Content is temporarily unavailable. Please try again later.', {
        status: 503,
        headers: {
          'Content-Type': 'text/plain; charset=utf-8',
          'Cache-Control': 'no-store',
          'Retry-After': '60',
          'X-Robots-Tag': 'noindex',
        },
      });
    }
    return response;
  });
}
