import { test } from 'node:test';
import assert from 'node:assert/strict';
import { matchesProject, projectFilterOptions } from '../src/scripts/project-filter.js';

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

test('filter options come only from current locale projects and include every assigned term', () => {
  const projects = [{tags:{nodes:[{name:'WordPress'},{name:'React'},{name:'CSS'},{name:'SCSS'},{name:'Svelte'},{name:'React'}]}},{tags:{nodes:[]}}];
  assert.deepEqual(projectFilterOptions(projects,'tags','es'),['CSS','React','SCSS','Svelte','WordPress']);
  assert.deepEqual(projectFilterOptions([],'categories','en'),[]);
});
