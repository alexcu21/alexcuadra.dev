import assert from 'node:assert/strict';

// Resume only within the focused editorial smoke. Never silently skip unrelated checks.
export function editorialResume(args) {
  const index = args.indexOf('--editorial-start-at');
  if (index === -1) {
    assert.ok(!args.includes('--blog-entry-path'), '--blog-entry-path requires --editorial-start-at blog-404');
    return null;
  }
  const stage = args[index + 1];
  assert.ok(['blog-404', 'lab', 'references'].includes(stage), 'Editorial start must be blog-404, lab or references');
  assert.ok(args.includes('--editorial-only') && args.includes('--check-blog-lab'),
    'Resuming requires --editorial-only --check-blog-lab');
  const allowed = new Set(['--editorial-start-at', '--editorial-only', '--check-blog-lab', '--blog-entry-path',
    '--require-cms-auth', '--check-seo', '--check-content', '--check-csp', '--check-contact-disabled', '--request-gap-ms']);
  for (const flag of args.filter(value => value.startsWith('--'))) {
    assert.ok(allowed.has(flag), `Cannot combine editorial resume with ${flag}`);
    assert.equal(args.filter(value => value === flag).length, 1, `Duplicate ${flag}`);
  }
  const entryIndex = args.indexOf('--blog-entry-path');
  const blogEntryPath = entryIndex === -1 ? undefined : args[entryIndex + 1];
  if (stage === 'blog-404') {
    assert.match(blogEntryPath || '', /^\/blog\/[a-z0-9]+(?:-[a-z0-9]+)*\/?$/,
      'Blog 404 resume requires a previously verified English --blog-entry-path');
  } else assert.equal(entryIndex, -1, '--blog-entry-path is only used by blog-404');
  return { stage, blogEntryPath };
}
