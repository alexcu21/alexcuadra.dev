import sanitizeHtml from 'sanitize-html';
import { parseDocument } from 'htmlparser2';

const classes = ['wp-block-columns', 'wp-block-column', 'wp-block-group', 'wp-block-image',
  'wp-block-gallery', 'wp-block-quote', 'wp-block-pullquote', 'wp-block-table', 'wp-block-code',
  'wp-block-preformatted', 'wp-block-list', 'wp-block-separator', 'wp-block-heading',
  'wp-element-caption', 'is-layout-flex', 'is-not-stacked-on-mobile',
  'alignleft', 'alignright', 'aligncenter', 'alignwide', 'alignfull',
  'has-text-align-left', 'has-text-align-center', 'has-text-align-right',
  'has-small-font-size', 'has-medium-font-size', 'has-large-font-size', 'has-x-large-font-size',
  'is-style-stripes'];

// Treat rendered CMS HTML as untrusted even when the GraphQL transport is authenticated.
export function sanitizeWordPressHtml(value) {
  return sanitizeContent(value, false);
}

function sanitizeContent(value, headingIds) {
  if (value == null) return '';
  if (typeof value !== 'string') throw new TypeError('CMS HTML must be a string');
  return sanitizeHtml(value, {
    allowedTags: ['p', 'br', 'div', 'span', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'b', 'em', 'i',
      's', 'del', 'ins', 'u', 'sub', 'sup', 'mark', 'small', 'a', 'ul', 'ol', 'li', 'dl', 'dt', 'dd',
      'blockquote', 'cite', 'q', 'figure', 'figcaption', 'img', 'pre', 'code', 'kbd', 'samp', 'hr',
      'table', 'caption', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td'],
    allowedAttributes: {
      '*': ['class', 'lang', 'dir', 'style'],
      a: ['href', 'title', 'target', 'rel'],
      img: ['src', 'srcset', 'sizes', 'alt', 'width', 'height', 'loading', 'decoding'],
      ol: ['start', 'reversed'], li: ['value'],
      th: ['colspan', 'rowspan', 'scope'], td: ['colspan', 'rowspan'],
      // Read author anchors only inside the outline compiler; never render them verbatim.
      ...(headingIds ? Object.fromEntries(['h2', 'h3', 'h4', 'h5', 'h6'].map(tag => [tag, ['id']])) : {}),
    },
    allowedClasses: { '*': classes, code: ['language-*'] },
    allowedStyles: { '*': { 'text-align': [/^(?:left|right|center|justify)$/] },
      div: { 'flex-basis': [/^(?:100|[0-9]{1,2}(?:\.[0-9]{1,3})?)%$/] } },
    allowedSchemes: ['https', 'http', 'mailto', 'tel'],
    allowedSchemesByTag: { img: ['https'] },
    allowProtocolRelative: false,
    nonTextTags: ['script', 'style', 'textarea', 'option', 'noscript', 'iframe', 'object', 'embed', 'svg', 'math', 'template'],
    nestingLimit: 40,
    transformTags: {
      // The page hero supplies the document H1; editorial headings start at H2.
      h1: 'h2',
      a: (tagName, attribs) => {
        const rel = (attribs.rel || '').split(/\s+/).filter(token => ['nofollow', 'ugc', 'sponsored'].includes(token));
        if (attribs.target === '_blank') rel.push('noopener', 'noreferrer');
        else delete attribs.target;
        if (rel.length) attribs.rel = [...new Set(rel)].join(' ');
        else delete attribs.rel;
        return { tagName, attribs };
      },
      img: (tagName, attribs) => {
        for (const key of ['width', 'height']) if (!/^[1-9]\d{0,4}$/.test(attribs[key] || '')) delete attribs[key];
        return { tagName, attribs: { ...attribs, loading: 'lazy', decoding: 'async' } };
      },
    },
  });
}

const escapeHtml = value => value.replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
const slug = value => [...value.normalize('NFKD').replace(/\p{M}/gu, '').toLowerCase()
  .replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-|-$/g, '')].slice(0, 80).join('').replace(/-$/g, '') || 'section';
