import { scenario } from './scenario.mjs';
import caseDrafts from '../../../docs/wordpress/project-case-study-drafts.json';
import caseCurrent from '../../../docs/wordpress/project-case-study-current.json';
export const gql = (parts, ...values) => parts.reduce((result, part, i) => result + part + (values[i] ?? ''), '');
const visible = { status: 'publish', isRestricted: false, hasPassword: false };
const connection = (nodes, hasNextPage = false, endCursor = null) => ({ nodes, pageInfo: { hasNextPage, endCursor } });
const category = { id: 'category', databaseId: 1, slug: 'medium', name: 'Medium', count: 2 };
function upstreamFailure() {
  throw Object.assign(new Error('SYNTHETIC_PRIVATE_UPSTREAM_ERROR'), {
    response: { status: 429, headers: new Headers({ 'Retry-After': '120' }) },
  });
}
export async function request({ document, variables }) {
  const mode = scenario.getStore();
  if (mode === 'outage') upstreamFailure();
  if (document.includes('query LocalizedProjects')) {
    const code = document.includes('language: ES') ? 'ES' : 'EN';
    const spanish = code === 'ES';
    const casePreview = ['project-case-draft', 'project-case-current'].includes(mode);
    if (mode === 'projects-empty') return { projects: connection([]) };
    return { projects: connection(['case-study', 'minimal'].map(slug => ({
      ...visible, language: { code }, slug: spanish && slug === 'case-study' ? 'caso-de-estudio' : slug,
      title: slug === 'minimal' ? 'Minimal project' : casePreview ? 'christianitytoday.com'
        : spanish ? 'Proyecto de demostración' : 'Demonstration project',
      modified: slug === 'minimal' ? '2026-10-01T00:00:00' : '2026-10-24T00:00:00',
      content: slug === 'minimal' ? '' : mode === 'project-case-current'
        ? caseCurrent.entries.find(entry => entry.language.code === code).content
        : mode === 'project-case-draft'
        ? caseDrafts.entries.find(entry => entry.locale === (spanish ? 'es' : 'en')).contentHtml
        : '<p>Synthetic project scope.</p><script>PRIVATE_SCRIPT</script>',
      projects: slug === 'minimal' ? null : { projectTagline: casePreview
        ? caseDrafts.entries.find(entry => entry.locale === (spanish ? 'es' : 'en')).tagline
        : spanish ? 'Diseño y desarrollo de una experiencia web' : 'Design and development of a web experience',
        websiteurl: casePreview ? 'https://www.christianitytoday.com/' : 'https://example.test/', githuburl: null },
      featuredImage: slug === 'minimal' ? null : { node: { sourceUrl: '/project-preview.svg' } },
      tags: connection(slug === 'minimal' ? [] : (mode === 'project-many-terms'
        ? ['WordPress', 'React', 'CSS', 'SCSS', 'ACF Pro', 'Svelte'] : ['WordPress', 'React'])
        .map(name => ({ name, language: { code } }))),
      categories: connection(slug === 'minimal' ? [] : [{ name: spanish ? 'Sitio WordPress' : 'WordPress Site', language: { code } }]),
      translations: slug === 'minimal' ? [] : [{ ...visible, databaseId: 900,
        slug: spanish ? 'case-study' : 'caso-de-estudio', language: { code: spanish ? 'EN' : 'ES' } }],
    }))) };
  }
  if (document.includes('query PageNavigation')) {
    if (mode === 'nav-outage') upstreamFailure();
    return { page: mode === 'nav-es' ? { ...visible, language: { code: 'EN' }, translations: [
      { ...visible, databaseId: 191, slug: 'sobre-mi', language: { code: 'ES' } },
    ] } : null };
  }
  if (document.includes('query SitemapReferences')) {
    if (mode === 'references-empty') return { articles: connection([]) };
    return { articles: connection([{ ...visible, slug: 'reference', title: mode === 'directory' ? 'Diseño & WordPress' : 'Synthetic reference',
      categories: connection(mode === 'directory' ? [category, {...category,slug:'other',name:'Other'}] : [category]) },
      { ...visible, slug: 'second', title: 'Second synthetic reference', categories: connection([category]) }]) };
  }
  if (document.includes('query LabAvailability')) {
    if (['home-lab-reuse', 'home-lab-empty-reuse'].includes(mode)) throw Error('Home must reuse its LAB preview for navigation');
    if (mode === 'lab-availability-outage') upstreamFailure();
    const code = document.includes('language: ES') ? 'ES' : 'EN';
    return { labProjects: { nodes: mode === 'lab-empty' ? [] : [{ ...visible, language: { code } }] } };
  }
  if (document.includes('query HomeLabProjects')) {
    if (['lab-empty', 'home-lab-empty-reuse'].includes(mode)) return { labProjects: { nodes: [] } };
    const es = document.includes('language: ES');
    return { labProjects: { nodes: [
      { ...visible, language: { code: es ? 'ES' : 'EN' }, title: es ? 'Laboratorio de prueba' : 'Personal lab fixture',
        slug: es ? 'laboratorio' : 'training-lab', excerpt: '<p>LAB summary</p>',
        labProjectFields: { status: ['in_progress'], purpose: es ? 'Propósito de aprendizaje de demostración.' : 'Synthetic learning purpose.' } },
    ] } };
  }
  if (document.includes('query LabProjects(')) {
    const code = document.includes('language: ES') ? 'ES' : 'EN';
    if (mode === 'lab-empty') return { labProjects: connection([]) };
    if (variables.after && mode === 'lab-late') upstreamFailure();
    const slug = code === 'ES' ? (variables.after ? 'second-lab-es' : 'laboratorio')
      : (variables.after ? 'second-lab' : 'training-lab');
    return { labProjects: connection([{ ...visible, language: { code }, slug,
      title: 'Personal lab fixture', excerpt: '<p>LAB summary</p>', labProjectFields: { status: ['in_progress'], purpose: 'Synthetic learning purpose' },
      translations: [] }], !variables.after, variables.after ? null : 'next-lab') };
  }
  if (document.includes('query LabProject(')) {
    if (variables.slug === 'missing') return { labProject: null };
    const code = ['laboratorio', 'second-lab-es'].includes(variables.slug) ? 'ES' : 'EN';
    return { labProject: { ...visible, ...(mode === 'lab-private' ? { status: 'draft' } : {}), language: { code },
      slug: variables.slug, title: 'Personal lab fixture', content: '<p>Personal project body</p><script>PRIVATE_SCRIPT</script>',
      labProjectFields: variables.slug === 'minimal' ? null : { status: ['in_progress'], purpose: 'Synthetic learning purpose',
        learningGoals: 'A real learning goal', technicalApproach: mode === 'outline' ? '<h2 id="setup">Technical details</h2><h3>Checks</h3><a href="#setup">Jump</a>' : '<p>Technical details</p>', qualityStrategy: '<p>Quality details</p>',
        devopsNotes: '<p>Deployment details</p>', lastReviewedAt: '2026-10-19', repositoryUrl: 'javascript:PRIVATE_SCRIPT', demoUrl: null },
      translations: [{ ...visible, databaseId: 2, slug: code === 'EN' ? 'laboratorio' : 'training-lab', language: { code: code === 'EN' ? 'ES' : 'EN' } }] } };
  }
  if (document.includes('query BlogAvailability')) {
    if (mode === 'blog-availability-outage') upstreamFailure();
    const code = document.includes('language: ES') ? 'ES' : 'EN';
    return { posts: { nodes: mode === 'blog-empty' ? [] : [{ ...visible, language: { code } }] } };
  }
  if (document.includes('query BlogPosts')) {
    const code = document.includes('language: ES') ? 'ES' : 'EN';
    if (mode === 'blog-empty') return { posts: connection([]) };
    if (variables.after && mode === 'blog-late') upstreamFailure();
    return { posts: connection([{ ...visible, language: { code }, slug: variables.after ? 'second' : 'native-post',
      title: variables.after ? 'Second native post' : 'Native blog fixture', content: '<p>Native post body</p>', excerpt: '<p>Native summary</p>',
      dateGmt: '2026-10-19T00:00:00', translations: [] }], !variables.after, variables.after ? null : 'next-post') };
  }
  if (document.includes('query BlogPost(')) {
    if (variables.slug === 'missing') return { post: null };
    const code = variables.slug === 'entrada' ? 'ES' : 'EN';
    return { post: { ...visible, ...(mode === 'blog-private' ? { status: 'private' } : {}), language: { code },
      slug: variables.slug, title: 'Native blog fixture', content: mode === 'outline'
        ? '<h2 id="main-content">Setup &amp; quality</h2><h3>Run &lt;check&gt;</h3><h2>Setup &amp; quality</h2><a href="#main-content">Jump</a><script>PRIVATE_SCRIPT</script>'
        : '<p>Native post body</p><script>PRIVATE_SCRIPT</script>',
      dateGmt: '2026-10-19T00:00:00', translations: [{ ...visible, databaseId: 2,
        slug: code === 'EN' ? 'entrada' : 'native-post', language: { code: code === 'EN' ? 'ES' : 'EN' } }] } };
  }
  if (mode === 'nav-es' && /query Localized(?:About|PageTranslation)/.test(document)) {
    throw new Error('Navigation must not fetch translated page bodies');
  }
  if (document.includes('query LocalizedAbout')) return { page: null };
  if (document.includes('query getArticleCategories')) {
    if (variables.after && mode === 'late-outage') upstreamFailure();
    return { categories: variables.after ? connection([category, { ...category, slug: 'empty', name: 'Empty' }])
      : connection([{ ...category, slug: 'other', name: 'Other' }], true, 'next-category') };
  }
  if (document.includes('query getArticlesByCategory')) {
    if (variables.slug === 'empty') return { articles: connection([]) };
    if (variables.after && mode === 'late-articles') upstreamFailure();
    return { articles: variables.after ? connection([{ ...visible, slug: 'second', title: 'Second synthetic reference' }])
      : connection([{ ...visible, slug: 'reference', title: 'Synthetic reference' }], true, 'next-article') };
  }
  if (document.includes('query getArticleBySlug')) {
    if (variables.slug === 'missing') return { article: null };
    return { article: { ...visible, slug: variables.slug, title: 'Synthetic reference',
      categories: variables.slug === 'paginated' ? connection([{ ...category, slug: 'other' }], true, 'next-term') : connection([category]),
      content: '<p>Confirmed synthetic body <a href="https://example.test/source">External source</a></p><script>PRIVATE_SCRIPT</script>',
      ...(variables.slug === 'minimal' ? {} : { articlesFields: { quote: 'Synthetic quote' },
        featuredImage: { node: { sourceUrl: 'https://example.test/reference.png' } } }),
    } };
  }
  if (document.includes('query ArticleCategoryPage')) {
    if (mode === 'nested-outage') upstreamFailure();
    return { article: { ...visible, categories: connection([category]) } };
  }
  if (document.includes('query LocalizedHome')) {
    // This fails in the shared navigation after route content has already loaded.
    if (mode === 'layout-outage') upstreamFailure();
    return { viewer: { databaseId: 1 }, page: { ...visible, id: '19', slug: 'home', language: { code: 'EN' },
      translations: mode === 'nav-es' ? [{ ...visible, databaseId: 193, slug: 'inicio', language: { code: 'ES' } }] : [],
      homePageFields: { name_field: 'Synthetic author', twitterUrl: 'https://example.test/twitter', githubUrl: 'https://example.test/github',
        youtubeUrl: 'https://example.test/youtube', linkedinUrl: 'https://example.test/linkedin', email: 'fixture@example.test' },
    } };
  }
  throw new Error('Unexpected fixture GraphQL operation; network is never used');
}
