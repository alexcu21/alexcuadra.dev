import { AsyncLocalStorage } from 'node:async_hooks';
import { GraphQLClient } from 'graphql-request';
import { publicCacheKey, PUBLIC_CMS_TTL, retryAfterSeconds } from './cms-cache.js';
import { cmsAuthHeaders } from './cms-auth.js';
import { cmsOriginCooldown } from './cms-origin-cooldown.js';
import { coordinateCmsOrigin } from './cms-origin-coordination.js';
import { cmsOriginDiagnosticFetch } from './cms-origin-diagnostics.js';

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
  if (scope) {
    scope.failure = error;
    scope.metrics.failures++;
    if (status === 429) scope.metrics.upstream429++;
  }
  error.retryAfter = details.retryAfter;
  // Do not log raw GraphQL errors, queries, headers or response bodies.
  console.error(JSON.stringify({ event: 'cms_failure', code, operation, httpStatus: status,
    requestId: scope?.requestId, ...details }));
  return error;
}

export async function request(endpoint, document, variables, options = {}) {
  const operation = /\bquery\s+(\w+)/.exec(document)?.[1] || 'unknown';
  const scope = requests.getStore();
  // Caller-provided credentials always bypass shared cache.
  const credentials = new Headers(options.requestHeaders);
  let serviceHeaders;
  try { serviceHeaders = cmsAuthHeaders(scope?.cmsConfig); }
  catch { throw cmsFailure('CMS_AUTH_CONFIG', operation); }
  if (serviceHeaders.has('authorization')) {
    if (new URL(endpoint).protocol !== 'https:') throw cmsFailure('CMS_AUTH_CONFIG', operation);
    credentials.set('authorization', serviceHeaders.get('authorization'));
  }
  const servicePublic = serviceHeaders.has('authorization')
    && [...new Headers(options.requestHeaders)].length === 0
    && typeof options.servicePublicProjection === 'function';
  const canShare = options.publicCache === true && scope?.edgeCache
    && typeof options.validatePublicData === 'function' && ([...credentials].length === 0 || servicePublic);
  // Separate anonymous/service entries and rotate the key when credentials change.
  // The partition is hashed, never logged or placed verbatim in the cache URL.
  const partition = servicePublic ? `service-public-v1:${serviceHeaders.get('authorization')}` : 'anonymous';
  // Explicitly enabled for server service credentials only. Custom transport
  // headers are outside this contract. Hash the credential; never retain it in state.
  const cooldown = scope?.cmsConfig?.CMS_ORIGIN_COOLDOWN_ENABLED === 'true'
    && serviceHeaders.has('authorization') && [...new Headers(options.requestHeaders)].length === 0
    ? scope.originCooldown ?? cmsOriginCooldown : undefined;
  const coordinated = scope?.cmsConfig?.CMS_ORIGIN_COORDINATOR_ENABLED === 'true'
    && serviceHeaders.has('authorization') && [...new Headers(options.requestHeaders)].length === 0;
  const load = async () => {
    const started = Date.now();
    let key;
    if (canShare) {
      try {
        key = await publicCacheKey(scope.cacheOrigin, endpoint, document, variables, partition);
        const hit = await scope.edgeCache.match(key);
        if (hit) {
          let cached = await hit.json();
          if (servicePublic) cached = options.servicePublicProjection(cached);
          if (options.validatePublicData(cached) === true) {
            scope.metrics.cacheHits++;
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
    let cooldownKey;
    if (cooldown || coordinated) {
      try {
        cooldownKey = (await publicCacheKey(scope.cacheOrigin, endpoint, 'origin-cooldown-v1', null,
          serviceHeaders.get('authorization'))).url;
      } catch { /* Optional local protection must not break origin access. */ }
      const remaining = cooldownKey && cooldown ? cooldown.remaining(cooldownKey) : 0;
      if (remaining) {
        scope.metrics.originSuppressed++;
        throw cmsFailure('CMS_ORIGIN_COOLDOWN', operation, undefined, {
          retryAfter: remaining, durationMs: Date.now() - started, attempt: 0,
        });
      }
    }
    if (coordinated) {
      let remaining;
      scope.metrics.coordinatorChecks++;
      try {
        remaining = await coordinateCmsOrigin(scope.cmsConfig.CMS_ORIGIN_COORDINATOR,
          cooldownKey?.split('/').at(-1));
      } catch {
        scope.metrics.originSuppressed++;
        throw cmsFailure('CMS_COORDINATOR_UNAVAILABLE', operation, undefined, { attempt: 0, retryAfter: 60 });
      }
      if (remaining) {
        cooldown?.block(cooldownKey, remaining);
        scope.metrics.originSuppressed++;
        throw cmsFailure('CMS_COORDINATED_COOLDOWN', operation, undefined, { attempt: 0, retryAfter: remaining });
      }
    }
    try {
      const headers = new Headers(credentials);
      headers.set('User-Agent', 'AlexCuadraPortfolio/1.0 (SSR; Cloudflare Workers)');
      if (scope?.requestId) headers.set('X-Request-ID', scope.requestId);
      if (scope) {
        scope.metrics.originAttempts++;
        scope.metrics[canShare ? 'sharedOriginAttempts' : 'bypassOriginAttempts']++;
      }
      const diagnosticFetch = cmsOriginDiagnosticFetch({ config: scope?.cmsConfig, operation, url: endpoint,
        requestId: scope?.requestId, cacheEligible: Boolean(canShare), authorizationAttached: credentials.has('authorization') });
      let data = await new GraphQLClient(endpoint, { fetch: diagnosticFetch ?? globalThis.fetch }).request({ document, variables,
        requestHeaders: headers, signal: AbortSignal.timeout(10000) });
      if (serviceHeaders.has('authorization') && typeof options.validateServiceData === 'function'
        && options.validateServiceData(data) !== true) {
        throw cmsFailure('CMS_PUBLIC_CONTRACT', operation);
      }
      if (servicePublic) {
        data = options.servicePublicProjection(data);
        if (!data) throw cmsFailure('CMS_PUBLIC_CONTRACT', operation);
      }
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
      if (status === 429 && cooldownKey) {
        cooldown?.block(cooldownKey, retryAfter);
        if (coordinated) {
          try {
            await coordinateCmsOrigin(scope.cmsConfig.CMS_ORIGIN_COORDINATOR,
              cooldownKey.split('/').at(-1), retryAfter ?? 60);
          } catch {
            // Do not retry the report or the origin. A later failed check closes access.
            console.error(JSON.stringify({ event: 'cms_coordination_report_failed', requestId: scope.requestId }));
          }
        }
      }
      throw cmsFailure(code, operation, status, { durationMs: Date.now() - started,
        attempt: 1, retryAfter });
    }
  };
  if (!scope) return load();
  const key = JSON.stringify([endpoint, document, variables, [...credentials], options.publicCache === true,
    options.verifyServiceIdentity === true, servicePublic]);
  if (!scope.cache.has(key)) scope.cache.set(key, load());
  else scope.metrics.deduplicated++;
  return scope.cache.get(key);
}

export function withCmsRequest(next, options = {}) {
  const started = Date.now();
  const scope = { ...options, cache: new Map(), failure: null, metrics: {
    cacheHits: 0, originAttempts: 0, sharedOriginAttempts: 0,
    bypassOriginAttempts: 0, deduplicated: 0, failures: 0, upstream429: 0, originSuppressed: 0, coordinatorChecks: 0,
  } };
  return requests.run(scope, async () => {
    let response;
    try {
      try {
        response = await next();
        // Astro can resolve next() before nested components finish streaming.
        // Finish HTML inside this scope so a late CMS failure cannot commit a
        // successful status with a partial (or empty) document.
        if (options.bufferHtml === true && response.body
          && /^text\/html(?:\s*;|$)/i.test(response.headers.get('content-type') || '')) {
          const body = await response.arrayBuffer();
          response = new Response(body, { status: response.status, statusText: response.statusText,
            headers: response.headers });
        }
      } catch (error) {
        if (!(error instanceof CmsError) && !scope.failure) {
          response = undefined;
          throw error;
        }
      }
      // Astro may turn a rendering exception into a response internally.
      if (scope.failure) {
        response = new Response('Content is temporarily unavailable. Please try again later.', {
          status: 503,
          headers: {
            'Content-Type': 'text/plain; charset=utf-8',
            'Cache-Control': 'no-store',
            'Retry-After': String(scope.failure.retryAfter ?? 60),
            'X-Robots-Tag': 'noindex',
          },
        });
        return response;
      }
      if (scope.serviceIdentityVerified) {
        const headers = new Headers(response.headers);
        headers.set('X-CMS-Auth', 'verified');
        return new Response(response.body, { status: response.status, statusText: response.statusText, headers });
      }
      return response;
    } finally {
      // One bounded record after HTML completes, including failures caught by Astro.
      // No URLs, query strings, credentials, editorial data or raw exceptions.
      console.info(JSON.stringify({ event: 'cms_request_summary', requestId: scope.requestId,
        route: scope.routePattern ?? 'unknown',
        status: response?.status ?? 500, durationMs: Date.now() - started, ...scope.metrics }));
    }
  });
}
