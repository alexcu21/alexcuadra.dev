import { normalizedTitle } from './reference-filter.js';

export function projectFilterOptions(projects, field, locale) {
  return [...new Set(projects.flatMap(project => (project[field]?.nodes || []).map(term => term.name)))]
    .filter(name => typeof name === 'string' && name.trim())
    .sort((a, b) => a.localeCompare(b, locale));
}

export function matchesProject(project, query, category, technology) {
  return normalizedTitle(typeof project.title === 'string' ? project.title : '').includes(normalizedTitle(query))
    && (!category || project.categories.includes(category))
    && (!technology || project.technologies.includes(technology));
}

export function mountProjectFilter(root) {
  const controls = root.querySelector('[data-project-controls]');
  if (!controls || controls.dataset.mounted) return;
  const search = controls.querySelector('input');
  const category = controls.querySelector('[data-project-category]');
  const technology = controls.querySelector('[data-project-technology]');
  const clear = controls.querySelector('button');
  const status = root.querySelector('[data-project-status]');
  const empty = root.querySelector('[data-project-empty]');
  const entries = [...root.querySelectorAll('[data-project-entry]')].map(element => ({element,
    title: element.dataset.title, categories: JSON.parse(element.dataset.categories),
    technologies: JSON.parse(element.dataset.technologies)}));
  const update = () => {
    let count = 0;
    for (const entry of entries) {
      const visible = matchesProject(entry, search.value, category.value, technology.value);
      entry.element.hidden = !visible;
      if (visible) count++;
    }
    const es = root.dataset.locale === 'es';
    status.textContent = es ? `${count} de ${entries.length} proyectos` : `${count} of ${entries.length} projects`;
    empty.hidden = count !== 0;
    clear.disabled = !search.value && !category.value && !technology.value;
  };
  search.addEventListener('input', update);
  category.addEventListener('change', update);
  technology.addEventListener('change', update);
  controls.addEventListener('submit', event => event.preventDefault());
  clear.addEventListener('click', () => {
    search.value = category.value = technology.value = '';
    update();
    search.focus();
  });
  controls.dataset.mounted = 'true';
  update();
  controls.hidden = false;
}
