export function normalizedTitle(value) {
  return value.normalize('NFD').replace(/\p{M}/gu, '').toLocaleLowerCase('en').trim();
}

export function matchesReference(title, channels, query, channel) {
  return (!channel || channels.includes(channel))
    && normalizedTitle(title).includes(normalizedTitle(query));
}

export function referenceFilterState(params, channels) {
  const single = key => params.getAll(key).length === 1 ? params.get(key) : '';
  const channel = single('channel');
  return {query:single('q').slice(0,200),channel:channels.includes(channel) ? channel : ''};
}

export function referenceFilterUrl(current, state) {
  const url = new URL(current);
  for (const [key,value] of [['q',state.query],['channel',state.channel]]) {
    if (value) url.searchParams.set(key,value);
    else url.searchParams.delete(key);
  }
  return url;
}

export function mountReferenceFilter(root) {
  const controls = root.querySelector('[data-reference-controls]');
  if (!controls || controls.dataset.mounted) return;
  const search = root.querySelector('input[type="search"]');
  const channel = root.querySelector('select');
  const clear = root.querySelector('[data-reference-clear]');
  const status = root.querySelector('[data-reference-status]');
  const empty = root.querySelector('[data-reference-empty]');
  const entries = [...root.querySelectorAll('[data-reference-entry]')]
    .map(element => ({ element, title: element.dataset.title,
      channels: JSON.parse(element.dataset.channels) }));
  const update = () => {
    let count = 0;
    for (const entry of entries) {
      const visible = matchesReference(entry.title, entry.channels, search.value, channel.value);
      entry.element.hidden = !visible;
      const link = entry.element.querySelector('a');
      if (link) link.href = referenceFilterUrl(link.href,{query:search.value,channel:channel.value}).href;
      if (visible) count++;
    }
    status.textContent = root.dataset.locale==='es'?`${count} de ${entries.length} ${entries.length===1?'referencia':'referencias'}`:`${count} of ${entries.length} ${entries.length === 1 ? 'reference' : 'references'}`;
    empty.hidden = count !== 0;
    clear.disabled = !search.value && !channel.value;
  };
  const save = mode => {
    const url = referenceFilterUrl(location.href,{query:search.value,channel:channel.value});
    if ((search.value || channel.value) && !url.hash) url.hash='reference-directory-title';
    if (url.href === location.href) return;
    try { history[mode](history.state,'',url.href); } catch { /* Search remains usable without history access. */ }
  };
  const restore = () => {
    const state=referenceFilterState(new URL(location.href).searchParams,[...channel.options].map(option=>option.value));
    search.value=state.query;channel.value=state.channel;update();
  };
  search.addEventListener('input', () => {update();save('replaceState');});
  channel.addEventListener('change', () => {update();save('pushState');});
  controls.addEventListener('submit', event => event.preventDefault());
  clear.addEventListener('click', () => {
    search.value = '';
    channel.value = '';
    update();
    save('pushState');
    search.focus();
  });
  controls.dataset.mounted='true';
  restore();
  save('replaceState');
  addEventListener('popstate',restore);
  addEventListener('pageshow',restore);
  controls.hidden = false;
}
