import { contentLocale } from './content-language.js';
import { isPublicContent } from './content-visibility.js';
import { contentPath } from './site-routes.js';

// WordPress returns ISO date strings; subtracting them produces NaN.
export function sortProjectsByModified(projects) {
  const timestamp = (value) => Date.parse(value) || 0;
  return [...projects].sort((a, b) => timestamp(b.modified) - timestamp(a.modified));
}

export function projectNavigation(projects, currentSlug, locale) {
  const ordered = sortProjectsByModified(projects).filter(project => isPublicContent(project)
    && contentLocale(project) === locale && typeof project.title === 'string' && project.title.trim())
    .flatMap(project => {
      try { return [{slug:project.slug, title:project.title, path:contentPath('projects',locale,project.slug)}]; }
      catch { return []; }
    });
  const unique = ordered.filter(project => ordered.filter(other => other.slug === project.slug).length === 1);
  const index = unique.findIndex(project => project.slug === currentSlug);
  const link = project => project ? {title:project.title,path:project.path} : null;
  return index < 0 ? {previous:null,next:null} : {
    previous:link(unique[index - 1]), next:link(unique[index + 1]),
  };
}
