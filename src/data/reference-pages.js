import { getArticleBySlug, getArticlesByCategory, getArticlesCategories } from './client.js';

export function referenceNotFound() {
  return new Response('Not found', { status: 404, headers: {
    'Cache-Control': 'no-store', 'X-Robots-Tag': 'noindex',
  } });
}

// Keep the legacy all-language archive until editorial classification is agreed.
export async function referenceCategoryPage(slug) {
  const categories = await getArticlesCategories();
  const category = categories.find(item => item.slug === slug);
  if (!category) return null;
  return { category, articles: await getArticlesByCategory(slug) };
}

export async function referenceDetailPage(categorySlug, articleSlug) {
  const article = await getArticleBySlug(articleSlug);
  const category = article?.categories?.nodes?.find(item => item.slug === categorySlug);
  return article && category ? { article, category } : null;
}
