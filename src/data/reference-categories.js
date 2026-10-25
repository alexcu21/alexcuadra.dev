import { getArticlesCategories } from './client.js';
import { referenceInventory } from './reference-inventory.js';
import { contentPath } from './site-routes.js';

// WordPress category.count includes posts and other CPTs using this taxonomy.
// Cards must describe only public references, including when Blog shares a term.
export function referenceCategoryCards(categories, references) {
  const counts = new Map();
  for (const reference of references) {
    for (const category of reference.categories.nodes) {
      if (!counts.has(category.slug)) counts.set(category.slug, new Set());
      counts.get(category.slug).add(reference.slug);
    }
  }
  return categories.filter(category => counts.get(category.slug)?.size)
    .map(category => ({ ...category, count: counts.get(category.slug).size }));
}

export async function getReferenceCategoryCards() {
  const [categories, references] = await Promise.all([getArticlesCategories(), referenceInventory()]);
  return referenceCategoryCards(categories, references);
}

export function referenceDirectory(categories, references) {
  const channels = new Map(categories.map(category => [category.slug, category]));
  const seen = new Set();
  return references.flatMap(reference => {
    if (typeof reference.title !== 'string' || !reference.title.trim() || seen.has(reference.slug)) return [];
    const terms = [...new Set(reference.categories.nodes.map(term => term.slug))]
      .filter(slug => channels.has(slug)).sort().map(slug => ({ slug, name: channels.get(slug).name }));
    if (!terms.length) return [];
    const path = contentPath('articles', 'en', reference.slug, terms[0].slug);
    seen.add(reference.slug);
    return [{ title: reference.title, path, channels: terms }];
  });
}

export async function getReferenceArchive() {
  const [categories, references] = await Promise.all([getArticlesCategories(), referenceInventory()]);
  const channels = referenceCategoryCards(categories, references);
  return { channels, entries: referenceDirectory(channels, references) };
}
