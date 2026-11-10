import { request, cmsFailure } from './cms-request.js';
import { graphqlLanguage } from './content-language.js';
import { publicAvailabilityOptions } from './cms-public-availability.js';
import { hasBlogPosts } from './blog-content.js';
import { hasLabProjects } from './lab-content.js';

// Reuse route-proven visibility; only combine the checks when both are needed.
export async function navigationAvailability(locale, { blogAvailable, labAvailable } = {}) {
  const language = graphqlLanguage(locale);
  for (const value of [blogAvailable, labAvailable]) {
    if (value !== undefined && typeof value !== 'boolean') throw new TypeError('Invalid navigation availability');
  }
  if (blogAvailable !== undefined && labAvailable !== undefined) return { blogAvailable, labAvailable };
  if (blogAvailable !== undefined) return { blogAvailable, labAvailable: await hasLabProjects(locale) };
  if (labAvailable !== undefined) return { blogAvailable: await hasBlogPosts(locale), labAvailable };
  const blog = publicAvailabilityOptions('posts', locale), lab = publicAvailabilityOptions('labProjects', locale);
  const project = data => {
    const posts = blog.servicePublicProjection(data), projects = lab.servicePublicProjection(data);
    return posts && projects ? { ...posts, ...projects } : undefined;
  };
  const data = await request('https://content.alexcuadra.dev/graphql', `query NavigationAvailability {
    posts(first: 1, where: {language: ${language}, status: PUBLISH, hasPassword: false}) {
      nodes { status isRestricted hasPassword language { code } }
    }
    labProjects(first: 1, where: {language: ${language}, status: PUBLISH, hasPassword: false}) {
      nodes { status isRestricted hasPassword language { code } }
    }
  }`, {}, { publicCache: true, servicePublicProjection: project,
    validateServiceData: data => Boolean(project(data)), validatePublicData: data => Boolean(project(data)) });
  const projected = project(data);
  if (!projected) throw cmsFailure('CMS_PUBLIC_CONTRACT', 'NavigationAvailability');
  return { blogAvailable: projected.posts.nodes.length > 0, labAvailable: projected.labProjects.nodes.length > 0 };
}
