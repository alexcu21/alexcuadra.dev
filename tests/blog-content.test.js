import { test } from 'node:test';
import assert from 'node:assert/strict';
import { blogSummary, blogDate } from '../src/data/blog-content.js';

test('excerpt summaries remove unsafe HTML and preserve readable entities', () => {
  assert.equal(blogSummary('<p>WordPress &#8217; &amp; &lt;code&gt;</p><script>PRIVATE</script>'), 'WordPress ’ & <code>');
  assert.equal(blogSummary(null), '');
});

test('dates use real GMT values only, reject invalid calendar values and preserve UTC date', () => {
  assert.equal(blogDate('2026-02-30T00:00:00', 'en'), null);
  assert.equal(blogDate(null, 'es'), null);
  assert.equal(blogDate('2026-10-19T00:00:00', 'en').iso, '2026-10-19T00:00:00Z');
  assert.match(blogDate('2026-10-19T00:00:00', 'es').label, /19.*octubre.*2026/);
});
