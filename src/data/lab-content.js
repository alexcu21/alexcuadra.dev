import { request, cmsFailure } from './cms-request.js';
import { collectCmsPages } from './cms-pagination.js';
import { contentLocale, graphqlLanguage, publicLocalizedCollection, publicTranslationLinks } from './content-language.js';
import { isPublicContent } from './content-visibility.js';
import { contentPath } from './site-routes.js';
import { publicAvailabilityOptions } from './cms-public-availability.js';

const endpoint = 'https://content.alexcuadra.dev/graphql';
const visibility = 'status isRestricted hasPassword';
const fields = `databaseId title slug excerpt ${visibility} language { code }
  featuredImage { node { sourceUrl } }
  translations { databaseId slug ${visibility} language { code } }`;
const overview = 'status purpose featured displayOrder';
const detail = 'learningGoals technicalApproach qualityStrategy devopsNotes lastReviewedAt repositoryUrl demoUrl';
const text = value => typeof value === 'string' ? value : null;
const states = ['idea', 'in_progress', 'beta', 'released', 'archived'];

export function labStatus(value, locale) {
  graphqlLanguage(locale);
  // WPGraphQL for ACF returns a list even for this single-choice select.
  const state = Array.isArray(value) && value.length === 1 ? value[0] : value;
  if (!states.includes(state)) return null;
  const labels = locale === 'es' ? ['Idea', 'En desarrollo', 'Beta', 'Publicado', 'Archivado']
    : ['Idea', 'In progress', 'Beta', 'Released', 'Archived'];
  return { value: state, label: labels[states.indexOf(state)] };
}

export function labLink(value) {
  if (typeof value !== 'string' || !value.trim()) return null;
  try {
    const url = new URL(value);
    return ['https:', 'http:'].includes(url.protocol) && !url.username && !url.password ? url.href : null;
  } catch { return null; }
}

function validSlug(slug, locale) {
  try { contentPath('lab', locale, slug); return true; } catch { return false; }
}

function project(node, locale) {
  if (!isPublicContent(node) || contentLocale(node) !== locale || !text(node.title)?.trim() || !validSlug(node.slug, locale)) return null;
  const editorial = node.labProjectFields || {};
  return { title: node.title, slug: node.slug, excerpt: text(node.excerpt), content: text(node.content),
    image: text(node.featuredImage?.node?.sourceUrl), translations: publicTranslationLinks(node),
    state: labStatus(editorial.status, locale), purpose: text(editorial.purpose),
    learningGoals: text(editorial.learningGoals), technicalApproach: text(editorial.technicalApproach),
    qualityStrategy: text(editorial.qualityStrategy), devopsNotes: text(editorial.devopsNotes),
    lastReviewedAt: text(editorial.lastReviewedAt), repositoryUrl: labLink(editorial.repositoryUrl),
    demoUrl: labLink(editorial.demoUrl), featured: editorial.featured === true,
    displayOrder: typeof editorial.displayOrder === 'number' && Number.isFinite(editorial.displayOrder) ? editorial.displayOrder : 0 };
}

export async function hasLabProjects(locale) {
  const language = graphqlLanguage(locale);
  const data = await request(endpoint, `query LabAvailability {
    labProjects(first: 1, where: {language: ${language}, status: PUBLISH, hasPassword: false}) {
      nodes { ${visibility} language { code } }
    }
  }`, {}, publicAvailabilityOptions('labProjects', locale));
  if (!publicLocalizedCollection(data?.labProjects?.nodes, locale)) throw cmsFailure('CMS_LAB_CONTRACT', 'LabAvailability');
  return data.labProjects.nodes.length > 0;
}

// Home needs a small preview, not a paginated archive or full project bodies.
function homeLabProjection(data, locale) {
  const nodes = data?.labProjects?.nodes;
  if (!publicLocalizedCollection(nodes, locale) || nodes.length > 3) return undefined;
  const items = nodes.map(node => project(node, locale));
  if (items.some(item => !item)) return undefined;
  return { labProjects: { nodes: items.map(item => ({
    status: 'publish', isRestricted: false, hasPassword: false, language: { code: graphqlLanguage(locale) },
    title: item.title, slug: item.slug, excerpt: item.excerpt,
    labProjectFields: { purpose: item.purpose, status: item.state ? [item.state.value] : null },
  })) } };
}

export async function getHomeLabProjects(locale) {
  const language = graphqlLanguage(locale);
  const data = await request(endpoint, `query HomeLabProjects {
    labProjects(first: 3, where: {language: ${language}, status: PUBLISH, hasPassword: false,
      orderby: {field: DATE, order: DESC}}) {
      nodes { title slug excerpt ${visibility} language { code } labProjectFields { status purpose } }
    }
  }`, {}, { publicCache: true, servicePublicProjection: data => homeLabProjection(data, locale),
    validatePublicData: data => Boolean(homeLabProjection(data, locale)),
    validateServiceData: data => Boolean(homeLabProjection(data, locale)) });
  const nodes = data?.labProjects?.nodes;
  if (!publicLocalizedCollection(nodes, locale) || nodes.length > 3) throw cmsFailure('CMS_LAB_CONTRACT', 'HomeLabProjects');
  return nodes.map(node => {
    const item = project(node, locale);
    if (!item) throw cmsFailure('CMS_LAB_CONTRACT', 'HomeLabProjects');
    return { title: item.title, slug: item.slug, excerpt: item.excerpt, purpose: item.purpose, state: item.state };
  });
}

export async function getLabProjects(locale) {
  const language = graphqlLanguage(locale);
  const projects = await collectCmsPages(async after => {
    const data = await request(endpoint, `query LabProjects($after: String) {
      labProjects(first: 100, after: $after, where: {language: ${language}, status: PUBLISH, hasPassword: false,
        orderby: {field: DATE, order: DESC}}) {
        nodes { ${fields} labProjectFields { ${overview} } } pageInfo { hasNextPage endCursor }
      }
    }`, { after }, { validateServiceData: data => publicLocalizedCollection(data?.labProjects?.nodes, locale) });
    if (!publicLocalizedCollection(data?.labProjects?.nodes, locale)) throw cmsFailure('CMS_LAB_CONTRACT', 'LabProjects');
    const nodes = data.labProjects.nodes.map(node => project(node, locale));
    if (nodes.some(node => !node)) throw cmsFailure('CMS_LAB_CONTRACT', 'LabProjects');
    return { nodes, pageInfo: data.labProjects.pageInfo };
  }, 'LabProjects');
  return projects.sort((a, b) => a.displayOrder - b.displayOrder);
}

export async function getLabProject(slug, locale) {
  graphqlLanguage(locale);
  if (!validSlug(slug, locale)) return null;
  const data = await request(endpoint, `query LabProject($slug: ID!) {
    labProject(id: $slug, idType: SLUG) { ${fields} content labProjectFields { ${overview} ${detail} } }
  }`, { slug }, { validateServiceData: data => data?.labProject === null || isPublicContent(data?.labProject) });
  if (data?.labProject === null) return null;
  if (!isPublicContent(data?.labProject) || !contentLocale(data.labProject)) throw cmsFailure('CMS_LAB_CONTRACT', 'LabProject');
  if (contentLocale(data.labProject) !== locale) return null;
  const result = project(data.labProject, locale);
  if (!result) throw cmsFailure('CMS_LAB_CONTRACT', 'LabProject');
  return result;
}
