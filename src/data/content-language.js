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
export function publicTranslationLinks(node) {
  const sourceLocale = contentLocale(node);
  if (!isPublicContent(node) || !sourceLocale || !Array.isArray(node.translations)) return [];
  const candidates = node.translations.filter(target => isPublicContent(target)
    && contentLocale(target) && contentLocale(target) !== sourceLocale
    && Number.isSafeInteger(target.databaseId) && target.databaseId > 0
    && typeof target.slug === 'string' && target.slug.length > 0
    && !/[\s/\\?#\u0000-\u001f\u007f]/u.test(target.slug));
  // Ambiguous relationships must not produce a guessed language-switcher destination.
  return candidates.filter(target => candidates.filter(other => contentLocale(other) === contentLocale(target)).length === 1)
    .map(target => ({ databaseId: target.databaseId, slug: target.slug, locale: contentLocale(target) }));
}

export function publicLocalizedCollection(nodes, locale) {
  graphqlLanguage(locale);
  return Array.isArray(nodes) && nodes.every(node => isPublicContent(node) && contentLocale(node) === locale);
}
