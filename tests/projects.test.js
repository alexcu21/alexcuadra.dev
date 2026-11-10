import { test } from 'node:test';
import assert from 'node:assert/strict';
import { sortProjectsByModified, projectNavigation } from '../src/data/projects.js';

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

const entry = (slug, modified, extra = {}) => ({slug,title:slug,modified,status:'publish',isRestricted:false,hasPassword:false,language:{code:'EN'},...extra});
test('adjacent projects follow the archive order with no wrap or source mutation', () => {
  const projects = [entry('old','2024-01-01'),entry('new','2026-01-01'),entry('middle','2025-01-01')];
  assert.deepEqual(projectNavigation(projects,'middle','en'),{
    previous:{title:'new',path:'/work/new'},next:{title:'old',path:'/work/old'},
  });
  assert.equal(projectNavigation(projects,'new','en').previous,null);
  assert.equal(projectNavigation(projects,'old','en').next,null);
  assert.equal(projects[0].slug,'old');
});
test('adjacent projects exclude other languages, nonpublic entries and invalid destinations', () => {
  const projects = [entry('source','2026-01-01'),entry('draft','2025-01-01',{status:'draft'}),
    entry('protected','2025-01-01',{hasPassword:true}),entry('restricted','2025-01-01',{isRestricted:true}),
    entry('spanish','2025-01-01',{language:{code:'ES'}}),entry('bad%2fpath','2025-01-01'),
    entry('empty-title','2025-01-01',{title:''}),entry('target','2024-01-01')];
  assert.deepEqual(projectNavigation(projects,'source','en'),{previous:null,next:{title:'target',path:'/work/target'}});
});
test('Spanish project navigation uses Spanish routes and projects only', () => {
  const projects = [entry('uno','2026-01-01',{language:{code:'ES'}}),entry('dos','2025-01-01',{language:{code:'ES'}})];
  assert.deepEqual(projectNavigation(projects,'uno','es').next,{title:'dos',path:'/es/proyectos/dos'});
});
test('missing, singleton and ambiguous project navigation does not invent links', () => {
  assert.deepEqual(projectNavigation([],'missing','en'),{previous:null,next:null});
  assert.deepEqual(projectNavigation([entry('only','2026-01-01')],'only','en'),{previous:null,next:null});
  assert.deepEqual(projectNavigation([entry('same','2026-01-01'),entry('same','2025-01-01')],'same','en'),{previous:null,next:null});
});
