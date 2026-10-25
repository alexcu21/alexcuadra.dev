import { request, cmsFailure } from './cms-request.js';
import { publicCollection } from './content-visibility.js';

// Cache only public routing metadata, never article bodies or service identity.
export function publicReferenceInventory(data) {
  const connection = data?.articles;
  const safeSlug = value => typeof value === 'string' && value.length > 0
    && !/[\s/\\?#\u0000-\u001f\u007f]/u.test(value) && !['.', '..'].includes(value);
  if (!publicCollection(connection?.nodes) || typeof connection.pageInfo?.hasNextPage !== 'boolean'
    || (connection.pageInfo.hasNextPage && (typeof connection.pageInfo.endCursor !== 'string' || !connection.pageInfo.endCursor))
    || connection.nodes.some(node => !safeSlug(node.slug) || !Array.isArray(node.categories?.nodes)
      || node.categories.pageInfo?.hasNextPage !== false
      || node.categories.nodes.some(term => !safeSlug(term?.slug)))) return undefined;
  return { articles: {
    nodes: connection.nodes.map(node => ({ status: 'publish', isRestricted: false, hasPassword: false,
      slug: node.slug, categories: { nodes: node.categories.nodes.map(term => ({ slug: term.slug })),
        pageInfo: { hasNextPage: false } } })),
    pageInfo: { hasNextPage: connection.pageInfo.hasNextPage,
      endCursor: typeof connection.pageInfo.endCursor === 'string' ? connection.pageInfo.endCursor : null },
  } };
}

// References keep their existing category URLs while their language model is pending.
export async function referenceInventory() {
  const nodes = [], cursors = new Set();
  let after = null;
  do {
    const data = await request('https://content.alexcuadra.dev/graphql', `query SitemapReferences($after: String) {
      articles(first: 100, after: $after, where: {status: PUBLISH, hasPassword: false}) {
        nodes { status isRestricted hasPassword slug
          categories(first: 100) { nodes { slug } pageInfo { hasNextPage } }
        } pageInfo { hasNextPage endCursor }
      }
    }`, { after }, {
      publicCache: true, servicePublicProjection: publicReferenceInventory,
      validatePublicData: data => Boolean(publicReferenceInventory(data)),
      validateServiceData: data => Boolean(publicReferenceInventory(data)),
    });
    const connection = publicReferenceInventory(data)?.articles;
    if (!connection) {
      throw cmsFailure('CMS_SITEMAP_CONTRACT', 'SitemapReferences');
    }
    nodes.push(...connection.nodes);
    if (!connection.pageInfo.hasNextPage) return nodes;
    after = connection.pageInfo.endCursor;
    if (typeof after !== 'string' || !after || cursors.has(after) || cursors.size >= 100) {
      throw cmsFailure('CMS_PAGINATION', 'SitemapReferences');
    }
    cursors.add(after);
  } while (true);
}
