import { request, cmsFailure } from './cms-request.js';
import { graphqlLanguage, contentLocale, publicLocalizedCollection, publicTranslationLinks } from './content-language.js';
import { isPublicContent } from './content-visibility.js';
import { publicProjects } from './public-projects.js';
import { projectTaxonomyFields, publicProjectTaxonomies } from './project-taxonomies.js';

const endpoint = 'https://content.alexcuadra.dev/graphql';
const visibility = 'status isRestricted hasPassword';
const languageFields = `language { code } translations { databaseId slug ${visibility} language { code } }`;
const projectFields = `title content slug modified projects { githuburl projectTagline websiteurl }
  ${projectTaxonomyFields} featuredImage { node { sourceUrl } }`;
const articleFields = `id title content slug featuredImage { node { sourceUrl } }
  categories { nodes { id name slug } } articlesFields { quote }`;
const text = value => typeof value === 'string' ? value : null;

export function publicLanguageMetadata(node) {
  // Keep this projection idempotent: cache hits pass through the same public contract.
  return { language: { code: graphqlLanguage(contentLocale(node)) },
    translations: publicTranslationLinks(node).map(target => ({
      databaseId: target.databaseId, slug: target.slug,
      status: 'publish', isRestricted: false, hasPassword: false,
      language: { code: graphqlLanguage(target.locale) },
    })) };
}

export function localizedProjection(data, type, locale) {
  if (!['projects', 'articles'].includes(type)) throw new TypeError('Unsupported content type');
  const nodes = data?.[type]?.nodes;
  if (!publicLocalizedCollection(nodes, locale)) return undefined;
  const pageInfo = data[type].pageInfo;
  if (typeof pageInfo?.hasNextPage !== 'boolean'
    || (pageInfo.hasNextPage && (typeof pageInfo.endCursor !== 'string' || !pageInfo.endCursor))) return undefined;
  const taxonomies = type === 'projects' ? nodes.map(node => publicProjectTaxonomies(node, locale)) : [];
  if (taxonomies.some(terms => !terms)) return undefined;
  const projected = type === 'projects' ? publicProjects(data).projects.nodes : nodes.map(node => ({
    status: 'publish', isRestricted: false, hasPassword: false,
    id: text(node.id), title: text(node.title), content: text(node.content), slug: text(node.slug),
    featuredImage: node.featuredImage?.node ? { node: { sourceUrl: text(node.featuredImage.node.sourceUrl) } } : null,
    categories: { nodes: Array.isArray(node.categories?.nodes)
      ? node.categories.nodes.map(term => ({ id: text(term.id), name: text(term.name), slug: text(term.slug) })) : [] },
    articlesFields: node.articlesFields ? { quote: text(node.articlesFields.quote) } : null,
  }));
  return { [type]: { nodes: projected.map((node, index) => ({ ...node,
    ...(type === 'projects' ? taxonomies[index] : {}), ...publicLanguageMetadata(nodes[index]) })),
    pageInfo: { hasNextPage: pageInfo.hasNextPage, endCursor: text(pageInfo.endCursor) } } };
}

export async function localizedCollection(type, locale, category) {
  if (!['projects', 'articles'].includes(type)) throw new TypeError('Unsupported content type');
  const language = graphqlLanguage(locale); // Fixed EN/ES literals, never caller-supplied GraphQL text.
  const fields = type === 'projects' ? projectFields : articleFields;
  const filter = category === undefined ? '' : ', categoryName: $category';
  if (category !== undefined && (type !== 'articles' || typeof category !== 'string' || !category)) {
    throw new TypeError('Invalid article category');
  }
  const query = `query Localized${type === 'projects' ? 'Projects' : 'Articles'}($after: String${category === undefined ? '' : ', $category: String!'}) {
    ${type}(first: 100, after: $after, where: {language: ${language}, status: PUBLISH, hasPassword: false${filter}}) {
      nodes { ${visibility} ${fields} ${languageFields} } pageInfo { hasNextPage endCursor }
    }
  }`;
  const results = [];
  const cursors = new Set();
  let after = null;
  do {
    const data = await request(endpoint, query, { after, ...(category === undefined ? {} : { category }) }, {
      publicCache: true,
      servicePublicProjection: data => localizedProjection(data, type, locale),
      validatePublicData: data => Boolean(localizedProjection(data, type, locale)),
      validateServiceData: data => Boolean(localizedProjection(data, type, locale)),
    });
    // Validate also outside authenticated request scopes; never rely only on server-side filters.
    const projected = localizedProjection(data, type, locale);
    if (!projected) throw cmsFailure('CMS_LOCALE_CONTRACT', 'LocalizedCollection');
    results.push(...projected[type].nodes);
    if (!projected[type].pageInfo.hasNextPage) return results;
    after = projected[type].pageInfo.endCursor;
    if (cursors.has(after) || cursors.size >= 100) throw cmsFailure('CMS_PAGINATION', 'LocalizedCollection');
    cursors.add(after);
  } while (true);
}

export async function localizedArticle(slug, locale) {
  graphqlLanguage(locale);
  const query = `query LocalizedArticle($slug: ID!) { article(id: $slug, idType: SLUG) {
    ${visibility} ${articleFields} ${languageFields}
  } }`;
  const data = await request(endpoint, query, { slug }, {
    validateServiceData: data => data?.article === null || isPublicContent(data?.article),
  });
  if (data.article === null) return null;
  if (!isPublicContent(data.article)) throw cmsFailure('CMS_PUBLIC_CONTRACT', 'LocalizedArticle');
  // Wrong-language slugs do not silently substitute a different edition.
  if (contentLocale(data.article) !== locale) return null;
  return localizedProjection({ articles: { nodes: [data.article], pageInfo: { hasNextPage: false } } }, 'articles', locale).articles.nodes[0];
}

export async function localizedCategories(locale) {
  const language = graphqlLanguage(locale);
  const query = `query LocalizedCategories($after: String) {
    categories(first: 100, after: $after, where: {language: ${language}, hideEmpty: true}) {
      nodes { databaseId count name slug id language { code } } pageInfo { hasNextPage endCursor }
    }
  }`;
  const nodes = [];
  const cursors = new Set();
  let after = null;
  do {
    // Categories remain uncached until a separate public taxonomy contract is established.
    const data = await request(endpoint, query, { after });
    const connection = data?.categories;
    if (!Array.isArray(connection?.nodes) || !connection.nodes.every(node => contentLocale(node) === locale)
      || typeof connection.pageInfo?.hasNextPage !== 'boolean') {
      throw cmsFailure('CMS_LOCALE_CONTRACT', 'LocalizedCategories');
    }
    nodes.push(...connection.nodes.map(node => ({ databaseId: node.databaseId,
      count: node.count, name: text(node.name), slug: text(node.slug), id: text(node.id), language: { code: language } })));
    if (!connection.pageInfo.hasNextPage) return nodes;
    after = connection.pageInfo.endCursor;
    if (typeof after !== 'string' || !after || cursors.has(after) || cursors.size >= 100) {
      throw cmsFailure('CMS_PAGINATION', 'LocalizedCategories');
    }
    cursors.add(after);
  } while (true);
}
