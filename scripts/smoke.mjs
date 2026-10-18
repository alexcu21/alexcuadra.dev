import assert from 'node:assert/strict';

const base = process.env.SMOKE_BASE_URL || 'http://127.0.0.1:4321';
async function check(path) {
  const response = await fetch(new URL(path, base), { signal: AbortSignal.timeout(30000) });
  const html = await response.text();
  assert.equal(response.status, 200, `${path}: HTTP ${response.status}`);
  assert.match(html, /<title>[^<]+<\/title>/, `${path}: missing title`);
  assert.match(html, /<h1[\s>]/, `${path}: missing page heading`);
  assert.doesNotMatch(html, /astro-error-overlay|Cannot read properties of undefined|Invalid URL/);
  console.log(`PASS ${path}: HTML rendered`);
  return html;
}

function links(html) {
  return [...html.matchAll(/href="([^"#]+)"/g)].map(match => match[1]);
}

const home = await check('/');
for (const path of ['/work/', '/about/', '/articles/']) await check(path);
const project = links(home).find(path => /^\/work\/[^/]+\/?$/.test(path));
assert.ok(project, 'CMS must provide at least one project for this live smoke test');
await check(project);
const category = links(home).find(path => /^\/articles\/category\/[^/]+\/?$/.test(path));
assert.ok(category, 'CMS must provide at least one article category');
const categoryHtml = await check(category);
const article = links(categoryHtml).find(path => /^\/articles\/category\/[^/]+\/[^/]+\/?$/.test(path));
assert.ok(article, 'Selected category must provide an article for this live smoke test');
await check(article);
