import { isPublicContent, publicCollection } from './content-visibility.js';

const textFields = (value, keys) => Object.fromEntries(keys.filter(key =>
  typeof value?.[key] === 'string' || value?.[key] === null).map(key => [key, value[key]]));
const visible = { status: 'publish', isRestricted: false, hasPassword: false };
function safeSlug(value) {
  if (typeof value !== 'string' || !value) return false;
  try { return !/[\s/\\?#\u0000-\u001f\u007f]/u.test(decodeURIComponent(value))
    && !['.', '..'].includes(decodeURIComponent(value)); } catch { return false; }
}
function pageInfo(info) {
  if (typeof info?.hasNextPage !== 'boolean' || (info.hasNextPage
    && (typeof info.endCursor !== 'string' || !info.endCursor))) return;
  return { hasNextPage: info.hasNextPage, endCursor: typeof info.endCursor === 'string' ? info.endCursor : null };
}
function categories(connection) {
  const info = pageInfo(connection?.pageInfo);
  if (!info || !Array.isArray(connection?.nodes) || connection.nodes.some(term => !safeSlug(term?.slug))) return;
  return { nodes: connection.nodes.map(term => textFields(term, ['id', 'name', 'slug'])), pageInfo: info };
}
function reference(node, detail) {
  if (!isPublicContent(node) || !safeSlug(node.slug)) return;
  const result = { ...visible, ...textFields(node, ['id', 'slug', 'title', 'content']) };
  if (node.featuredImage === null) result.featuredImage = null;
  else if (node.featuredImage?.node) result.featuredImage = { node: textFields(node.featuredImage.node, ['sourceUrl']) };
  if (detail) {
    result.categories = categories(node.categories);
    if (!result.categories) return;
    if (node.articlesFields === null) result.articlesFields = null;
    else if (node.articlesFields) result.articlesFields = textFields(node.articlesFields, ['quote']);
  }
  return result;
}
export function publicReferenceDetail(data) {
  if (data?.article === null) return { article: null };
  const article = reference(data?.article, true);
  return article ? { article } : undefined;
}
export function publicReferenceCategoryPage(data) {
  if (!isPublicContent(data?.article)) return;
  const terms = categories(data.article.categories);
  return terms ? { article: { ...visible, categories: terms } } : undefined;
}
export function publicReferenceArchive(data) {
  const connection = data?.articles, info = pageInfo(connection?.pageInfo);
  if (!info || !publicCollection(connection?.nodes)) return;
  const nodes = connection.nodes.map(node => reference(node, false));
  return nodes.some(node => !node) ? undefined : { articles: { nodes, pageInfo: info } };
}
export function publicReferenceCache(project) {
  return { publicCache: true, servicePublicProjection: project,
    validateServiceData: data => Boolean(project(data)), validatePublicData: data => Boolean(project(data)) };
}
