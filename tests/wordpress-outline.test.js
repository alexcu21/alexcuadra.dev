import { test } from 'node:test';
import assert from 'node:assert/strict';
import { prepareWordPressDocument as prepare, sanitizeWordPressHtml } from '../src/data/wordpress-html.js';
const compile = html => prepare([{ key: 'body', html }], 'blog');

test('outline uses decoded text, accents and inline formatting without exposing HTML', () => {
  const document = compile('<h1>Introducción &amp; alcance</h1><h3>Probar <code>&lt;button&gt;</code></h3><h2>Fin</h2>');
  assert.deepEqual(document.headings.map(h => [h.id, h.level, h.label]), [
    ['wp-content-blog-body-introduccion-alcance', 2, 'Introducción & alcance'],
    ['wp-content-blog-body-probar-button', 3, 'Probar <button>'], ['wp-content-blog-body-fin', 2, 'Fin'],
  ]);
  assert.equal(document.outline[0].children[0].label, 'Probar <button>');
  assert.equal(document.outline[1].label, 'Fin');
  assert.doesNotMatch(document.sections[0].html, /<h1|<button>/);
  assert.match(document.sections[0].html, /tabindex="-1"/);
});

test('duplicate headings and suffix collisions get deterministic unique destinations', () => {
  const html = '<h2>Setup</h2><h2>Setup-2</h2><h2>Setup</h2><h2>Setup</h2>';
  const document = compile(html);
  assert.deepEqual(document.headings.map(h => h.id), ['setup', 'setup-2', 'setup-3', 'setup-4'].map(s => 'wp-content-blog-body-' + s));
  assert.deepEqual(compile(html), document);
});

test('author IDs are namespaced and matching local fragments follow the actual heading', () => {
  const document = compile('<p id="menu-content">No ID</p><a href="#main-content">Jump</a><h2 id="main-content">Start</h2>' +
    '<a href="#introducci%C3%B3n">Next</a><h2 id="introducción">Introducción</h2><a href="https://example.test/#main-content">External</a>');
  const html = document.sections[0].html;
  assert.doesNotMatch(html, /id="(?:main-content|menu-content)"/);
  assert.match(html, /href="#wp-content-blog-body-main-content"/);
  assert.match(html, /href="#wp-content-blog-body-introduccion"/);
  assert.match(html, /href="https:\/\/example.test\/#main-content"/);
});

test('explicit anchors take priority over generated text aliases, including across ACF sections', () => {
  const document = prepare([{ key: 'body', html: '<a href="#target">Jump</a><h2>Target</h2>' },
    { key: 'approach', title: 'Approach', html: '<h2 id="target">Actual target</h2><h3>Details</h3>' }], 'lab');
  assert.match(document.sections[0].html, /href="#wp-content-lab-approach-target"/);
  assert.deepEqual(document.headings.map(h => h.level), [2, 2, 3, 4]);
  assert.equal(document.outline[1].children[0].children[0].label, 'Details');
});

test('repeated author anchors resolve to the first actual destination; unknown/malformed fragments are preserved', () => {
  const document = compile('<h2 id="same">First</h2><h2 id="same">Second</h2><a href="#same">Go</a>' +
    '<a href="#unknown">Other</a><a href="#%invalid">Malformed</a>');
  assert.match(document.sections[0].html, /id="wp-content-blog-body-same-2"/);
  assert.match(document.sections[0].html, /href="#wp-content-blog-body-same"/);
  assert.match(document.sections[0].html, /href="#unknown"/);
  assert.match(document.sections[0].html, /href="#%invalid"/);
});

test('empty headings are not invented as TOC labels; skipped heading levels produce a valid tree', () => {
  const document = compile('<h2> </h2><h4>Deep start</h4><h2>Top</h2><h6>Nested</h6>');
  assert.deepEqual(document.headings.map(h => h.label), ['Deep start', 'Top', 'Nested']);
  assert.equal(document.outline.length, 2);
  assert.equal(document.outline[1].children[0].label, 'Nested');
  assert.deepEqual(compile(null).outline, []);
});

test('compilation preserves the sanitizer boundary for IDs, attributes, blocked content and URLs', () => {
  const html = compile('<h2 id="menu-content" onclick="attack()">Safe &lt;img onerror=attack()&gt;</h2>' +
    '<script><h2>PRIVATE_SCRIPT</h2></script><iframe><h2>PRIVATE_FRAME</h2></iframe>' +
    '<a href="javascript:attack()">Bad</a><img src="https://example.test/a.png" onerror="attack()">' +
    '<p style="position:fixed">Public</p><h2>Second</h2>').sections[0].html;
  assert.doesNotMatch(html, /PRIVATE|<script|<iframe|onclick=|onerror="|javascript:|position:|id="menu-content"/);
  assert.match(html, /&lt;img onerror=attack\(\)&gt;/);
  assert.match(html, /loading="lazy" decoding="async"/);
  assert.equal(sanitizeWordPressHtml('<h2 id="main-content">Title</h2>'), '<h2>Title</h2>');
});

test('outline serialization preserves all supported Gutenberg blocks and safe attributes', () => {
  const html = '<div class="wp-block-columns"><div class="wp-block-column" style="flex-basis:33.33%">' +
    '<h1>Intro</h1><p lang="es" dir="ltr" style="text-align:center"><strong>Bold &amp; text</strong></p>' +
    '<ol start="3" reversed><li value="4">Item</li></ol><figure class="wp-block-image">' +
    '<img src="https://example.test/a.jpg" srcset="https://example.test/a.jpg 1x, https://example.test/b.jpg 2x" width="800" height="600" alt="A &amp; B"><figcaption>Caption</figcaption></figure>' +
    '<blockquote><p>Quote</p><cite>Author</cite></blockquote><figure class="wp-block-table"><table><caption>Data</caption>' +
    '<tbody><tr><th scope="row">One</th><td colspan="2">Two</td></tr></tbody></table></figure>' +
    '<pre class="wp-block-code"><code class="language-js">const x = &lt;value&gt;;</code></pre>' +
    '<a href="https://example.test/?a=1&amp;b=2" target="_blank" rel="ugc">Source</a><br><hr></div></div>';
  assert.equal(sanitizeWordPressHtml(compile(html).sections[0].html), sanitizeWordPressHtml(html));
});

test('unicode heading destinations stay complete and URI-encodable at the length limit', () => {
  const document = compile(`<h2>${'𠮷'.repeat(81)}</h2><h2>第二章</h2>`);
  assert.doesNotThrow(() => document.headings.forEach(h => encodeURIComponent(h.id)));
  assert.equal([...document.headings[0].id.replace('wp-content-blog-body-', '')].length, 80);
});

test('plain sections remain plain data and section scopes cannot collide or inject attributes', () => {
  const document = prepare([{ key: 'goals', title: 'Goals <script>', text: 'A <b>literal</b> goal' }], 'lab');
  assert.equal(document.sections[0].text, 'A <b>literal</b> goal');
  assert.equal(document.sections[0].html, '');
  assert.equal(document.headings[0].label, 'Goals <script>');
  for (const [parts, scope] of [[[], 'bad" scope'], [[{ key: 'a"bad', html: '' }], 'lab'],
    [[{ key: 'a' }, { key: 'a' }], 'lab'], [[{ key: 'a', html: {}, title: 'A' }], 'lab'],
    [[{ key: 'a', text: 123 }], 'lab'], [[{ key: 'a', html: '', text: '' }], 'lab']]) {
    assert.throws(() => prepare(parts, scope), TypeError);
  }
});
