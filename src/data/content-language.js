import { isPublicContent } from './content-visibility.js';

// Polylang's GraphQL enum serializes as EN/ES. Never infer a language from a slug.
export function contentLocale(node) {
  const code = node?.language?.code;
  return code === 'EN' ? 'en' : code === 'ES' ? 'es' : undefined;
}

export function graphqlLanguage(locale) {
  if (locale === 'en') return 'EN';
  if (locale === 'es') return 'ES';
  throw new TypeError('Unsupported content locale');
}

// Project only navigation metadata, never the authenticated translation object or ACF.
// Private local preview only. No fabricated public status for draft entries.
export function publicTranslationLinks(node){return node.translationLinks??node.translations??[];}

export function publicLocalizedCollection(nodes, locale) {
  graphqlLanguage(locale);
  return Array.isArray(nodes) && nodes.every(node => isPublicContent(node) && contentLocale(node) === locale);
}
