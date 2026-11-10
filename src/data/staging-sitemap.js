import { read } from './emdash-provider.js';
import { canonicalUrl } from './page-seo.js';

export async function stagingSitemap() {
  const paths = new Set(['/contact/', '/es/contacto/']);
  for (const locale of ['en', 'es']) {
    const prefix = locale === 'es' ? '/es' : '';
    for (const collection of ['pages', 'projects', 'posts', 'lab_projects', 'references']) {
      const entries = await read('list', collection, locale, { limit: '20' });
      if (entries.length && collection !== 'pages') {
        const archives = locale === 'es'
          ? { projects: '/proyectos/', posts: '/blog/', lab_projects: '/laboratorio/', references: '/articulos/' }
          : { projects: '/work/', posts: '/blog/', lab_projects: '/lab/', references: '/articles/' };
        paths.add(prefix + archives[collection]);
      }
      for (const entry of entries) {
        paths.add(entry.route);
        for (const term of entry.terms?.reference_category ?? []) {
          const categoryBase = locale === 'es' ? '/es/articulos/categoria/' : '/articles/category/';
          paths.add(categoryBase + encodeURIComponent(term.slug) + '/');
        }
      }
    }
  }
  const locations = [...paths].sort().map(path => '<url><loc>' + canonicalUrl(path).replaceAll('&', '&amp;') + '</loc></url>');
  return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' + locations.join('') + '</urlset>';
}
