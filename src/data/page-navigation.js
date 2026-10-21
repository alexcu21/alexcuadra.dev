import { request, cmsFailure } from './cms-request.js';
import { contentLocale, graphqlLanguage, publicTranslationLinks } from './content-language.js';
import { isPublicContent } from './content-visibility.js';
import { publicLanguageMetadata } from './localized-content.js';

function projection(data) {
  if (data?.page === null) return { page: null };
  if (!isPublicContent(data?.page) || !contentLocale(data.page)) return undefined;
  return { page: { status: 'publish', isRestricted: false, hasPassword: false,
    ...publicLanguageMetadata(data.page) } };
}

// Navigation needs a public relationship, not the translated page's body or ACF.
export async function hasPageTranslation(sourceId, locale) {
  graphqlLanguage(locale);
  if (!Number.isSafeInteger(sourceId) || sourceId <= 0) throw new TypeError('Invalid page source');
  const data = await request('https://content.alexcuadra.dev/graphql', `query PageNavigation($id: ID!) {
    page(id: $id, idType: DATABASE_ID) {
      status isRestricted hasPassword language { code }
      translations { databaseId slug status isRestricted hasPassword language { code } }
    }
  }`, { id: String(sourceId) }, { publicCache: true, servicePublicProjection: projection,
    validatePublicData: data => Boolean(projection(data)), validateServiceData: data => Boolean(projection(data)) });
  const result = projection(data);
  if (!result) throw cmsFailure('CMS_PUBLIC_CONTRACT', 'PageNavigation');
  return result.page !== null && (contentLocale(result.page) === locale
    || publicTranslationLinks(result.page).some(target => target.locale === locale));
}
