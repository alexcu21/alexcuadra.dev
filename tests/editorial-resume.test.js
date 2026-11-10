import { test } from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { editorialResume } from '../scripts/lib/editorial-resume.mjs';

const focused = ['--editorial-only', '--check-blog-lab'];
test('editorial resume keeps the default run and validates explicit checkpoints', () => {
  assert.equal(editorialResume(['--check-blog-lab']), null);
  for (const stage of ['lab', 'references']) {
    assert.deepEqual(editorialResume([...focused, '--editorial-start-at', stage]), { stage, blogEntryPath: undefined });
  }
  assert.deepEqual(editorialResume([...focused, '--editorial-start-at', 'blog-404', '--blog-entry-path', '/blog/real-entry']),
    { stage: 'blog-404', blogEntryPath: '/blog/real-entry' });
});

test('invalid or misleading resume plans are rejected before any HTTP request', () => {
  for (const args of [
    ['--editorial-start-at', 'lab'],
    [...focused, '--editorial-start-at', 'unknown'],
    [...focused, '--editorial-start-at', 'blog-404'],
    [...focused, '--editorial-start-at', 'lab', '--check-sitemap'],
    [...focused, '--editorial-start-at', 'lab', '--check-home-es'],
    [...focused, '--editorial-start-at', 'lab', '--blog-entry-path', '/blog/real-entry'],
    [...focused, '--editorial-start-at', 'lab', '--editorial-start-at', 'references'],
    ['--blog-entry-path', '/blog/real-entry'],
    ...['https://other.test/blog/entry', '//other.test/entry', '/blog/entry?preview=1', '/es/blog/entrada', '/blog/../private']
      .map(path => [...focused, '--editorial-start-at', 'blog-404', '--blog-entry-path', path]),
  ]) assert.throws(() => editorialResume(args));
  const child = spawnSync(process.execPath, ['scripts/smoke.mjs', 'https://network-must-not-be-used.invalid',
    ...focused, '--editorial-start-at', 'lab', '--check-sitemap'], { encoding: 'utf8' });
  assert.notEqual(child.status, 0);
  assert.match(child.stderr, /Cannot combine editorial resume/);
  assert.doesNotMatch(child.stdout, /Smoke target:/);
});

const pendingPaths = ['/blog/__missing_entry__', '/es/blog/real-entry', '/lab/', '/es/laboratorio/',
  '/lab/training', '/es/laboratorio/entrenamiento', '/lab/__missing_entry__', '/es/laboratorio/training', '/articles/'];
function fixtureRun(stage, expected, failFirst = false) {
  const preloader = `
    import assert from 'node:assert/strict';
    const expected = ${JSON.stringify(expected)};
    let calls = 0;
    globalThis.fetch = async input => {
      const path = new URL(input).pathname;
      assert.equal(path, expected[calls++], 'Unexpected/duplicated HTTP request');
      if (${failFirst}) return new Response('Temporarily unavailable', { status: 503 });
      if (path.includes('__missing_entry__') || path === '/es/blog/real-entry' || path === '/es/laboratorio/training')
        return new Response('Not found', { status: 404, headers: { 'cache-control': 'no-store', 'x-robots-tag': 'noindex' } });
      const es = path.startsWith('/es/');
      const detail = path === '/lab/training' || path === '/es/laboratorio/entrenamiento';
      const enPath = detail ? '/lab/training' : '/lab/';
      const esPath = detail ? '/es/laboratorio/entrenamiento' : '/es/laboratorio/';
      return new Response('<html lang="' + (es ? 'es' : 'en') + '"><title>Fixture</title>' +
        '<link hreflang="en" href="https://alexcuadra.dev' + enPath + '">' +
        '<link hreflang="es" href="https://alexcuadra.dev' + esPath + '">' +
        '<a href="/es/">Inicio</a><a href="/es/sobre-mi/">Sobre mí</a>' +
        '<main id="main-content" tabindex="-1"><h1>Fixture</h1><div class="wordpress-content">Fixture body</div>' +
        '<a href="' + (es ? '/es/laboratorio/entrenamiento' : '/lab/training') + '">Entry</a></main></html>',
        { headers: { 'x-cms-auth': 'verified' } });
    };
    process.on('exit', () => { assert.equal(calls, expected.length); console.log('FIXTURE_REQUESTS=' + calls); });
  `;
  return spawnSync(process.execPath, ['--import', 'data:text/javascript,' + encodeURIComponent(preloader),
    'scripts/smoke.mjs', 'https://fixture.test', ...focused, '--editorial-start-at', stage,
    ...(stage === 'blog-404' ? ['--blog-entry-path', '/blog/real-entry'] : []),
    '--require-cms-auth', '--check-content', '--check-contact-disabled'], { encoding: 'utf8' });
}

test('resumed smoke requests only pending cases and preserves their content/auth/404 contracts', () => {
  for (const [stage, expected] of [['blog-404', pendingPaths], ['lab', pendingPaths.slice(2)], ['references', pendingPaths.slice(-1)]]) {
    const result = fixtureRun(stage, expected);
    assert.equal(result.status, 0, result.stdout + result.stderr);
    assert.match(result.stdout, /skipped checks are not certified by this run/);
    assert.match(result.stdout, new RegExp('FIXTURE_REQUESTS=' + expected.length));
  }
});

test('resumed smoke stops on 503 without retrying or fetching later sections', () => {
  const result = fixtureRun('blog-404', pendingPaths.slice(0, 1), true);
  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /503 !== 404/);
  assert.match(result.stdout, /FIXTURE_REQUESTS=1/);
  assert.doesNotMatch(result.stdout, /PASS editorial checks/);
});
