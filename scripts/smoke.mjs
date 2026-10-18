import assert from 'node:assert/strict';

const base = process.argv[2] || process.env.SMOKE_BASE_URL || 'http://127.0.0.1:4321';
console.log(`Smoke target: ${base}`);
const favicon = await fetch(new URL('/favicon.ico', base), {
  redirect: 'manual', signal: AbortSignal.timeout(30000),
});
assert.equal(favicon.status, 308, 'favicon.ico must redirect without rendering CMS content');
assert.equal(favicon.headers.get('location'), '/favicon.svg');
console.log('PASS /favicon.ico: direct redirect to static SVG');
async function check(path) {
  const response = await fetch(new URL(path, base), { signal: AbortSignal.timeout(30000) });
  const html = await response.text();
  assert.equal(response.status, 200, `${path}: HTTP ${response.status}`);
  if (path === '/' && process.argv.includes('--require-cms-auth')) {
    assert.equal(response.headers.get('x-cms-auth'), 'verified', 'CMS service identity must be verified');
    console.log('PASS CMS service identity: verified');
  }
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
