export function normalizedTitle(value) {
  return value.normalize('NFD').replace(/\p{M}/gu, '').toLocaleLowerCase('en').trim();
}

export function matchesReference(title, channels, query, channel) {
  return (!channel || channels.includes(channel))
    && normalizedTitle(title).includes(normalizedTitle(query));
}

export function mountReferenceFilter(root) {
  const controls = root.querySelector('[data-reference-controls]');
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
      if (visible) count++;
    }
    status.textContent = `${count} of ${entries.length} ${entries.length === 1 ? 'reference' : 'references'}`;
    empty.hidden = count !== 0;
    clear.disabled = !search.value && !channel.value;
  };
  search.addEventListener('input', update);
  channel.addEventListener('change', update);
  controls.addEventListener('submit', event => event.preventDefault());
  clear.addEventListener('click', () => {
    search.value = '';
    channel.value = '';
    update();
    search.focus();
  });
  update();
  controls.hidden = false;
}
