import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { writeFile } from 'node:fs/promises';
import { editorialResume } from './lib/editorial-resume.mjs';
import { createSmokeFetch } from './lib/smoke-fetch.mjs';

const base = process.argv[2] || process.env.SMOKE_BASE_URL || 'http://127.0.0.1:4321';
const resume = editorialResume(process.argv.slice(3));
const gapIndex = process.argv.indexOf('--request-gap-ms');
const requestGap = gapIndex === -1 ? 0 : Number(process.argv[gapIndex + 1]);
assert.ok(Number.isInteger(requestGap) && requestGap >= 0 && requestGap <= 30000, 'Request gap must be0–30000ms');
const smokeFetch = createSmokeFetch(requestGap);
console.log(`Smoke target: ${base}`);
if (resume) console.log(`Resuming editorial checks at ${resume.stage}; technical routes, Home, sitemap and contact endpoint are outside this run`);
if (!resume) {
  const favicon = await smokeFetch(new URL('/favicon.ico', base), {
    redirect: 'manual',
  });
  assert.equal(favicon.status, 308, 'favicon.ico must redirect without rendering CMS content');
  assert.equal(favicon.headers.get('location'), '/favicon.svg');
  console.log('PASS /favicon.ico: direct redirect to static SVG');
  for (const path of ['/__smoke_missing_page__', '/?rest_route=%2Fwp%2Fv2%2Fusers']) {
    const response = await smokeFetch(new URL(path, base));
    assert.equal(response.status, 404, `${path}: must return 404 independently of CMS availability`);
    assert.equal(response.headers.get('x-cms-auth'), null);
    console.log(`PASS ${path}: 404 without CMS identity query`);
  }
}
const checked = new Map();
async function check(path) {
  if (checked.has(path)) return checked.get(path);
  const response = await smokeFetch(new URL(path, base));
  const html = await response.text();
  assert.equal(response.status, 200, `${path}: HTTP ${response.status}`);
  if ((path === '/' || resume) && process.argv.includes('--require-cms-auth')) {
    assert.equal(response.headers.get('x-cms-auth'), 'verified', 'CMS service identity must be verified');
    console.log(`PASS ${path}: CMS service identity verified`);
  }
  assert.match(html, /<title>[^<]+<\/title>/, `${path}: missing title`);
  assert.match(html, /<h1[\s>]/, `${path}: missing page heading`);
  assert.doesNotMatch(html, /astro-error-overlay|Cannot read properties of undefined|Invalid URL/);
  if (new URL(base).hostname.endsWith('.workers.dev')) {
    assert.match(response.headers.get('x-robots-tag') || '', /noindex/, `${path}: staging must remain noindex`);
  }
  if (process.argv.includes('--check-csp')) {
    const policy = response.headers.get('content-security-policy')
      || /<meta\b[^>]*http-equiv="content-security-policy"[^>]*content="([^"]+)"/i.exec(html)?.[1];
    assert.ok(policy, `${path}: missing CSP`);
    const scripts = /(?:^|;)\s*script-src\s+([^;]+)/.exec(policy)?.[1];
    assert.ok(scripts?.includes('https://challenges.cloudflare.com'), `${path}: Turnstile script blocked`);
    assert.doesNotMatch(scripts, /'unsafe-inline'|'unsafe-eval'/);
    assert.match(policy, /frame-src https:\/\/challenges\.cloudflare\.com/);
    for (const [, attributes, body] of html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/g)) {
      if (/\bsrc=/.test(attributes) || /type="application\/(?:ld\+)?json"/.test(attributes) || !body.trim()) continue;
      const hash = createHash('sha256').update(body).digest('base64');
      assert.ok(scripts.includes(`'sha256-${hash}'`), `${path}: executable inline script missing CSP hash`);
    }
    console.log(`PASS ${path}: CSP and executable inline script hashes`);
  }
  if (process.argv.includes('--check-seo')) {
    assert.equal([...html.matchAll(/<meta\b[^>]*\bname="viewport"/g)].length, 1, `${path}: duplicate viewport`);
    assert.equal([...html.matchAll(/<meta\b[^>]*\bname="description"/g)].length, 1, `${path}: duplicate description`);
    const canonical = /<link\b[^>]*rel="canonical"[^>]*href="([^"]+)"/.exec(html)?.[1];
    assert.equal(canonical, new URL(path, 'https://alexcuadra.dev').href, `${path}: canonical must use production`);
    if (!path.startsWith('/es/')) assert.match(html, /<meta\b[^>]*property="og:locale"[^>]*content="en_US"/);
    assert.doesNotMatch(html, /<meta\b[^>]*(?:property|name)="(?:og:image|twitter:image)"[^>]*content=""/);
    console.log(`PASS ${path}: canonical and base SEO metadata`);
  }
  if (process.argv.includes('--check-content') && (path === '/about/' || path === '/es/sobre-mi/' || path === '/privacy/' || path === '/es/privacidad/' || /^\/es\/proyectos\/[^/]+\/?$/.test(path)
    || /^\/work\/[^/]+\/?$/.test(path) || /^\/articles\/category\/[^/]+\/[^/]+\/?$/.test(path)
    || /^\/(?:blog|lab)\/[^/]+\/?$/.test(path) || /^\/es\/(?:blog|laboratorio)\/[^/]+\/?$/.test(path))) {
    assert.match(html, /class="wordpress-content"/, `${path}: missing shared CMS renderer`);
    assert.equal([...html.matchAll(/<h1[\s>]/g)].length, 1, `${path}: editorial content must not add another H1`);
    console.log(`PASS ${path}: shared editorial renderer and one H1`);
  }
  console.log(`PASS ${path}: HTML rendered`);
  checked.set(path, html);
  return html;
}

