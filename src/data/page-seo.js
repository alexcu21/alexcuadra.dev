export const PRODUCTION_ORIGIN = 'https://alexcuadra.dev';

export function canonicalUrl(pathname) {
  if (typeof pathname !== 'string' || !pathname.startsWith('/') || pathname.startsWith('//')
    || /[\\\u0000-\u001f\u007f]/u.test(pathname)) throw new TypeError('Invalid public path');
  const url = new URL(pathname, PRODUCTION_ORIGIN);
  url.search = '';
  url.hash = '';
  return url.href;
}

export function socialImageUrl(value) {
  if (typeof value !== 'string' || !value.trim()) return undefined;
  try {
    const url = new URL(value, PRODUCTION_ORIGIN);
    return url.protocol === 'https:' && !url.username && !url.password ? url.href : undefined;
  } catch { return undefined; }
}

export function openGraphLocale(locale, regionalLocale) {
  if (!['en', 'es'].includes(locale)) throw new TypeError('Unsupported page locale');
  if (regionalLocale === undefined) return locale === 'en' ? 'en_US' : undefined;
  if (typeof regionalLocale !== 'string' || !new RegExp(`^${locale}_[A-Z]{2}$`).test(regionalLocale)) {
    throw new TypeError('Invalid regional locale');
  }
  return regionalLocale;
}
