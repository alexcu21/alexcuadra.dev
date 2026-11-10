import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mountPageMotion } from '../src/scripts/page-motion.js';

function fixture(reduced = false, failing = false) {
  const values = new Map([['transform', ['rotate(0deg)', 'important']]]);
  const element = { dataset: { motion: 'reveal', motionDelay: '99' }, style: {
    getPropertyValue: key => values.get(key)?.[0] || '',
    getPropertyPriority: key => values.get(key)?.[1] || '',
    setProperty: (key, value, priority = '') => values.set(key, [value, priority]),
    removeProperty: key => values.delete(key),
  } };
  let changed, entered, stopped = 0;
  const calls = [];
  const preference = { matches: reduced,
    addEventListener: (_, cb) => { changed = cb; },
    removeEventListener: (_, cb) => { assert.equal(cb, changed); changed = null; },
  };
  const dispose = mountPageMotion({ root: { querySelectorAll: () => [element] }, preference,
    inView: (targets, cb) => { assert.deepEqual(targets, [element]); entered = cb; return () => { stopped++; }; },
    animate: (node, frames, options) => {
      assert.equal(node, element);
      if (failing) throw Error('Unsupported animation');
      let finish;
      const call = { frames, options, cancelled: false,
        cancel: () => { call.cancelled = true; },
        then: cb => { finish = cb; },
        finish: () => { values.set('opacity', ['1', '']); values.set('transform', ['translateY(0px)', '']); finish(); },
      };
      calls.push(call); return call;
    },
  });
  return { element, values, calls, preference, dispose, enter: () => entered?.(element),
    change: value => { preference.matches = value; changed(); },
    observed: () => !!entered, stopped: () => stopped };
}

test('reduced motion starts with static content and no observer or animation', () => {
  const f = fixture(true);
  assert.equal(f.observed(), false); assert.equal(f.calls.length, 0);
  assert.deepEqual(f.values.get('transform'), ['rotate(0deg)', 'important']); f.dispose();
});
test('reveal runs once, caps stagger and restores original inline styles on completion', () => {
  const f = fixture(); f.enter(); f.enter();
  assert.equal(f.calls.length, 1); assert.equal(f.calls[0].options.delay, .18);
  assert.deepEqual(f.calls[0].frames.opacity, [.65, 1]);
  f.calls[0].finish();
  assert.deepEqual(f.values.get('transform'), ['rotate(0deg)', 'important']); assert.equal(f.values.has('opacity'), false);
  f.dispose();
});
test('changing preference cancels active motion and does not replay completed targets', () => {
  const f = fixture(); f.enter(); f.change(true);
  assert.equal(f.calls[0].cancelled, true); assert.equal(f.stopped(), 1);
  f.change(false); f.enter(); assert.equal(f.calls.length, 1); f.dispose();
});
test('turning motion on after a reduced start can animate still unplayed content', () => {
  const f = fixture(true); f.change(false); f.element.dataset.motion = 'hero'; f.enter();
  assert.deepEqual(f.calls[0].frames.opacity, [1, 1]); f.dispose();
  assert.equal(f.calls[0].cancelled, true);
});
test('animation failure leaves original styles intact and links/content available', () => {
  const f = fixture(false, true); assert.doesNotThrow(() => f.enter());
  assert.deepEqual(f.values.get('transform'), ['rotate(0deg)', 'important']); assert.equal(f.values.has('opacity'), false); f.dispose();
});
