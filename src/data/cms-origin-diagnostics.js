const endpoint = 'https://content.alexcuadra.dev/graphql';
const successfulOperations = new Set(['getArticleCategories', 'LabAvailability']);
const uuid = /^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/i;
const opaqueId = value => typeof value === 'string'
  && (uuid.test(value) || /^[a-f0-9]{12,128}$/i.test(value)) ? value : null;

// Opt-in, expiring and limited to the CMS endpoint. Failures across operations
// must remain observable; successful samples are restricted to two operations.
// Never inspect bodies,
// request headers, credentials, arbitrary URLs or credential-derived cache keys.
export function cmsOriginDiagnosticFetch({ config, operation, url, requestId, cacheEligible,
  authorizationAttached, transport = globalThis.fetch, log = console.info, now = Date.now }) {
  const until = config?.CMS_ORIGIN_DIAGNOSTICS_UNTIL;
  const expires = typeof until === 'string' && /^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d\.\d{3}Z$/.test(until)
    ? Date.parse(until) : NaN;
  if (typeof operation !== 'string' || !/^[A-Za-z_]\w{0,79}$/.test(operation)
    || url !== endpoint || !uuid.test(requestId ?? '')
    || !Number.isFinite(expires) || expires <= now() || expires - now() > 24 * 60 * 60 * 1000) return;
  return async (...args) => {
    const started = now();
    const emit = (response, outcome) => {
      try {
        const headers = response?.headers;
        const server = headers?.get('server');
        const contentType = headers?.get('content-type')?.split(';')[0].trim().toLowerCase();
        const retry = headers?.get('retry-after');
        log(JSON.stringify({ event: 'cms_origin_diagnostic', operationName: operation, requestId,
          upstreamUrl: endpoint, startedAt: new Date(started).toISOString(), completedAt: new Date(now()).toISOString(),
          status: response?.status ?? null, outcome, cacheEligible: cacheEligible === true,
          authorizationAttached: authorizationAttached === true,
          server: !server ? null : /^(?:cloudflare|litespeed|nginx|apache)(?:\b|\/)/i.exec(server)?.[0].replace(/\/$/, '').toLowerCase() ?? 'other',
          contentType: !contentType ? null : ['application/json', 'application/graphql-response+json', 'text/html', 'text/plain'].includes(contentType) ? contentType : 'other',
          retryAfter: retry && /^(?:\d{1,9}|[A-Z][a-z]{2}, \d\d [A-Z][a-z]{2} \d{4} \d\d:\d\d:\d\d GMT)$/.test(retry) ? retry : null,
          cfRay: /^[a-f0-9]{16,32}-[A-Z]{3}$/.test(headers?.get('cf-ray') ?? '') ? headers.get('cf-ray') : null,
          upstreamRequestId: opaqueId(headers?.get('x-request-id')),
          hostingerRequestId: opaqueId(headers?.get('x-hcdn-request-id')),
          headerPresence: Object.fromEntries(['server', 'content-type', 'retry-after', 'cf-ray',
            'x-request-id', 'x-hcdn-request-id'].map(key => [key, headers?.has(key) === true])),
          redirected: response?.redirected === true,
        }));
      } catch { /* Diagnostics cannot change the transport result. */ }
    };
    try {
      const response = await transport(...args);
      if (response.status >= 400 || successfulOperations.has(operation)) emit(response, 'response');
      return response;
    } catch (error) {
      emit(undefined, 'transport_error');
      throw error;
    }
  };
}
