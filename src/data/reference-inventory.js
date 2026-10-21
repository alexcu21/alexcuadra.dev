import { request, cmsFailure } from './cms-request.js';
import { publicCollection } from './content-visibility.js';

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
    }`, { after });
    const connection = data?.articles;
    if (!publicCollection(connection?.nodes) || typeof connection.pageInfo?.hasNextPage !== 'boolean'
      || connection.nodes.some(node => !Array.isArray(node.categories?.nodes)
        || node.categories.pageInfo?.hasNextPage !== false)) {
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
