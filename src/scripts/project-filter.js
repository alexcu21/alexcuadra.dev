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

export function projectFilterState(params, categories, technologies) {
  const single = key => params.getAll(key).length === 1 ? params.get(key) : '';
  const category = single('category');
  const technology = single('technology');
  return {query:single('q').slice(0,200),
    category:categories.includes(category) ? category : '',
    technology:technologies.includes(technology) ? technology : ''};
}

export function projectFilterUrl(current, state) {
  const url = new URL(current);
  for (const [key,value] of [['q',state.query],['category',state.category],['technology',state.technology]]) {
    if (value) url.searchParams.set(key,value);
    else url.searchParams.delete(key);
  }
  return url;
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
      const link = entry.element.querySelector('a');
      if (link) link.href = projectFilterUrl(link.href,{query:search.value,category:category.value,technology:technology.value}).href;
      if (visible) count++;
    }
    const es = root.dataset.locale === 'es';
    status.textContent = es ? `${count} de ${entries.length} proyectos` : `${count} of ${entries.length} projects`;
    empty.hidden = count !== 0;
    clear.disabled = !search.value && !category.value && !technology.value;
  };
  const state = () => ({query:search.value,category:category.value,technology:technology.value});
  const save = mode => {
    const url = projectFilterUrl(location.href,state());
    if (url.href === location.href) return;
    try { history[mode](history.state,'',url.href); } catch { /* Filtering also works when history is unavailable. */ }
  };
  const restore = () => {
    const selected = projectFilterState(new URL(location.href).searchParams,
      [...category.options].map(option=>option.value),[...technology.options].map(option=>option.value));
    search.value=selected.query;category.value=selected.category;technology.value=selected.technology;
    update();
  };
  search.addEventListener('input', () => { update();save('replaceState'); });
  category.addEventListener('change', () => { update();save('pushState'); });
  technology.addEventListener('change', () => { update();save('pushState'); });
  controls.addEventListener('submit', event => event.preventDefault());
  clear.addEventListener('click', () => {
    search.value = category.value = technology.value = '';
    update();
    save('pushState');
    search.focus();
  });
  controls.dataset.mounted = 'true';
  restore();
  save('replaceState');
  addEventListener('popstate',restore);
  addEventListener('pageshow',restore);
  controls.hidden = false;
}
