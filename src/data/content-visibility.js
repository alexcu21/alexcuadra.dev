export function isPublicContent(node) {
  return node?.status === 'publish' && node.isRestricted === false && node.hasPassword === false;
}

export function publicCollection(nodes) {
  return Array.isArray(nodes) && nodes.every(isPublicContent);
}
