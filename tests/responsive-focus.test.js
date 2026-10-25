import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mountResponsiveFocus } from '../src/scripts/responsive-focus.js';

function fixture(desktop = true) {
  const listeners = new Map(), mediaListeners = new Map(), windowListeners = new Map();
  const eventTarget = map => ({addEventListener:(name, fn) => map.set(name,fn), removeEventListener:(name, fn) => {if(map.get(name)===fn)map.delete(name);}});
  const root = {...eventTarget(listeners),body:{name:'body'},defaultView:eventTarget(windowListeners)};
  root.activeElement = root.body;
  const target = name => ({name,focus:()=>{root.activeElement=targets[name];listeners.get('focusin')?.({target:targets[name]});}});
  const targets = {compact:target('compact'),sidebar:target('sidebar'),outside:target('outside')};
  const region = member => ({contains:value=>value===member});
  const media = {...eventTarget(mediaListeners),matches:desktop};
  const updates=[];
  const dispose = mountResponsiveFocus({root,media,narrow:{region:region(targets.compact),target:targets.compact},
    wide:{region:region(targets.sidebar),target:targets.sidebar},update:value=>updates.push(value)});
  return {root,targets,updates,dispose,listeners,mediaListeners,windowListeners,
    focus:name=>targets[name].focus(),
    cssBlur:()=>{root.activeElement=root.body;},
    change:matches=>{media.matches=matches;mediaListeners.get('change')?.({matches});}};
}

test('responsive focus initialization never moves focus', () => {
  const f=fixture();assert.equal(f.root.activeElement,f.root.body);assert.deepEqual(f.updates,[true]);
});
test('desktop region transfers focus to the compact control even after CSS blur', () => {
  const f=fixture();f.focus('sidebar');f.cssBlur();f.change(false);
  assert.equal(f.root.activeElement,f.targets.compact);assert.deepEqual(f.updates,[true,false]);
});
test('compact control transfers focus to its desktop equivalent', () => {
  const f=fixture(false);f.focus('compact');f.cssBlur();f.change(true);
  assert.equal(f.root.activeElement,f.targets.sidebar);
});
test('resizing leaves unrelated keyboard focus untouched', () => {
  const f=fixture();f.focus('sidebar');f.focus('outside');f.change(false);
  assert.equal(f.root.activeElement,f.targets.outside);
});
test('clicking elsewhere discards stale focus before a viewport change', () => {
  const f=fixture();f.focus('sidebar');f.listeners.get('pointerdown')({target:f.root.body});f.cssBlur();f.change(false);
  assert.equal(f.root.activeElement,f.root.body);
});
test('clicking the already-focused control preserves its focus during reflow', () => {
  const f=fixture(false);f.focus('compact');f.listeners.get('pointerdown')({target:f.targets.compact});f.cssBlur();f.change(true);
  assert.equal(f.root.activeElement,f.targets.sidebar);
});
test('window blur discards stale focus and disposal removes all listeners', () => {
  const f=fixture();f.focus('sidebar');f.windowListeners.get('blur')();f.cssBlur();f.change(false);
  assert.equal(f.root.activeElement,f.root.body);f.dispose();
  assert.equal(f.listeners.size,0);assert.equal(f.mediaListeners.size,0);assert.equal(f.windowListeners.size,0);
});
