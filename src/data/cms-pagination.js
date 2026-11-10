import { cmsFailure } from './cms-request.js';

// Do not return a partial inventory when a later page or cursor is invalid.
export async function collectCmsPages(loadPage, operation) {
  const nodes = [], cursors = new Set();
  let after = null;
  for (let page = 0; page < 100; page++) {
    const connection = await loadPage(after);
    if (!Array.isArray(connection?.nodes) || typeof connection.pageInfo?.hasNextPage !== 'boolean') {
      throw cmsFailure('CMS_PAGINATION', operation);
    }
    nodes.push(...connection.nodes);
    if (!connection.pageInfo.hasNextPage) return nodes;
    after = connection.pageInfo.endCursor;
    if (typeof after !== 'string' || !after || cursors.has(after)) {
      throw cmsFailure('CMS_PAGINATION', operation);
    }
    cursors.add(after);
  }
  throw cmsFailure('CMS_PAGINATION', operation);
}
