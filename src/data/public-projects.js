// Explicit public contract for the portfolio; never store the raw authenticated response.
import { publicCollection } from './content-visibility.js';
function text(value) { return typeof value === 'string' ? value : null; }
export function publicProjects(data) {
  const nodes = data?.projects?.nodes;
  if (!publicCollection(nodes)) return undefined;
  return { projects: { nodes: nodes.map(node => ({
    status: 'publish', isRestricted: false, hasPassword: false,
    title: text(node.title), content: text(node.content), slug: text(node.slug), modified: text(node.modified),
    projects: node.projects ? {
      githuburl: text(node.projects.githuburl), projectTagline: text(node.projects.projectTagline),
      websiteurl: text(node.projects.websiteurl),
    } : null,
    tags: { nodes: Array.isArray(node.tags?.nodes) ? node.tags.nodes.map(tag => ({ name: text(tag.name) })) : [] },
    featuredImage: node.featuredImage?.node ? { node: { sourceUrl: text(node.featuredImage.node.sourceUrl) } } : null,
  })) } };
}
