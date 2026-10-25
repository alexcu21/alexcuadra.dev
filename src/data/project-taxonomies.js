import { contentLocale, graphqlLanguage } from './content-language.js';

// Aliases preserve the public DTO while reading only project-owned taxonomies.
export const projectTaxonomyFields = `
  categories: projectCategories(first: 100) {
    nodes { name language { code } } pageInfo { hasNextPage }
  }
  tags: projectTechnologies(first: 100) {
    nodes { name language { code } } pageInfo { hasNextPage }
  }`;

export function publicProjectTaxonomies(node, locale) {
  const code = graphqlLanguage(locale);
  const result = {};
  for (const field of ['categories', 'tags']) {
    const connection = node?.[field];
    if (!Array.isArray(connection?.nodes) || connection.pageInfo?.hasNextPage !== false
      || connection.nodes.some(term => typeof term?.name !== 'string' || !term.name.trim()
        || contentLocale(term) !== locale)) return undefined;
    result[field] = { nodes: connection.nodes.map(term => ({ name: term.name, language: { code } })),
      pageInfo: { hasNextPage: false } };
  }
  return result;
}
