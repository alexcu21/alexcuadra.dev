import { request } from './cms-request.js';
import { isPublicContent } from './content-visibility.js';
import { contentLocale } from './content-language.js';

// Explicit CMS ID; never guess a slug/translation or expose a draft to the site.
export async function getPrivacyPage(sourceId, locale = 'en') {
  if (!['en', 'es'].includes(locale)) return null;
  if (typeof sourceId !== 'string' || !/^[1-9]\d{0,9}$/.test(sourceId)) return null;
  const data = await request('https://content.alexcuadra.dev/graphql', `query PortfolioPrivacy($id: ID!) {
    page(id: $id, idType: DATABASE_ID) {
      databaseId title content status isRestricted hasPassword language { code }
    }
  }`, { id: sourceId });
  const page = data?.page;
  if (!isPublicContent(page) || contentLocale(page) !== locale || page.databaseId !== Number(sourceId)
    || typeof page.title !== 'string' || !page.title.trim()
    || typeof page.content !== 'string' || !page.content.trim()) return null;
  return { title: page.title, content: page.content };
}
