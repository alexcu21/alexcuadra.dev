import { graphqlLanguage, publicLocalizedCollection } from './content-language.js';

// Store only the visibility of one public entry, never editorial fields or IDs.
// The request layer partitions service caches by credentials and uses its60s TTL.
export function publicAvailabilityOptions(field, locale) {
  if (!['posts', 'labProjects'].includes(field)) throw new TypeError('Unsupported availability field');
  const code = graphqlLanguage(locale);
  const project = data => {
    const nodes = data?.[field]?.nodes;
    if (!publicLocalizedCollection(nodes, locale) || nodes.length > 1) return undefined;
    return { [field]: { nodes: nodes.map(() => ({ status: 'publish', isRestricted: false,
      hasPassword: false, language: { code } })) } };
  };
  return { publicCache: true, servicePublicProjection: project,
    validatePublicData: data => Boolean(project(data)), validateServiceData: data => Boolean(project(data)) };
}
