import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { createHash } from 'node:crypto';

const source = await readFile(new URL('../docs/CONTACT_PRIVACY_DRAFT.md', import.meta.url), 'utf8');
const output = new URL('../docs/wordpress/privacy/', import.meta.url);
await mkdir(output, { recursive: true });
const escape = text => text.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
function inline(text) {
  return escape(text).replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_match, label, href) => {
    if (!/^(https:\/\/|mailto:)/.test(href)) throw Error('Unsupported privacy link');
    return `<a href="${href}">${label}</a>`;
  });
}
const manifest = { source: 'docs/CONTACT_PRIVACY_DRAFT.md', status: 'draft', pages: [] };
for (const [locale, start, end] of [
  ['en', '## English — proposed WordPress body', '## Español — borrador para una traducción real en WordPress'],
  ['es', '## Español — borrador para una traducción real en WordPress', '## Integración prevista'],
]) {
  const section = source.split(start)[1]?.split(end)[0]?.trim();
  if (!section) throw Error('Privacy section missing');
  const blocks = section.split(/\n\s*\n/);
  const title = blocks.shift()?.replace(/^### /, '');
  const pending = [...section.matchAll(/\[([^\]]+)\](?!\()/g)].map(match => match[1]);
  const content = blocks.map(block => {
    if (block.startsWith('### ')) return `<!-- wp:heading -->\n<h2 class="wp-block-heading">${inline(block.slice(4))}</h2>\n<!-- /wp:heading -->`;
    if (block.includes('\n') || block.startsWith('#')) throw Error('Unsupported draft block; review export');
    return `<!-- wp:paragraph -->\n<p>${inline(block)}</p>\n<!-- /wp:paragraph -->`;
  }).join('\n\n') + '\n';
  await writeFile(new URL(`${locale}.gutenberg.html`, output), content);
  const notice = locale === 'en' ? 'DRAFT — not published. Resolve bracketed notes before publishing.' : 'BORRADOR — sin publicar. Resolver notas entre corchetes antes de publicar.';
  await writeFile(new URL(`${locale}.preview.html`, output), `<!doctype html>
<html lang="${locale}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>${escape(title)} — draft</title>
<style>body{font:18px/1.65 system-ui,sans-serif;max-width:48rem;margin:3rem auto;padding:0 1rem;color:#172033;background:#fff}h1,h2{line-height:1.2}h2{margin-top:2rem}a{color:#123f9d}aside{border:2px solid #8a5200;padding:1rem;background:#fff5dc}p{overflow-wrap:anywhere}</style></head>
<body><aside>${notice}</aside><main><h1>${escape(title)}</h1>${content}</main></body></html>\n`);
  manifest.pages.push({ locale, title, file: `${locale}.gutenberg.html`,
    sha256: createHash('sha256').update(content).digest('hex'), unresolvedNotes: pending });
}
await writeFile(new URL('manifest.json', output), JSON.stringify(manifest, null, 2) + '\n');
console.log('Privacy draft exported for EN/ES. No CMS writes or publication.');
