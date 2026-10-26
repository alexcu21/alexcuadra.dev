import { cmsFailure } from './cms-request.js';
import { localizedPage } from './localized-pages.js';
import { localizedCollection } from './localized-content.js';
import { getPrivacyPage } from './privacy-page.js';
import { publicCollection } from './content-visibility.js';
import { canonicalUrl } from './page-seo.js';
import { contentPath, categoryPath } from './site-routes.js';
import { getBlogPosts } from './blog-content.js';
import { getLabProjects } from './lab-content.js';
import { referenceInventory } from './reference-inventory.js';

export { referenceInventory as sitemapReferences } from './reference-inventory.js';

export function sitemapPaths({ pages, projects, references, blog = { en: [], es: [] }, lab = { en: [], es: [] } }) {
  if (!publicCollection(projects.en) || !publicCollection(projects.es) || !publicCollection(references)) {
    throw new TypeError('Sitemap requires public inventory');
  }
  const paths = new Set(['/work/', '/es/proyectos/', '/articles/', '/contact/', '/es/contacto/']);
  for (const [path, page] of Object.entries(pages)) if (page) paths.add(path);
  for (const locale of ['en', 'es']) {
    for (const project of projects[locale]) paths.add(contentPath('projects', locale, project.slug));
    if (!Array.isArray(blog[locale])) throw new TypeError('Sitemap requires complete blog inventory');
    if (blog[locale].length) paths.add(locale === 'es' ? '/es/blog/' : '/blog/');
    // getBlogPosts already projects only public, language-validated native posts.
    for (const post of blog[locale]) paths.add(contentPath('blog', locale, post.slug));
    if (!Array.isArray(lab[locale])) throw new TypeError('Sitemap requires complete LAB inventory');
    if (lab[locale].length) paths.add(locale === 'es' ? '/es/laboratorio/' : '/lab/');
    for (const project of lab[locale]) paths.add(contentPath('lab', locale, project.slug));
  }
  for (const article of references) {
    for (const category of article.categories.nodes) {
      paths.add(categoryPath('en', category.slug));
      paths.add(contentPath('articles', 'en', article.slug, category.slug));
    }
  }
  return [...paths].sort();
}

const escape = value => value.replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&apos;' })[char]);
export function sitemapXml(paths) {
  if (paths.length > 50000) throw new TypeError('Sitemap requires an index');
  const urls = [...new Set(paths)].map(path => `  <url><loc>${escape(canonicalUrl(path))}</loc></url>`);
  const xml = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls.join('\n')}\n</urlset>\n`;
  if (new TextEncoder().encode(xml).length > 50 * 1024 * 1024) throw new TypeError('Sitemap requires an index');
  return xml;
}

export async function loadSitemap(config) {
  // One sitemap must not launch every collection at once against a rate-limited CMS.
  // The request scope still deduplicates shared source pages; errors stop the inventory.
  const results = [];
  for (const load of [
    () => localizedPage('home', 'en', 19), () => localizedPage('home', 'es', 19),
    () => localizedPage('about', 'en', 68), () => localizedPage('about', 'es', 68),
    () => getPrivacyPage(config.CONTACT_PRIVACY_PAGE_ID), () => getPrivacyPage(config.CONTACT_PRIVACY_ES_PAGE_ID, 'es'),
    () => localizedCollection('projects', 'en'), () => localizedCollection('projects', 'es'), () => referenceInventory(),
    () => getBlogPosts('en'), () => getBlogPosts('es'), () => getLabProjects('en'), () => getLabProjects('es'),
  ]) results.push(await load());
  const [homeEn, homeEs, aboutEn, aboutEs, privacyEn, privacyEs, en, es, references, blogEn, blogEs, labEn, labEs] = results;
  if (!homeEn) throw cmsFailure('CMS_HOME_MISSING', 'Sitemap');
  try {
    return sitemapXml(sitemapPaths({ pages: { '/': homeEn, '/es/': homeEs, '/about/': aboutEn,
      '/es/sobre-mi/': aboutEs, '/privacy/': privacyEn, '/es/privacidad/': privacyEs },
    projects: { en, es }, references, blog: { en: blogEn, es: blogEs }, lab: { en: labEn, es: labEs } }));
  } catch { throw cmsFailure('CMS_SITEMAP_CONTRACT', 'Sitemap'); }
}
