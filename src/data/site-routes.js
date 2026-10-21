// Route bases are technical configuration; editorial slugs come from WordPress.
const routes = {
  en: { home: '/', about: '/about/', projects: '/work/', articles: '/articles/', blog: '/blog/', lab: '/lab/' },
  es: { home: '/es/', about: '/es/sobre-mi/', projects: '/es/proyectos/', articles: '/es/articulos/', blog: '/es/blog/', lab: '/es/laboratorio/' },
};

export function routeBase(section, locale) {
  if (!Object.hasOwn(routes, locale) || !Object.hasOwn(routes[locale], section)) throw new TypeError('Unsupported route');
  return routes[locale][section];
}

function segment(slug) {
  if (typeof slug !== 'string' || !slug) throw new TypeError('Missing CMS slug');
  let decoded;
  try { decoded = decodeURIComponent(slug); } catch { throw new TypeError('Invalid CMS slug'); }
  if (decoded === '.' || decoded === '..' || /[\s/\\?#\u0000-\u001f\u007f]/u.test(decoded)) throw new TypeError('Invalid CMS slug');
  return encodeURIComponent(decoded);
}

export function contentPath(section, locale, slug, categorySlug) {
  if (!['projects', 'articles', 'blog', 'lab'].includes(section)) throw new TypeError('Unsupported detail route');
  const base = routeBase(section, locale);
  if (section === 'articles') return `${categoryPath(locale, categorySlug)}/${segment(slug)}`;
  return `${base}${segment(slug)}`;
}

export function categoryPath(locale, slug) {
  return `${routeBase('articles', locale)}${locale === 'en' ? 'category' : 'categoria'}/${segment(slug)}`;
}
