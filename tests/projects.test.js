import { test } from 'node:test';
import assert from 'node:assert/strict';
import { sortProjectsByModified } from '../src/data/projects.js';

test('orders WordPress ISO dates newest first without mutating input', () => {
  const projects = [
    { slug: 'old', modified: '2024-01-01T12:00:00' },
    { slug: 'new', modified: '2026-10-17T12:00:00' },
    { slug: 'middle', modified: '2025-08-01T12:00:00' },
  ];
  assert.deepEqual(sortProjectsByModified(projects).map(p => p.slug), ['new', 'middle', 'old']);
  assert.equal(projects[0].slug, 'old');
});

test('places missing and invalid dates after valid dates', () => {
  const projects = [{ slug: 'missing' }, { slug: 'invalid', modified: 'invalid' },
    { slug: 'valid', modified: '2026-01-01T00:00:00Z' }];
  assert.deepEqual(sortProjectsByModified(projects).map(p => p.slug), ['valid', 'missing', 'invalid']);
});

test('handles an empty collection', () => {
  assert.deepEqual(sortProjectsByModified([]), []);
});
