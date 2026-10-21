import { test } from 'node:test';
import assert from 'node:assert/strict';
import { sanitizeWordPressHtml as clean } from '../src/data/wordpress-html.js';

test('Gutenberg headings, columns, images, quotes, tables, lists and code survive sanitization', () => {
  const result = clean(`<!-- wp:columns --><div class="wp-block-columns"><div class="wp-block-column" style="flex-basis:33.33%">
    <h2>Heading</h2><p><strong>Bold</strong> <em>Emphasis</em></p><ul><li>Item</li></ul>
    <figure class="wp-block-image"><img src="https://content.alexcuadra.dev/example.jpg" width="800" height="600" alt="Actual description"><figcaption>Caption</figcaption></figure>
    <blockquote class="wp-block-quote"><p>Quote</p><cite>Author</cite></blockquote>
    <figure class="wp-block-table"><table><tbody><tr><th scope="row">Title</th><td>Value</td></tr></tbody></table></figure>
    <pre class="wp-block-code"><code class="language-js">const x = &lt;value&gt;;</code></pre></div></div><!-- /wp:columns -->`);
  for (const fragment of ['wp-block-columns', 'wp-block-column', 'flex-basis:33.33%', '<h2>Heading</h2>',
    '<strong>Bold</strong>', '<em>Emphasis</em>', '<li>Item</li>', 'alt="Actual description"', 'loading="lazy"',
    '<figcaption>Caption</figcaption>', '<cite>Author</cite>', 'scope="row"', 'language-js', '&lt;value&gt;']) assert.ok(result.includes(fragment), fragment);
  assert.doesNotMatch(result, /<!--/);
});

test('scripts, embedded documents, SVG, MathML, forms and event handlers are removed', () => {
  const result = clean(`<script>EXECUTE_SECRET()</script><style>body{display:none}</style>
    <iframe src="https://evil.example">IFRAME_SECRET</iframe><object data="evil">OBJECT_SECRET</object>
    <svg><a href="javascript:attack()">SVG_SECRET</a></svg><math><mtext>MATH_SECRET</mtext></math>
    <form action="https://evil.example"><input name="password"></form>
    <img src="https://example.com/safe.jpg" onerror="attack()"><p onclick="attack()">Public text</p>`);
  assert.match(result, /Public text/);
  assert.doesNotMatch(result, /script|iframe|object|svg|math|form|input|onerror|onclick|SECRET|display:none/i);
});

test('obfuscated script URLs, unsafe images and external protocol-relative URLs fail closed', () => {
  for (const href of ['javascript:alert(1)', 'jav&#x61;script:alert(1)', 'java&#10;script:alert(1)', 'data:text/html,x', '//evil.example']) {
    assert.doesNotMatch(clean(`<a href="${href}">Link</a>`), /href=/);
  }
  assert.doesNotMatch(clean('<img src="data:image/svg+xml,evil"><img src="http://example.com/a.jpg">'), /src=/);
  assert.match(clean('<a href="mailto:alex@example.test">Email</a>'), /href="mailto:/);
  assert.match(clean('<a href="/work/example">Internal</a>'), /href="\/work\/example"/);
  assert.doesNotMatch(clean('<img srcset="javascript:alert(1) 1x, https://example.com/good.jpg 2x">'), /javascript:/);
});

test('inline CSS and classes cannot escape the editorial layout', () => {
  const result = clean('<p id="menu-content" class="fixed wp-block-heading top-0" style="text-align:center;position:fixed;background:url(https://evil.example);color:red">Text</p>');
  assert.match(result, /text-align:center/);
  assert.match(result, /class="wp-block-heading"/);
  assert.doesNotMatch(result, /menu-content|position|background|evil|color|fixed|top-0/);
});

test('blank-target links keep editorial rel values and always prevent opener access', () => {
  const result = clean('<a href="https://example.com" target="_blank" rel="opener sponsored ugc nofollow">External</a>');
  assert.match(result, /rel="sponsored ugc nofollow noopener noreferrer"/);
  assert.doesNotMatch(clean('<a href="/" target="_top">Home</a>'), /target=/);
});

test('the page keeps a single H1, and malformed or repeated HTML stays safe', () => {
  const result = clean('<h1>Editorial title</h1><p><strong>Unclosed <img src=x onerror=alert(1)>');
  assert.match(result, /<h2>Editorial title<\/h2>/);
  assert.doesNotMatch(result, /<h1|onerror/);
  assert.equal(clean(result), result);
  assert.equal(clean(null), '');
  assert.equal(clean(undefined), '');
  assert.throws(() => clean({ html: 'invalid' }), TypeError);
});
