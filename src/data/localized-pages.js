import { request, cmsFailure } from './cms-request.js';
import { graphqlLanguage, contentLocale, publicTranslationLinks } from './content-language.js';
import { isPublicContent } from './content-visibility.js';
import { publicLanguageMetadata } from './localized-content.js';

const endpoint = 'https://content.alexcuadra.dev/graphql';
const homeKeys = ['fieldGroupName', 'name_field', 'role1_field', 'role2_field', 'role3_field', 'tagLine_field',
  'twitterUrl', 'youtubeUrl', 'githubUrl', 'linkedinUrl', 'email'];
const skillKeys = ['skillDescription1', 'skillDescription2', 'skillDescription3', 'skillName1', 'skillName2', 'skillName3'];
const aboutKeys = ['education', 'introText', 'skillsText'];
const text = value => typeof value === 'string' ? value : null;
const pick = (object, keys) => Object.fromEntries(keys.map(key => [key, text(object?.[key])]));
const fields = {
  home: `homePageFields { ${homeKeys.join(' ')} photo_field { node { link sourceUrl } } skills { ${skillKeys.join(' ')} } }`,
  about: `aboutPage { ${aboutKeys.join(' ')} } featuredImage { node { sourceUrl } }`,
};
const common = `databaseId id slug title content status isRestricted hasPassword language { code }
  translations { databaseId slug status isRestricted hasPassword language { code } }`;

// Resolve by an existing source page and its real translation relation, never a guessed ES ID.
export async function localizedPage(kind, locale, sourceId) {
  graphqlLanguage(locale);
  if (!Object.hasOwn(fields, kind) || !Number.isSafeInteger(sourceId) || sourceId <= 0) {
    throw new TypeError('Invalid localized page source');
  }
  const query = `query Localized${kind === 'home' ? 'Home' : 'About'}($id: ID!) {
    ${kind === 'home' ? 'viewer { databaseId }' : ''}
    page(id: $id, idType: DATABASE_ID) { ${common} ${fields[kind]} }
  }`;
  const data = await request(endpoint, query, { id: String(sourceId) }, { verifyServiceIdentity: kind === 'home' });
  const source = data?.page;
  if (source === null) return null;
  if (!isPublicContent(source) || !contentLocale(source)) throw cmsFailure('CMS_PUBLIC_CONTRACT', 'LocalizedPage');
  let selected = source;
  if (contentLocale(source) !== locale) {
    const target = publicTranslationLinks(source).find(item => item.locale === locale);
    if (!target) return null;
    // Ask for ACF only after the relationship metadata confirms a public target.
    // Resolving ACF on a private translation can produce GraphQL execution errors.
    const translated = await request(endpoint, `query LocalizedPageTranslation($id: ID!) {
      page(id: $id, idType: DATABASE_ID) { ${common} ${fields[kind]} }
    }`, { id: String(target.databaseId) });
    selected = translated?.page;
    if (selected === null) return null;
  }
  if (!isPublicContent(selected) || contentLocale(selected) !== locale) {
    throw cmsFailure('CMS_LOCALE_CONTRACT', 'LocalizedPage');
  }
  const group = kind === 'home' ? selected.homePageFields : selected.aboutPage;
  if (!group) throw cmsFailure('CMS_PAGE_FIELDS_MISSING', 'LocalizedPage');
  const result = { status: 'publish', isRestricted: false, hasPassword: false,
    id: text(selected.id), slug: text(selected.slug), title: text(selected.title), content: text(selected.content),
    ...publicLanguageMetadata(selected) };
  if (kind === 'home') {
    result.homePageFields = { ...pick(group, homeKeys),
      photo_field: group.photo_field?.node ? { node: pick(group.photo_field.node, ['link', 'sourceUrl']) } : null,
      skills: group.skills ? pick(group.skills, skillKeys) : null };
  } else {
    result.aboutPage = pick(group, aboutKeys);
    result.featuredImage = selected.featuredImage?.node ? { node: pick(selected.featuredImage.node, ['sourceUrl']) } : null;
  }
  return result;
}
