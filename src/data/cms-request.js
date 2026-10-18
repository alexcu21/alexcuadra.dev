import { AsyncLocalStorage } from 'node:async_hooks';
import { request as graphqlRequest } from 'graphql-request';
import { publicCacheKey, PUBLIC_CMS_TTL, retryAfterSeconds } from './cms-cache.js';
import { cmsAuthHeaders } from './cms-auth.js';

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

export function cmsFailure(code, operation, status, details = {}) {
  const error = new CmsError(code, operation, status);
  const scope = requests.getStore();
  if (scope) scope.failure = error;
  error.retryAfter = details.retryAfter;
  // Do not log raw GraphQL errors, queries, headers or response bodies.
  console.error(JSON.stringify({ event: 'cms_failure', code, operation, httpStatus: status,
    requestId: scope?.requestId, ...details }));
  return error;
}

export async function request(endpoint, document, variables, options = {}) {
  const operation = /\bquery\s+(\w+)/.exec(document)?.[1] || 'unknown';
  const scope = requests.getStore();
  // Opt-in only. Any future credential-bearing request bypasses shared cache.
  const credentials = new Headers(options.requestHeaders);
  let serviceHeaders;
  try { serviceHeaders = cmsAuthHeaders(scope?.cmsConfig); }
  catch { throw cmsFailure('CMS_AUTH_CONFIG', operation); }
  if (serviceHeaders.has('authorization')) {
    if (new URL(endpoint).protocol !== 'https:') throw cmsFailure('CMS_AUTH_CONFIG', operation);
    credentials.set('authorization', serviceHeaders.get('authorization'));
  }
  const canShare = options.publicCache === true && scope?.edgeCache
    && typeof options.validatePublicData === 'function' && [...credentials].length === 0;
  const load = async () => {
    const started = Date.now();
    let key;
    if (canShare) {
      try {
        key = await publicCacheKey(scope.cacheOrigin, endpoint, document, variables);
        const hit = await scope.edgeCache.match(key);
        if (hit) {
          const cached = await hit.json();
          if (options.validatePublicData(cached) === true) {
            console.info(JSON.stringify({ event: 'cms_query', requestId: scope.requestId, operation,
              cache: 'HIT', durationMs: Date.now() - started, attempt: 0 }));
            return cached;
          }
        }
      } catch {
        // A cache failure must not prevent a request to the CMS.
        key = undefined;
      }
    }
    try {
      const headers = new Headers(credentials);
      headers.set('User-Agent', 'AlexCuadraPortfolio/1.0 (SSR; Cloudflare Workers)');
      if (scope?.requestId) headers.set('X-Request-ID', scope.requestId);
      const data = await graphqlRequest({ url: endpoint, document, variables,
        requestHeaders: headers, signal: AbortSignal.timeout(10000) });
      if (options.verifyServiceIdentity) {
        const authenticated = Number.isInteger(data?.viewer?.databaseId) && data.viewer.databaseId > 0;
        delete data.viewer;
        if (serviceHeaders.has('authorization')) {
          if (!authenticated) throw cmsFailure('CMS_IDENTITY_UNCONFIRMED', operation);
          scope.serviceIdentityVerified = true;
          console.info(JSON.stringify({ event: 'cms_auth_verified', requestId: scope.requestId,
            operation, authenticated: true }));
        }
      }
      if (key && options.validatePublicData?.(data) === true) {
        try {
          await scope.edgeCache.put(key, new Response(JSON.stringify(data), {
            headers: { 'Content-Type': 'application/json', 'Cache-Control': `public, max-age=${PUBLIC_CMS_TTL}` },
          }));
        } catch { /* Optional cache storage must not affect a successful query. */ }
      }
      if (scope?.requestId) console.info(JSON.stringify({ event: 'cms_query', requestId: scope.requestId,
        operation, cache: canShare ? 'MISS' : 'BYPASS', durationMs: Date.now() - started, attempt: 1 }));
      return data;
    } catch (error) {
      if (error instanceof CmsError) throw error;
      const status = error?.response?.status;
      const messages = error?.response?.errors?.map(item => item.message).join(' ') || '';
      const code = status === 401 || status === 403 || /authenticat|not authorized|unauthorized/i.test(messages)
        ? 'CMS_AUTHORIZATION'
        : status >= 400 ? 'CMS_HTTP'
        : error?.response ? 'CMS_GRAPHQL'
        : ['TimeoutError', 'AbortError'].includes(error?.name) ? 'CMS_TIMEOUT' : 'CMS_NETWORK';
      const retryAfter = retryAfterSeconds(error?.response?.headers?.get?.('retry-after'));
      throw cmsFailure(code, operation, status, { durationMs: Date.now() - started,
        attempt: 1, retryAfter });
    }
  };
  if (!scope) return load();
  const key = JSON.stringify([endpoint, document, variables, [...credentials], options.publicCache === true,
    options.verifyServiceIdentity === true]);
  if (!scope.cache.has(key)) scope.cache.set(key, load());
  return scope.cache.get(key);
}

export function withCmsRequest(next, options = {}) {
  const scope = { ...options, cache: new Map(), failure: null };
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
          'Retry-After': String(scope.failure.retryAfter ?? 60),
          'X-Robots-Tag': 'noindex',
        },
      });
    }
    if (scope.serviceIdentityVerified) {
      const headers = new Headers(response.headers);
      headers.set('X-CMS-Auth', 'verified');
      return new Response(response.body, { status: response.status, statusText: response.statusText, headers });
    }
    return response;
  });
}
