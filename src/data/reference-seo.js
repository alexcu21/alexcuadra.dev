import { canonicalUrl } from './page-seo.js';
import { categoryPath, contentPath } from './site-routes.js';

// References may be mentions or externally authored material: never infer authorship.
export function referenceBreadcrumbs(category, article) {
  const entries = [{ name: 'Articles & Mentions', path: '/articles/' },
    { name: category.name, path: categoryPath('en', category.slug) }];
  if (article) entries.push({ name: article.title,
    path: contentPath('articles', 'en', article.slug, category.slug) });
  return JSON.stringify({ '@context': 'https://schema.org', '@type': 'BreadcrumbList',
    itemListElement: entries.map((entry, index) => ({ '@type': 'ListItem', position: index + 1,
      name: entry.name, item: canonicalUrl(entry.path) })),
  }).replace(/</g, '\\u003c');
}
