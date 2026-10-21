import { request } from './cms-request.js';
import { isPublicContent } from './content-visibility.js';
import { contentLocale, publicTranslationLinks, graphqlLanguage } from './content-language.js';

export const contactTextKeys = ['heading', 'introduction', 'name_label', 'email_label', 'message_label',
  'submit_label', 'sending_message', 'success_message', 'validation_message', 'challenge_message',
  'rate_limit_message', 'unavailable_message', 'unknown_delivery_message', 'privacy_text',
  'privacy_link_label', 'privacy_url', 'alternative_label'];

export function contactCopy(value) {
  if (!value || contactTextKeys.some(key => typeof value[key] !== 'string' || !value[key].trim()
    || value[key].length > (key.endsWith('_message') || ['introduction', 'privacy_text'].includes(key) ? 2000 : 200))) return null;
  let privacy;
  try { privacy = new URL(value.privacy_url); } catch { return null; }
  if (privacy.protocol !== 'https:' || privacy.username || privacy.password) return null;
  return Object.fromEntries(contactTextKeys.map(key => [key, value[key].trim()]));
}

// Query only public, real translations of Home; never display English copy on an ES form.
export async function getContactCopy(locale = 'en') {
  graphqlLanguage(locale);
  const query = `query ContactCopy($id: ID!) { page(id: $id, idType: DATABASE_ID) {
    databaseId slug status isRestricted hasPassword language { code }
    translations { databaseId slug status isRestricted hasPassword language { code } }
    contactForm { ${contactTextKeys.join(' ')} }
  } }`;
  const read = async id => (await request('https://content.alexcuadra.dev/graphql', query, { id: String(id) }))?.page;
  let page = await read(19);
  if (!isPublicContent(page)) return null;
  if (contentLocale(page) !== locale) {
    const translated = publicTranslationLinks(page).find(item => item.locale === locale);
    if (!translated) return null;
    page = await read(translated.databaseId);
  }
  return isPublicContent(page) && contentLocale(page) === locale ? contactCopy(page.contactForm) : null;
}
