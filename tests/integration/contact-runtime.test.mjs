import { test } from 'node:test';
import assert from 'node:assert/strict';
import { build } from 'esbuild';
import { Miniflare, convertV4MiniflareOptions } from 'miniflare';
import { fileURLToPath } from 'node:url';

const bundle = await build({ entryPoints: [fileURLToPath(new URL('../fixtures/contact-runtime/worker.js', import.meta.url))],
  bundle: true, write: false, format: 'esm', platform: 'browser', target: 'es2022' });
const siteverifyBundle = await build({ entryPoints: [fileURLToPath(new URL('../fixtures/contact-runtime/siteverify-worker.js', import.meta.url))],
  bundle: true, write: false, format: 'esm', platform: 'browser', target: 'es2022' });
const origin = 'https://contact.example.test';
const payload = { name: 'Synthetic visitor', email: 'visitor@example.test', message: '<script>Literal synthetic text</script>', locale: 'en', turnstileToken: 'synthetic-token' };

test('Siteverify uses native workerd fetch and reports intercepted HTTP/JSON outcomes without external access', async t => {
  let mode = 'verified';
  let attempts = 0;
  const mf = new Miniflare(convertV4MiniflareOptions({ workers: [{ name: 'siteverify-transport-test', modules: true,
    compatibilityDate: '2026-10-17', script: siteverifyBundle.outputFiles[0].text,
    outboundService: async request => {
      attempts++;
      assert.equal(request.url, 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
      assert.equal(request.method, 'POST');
      const body = new URLSearchParams(await request.text());
      assert.equal(body.get('secret'), 'synthetic-secret');
      assert.equal(body.get('response'), 'synthetic-token');
      if (mode === 'http') return new Response('PRIVATE', { status: 502 });
      if (mode === 'redirect') return new Response(null, { status: 302, headers: { Location: 'https://other.example.test/PRIVATE' } });
      if (mode === 'invalid_json') return new Response('<html>PRIVATE</html>');
      return Response.json({ success: true, hostname: 'contact.example.test', action: 'contact' });
    },
  }] }));
  t.after(() => mf.dispose());
  for (mode of ['verified', 'http', 'invalid_json', 'redirect']) {
    const response = await mf.dispatchFetch(origin + '/verify');
    const result = await response.json();
    assert.equal(response.status, mode === 'verified' ? 200 : 503, JSON.stringify({ result, attempts }));
    assert.equal(result.diagnostic.reason, mode === 'redirect' ? 'http' : mode);
    if (mode === 'redirect') assert.equal(result.diagnostic.httpStatus, 302);
    assert.doesNotMatch(JSON.stringify(result), /PRIVATE|synthetic-secret|synthetic-token/);
  }
  assert.equal(attempts, 4, 'one intercepted request per verification, no retries or redirects');
});
async function setup(t, overrides = {}) {
  let outboundAttempts = 0;
  const mf = new Miniflare(convertV4MiniflareOptions({ workers: [{ name: 'contact-runtime-test', modules: true,
    compatibilityDate: '2026-10-17', script: bundle.outputFiles[0].text,
    bindings: { CONTACT_FORM_ENABLED: 'true', CONTACT_ORIGIN: origin, CONTACT_FROM_EMAIL: 'contact@forms.alexcuadra.dev',
      CONTACT_TO_EMAIL: 'synthetic-recipient@example.test', TURNSTILE_SECRET_KEY: 'synthetic-turnstile-secret',
      CONTACT_DEDUP_SECRET: 'synthetic-dedup-secret-at-least-32-characters', ...overrides },
    durableObjects: { CONTACT_COORDINATOR: { className: 'ContactCoordinator', useSQLite: true } },
    ratelimits: { CONTACT_RATE_LIMITER: { simple: { limit: 5, period: 60 }, namespace_id: '1' } },
    // Native local email simulator, no remoteProxyConnectionString or credentials.
    email: { send_email: [{ name: 'CONTACT_EMAIL', allowed_sender_addresses: ['contact@forms.alexcuadra.dev'] }] },
    outboundService: () => { outboundAttempts++; return new Response('External network disabled in this test', { status: 503 }); },
  }] }));
  t.after(() => mf.dispose());
  t.after(() => assert.equal(outboundAttempts, 0, 'Test must not attempt external network access'));
  return {
    send: (value = payload, ip = '192.0.2.1', headers = {}) => mf.dispatchFetch(origin + '/api/contact', {
      method: 'POST', headers: { origin, 'content-type': 'application/json', 'cf-connecting-ip': ip, ...headers }, body: JSON.stringify(value),
    }),
    stats: async () => (await mf.dispatchFetch(origin + '/__test/stats')).json(),
  };
}

test('runtime accepts one identical concurrent message across IPs using native local email and SQLite', async t => {
  const ctx = await setup(t);
  const responses = await Promise.all(Array.from({ length: 8 }, (_, i) => ctx.send({ ...payload, turnstileToken: `fresh-token-${i}` }, `192.0.2.${i + 1}`)));
  assert.equal(responses.filter(response => response.status === 202).length, 1);
  assert.equal(responses.filter(response => response.status === 503).length, 7);
  for (const response of responses) {
    assert.equal(response.headers.get('cache-control'), 'no-store');
    assert.doesNotMatch(await response.text(), /visitor@|synthetic-recipient|Literal synthetic|fresh-token|secret/);
  }
  const stats = await ctx.stats();
  assert.equal(stats.emailAttempts, 1); assert.equal(stats.messages.length, 1);
  assert.equal(stats.messages[0].text, `${payload.name}\n${payload.email}\n\n${payload.message}`);
  assert.equal(stats.messages[0].replyTo, payload.email);
  assert.doesNotMatch(stats.logs.join(''), /visitor@|synthetic-recipient|Literal synthetic|fresh-token|secret|192\.0\.2/);
});

test('runtime limits global sends across IPs before the native email simulator', async t => {
  const ctx = await setup(t);
  for (let i = 0; i < 20; i++) assert.equal((await ctx.send({ ...payload, message: `Unique message ${i}` }, `192.0.2.${i + 1}`)).status, 202);
  const response = await ctx.send({ ...payload, message: 'Exceeds global quota' }, '192.0.2.100');
  assert.equal(response.status, 429); assert.equal(response.headers.get('retry-after'), '60');
  assert.equal((await ctx.stats()).emailAttempts, 20);
});

test('runtime preserves reservation after acceptance followed by provider acknowledgement failure', async t => {
  const ctx = await setup(t, { TEST_DELIVERY_UNKNOWN: 'true' });
  const first = await ctx.send();
  assert.equal((await first.json()).code, 'CONTACT_DELIVERY_UNKNOWN');
  const retry = await ctx.send({ ...payload, turnstileToken: 'another-token' }, '192.0.2.2');
  assert.equal((await retry.json()).code, 'CONTACT_DELIVERY_UNKNOWN');
  assert.equal((await ctx.stats()).emailAttempts, 1);
});

test('runtime native IP limiter blocks before another challenge', async t => {
  const ctx = await setup(t);
  for (let i = 0; i < 5; i++) await ctx.send({ ...payload, message: `Message ${i}` });
  assert.equal((await ctx.send({ ...payload, message: 'Sixth attempt' })).status, 429);
  const stats = await ctx.stats();
  assert.equal(stats.challenges, 5); assert.equal(stats.emailAttempts, 5);
});

test('disabled endpoint, rejected origin/input and failed challenge never reach email', async t => {
  const disabled = await setup(t, { CONTACT_FORM_ENABLED: 'false' });
  assert.equal((await disabled.send()).status, 404);
  assert.equal((await disabled.stats()).challenges, 0);
  const rejected = await setup(t, { TEST_CHALLENGE_FAILURE: 'true' });
  assert.equal((await rejected.send(payload, '192.0.2.1', { origin: 'https://other.example.test' })).status, 403);
  assert.equal((await rejected.send({ ...payload, to: 'other@example.test' })).status, 400);
  assert.equal((await rejected.send()).status, 403);
  const stats = await rejected.stats();
  assert.equal(stats.challenges, 1); assert.equal(stats.emailAttempts, 0);
});

test('native runtime rejects malformed text before challenge and sends valid Unicode as plain text only', async t => {
  const ctx = await setup(t);
  for (const changes of [{ name: 'Visitor\n' }, { message: 'hidden\u0085control' }, { message: 'broken\ud800' }]) {
    const response = await ctx.send({ ...payload, ...changes });
    assert.equal(response.status, 400);
  }
  const before = await ctx.stats();
  assert.equal(before.challenges, 0);
  assert.equal(before.emailAttempts, 0);
  const response = await ctx.send({ ...payload, name: 'Jose\u0301 👩🏽‍💻', message: '<script>literal()</script>\u2028Bcc: other@example.test' });
  assert.equal(response.status, 202);
  const after = await ctx.stats();
  assert.equal(after.emailAttempts, 1);
  assert.equal(after.messages[0].html, undefined);
  assert.equal(after.messages[0].bcc, undefined);
  assert.equal(after.messages[0].text, 'José 👩🏽‍💻\nvisitor@example.test\n\n<script>literal()</script>\nBcc: other@example.test');
  assert.doesNotMatch(after.logs.join(''), /literal\(\)|other@example|José|visitor@/);
});