function links(html) {
  return [...html.matchAll(/href="([^"#]+)"/g)].map(match => match[1]);
}

let sitemapUrls;
if (process.argv.includes('--check-sitemap')) {
  const response = await smokeFetch(new URL('/sitemap.xml', base));
  assert.equal(response.status, 200);
  assert.match(response.headers.get('content-type') || '', /application\/xml/);
  const xml = await response.text();
  assert.match(xml, /<urlset xmlns="http:\/\/www.sitemaps.org\/schemas\/sitemap\/0.9">/);
  const urls = [...xml.matchAll(/<loc>([^<]+)<\/loc>/g)].map(match => match[1]);
  sitemapUrls = new Set(urls);
  assert.ok(urls.length > 0);
  assert.equal(urls.length, new Set(urls).size);
  for (const value of urls) {
    const url = new URL(value);
    assert.equal(url.origin, 'https://alexcuadra.dev');
    assert.equal(url.search, ''); assert.equal(url.hash, '');
    assert.doesNotMatch(url.pathname, /^\/(?:api|404)(?:\/|$)|^\/es\/articulos(?:\/|$)/);
  }
  for (const path of ['/', '/es/', '/about/', '/es/sobre-mi/', '/work/', '/es/proyectos/', '/articles/']) {
    assert.ok(urls.includes(`https://alexcuadra.dev${path}`));
  }
  const robots = await smokeFetch(new URL('/robots.txt', base));
  assert.equal(robots.status, 200);
  const text = await robots.text();
  assert.match(text, /User-agent: \*/);
  if (new URL(base).hostname === 'alexcuadra.dev') assert.match(text, /Sitemap: https:\/\/alexcuadra.dev\/sitemap.xml/);
  else assert.doesNotMatch(text, /Sitemap:/);
  if (new URL(base).hostname.endsWith('.workers.dev')) assert.match(response.headers.get('x-robots-tag') || '', /noindex/);
  const outputIndex = process.argv.indexOf('--save-sitemap');
  if (outputIndex !== -1) await writeFile(process.argv[outputIndex + 1], xml);
  console.log(`PASS sitemap: ${urls.length} canonical public URLs, robots and environment indexing policy`);
  if (process.argv.includes('--sitemap-only')) process.exit(0);
}
const home = resume ? undefined : await check('/');
if (process.argv.includes('--check-home-es')) {
  const spanishHome = await check('/es/');
  assert.match(spanishHome, /<html[^>]*lang="es"/);
  assert.match(spanishHome.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' '), /Hola, soy Alex Cuadra/);
  assert.match(spanishHome, /Me apasionan el desarrollo frontend/);
  assert.match(spanishHome, /Colaborador de código abierto/);
  assert.match(spanishHome, /Proyectos destacados/);
  assert.match(spanishHome, /Envíame un mensaje/);
  assert.doesNotMatch(spanishHome, /data-contact-form|Inicio \(EN\)/);
  for (const html of [home, spanishHome]) {
    for (const [language, path] of [['en', '/'], ['es', '/es/']]) {
      assert.ok(html.includes(`hreflang="${language}" href="https://alexcuadra.dev${path}"`));
    }
  }
  assert.ok(links(spanishHome).some(path => /^\/work\/[^/]+/.test(path)), 'ES Home must preserve real project links');
  assert.ok(links(spanishHome).some(path => /^\/articles\/category\/[^/]+/.test(path)), 'Keep current reference category links');
  const featured = links(home).find(path => /^\/work\/[^/]+/.test(path));
  assert.ok(featured);
  const project = await check(featured);
  assert.match(project, /<html[^>]*lang="en"/);
  console.log('PASS Home EN/ES: translated CMS fields, reciprocal alternates and existing collection URLs');
}
if (process.argv.includes('--check-about-es-absent')) {
  const response = await smokeFetch(new URL('/es/sobre-mi/', base));
  assert.equal(response.status, 404, 'Missing About translation must not render English');
  assert.match(response.headers.get('x-robots-tag') || '', /noindex/);
  assert.equal(response.headers.get('cache-control'), 'no-store');
  const about = await check('/about/');
  assert.doesNotMatch(about, /Lorem Ipsum|hreflang="es"|href="\/es\/sobre-mi\/"/);
  console.log('PASS About: missing Spanish translation is 404/no-store/noindex, no invented alternate');
}
if (!resume && process.argv.includes('--check-contact-disabled')) {
  const result = await smokeFetch(new URL('/api/contact', base), { redirect: 'error', timeoutMs: 15000 });
  assert.equal(result.status, 404);
  assert.equal(result.headers.get('cache-control'), 'no-store');
  assert.equal((await result.json()).code, 'CONTACT_DISABLED');
  assert.doesNotMatch(home, /data-contact-form|challenges\.cloudflare\.com\/turnstile\/v0\/api\.js/);
  console.log('PASS contact: disabled endpoint contract and no public form/widget');
}
if (process.argv.includes('--check-blog-lab')) {
  const checkMissing = async (enBase, esBase, detailPath, spanishPath) => {
    for (const path of [enBase + '__missing_entry__', esBase + detailPath.slice(enBase.length)].filter(path => path !== spanishPath)) {
      const response = await smokeFetch(new URL(path, base));
      assert.equal(response.status, 404, `${path}: no missing or wrong-language fallback`);
      assert.equal(response.headers.get('cache-control'), 'no-store');
      assert.match(response.headers.get('x-robots-tag') || '', /noindex/);
      console.log(`PASS ${path}: 404/no-store/noindex`);
    }
  };
  const alternatePath = (html, locale) => {
    const value = new RegExp(`hreflang="${locale}" href="https://alexcuadra.dev([^\"]+)"`).exec(html)?.[1];
    assert.ok(value, `Missing published ${locale} translation`);
    return value;
  };
  for (const [enBase, esBase] of [['/blog/', '/es/blog/'], ['/lab/', '/es/laboratorio/']]) {
    if (resume?.stage === 'references') continue;
    if (resume && enBase === '/blog/') {
      if (resume.stage === 'blog-404') {
        await checkMissing(enBase, esBase, resume.blogEntryPath);
        console.log('PASS resumed Blog missing/wrong-language contracts; previously verified archives/details not repeated');
      }
      continue;
    }
    const englishArchive = await check(enBase);
    const spanishArchive = await check(esBase);
    assert.match(englishArchive, /<html[^>]*lang="en"/);
    assert.match(spanishArchive, /<html[^>]*lang="es"/);
    assert.match(spanishArchive, /href="\/es\/sobre-mi\/"/, 'Spanish About navigation must use its public translation');
    assert.match(spanishArchive, /href="\/es\/"/, 'Spanish Home navigation must use its public translation');
    assert.doesNotMatch(spanishArchive, /Inicio \(EN\)|Sobre mí \(EN\)/);
    assert.equal(alternatePath(englishArchive, 'es'), esBase);
    assert.equal(alternatePath(spanishArchive, 'en'), enBase);
    const detailPath = links(englishArchive).find(path => path.startsWith(enBase)
      && /^[^/?#]+\/?$/.test(path.slice(enBase.length)));
    assert.ok(detailPath, `${enBase}: missing published entry`);
    const english = await check(detailPath);
    const spanishPath = alternatePath(english, 'es');
    assert.ok(spanishPath.startsWith(esBase));
    const spanish = await check(spanishPath);
    assert.equal(alternatePath(spanish, 'en'), detailPath);
    assert.match(english, /<html[^>]*lang="en"/);
    assert.match(spanish, /<html[^>]*lang="es"/);
    for (const [path, html] of [[enBase, englishArchive], [esBase, spanishArchive], [detailPath, english], [spanishPath, spanish]]) {
      assert.equal([...html.matchAll(/<h1[\s>]/g)].length, 1, `${path}: one page heading`);
      assert.match(html, /<main[^>]*id="main-content"[^>]*tabindex="-1"/);
      if (process.argv.includes('--check-contact-disabled')) assert.doesNotMatch(html, /data-contact-form|turnstile\/v0\/api/);
      if (sitemapUrls) assert.ok(sitemapUrls.has(`https://alexcuadra.dev${path}`), `${path}: missing sitemap entry`);
    }
    await checkMissing(enBase, esBase, detailPath, spanishPath);
    console.log(`PASS ${enBase}: EN/ES archives, real entries, reciprocal translations and404 contracts`);
  }
  const references = await check('/articles/');
  assert.doesNotMatch(references, /href="\/articles\/category\/uncategorized(?:-es)?"/);
  console.log('PASS reference cards: blog-only categories excluded');
}
if (process.argv.includes('--editorial-only')) {
  assert.ok(process.argv.includes('--check-blog-lab'), '--editorial-only requires --check-blog-lab');
  console.log(resume ? `PASS editorial checks from ${resume.stage}; skipped checks are not certified by this run`
    : 'PASS focused Blog/LAB smoke; other collections not included');
  process.exit(0);
}
if (process.argv.includes('--check-projects-es-empty')) {
  const english = await check('/work/');
  const spanish = await check('/es/proyectos/');
  assert.match(spanish, /<html[^>]*lang="es"/);
  assert.match(spanish, /Todavía no hay proyectos publicados en español/);
  assert.match(spanish, /Ver proyectos en inglés/);
  for (const html of [english, spanish]) {
    for (const [locale, path] of [['en', '/work/'], ['es', '/es/proyectos/']]) {
      assert.ok(html.includes(`hreflang="${locale}" href="https://alexcuadra.dev${path}"`));
    }
  }
  const detail = links(english).find(path => /^\/work\/[^/]+/.test(path));
  assert.ok(detail);
  await check(detail);
  for (const path of ['/work/__missing_project__', detail.replace('/work/', '/es/proyectos/')]) {
    const response = await smokeFetch(new URL(path, base));
    assert.equal(response.status, 404, `${path}: no missing or wrong-language fallback`);
    assert.equal(response.headers.get('cache-control'), 'no-store');
    assert.match(response.headers.get('x-robots-tag') || '', /noindex/);
  }
  console.log('PASS projects: localized archives, empty ES, real EN detail and missing/wrong-language 404');
}
if (process.argv.includes('--check-projects-es')) {
  const archive = await check('/es/proyectos/');
  assert.doesNotMatch(archive, /Todavía no hay proyectos publicados en español/);
  const path = links(archive).find(path => /^\/es\/proyectos\/alexcuadra-dev[^/]*\/?$/.test(path));
  assert.ok(path, 'Published Spanish project must appear in its archive');
  const spanish = await check(path);
  assert.match(spanish, /<html[^>]*lang="es"/);
  assert.match(spanish, /Un sitio de portfolio con Astro/);
  assert.match(spanish, /Este es mi primer proyecto con/);
  assert.match(spanish, /href="https:\/\/github.com\/alexcu21\/alexcuadra.dev"/);
  assert.doesNotMatch(spanish, /data-contact-form/);
  const enPath = /hreflang="en" href="https:\/\/alexcuadra.dev([^\"]+)"/.exec(spanish)?.[1];
  assert.ok(enPath);
  const english = await check(enPath);
  assert.ok(english.includes(`hreflang="es" href="https://alexcuadra.dev${path}"`));
  console.log('PASS published project ES: real CMS slug, archive card, content, URLs and reciprocal alternates');
}
if (process.argv.includes('--check-christianity-today')) {
  const archive = await check('/es/proyectos/');
  const path = links(archive).find(path => /^\/es\/proyectos\/christianitytoday-com[^/]*\/?$/.test(path));
  assert.ok(path, 'Christianity Today translation must appear in its archive');
  const spanish = await check(path);
  assert.match(spanish, /Trabajé como contratista externo/);
  assert.match(spanish, /Tecnologías: React, Svelte, ACF y la API de WordPress/);
  assert.match(spanish, /href="https:\/\/www.christianitytoday.com\/"/);
  assert.doesNotMatch(spanish, /href="\/404"/);
  const englishPath = /hreflang="en" href="https:\/\/alexcuadra.dev([^\"]+)"/.exec(spanish)?.[1];
  assert.equal(englishPath, '/work/christianitytoday-com');
  const english = await check(englishPath);
  assert.match(english, /I worked as an external contractor/);
  assert.match(english, /React, Svelte, ACF and the WordPress API/);
  assert.ok(english.includes(`hreflang="es" href="https://alexcuadra.dev${path}"`));
  console.log('PASS Christianity Today EN/ES: confirmed scope, technologies, public URLs and reciprocal alternates');
}
if (process.argv.includes('--check-valexweb-norceca')) {
  const archive = await check('/es/proyectos/');
  for (const [stem, text] of [['valexweb-com', 'un tema basado en bloques'], ['norceca-info', 'numerosas personalizaciones con ACF']]) {
    const path = links(archive).find(path => new RegExp(`^/es/proyectos/${stem}[^/]*$`).test(path));
    assert.ok(path, `${stem}: published card missing`);
    const spanish = await check(path);
    assert.match(spanish, /<html[^>]*lang="es"/);
    assert.ok(spanish.includes(text));
    const englishPath = /hreflang="en" href="https:\/\/alexcuadra.dev([^\"]+)"/.exec(spanish)?.[1];
    assert.equal(englishPath, `/work/${stem}`);
    const english = await check(englishPath);
    assert.ok(english.includes(`hreflang="es" href="https://alexcuadra.dev${path}"`));
    assert.doesNotMatch(spanish, /data-contact-form|href="\/404"/);
  }
  console.log('PASS Valexweb/Norceca: published cards, translated content and reciprocal alternates');
}
if (process.argv.includes('--projects-only')) {
  assert.ok(process.argv.includes('--check-projects-es-empty') || process.argv.includes('--check-projects-es') || process.argv.includes('--check-christianity-today') || process.argv.includes('--check-valexweb-norceca'));
  process.exit(0);
}
if (process.argv.includes('--home-only')) {
  assert.ok(process.argv.includes('--check-home-es'), '--home-only requires --check-home-es');
  console.log('PASS focused Home smoke; other pages not included');
  process.exit(0);
}
if (process.argv.includes('--check-privacy')) {
  const privacy = await check('/privacy/');
  assert.match(privacy, /Contact form privacy/);
  assert.match(privacy, /mailto:alex@alexcuadra\.dev/);
  assert.match(privacy, /30 days/);
  assert.doesNotMatch(privacy, /\[publication date\]|\[Confirm the retention/);
  console.log('PASS privacy: published policy, public contact and retention; no editorial placeholders');
}
if (process.argv.includes('--check-privacy-es')) {
  for (const [path, locale] of [['/privacy/', 'en'], ['/es/privacidad/', 'es']]) {
    const html = await check(path);
    assert.match(html, new RegExp(`<html[^>]*lang="${locale}"`));
    for (const [language, destination] of [['en', '/privacy/'], ['es', '/es/privacidad/']]) {
      assert.ok(html.includes(`hreflang="${language}" href="https://alexcuadra.dev${destination}"`), `${path}: missing reciprocal alternate`);
    }
    if (locale === 'es') {
      assert.match(html, /Privacidad del formulario de contacto/);
      assert.match(html, /30 días/);
      assert.match(html, /mailto:alex@alexcuadra\.dev/);
      if (process.argv.includes('--check-shell-es')) {
        assert.match(html, /aria-label="Navegación principal"/);
        assert.match(html, /Menú/);
        assert.match(html, /Tema oscuro/);
        assert.match(html, /Diseñado y desarrollado en Centroamérica con/);
        assert.match(html, /Inicio/);
        assert.doesNotMatch(html, /href="\/es\/articulos\//);
        assert.match(html, /aria-controls="menu-content"/);
      }
    }
  }
  console.log('PASS bilingual privacy: language, published Spanish content and reciprocal alternates');
}
for (const path of ['/work/', '/about/', '/articles/']) await check(path);
if (process.argv.includes('--check-about-es')) {
  for (const [path, locale] of [['/about/', 'en'], ['/es/sobre-mi/', 'es']]) {
    const html = await check(path);
    assert.match(html, new RegExp(`<html[^>]*lang="${locale}"`));
    for (const [language, destination] of [['en', '/about/'], ['es', '/es/sobre-mi/']]) {
      assert.ok(html.includes(`hreflang="${language}" href="https://alexcuadra.dev${destination}"`));
    }
    assert.doesNotMatch(html, /Lorem Ipsum/);
    if (locale === 'es') {
      assert.match(html, /Conoce mi trayectoria profesional/);
      assert.match(html, /Ingeniero de sistemas con formación en desarrollo web/);
      assert.match(html, /Envíame un mensaje/);
      assert.doesNotMatch(html, /Sobre mí \(EN\)|data-contact-form/);
    }
  }
  console.log('PASS About EN/ES: published translation, fields, CTA and reciprocal alternates');
}
const project = links(home).find(path => /^\/work\/[^/]+\/?$/.test(path));
assert.ok(project, 'CMS must provide at least one project for this live smoke test');
await check(project);
const category = links(home).find(path => /^\/articles\/category\/[^/]+\/?$/.test(path));
assert.ok(category, 'CMS must provide at least one article category');
const categoryHtml = await check(category);
const article = links(categoryHtml).find(path => /^\/articles\/category\/[^/]+\/[^/]+\/?$/.test(path));
assert.ok(article, 'Selected category must provide an article for this live smoke test');
await check(article);