const text = node => node.type === 'text' ? node.data : (node.children || []).map(text).join('');
function walk(node, visit) { visit(node); for (const child of node.children || []) walk(child, visit); }
function serialize(node) {
  if (node.type === 'text') return escapeHtml(node.data);
  if (node.type === 'root') return node.children.map(serialize).join('');
  if (node.type !== 'tag') return '';
  const attributes = Object.entries(node.attribs).map(([key, value]) => ` ${key}="${escapeHtml(value)}"`).join('');
  const open = `<${node.name}${attributes}>`;
  return ['br', 'hr', 'img'].includes(node.name) ? open : `${open}${node.children.map(serialize).join('')}</${node.name}>`;
}

// Compile one editorial document, including optional ACF sections, after sanitization.
// IDs are generated in a reserved namespace; only heading fragments are remapped.
export function prepareWordPressDocument(parts, scope = 'body') {
  if (!/^[a-z][a-z0-9-]{0,47}$/.test(scope)) throw new TypeError('Invalid editorial scope');
  if (!Array.isArray(parts)) throw new TypeError('Editorial sections must be an array');
  const used = new Set();
  const keys = new Set();
  const headings = [];
  const explicit = new Map();
  const implicit = new Map();
  const allocate = base => {
    let id = base;
    for (let suffix = 2; used.has(id); suffix++) id = `${base}-${suffix}`;
    used.add(id);
    return id;
  };
  const sections = parts.map(part => {
    if (!part || !/^[a-z][a-z0-9-]{0,47}$/.test(part.key) || keys.has(part.key)) throw new TypeError('Invalid editorial section key');
    keys.add(part.key);
    for (const field of ['title', 'text']) if (part[field] != null && typeof part[field] !== 'string') throw new TypeError('Invalid editorial text');
    if (part.text != null && part.html != null) throw new TypeError('Ambiguous editorial section');
    const title = part.title?.trim() || '';
    const prefix = `wp-content-${scope}-${part.key}`;
    const id = title ? allocate(prefix) : undefined;
    if (title) headings.push({ id, level: 2, label: title });
    const tree = parseDocument(sanitizeContent(part.html, true));
    walk(tree, node => {
      if (node.type !== 'tag' || !/^h[2-6]$/.test(node.name)) return;
      const label = text(node).replace(/\s+/g, ' ').trim();
      const sourceId = node.attribs.id;
      node.name = `h${Math.min(Number(node.name[1]) + (title ? 1 : 0), 6)}`;
      node.attribs.id = allocate(`${prefix}-${slug(sourceId || label)}`);
      node.attribs.tabindex = '-1';
      if (sourceId && !explicit.has(sourceId)) explicit.set(sourceId, node.attribs.id);
      if (label && !implicit.has(slug(label))) implicit.set(slug(label), node.attribs.id);
      if (label) headings.push({ id: node.attribs.id, level: Number(node.name[1]), label });
    });
    return { key: part.key, id, title, text: part.text, tree };
  });
  for (const section of sections) {
    walk(section.tree, node => {
      if (node.type !== 'tag' || node.name !== 'a' || !node.attribs.href?.startsWith('#')) return;
      let fragment;
      try { fragment = decodeURIComponent(node.attribs.href.slice(1)); } catch { return; }
      const target = explicit.get(fragment) || implicit.get(fragment);
      if (target) node.attribs.href = `#${encodeURIComponent(target)}`;
    });
  }
  const outline = [];
  const stack = [];
  for (const heading of headings) {
    const item = { ...heading, children: [] };
    while (stack.length && stack.at(-1).level >= item.level) stack.pop();
    (stack.length ? stack.at(-1).children : outline).push(item);
    stack.push(item);
  }
  return { headings, outline, sections: sections.map(({ tree, ...section }) => ({ ...section, html: serialize(tree) })) };
}
