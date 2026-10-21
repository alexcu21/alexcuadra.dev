import { getArticlesCategories } from './client.js';
import { referenceInventory } from './reference-inventory.js';

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
