import { retryAfterSeconds } from './cms-cache.js';

const labels = {
  en: {missing:'Page not found',temporary:'Content temporarily unavailable',
    missingBody:'The page may have moved, or the address may be incorrect.',
    temporaryBody:'We could not load this page right now.',wait:seconds=>`Please wait at least ${seconds} seconds before trying again.`,
    retry:'Try this page again',home:'Return home',projects:'Explore projects',navigation:'Recovery options'},
  es: {missing:'No encontramos esta página',temporary:'Contenido temporalmente no disponible',
    missingBody:'La página puede haber cambiado de dirección o el enlace puede ser incorrecto.',
    temporaryBody:'No pudimos cargar esta página en este momento.',wait:seconds=>`Espera al menos ${seconds} segundos antes de volver a intentarlo.`,
    retry:'Volver a intentar',home:'Volver al inicio',projects:'Explorar proyectos',navigation:'Opciones para continuar'},
};

export function errorDocument(status, locale = 'en', seconds = 60) {
  const copy = labels[locale === 'es' ? 'es' : 'en'];
  const spanish = locale === 'es';
  const unavailable = status === 503;
  const title = unavailable ? copy.temporary : copy.missing;
  const wait = Number.isSafeInteger(seconds) && seconds >= 1 ? seconds : 60;
  return `<!doctype html><html lang="${spanish ? 'es' : 'en'}"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width"><meta name="robots" content="noindex">
<title>${title} | Alex Cuadra</title><link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="/error-pages.css">
</head><body><main id="main-content" tabindex="-1"><a class="brand" href="${spanish ? '/es/' : '/'}">&lt;/&gt; Alex Cuadra</a>
<p class="status">${unavailable ? '503' : '404'}</p><h1>${title}</h1>
<p>${unavailable ? copy.temporaryBody : copy.missingBody}</p>${unavailable ? `<p>${copy.wait(wait)}</p>` : ''}
<nav aria-label="${copy.navigation}">${unavailable ? `<a class="primary" href="">${copy.retry}</a>` : ''}
<a href="${spanish ? '/es/' : '/'}">${copy.home}</a><a href="${spanish ? '/es/proyectos/' : '/work/'}">${copy.projects}</a></nav>
</main></body></html>`;
}

// Presentation only: never fetch CMS data, change status, or replace API/XML errors.
export function presentDocumentError(request, response) {
  const path = new URL(request.url).pathname;
  if (![404,503].includes(response.status) || !['GET','HEAD'].includes(request.method)
    || !/\btext\/html\b/i.test(request.headers.get('accept') || '')
    || path === '/api' || path.startsWith('/api/')
    || /^\/(?:robots\.txt|sitemap\.xml|favicon\.ico)$/.test(path)
    || /(?:json|xml)/i.test(response.headers.get('content-type') || '')) return response;
  const headers = new Headers(response.headers);
  const seconds = retryAfterSeconds(headers.get('retry-after')) ?? 60;
  const locale = path === '/es' || path.startsWith('/es/') ? 'es' : 'en';
  headers.set('Content-Type','text/html; charset=utf-8');
  headers.set('Cache-Control','no-store');
  headers.set('X-Robots-Tag','noindex');
  headers.set('Content-Security-Policy',"default-src 'none'; style-src 'self'; img-src 'self'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
  headers.set('X-Content-Type-Options','nosniff');
  headers.set('Referrer-Policy','no-referrer');
  for (const name of ['Content-Length','Content-Encoding','ETag','Last-Modified']) headers.delete(name);
  return new Response(request.method === 'HEAD' ? null : errorDocument(response.status,locale,seconds),
    {status:response.status,statusText:response.statusText,headers});
}
