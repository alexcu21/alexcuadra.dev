import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import ts from 'typescript';

// Execute the real client controller with deterministic DOM and Turnstile doubles.
// This exercises submission state; visual/keyboard browser QA remains separate.
const source = ts.transpileModule(readFileSync(new URL('../src/scripts/contact-form.ts', import.meta.url), 'utf8'), {
  compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
}).outputText;

async function setup(transport, { unavailable = false, locale = 'en' } = {}) {
  const listeners = [], requests = [];
  const button = { disabled: true };
  const status = { textContent: '', focused: false, focus() { this.focused = true; } };
  let callbacks, resets = 0, cleared = 0;
  const form = {
    dataset: Object.fromEntries(['sending', 'success', 'invalid', 'challenge', 'limited', 'unavailable', 'unknown'].map(k => [k, `${locale}:${k}`])),
    valid: true, values: { name: 'Visitor', email: 'visitor@example.test', message: '<script>literal</script>' },
    addEventListener(type, fn) { if (type === 'submit') listeners.push(fn); },
    querySelector(selector) { return selector === 'button' ? button : selector === '[role="status"]' ? status : {}; },
    reportValidity() { return this.valid; }, reset() { cleared++; },
  };
  Object.assign(form.dataset, { locale, sitekey: 'test-sitekey' });
  const api = { render(_element, options) { if (unavailable) throw Error('unavailable'); callbacks = options; return 'widget'; },
    reset(id) { assert.equal(id, 'widget'); resets++; } };
  const context = { exports: {}, window: { turnstile: api }, AbortSignal,
    FormData: class { constructor(value) { this.values = value.values; } get(key) { return this.values[key]; } },
    fetch: async (url, options) => { requests.push({ url, options }); return transport(url, options); },
  };
  vm.runInNewContext(source, context);
  await context.exports.initializeContactForm(form);
  return { form, button, status, requests, get callbacks() { return callbacks; },
    get resets() { return resets; }, get cleared() { return cleared; },
    async submit() { for (const listener of listeners) await listener({ preventDefault() {} }); },
  };
}

test('client requires valid fields and a fresh challenge before sending', async () => {
  const ctx = await setup(() => { throw Error('must not send'); });
  await ctx.submit();
  assert.equal(ctx.status.textContent, 'en:challenge');
  ctx.callbacks.callback('token'); ctx.form.valid = false;
  await ctx.submit();
  assert.equal(ctx.status.textContent, 'en:invalid');
  ctx.callbacks['expired-callback']();
  assert.equal(ctx.button.disabled, true);
  ctx.callbacks.callback('new-token'); ctx.callbacks['error-callback']();
  assert.equal(ctx.button.disabled, true);
  assert.equal(ctx.requests.length, 0);
});

test('client submits once while busy, clears only on acceptance and announces localized status', async () => {
  let finish;
  const ctx = await setup(() => new Promise(resolve => { finish = resolve; }), { locale: 'es' });
  assert.equal(ctx.callbacks.action, 'contact'); assert.equal(ctx.callbacks.language, 'es');
  assert.equal(ctx.callbacks.size, 'flexible');
  ctx.callbacks.callback('token');
  const pending = ctx.submit();
  await ctx.submit();
  assert.equal(ctx.requests.length, 1); assert.equal(ctx.button.disabled, true);
  assert.equal(ctx.status.textContent, 'es:sending');
  const { url, options } = ctx.requests[0];
  assert.equal(url, '/api/contact'); assert.equal(options.credentials, 'omit');
  assert.equal(JSON.parse(options.body).message, ctx.form.values.message);
  finish(Response.json({ code: 'CONTACT_ACCEPTED' }, { status: 202 }));
  await pending;
  assert.equal(ctx.cleared, 1); assert.equal(ctx.resets, 1);
  assert.equal(ctx.status.textContent, 'es:success'); assert.equal(ctx.status.focused, true);
  ctx.callbacks.callback('renewed-token');
  ctx.callbacks['expired-callback'](); ctx.callbacks['error-callback']();
  assert.equal(ctx.status.textContent, 'es:success');
});

test('uncertain delivery, network failures and malformed responses block repeat submissions', async () => {
  for (const transport of [
    () => Response.json({ code: 'CONTACT_DELIVERY_UNKNOWN' }, { status: 503 }),
    () => { throw Error('network'); },
    () => new Response('not json', { status: 502 }),
  ]) {
    const ctx = await setup(transport); ctx.callbacks.callback('token');
    await ctx.submit();
    assert.equal(ctx.status.textContent, 'en:unknown');
    assert.equal(ctx.cleared, 0); assert.equal(ctx.resets, 0);
    ctx.callbacks.callback('another-token'); await ctx.submit();
    assert.equal(ctx.button.disabled, true); assert.equal(ctx.requests.length, 1);
    ctx.callbacks['expired-callback'](); ctx.callbacks['error-callback']();
    assert.equal(ctx.status.textContent, 'en:unknown');
  }
});

test('known rejection preserves input and requires a renewed challenge', async () => {
  for (const [status, outcome] of [[400, 'invalid'], [403, 'challenge'], [429, 'limited'], [503, 'unavailable']]) {
    const ctx = await setup(() => Response.json({ code: 'REJECTED_BEFORE_DELIVERY' }, { status }));
    ctx.callbacks.callback('token'); await ctx.submit();
    assert.equal(ctx.status.textContent, `en:${outcome}`);
    assert.equal(ctx.cleared, 0); assert.equal(ctx.resets, 1); assert.equal(ctx.button.disabled, true);
    ctx.callbacks.callback('renewed-token');
    assert.equal(ctx.button.disabled, false);
    ctx.callbacks['expired-callback'](); ctx.callbacks['error-callback']();
    assert.equal(ctx.status.textContent, `en:${outcome}`);
    await ctx.submit(); assert.equal(ctx.requests.length, 1);
  }
});

test('unavailable widget leaves submission disabled with an accessible status', async () => {
  const ctx = await setup(() => { throw Error('must not send'); }, { unavailable: true });
  await ctx.submit();
  assert.equal(ctx.button.disabled, true); assert.equal(ctx.status.textContent, 'en:unavailable');
  assert.equal(ctx.requests.length, 0);
});
