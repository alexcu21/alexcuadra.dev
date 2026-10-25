import { test } from 'node:test';
import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { setTimeout } from 'node:timers/promises';

test('actual reference templates preserve HTTP, content and pagination contracts', { timeout: 60000 }, async t => {
  const port = Number(process.env.REFERENCES_SSR_PORT || 4398);
  assert.ok(Number.isInteger(port) && port >= 1024 && port <= 65535, 'valid fixture port');
  const root = fileURLToPath(new URL('../../', import.meta.url));
  const server = spawn(process.execPath, ['node_modules/astro/bin/astro.mjs', 'dev', '--root',
    'tests/fixtures/references', '--host', '127.0.0.1', '--port', String(port), '--ignore-lock'],
  { cwd: root, stdio: ['ignore', 'pipe', 'pipe'], env: { ...process.env, ASTRO_TELEMETRY_DISABLED: '1' } });
  let output = '';
  for (const stream of [server.stdout, server.stderr]) stream.on('data', chunk => { output += chunk; });
  t.after(async () => {
    if (server.exitCode !== null) return;
    const exited = new Promise(resolve => server.once('exit', resolve));
    server.kill('SIGTERM');
    await exited;
  });
  const base = `http://127.0.0.1:${port}`;
  let ready = false;
  for (let attempt = 0; attempt < 200; attempt++) {
    if (server.exitCode !== null) throw Error(`Fixture startup failed: ${output.slice(-3000)}`);
    try {
      const response = await fetch(base, { signal: AbortSignal.timeout(1000) });
      if (response.status === 200) { await response.text(); ready = true; break; }
    } catch { /* Wait for isolated Astro startup. */ }
    await setTimeout(100);
  }
  assert.ok(ready, output.slice(-3000));
  const get = async path => {
    const response = await fetch(base + path);
    return { response, html: await response.text() };
  };
  // Actual project routes: multilingual links and missing content must survive
  // the shared visual component changes, without inventing case-study sections.
  for (const [path, title] of [['/work/', 'Demonstration project'], ['/es/proyectos/', 'Proyecto de demostración'],
    ['/work/case-study', 'Demonstration project'], ['/es/proyectos/caso-de-estudio', 'Proyecto de demostración']]) {
    const { response, html } = await get(path);
    assert.equal(response.status, 200, path);
    assert.ok(html.includes(title), path);
    assert.equal((html.match(/<h1(?:\s|>)/g) || []).length, 1);
    assert.match(html, /project-preview.svg/);
    assert.doesNotMatch(html, /PRIVATE_SCRIPT|99\.9%|450\+|testimonial/);
  }
  const projectDetail = await get('/work/case-study');
  assert.match(projectDetail.html, /Synthetic project scope/);
  assert.match(projectDetail.html, /href="\/es\/proyectos\/caso-de-estudio"/);
  const projectMinimal = await get('/work/minimal');
  assert.equal(projectMinimal.response.status, 200);
  assert.doesNotMatch(projectMinimal.html, /project-story-title|project-preview.svg|class="project-details"|Explore the project/);
  assert.doesNotMatch(projectMinimal.html, /class="editorial-toc"/);
  assert.doesNotMatch(projectDetail.html, /class="editorial-toc"/);
  const currentCase = await get('/work/case-study?mode=project-case-current');
  assert.equal(currentCase.response.status, 200);
  assert.match(currentCase.html, /class="editorial-toc"/);
  assert.match(currentCase.html, /href="#wp-content-body-body-the-solution"/);
  assert.match(currentCase.html, /<h4\b[^>]*id="wp-content-body-body-the-solution"/);
  const currentSpanish = await get('/es/proyectos/caso-de-estudio?mode=project-case-current');
  assert.doesNotMatch(currentSpanish.html, /class="editorial-toc"/);
  for (const [path, label, heading] of [
    ['/work/case-study?mode=project-case-draft', 'On this page', 'the-solution'],
    ['/es/proyectos/caso-de-estudio?mode=project-case-draft', 'En esta página', 'la-solucion'],
  ]) {
    const { response, html } = await get(path);
    assert.equal(response.status, 200, path);
    assert.match(html, /class="editorial-toc"/);
    assert.ok(html.includes(`aria-label="${label}"`));
    assert.ok(html.includes(`href="#wp-content-body-body-${heading}"`));
    assert.ok(html.includes(`id="wp-content-body-body-${heading}" tabindex="-1"`));
    assert.doesNotMatch(html, /<h4\b/);
    assert.equal((html.match(/<h1(?:\s|>)/g) || []).length, 1);
    assert.doesNotMatch(html, /99\.9%|450\+|SDET|PRIVATE_SCRIPT/);
  }
  const projectEmpty = await get('/es/proyectos/?mode=projects-empty');
  assert.equal(projectEmpty.response.status, 200);
  assert.match(projectEmpty.html, /Todavía no hay proyectos publicados/);
  assert.match(projectEmpty.html, /href="\/work\/"/);
  const projectMissing = await get('/work/missing');
  assert.equal(projectMissing.response.status, 404);
  for (const path of ['/preview/home', '/preview/home-es']) {
    const { response, html } = await get(path);
    assert.equal(response.status, 200, path);
    assert.equal((html.match(/<h1(?:\s|>)/g) || []).length, 1);
    assert.match(html, /data-motion="hero"/);
    assert.match(html, /data-motion="reveal"/);
    assert.match(html, /profile-preview.svg/);
    assert.match(html, path.includes('home-es') ? /href="\/es\/proyectos\/caso-de-estudio"/ : /href="\/work\/case-study"/);
    if (path.includes('home-es')) {
      assert.match(html, /Proyecto de demostración/);
      assert.match(html, /href="\/es\/proyectos\/"/);
      assert.doesNotMatch(html, /href="\/work\/case-study"|Fichas disponibles en inglés|Ver todos \(EN\)/);
    }
    assert.doesNotMatch(html, /style="(?:opacity:\s*0|visibility:\s*hidden)/);
  }
  const emptyHome = await get('/preview/home?mode=projects-empty');
  assert.equal(emptyHome.response.status, 200);
  assert.doesNotMatch(emptyHome.html, /class="featured-preview"/);
  assert.match(emptyHome.html, /profile-preview.svg/);
  const emptySpanishHome = await get('/preview/home-es?mode=projects-empty');
  assert.equal(emptySpanishHome.response.status, 200);
  assert.match(emptySpanishHome.html, /Todavía no hay proyectos publicados en español/);
  assert.doesNotMatch(emptySpanishHome.html, /class="featured-preview"|href="\/work\/case-study"/);
  const homeLab = await get('/preview/home-es?mode=home-lab-reuse');
  assert.equal(homeLab.response.status, 200);
  assert.match(homeLab.html, /id="home-lab-title"/); assert.match(homeLab.html, /En desarrollo/);
  assert.match(homeLab.html, /href="\/es\/laboratorio\/laboratorio"/);
  const noHomeLab = await get('/preview/home?mode=home-lab-empty-reuse');
  assert.equal(noHomeLab.response.status, 200);
  assert.doesNotMatch(noHomeLab.html, /id="home-lab-title"|href="\/lab\/"/);
  const noSkills = await get('/preview/home?mode=skills-empty');
  assert.equal(noSkills.response.status, 200); assert.doesNotMatch(noSkills.html, /id="disciplines-title"/);
  const partialSkills = await get('/preview/home?mode=skills-partial');
  assert.equal(partialSkills.response.status, 200); assert.match(partialSkills.html, /Only confirmed skill/);
  assert.equal((partialSkills.html.match(/class="skill-icon"/g) || []).length, 1);
  for (const path of ['/preview/home', '/preview/home-es', '/articles/']) {
    const { response, html } = await get(path);
    assert.equal(response.status, 200);
    const channel = html.match(/<a[^>]*class="channel-card"[\s\S]*?<\/a>/)?.[0];
    assert.ok(channel, `${path}: reference channel card`);
    assert.match(channel, /href="\/articles\/category\/medium"/);
    assert.match(channel, /count-badge[^>]*>\s*2\s*</);
    assert.equal((html.match(/id="contact-cta-title"/g) || []).length, 1);
    assert.match(html, /href="mailto:fixture@example.test"/);
    const contact = html.match(/<aside[^>]*id="contact-cta"[\s\S]*?<\/aside>/)?.[0];
    assert.ok(contact);
    assert.match(contact, path.includes('home-es') ? /href="\/es\/proyectos\/"/ : /href="\/work\/"/);
    assert.match(contact, path.includes('home-es') ? /Abre tu aplicación de correo/ : /Opens your email app/);
    assert.doesNotMatch(html, /data-contact-form|turnstile\/v0\/api/);
  }
  const referencesEmptyHome = await get('/preview/home?mode=references-empty');
  assert.equal(referencesEmptyHome.response.status, 200);
  assert.doesNotMatch(referencesEmptyHome.html, /id="articles"|class="channel-card"/);
  const referencesEmptyArchive = await get('/articles/?mode=references-empty');
  assert.equal(referencesEmptyArchive.response.status, 200);
  assert.match(referencesEmptyArchive.html, /No public references are available yet/);
  assert.doesNotMatch(referencesEmptyArchive.html, /data-reference-directory/);
  const referenceArchive = await get('/articles/');
  assert.match(referenceArchive.html, /data-reference-controls[^>]*hidden/);
  assert.match(referenceArchive.html, /Search titles/);
  assert.match(referenceArchive.html, /role="status"/);
  assert.equal([...referenceArchive.html.matchAll(/<li\b[^>]*data-reference-entry/g)].length, 2);
  assert.match(referenceArchive.html, /href="\/articles\/category\/medium\/second"/);
  for (const [path, expected] of [
    ['/articles/', /Medium/],
    ['/articles/category/medium', /Second synthetic reference/],
    ['/articles/category/empty', /No references published/],
    ['/articles/category/medium/reference', /Synthetic quote/],
    ['/articles/category/medium/minimal', /Confirmed synthetic body/],
    ['/articles/category/medium/paginated', /Confirmed synthetic body/],
  ]) {
    const { response, html } = await get(path);
    assert.equal(response.status, 200, `${path}: ${output.slice(-3000)}`);
    assert.match(html, expected);
    assert.equal((html.match(/<h1(?:\s|>)/g) || []).length, 1);
    assert.match(html, /<main/);
    assert.match(html, /href="#main-content"/);
    assert.match(html, /<main[^>]*id="main-content"[^>]*tabindex="-1"/);
    assert.doesNotMatch(html, /PRIVATE_SCRIPT|turnstile\/v0\/api/);
    assert.match(html, new RegExp(`rel="canonical" href="https://alexcuadra.dev${path.replace(/\/$/, '')}/?"`));
  }
  const detailed = await get('/articles/category/medium/reference');
  assert.match(detailed.html, /class="reference-image"[^>]*src="https:\/\/example.test\/reference.png"/);
  assert.match(detailed.html, /property="og:image" content="https:\/\/example.test\/reference.png"/);
  const schema = JSON.parse(detailed.html.match(/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/s)[1]);
  assert.equal(schema['@type'], 'BreadcrumbList');
  assert.equal(schema.itemListElement[2].name, 'Synthetic reference');
  const minimal = await get('/articles/category/medium/minimal');
  assert.doesNotMatch(minimal.html, /class="reference-image"|<blockquote/);
  for (const path of ['/articles/category/missing', '/articles/category/medium/missing', '/articles/category/other/reference']) {
    const { response, html } = await get(path);
    assert.equal(response.status, 404, path);
    assert.equal(response.headers.get('cache-control'), 'no-store');
    assert.equal(response.headers.get('x-robots-tag'), 'noindex');
    assert.doesNotMatch(html, /Confirmed synthetic body/);
  }
  for (const path of ['/articles/?mode=outage', '/articles/?mode=late-outage',
    '/articles/category/medium?mode=late-articles', '/articles/category/medium/reference?mode=layout-outage', '/articles/category/medium/paginated?mode=nested-outage']) {
    const { response, html } = await get(path);
    assert.equal(response.status, 503, `${path}: ${output.slice(-3000)}`);
    assert.equal(response.headers.get('cache-control'), 'no-store');
    assert.equal(response.headers.get('x-robots-tag'), 'noindex');
    assert.equal(response.headers.get('retry-after'), '120');
    assert.doesNotMatch(html, /PRIVATE|Synthetic reference|Confirmed synthetic body|graphql|stack/);
  }
  for (const path of ['/blog/', '/es/blog/', '/blog/native-post', '/es/blog/entrada']) {
    const { response, html } = await get(path);
    assert.equal(response.status, 200, `${path}: ${output.slice(-2000)}`);
    assert.match(html, /Native blog fixture/);
    assert.doesNotMatch(html, /PRIVATE_SCRIPT|Synthetic quote/);
    assert.equal((html.match(/<h1(?:\s|>)/g) || []).length, 1);
    assert.match(html, path.startsWith('/es/') ? /<html lang="es"/ : /<html lang="en"/);
    assert.match(html, path.startsWith('/es/') ? /aria-current="page"[^>]*href="\/es\/blog\/"/ : /aria-current="page"[^>]*href="\/blog\/"/);
  }
  const archive = await get('/blog/');
  assert.match(archive.html, /hreflang="es"[^>]*href="https:\/\/alexcuadra.dev\/es\/blog\/"/);
  const localizedNavigation = await get('/es/blog/?mode=nav-es');
  assert.equal(localizedNavigation.response.status, 200);
  assert.match(localizedNavigation.html, /href="\/es\/sobre-mi\/"/);
  assert.match(localizedNavigation.html, /href="\/es\/"/);
  assert.doesNotMatch(localizedNavigation.html, /Inicio \(EN\)|Sobre mí \(EN\)/);
  const withoutBlog = await get('/articles/?mode=blog-empty');
  assert.equal(withoutBlog.response.status, 200);
  assert.doesNotMatch(withoutBlog.html, /href="\/blog\/"/);
  const native = await get('/blog/native-post');
  assert.match(native.html, /hreflang="es"[^>]*href="https:\/\/alexcuadra.dev\/es\/blog\/entrada"/);
  assert.doesNotMatch(native.html, /class="editorial-toc"/);
  for (const [path, label] of [['/blog/native-post?mode=outline', 'On this page'], ['/es/blog/entrada?mode=outline', 'En esta página'],
    ['/lab/training-lab?mode=outline', 'On this page'], ['/es/laboratorio/laboratorio?mode=outline', 'En esta página']]) {
    const { response, html } = await get(path);
    assert.equal(response.status, 200, path);
    assert.match(html, /<details[^>]*class="editorial-toc"[^>]*open/);
    assert.ok(html.includes(`<summary>${label}</summary>`));
    assert.ok(html.includes(`aria-label="${label}"`));
    const ids = [...html.matchAll(/\bid="([^"]+)"/g)].map(m => m[1]);
    assert.equal(ids.length, new Set(ids).size, `${path}: all document IDs unique`);
    const toc = html.match(/<details[^>]*class="editorial-toc"[^>]*>[\s\S]*?<\/details>/)?.[0];
    const destinations = [...toc.matchAll(/href="#([^"]+)"/g)].map(m => decodeURIComponent(m[1]));
    assert.ok(destinations.length >= 3);
    for (const id of destinations) assert.ok(ids.includes(id), `${path}: TOC target exists: ${id}`);
    assert.equal((html.match(/<h1(?:\s|>)/g) || []).length, 1);
    assert.doesNotMatch(html, /PRIVATE_SCRIPT|<check>|<script[^>]*>PRIVATE/);
    if (path.includes('blog')) {
      assert.match(html, /href="#wp-content-blog-body-main-content"/);
      assert.match(html, /<a href="#wp-content-blog-body-run-check">Run &lt;check&gt;<\/a>/);
    } else {
      assert.match(html, /<h2 id="wp-content-lab-learning-goals" tabindex="-1"/);
      assert.match(html, /<h3 id="wp-content-lab-technical-approach-setup" tabindex="-1"/);
      assert.match(html, /href="#wp-content-lab-technical-approach-setup"/);
    }
  }
  for (const path of ['/blog/?mode=blog-empty', '/es/blog/?mode=blog-empty', '/blog/missing', '/blog/entrada']) {
    const { response } = await get(path);
    assert.equal(response.status, 404, path);
    assert.equal(response.headers.get('x-robots-tag'), 'noindex');
  }
  for (const path of ['/blog/?mode=blog-late', '/blog/native-post?mode=blog-private', '/blog/?mode=outage',
    '/articles/?mode=blog-availability-outage', '/es/blog/?mode=nav-outage']) {
    const { response, html } = await get(path);
    assert.equal(response.status, 503, path);
    assert.equal(response.headers.get('cache-control'), 'no-store');
    assert.doesNotMatch(html, /Native post body|PRIVATE/);
  }

  for (const path of ['/lab/', '/es/laboratorio/', '/lab/training-lab', '/es/laboratorio/laboratorio']) {
    const { response, html } = await get(path);
    assert.equal(response.status, 200, `${path}: ${output.slice(-2000)}`);
    assert.match(html, /Personal lab fixture/);
    assert.match(html, path.startsWith('/es/') ? /En desarrollo/ : /In progress/);
    assert.equal((html.match(/<h1(?:\s|>)/g) || []).length, 1);
    assert.match(html, path.startsWith('/es/') ? /<html lang="es"/ : /<html lang="en"/);
    assert.doesNotMatch(html, /PRIVATE_SCRIPT|javascript:|turnstile\/v0\/api|Synthetic quote/);
  }
  const lab = await get('/es/laboratorio/laboratorio');
  // Follow the actual archive links: checking a hand-picked detail path alone
  // misses fixture slugs that incorrectly point to another language's entry.
  for (const base of ['/lab/', '/es/laboratorio/']) {
    const archive = await get(base);
    const paths = [...archive.html.matchAll(/href="([^"?#]+)"/g)]
      .map(match => match[1]).filter(path => path.startsWith(base) && path !== base);
    assert.ok(paths.length > 0, `${base}: archive has detail links`);
    for (const path of new Set(paths)) assert.equal((await get(path)).response.status, 200, path);
  }
  for (const expected of [/Objetivos de aprendizaje/, /Technical details/, /Quality details/, /Deployment details/,
    /19 de octubre de 2026/, /hreflang="en"[^>]*href="https:\/\/alexcuadra.dev\/lab\/training-lab"/]) assert.match(lab.html, expected);
  const minimalLab = await get('/lab/minimal');
  assert.equal(minimalLab.response.status, 200);
  assert.doesNotMatch(minimalLab.html, /Learning goals|Quality strategy|Last reviewed:|class="cover"/);
  const withoutLab = await get('/articles/?mode=lab-empty');
  assert.equal(withoutLab.response.status, 200);
  assert.doesNotMatch(withoutLab.html, /href="\/lab\/"/);
  for (const path of ['/lab/?mode=lab-empty', '/es/laboratorio/?mode=lab-empty', '/lab/missing', '/lab/laboratorio']) {
    const { response } = await get(path);
    assert.equal(response.status, 404, path);
    assert.equal(response.headers.get('x-robots-tag'), 'noindex');
  }
  for (const path of ['/lab/?mode=lab-late', '/lab/training-lab?mode=lab-private', '/lab/?mode=outage',
    '/articles/?mode=lab-availability-outage']) {
    const { response, html } = await get(path);
    assert.equal(response.status, 503, path);
    assert.equal(response.headers.get('cache-control'), 'no-store');
    assert.doesNotMatch(html, /Personal project body|PRIVATE/);
  }

});
