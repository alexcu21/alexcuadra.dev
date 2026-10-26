import { request, cmsFailure } from './cms-request.js';
import { collectCmsPages } from './cms-pagination.js';
import { contentLocale, graphqlLanguage, publicLocalizedCollection, publicTranslationLinks } from './content-language.js';
import { isPublicContent } from './content-visibility.js';
import { sanitizeWordPressHtml } from './wordpress-html.js';
import { publicAvailabilityOptions } from './cms-public-availability.js';
import { publicLanguageMetadata } from './localized-content.js';

const endpoint = 'https://content.alexcuadra.dev/graphql';
const visibility = 'status isRestricted hasPassword';
const fields = `databaseId title slug excerpt dateGmt modifiedGmt ${visibility}
  language { code } featuredImage { node { sourceUrl } }
  translations { databaseId slug ${visibility} language { code } }`;
const text = value => typeof value === 'string' ? value : null;

// A small visibility query keeps navigation independent of the full archive size.
export async function hasBlogPosts(locale) {
  const language = graphqlLanguage(locale);
  const data = await request(endpoint, `query BlogAvailability {
    posts(first: 1, where: {language: ${language}, status: PUBLISH, hasPassword: false}) {
      nodes { ${visibility} language { code } }
    }
  }`, {}, publicAvailabilityOptions('posts', locale));
  if (!publicLocalizedCollection(data?.posts?.nodes, locale)) throw cmsFailure('CMS_BLOG_CONTRACT', 'BlogAvailability');
  return data.posts.nodes.length > 0;
}

function postProjection(node, locale) {
  if (!isPublicContent(node) || contentLocale(node) !== locale || !node.slug || !node.title) return undefined;
  return { title: text(node.title), slug: text(node.slug), excerpt: text(node.excerpt), content: text(node.content),
    dateGmt: text(node.dateGmt), modifiedGmt: text(node.modifiedGmt),
    featuredImage: node.featuredImage?.node ? { node: { sourceUrl: text(node.featuredImage.node.sourceUrl) } } : null,
    translations: publicTranslationLinks(node) };
}

// Cache the public GraphQL shape, not the authenticated response. Reapplying
// this projection on a cache hit must preserve visibility and translation checks.
function publicPost(node) {
  const locale = contentLocale(node);
  const projected = locale && postProjection(node, locale);
  if (!projected) return undefined;
  return { ...projected, status: 'publish', isRestricted: false, hasPassword: false,
    ...publicLanguageMetadata(node) };
}

function publicBlogArchive(data, locale) {
  const connection = data?.posts;
  if (!publicLocalizedCollection(connection?.nodes, locale)) return undefined;
  const info = connection.pageInfo;
  if (typeof info?.hasNextPage !== 'boolean'
    || (info.hasNextPage && (typeof info.endCursor !== 'string' || !info.endCursor))) return undefined;
  const nodes = connection.nodes.map(publicPost);
  if (nodes.some(node => !node)) return undefined;
  return { posts: { nodes, pageInfo: { hasNextPage: info.hasNextPage, endCursor: text(info.endCursor) } } };
}

function publicBlogDetail(data) {
  if (data?.post === null) return { post: null };
  const post = publicPost(data?.post);
  return post ? { post } : undefined;
}

function publicBlogCache(project) {
  return { publicCache: true, servicePublicProjection: project,
    validatePublicData: data => Boolean(project(data)), validateServiceData: data => Boolean(project(data)) };
}

// Uses only WordPress's native post type; never articles or projects.
export async function getBlogPosts(locale) {
  const language = graphqlLanguage(locale);
  return collectCmsPages(async after => {
    const data = await request(endpoint, `query BlogPosts($after: String) {
      posts(first: 100, after: $after, where: {language: ${language}, status: PUBLISH, hasPassword: false,
        orderby: {field: DATE, order: DESC}}) {
        nodes { ${fields} } pageInfo { hasNextPage endCursor }
      }
    }`, { after }, publicBlogCache(data => publicBlogArchive(data, locale)));
    if (!publicLocalizedCollection(data?.posts?.nodes, locale)) throw cmsFailure('CMS_BLOG_CONTRACT', 'BlogPosts');
    const nodes = data.posts.nodes.map(node => postProjection(node, locale));
    if (nodes.some(node => !node)) throw cmsFailure('CMS_BLOG_CONTRACT', 'BlogPosts');
    return { nodes, pageInfo: data.posts.pageInfo };
  }, 'BlogPosts');
}

export async function getBlogPost(slug, locale) {
  graphqlLanguage(locale);
  if (typeof slug !== 'string' || !slug || /[\s/\\?#\u0000-\u001f\u007f]/u.test(slug)) return null;
  const data = await request(endpoint, `query BlogPost($slug: ID!) {
    post(id: $slug, idType: SLUG) { ${fields} content }
  }`, { slug }, publicBlogCache(publicBlogDetail));
  if (data?.post === null) return null;
  if (!isPublicContent(data?.post)) throw cmsFailure('CMS_BLOG_CONTRACT', 'BlogPost');
  if (!contentLocale(data.post)) throw cmsFailure('CMS_BLOG_CONTRACT', 'BlogPost');
  if (contentLocale(data.post) !== locale) return null;
  const post = postProjection(data.post, locale);
  if (!post) throw cmsFailure('CMS_BLOG_CONTRACT', 'BlogPost');
  return post;
}

// Excerpts are HTML in WordPress. Metadata/card summaries need plain, normalized text.
export function blogSummary(html) {
  return sanitizeWordPressHtml(html).replace(/<[^>]*>/g, ' ').replace(/&nbsp;|&#160;/g, ' ')
    .replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#0*39;|&apos;/g, "'")
    .replace(/&lt;/g, '<').replace(/&gt;/g, '>')
    .replace(/&#(x[0-9a-f]+|[0-9]+);/gi, (_entity, digits) => {
      const point = digits[0].toLowerCase() === 'x' ? parseInt(digits.slice(1), 16) : Number(digits);
      return point > 0 && point <= 0x10ffff && !(point >= 0xd800 && point <= 0xdfff) ? String.fromCodePoint(point) : '';
    }).replace(/\s+/g, ' ').trim();
}

export function blogDate(value, locale) {
  if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z)?$/.test(value)) return null;
  const iso = value.endsWith('Z') ? value : value + 'Z';
  const date = new Date(iso);
  if (!Number.isFinite(date.getTime()) || date.toISOString().slice(0, 19) !== iso.slice(0, 19)) return null;
  return { iso, label: new Intl.DateTimeFormat(locale, { dateStyle: 'long', timeZone: 'UTC' }).format(date) };
}
