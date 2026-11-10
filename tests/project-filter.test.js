import { test } from 'node:test';
import assert from 'node:assert/strict';
import { matchesProject, projectFilterOptions, projectFilterState, projectFilterUrl } from '../src/scripts/project-filter.js';

test('project discovery combines title, exact category and full technology assignments', () => {
  const project = {title:'Diseño editorial',categories:['Sitio WordPress'],technologies:['React','Svelte','CSS','SCSS','ACF Pro']};
  assert.equal(matchesProject(project,' DISENO ','Sitio WordPress','ACF Pro'),true);
  assert.equal(matchesProject(project,'','Sitio',''),false);
  assert.equal(matchesProject(project,'','Sitio WordPress','ACF'),false);
  assert.equal(matchesProject(project,'missing','',''),false);
  assert.equal(matchesProject(project,'','',''),true);
  assert.equal(matchesProject({...project,categories:[],technologies:[]},'editorial','',''),true);
  assert.equal(matchesProject({...project,categories:[],technologies:[]},'','','React'),false);
  assert.equal(matchesProject({...project,title:null},'','',''),true);
  assert.equal(matchesProject({...project,title:null},'editorial','',''),false);
});

test('shared project filters restore localized exact terms and accented title queries', () => {
  const state=projectFilterState(new URLSearchParams('q=Dise%C3%B1o&category=Sitio+WordPress&technology=Svelte'),['Sitio WordPress'],['Svelte']);
  assert.deepEqual(state,{query:'Diseño',category:'Sitio WordPress',technology:'Svelte'});
});
test('unknown and ambiguous terms are ignored and title queries have a bounded length', () => {
  assert.deepEqual(projectFilterState(new URLSearchParams('q=one&q=two&category=English&technology=Unknown'),['Español'],['React']),{query:'',category:'',technology:''});
  assert.equal(projectFilterState(new URLSearchParams({q:'a'.repeat(500)}),[],[]).query.length,200);
  assert.equal(projectFilterState(new URLSearchParams('category=one&category=two'),['one','two'],[]).category,'');
});
test('filter URLs preserve the current route, hash and unrelated parameters', () => {
  const url=projectFilterUrl('https://example.test/es/proyectos/?mode=fixture#results',{query:'Diseño & React',category:'Sitio WordPress',technology:'ACF Pro'});
  assert.equal(url.origin,'https://example.test');assert.equal(url.pathname,'/es/proyectos/');assert.equal(url.hash,'#results');
  assert.equal(url.searchParams.get('mode'),'fixture');assert.equal(url.searchParams.get('q'),'Diseño & React');
  assert.equal(url.searchParams.get('category'),'Sitio WordPress');assert.equal(url.searchParams.get('technology'),'ACF Pro');
});
test('clearing filters removes their parameters without dropping unrelated URL state', () => {
  const url=projectFilterUrl('https://example.test/work/?q=test&category=one&technology=React&keep=yes',{query:'',category:'',technology:''});
  assert.equal(url.search,'?keep=yes');
});
test('query text remains encoded URL data, including markup-like values', () => {
  const query='<script>alert("x")</script>';
  const url=projectFilterUrl('https://example.test/work/',{query,category:'',technology:''});
  assert.ok(!url.href.includes('<script>'));assert.equal(url.searchParams.get('q'),query);
});

test('filter options come only from current locale projects and include every assigned term', () => {
  const projects = [{tags:{nodes:[{name:'WordPress'},{name:'React'},{name:'CSS'},{name:'SCSS'},{name:'Svelte'},{name:'React'}]}},{tags:{nodes:[]}}];
  assert.deepEqual(projectFilterOptions(projects,'tags','es'),['CSS','React','SCSS','Svelte','WordPress']);
  assert.deepEqual(projectFilterOptions([],'categories','en'),[]);
});
