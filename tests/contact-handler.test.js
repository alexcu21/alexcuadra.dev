import { test } from 'node:test';
import assert from 'node:assert/strict';
import { handleContactRequest } from '../src/data/contact-handler.js';

const origin = 'https://portfolio.example.test';
const input = { name: 'Visitor', email: 'visitor@example.test', message: '<script>Literal message</script>', locale: 'en', turnstileToken: 'private-token' };
const req = (value = input, headers = {}, method = 'POST') => new Request(origin + '/api/contact', {
  method, ...(method === 'POST' ? { body: JSON.stringify(value) } : {}),
  headers: { origin, 'content-type': 'application/json', 'cf-connecting-ip': '192.0.2.1', ...headers },
});
function setup() {
  const messages = [], logs = [], calls = [];
  const env = { CONTACT_FORM_ENABLED: 'true', CONTACT_ORIGIN: origin,
    CONTACT_TO_EMAIL: 'personal-recipient@example.test', CONTACT_FROM_EMAIL: 'contact@forms.alexcuadra.dev',
    TURNSTILE_SECRET_KEY: 'private-secret',
    CONTACT_DEDUP_SECRET: 'test-only-secret-with-at-least-32-characters',
    CONTACT_COORDINATOR: { idFromName: name => name, get: () => ({ async fetch() { calls.push('reserve'); return new Response(null, { status: 201 }); } }) },
    CONTACT_EMAIL: { async send(message) { calls.push('email'); messages.push(message); return { messageId: 'internal-id' }; } },
    CONTACT_RATE_LIMITER: { async limit() { calls.push('limit'); return { success: true }; } },
  };
  const options = { log: value => logs.push(value), fetch: async () => {
    calls.push('turnstile'); return Response.json({ success: true, hostname: new URL(origin).hostname, action: 'contact' });
  } };
  return { env, options, messages, logs, calls };
}
test('contact is disabled by default with no email, challenge or CMS activity', async () => {
  const ctx = setup();
  const result = await handleContactRequest(req(), {}, ctx.options);
  assert.equal(result.status, 404);
  assert.deepEqual(ctx.calls, []);
  assert.equal(result.headers.get('cache-control'), 'no-store');
});
test('accepted contact uses fixed delivery addresses and text-only structured fields', async () => {
  const ctx = setup();
  const result = await handleContactRequest(req(), ctx.env, ctx.options);
  assert.equal(result.status, 202);
  assert.deepEqual(ctx.calls, ['limit', 'turnstile', 'reserve', 'email']);
  assert.deepEqual(ctx.messages[0], { to: ctx.env.CONTACT_TO_EMAIL, from: ctx.env.CONTACT_FROM_EMAIL,
    replyTo: input.email, subject: 'Portfolio contact', text: `Visitor\nvisitor@example.test\n\n${input.message}` });
  for (const value of [await result.text(), ...ctx.logs]) {
    assert.doesNotMatch(value, /personal-recipient|visitor@|Literal message|private-token|private-secret|internal-id|192\.0\.2\.1/);
  }
});
test('missing bindings, invalid config and methods never invoke delivery', async () => {
  for (const changes of [{ CONTACT_EMAIL: undefined }, { CONTACT_RATE_LIMITER: undefined },
    { CONTACT_COORDINATOR: undefined }, { CONTACT_DEDUP_SECRET: 'short' },
    { TURNSTILE_SECRET_KEY: '' }, { CONTACT_TO_EMAIL: 'attacker\r\nBcc: other' },
    { CONTACT_FROM_EMAIL: 'someone@other.test' }, { CONTACT_ORIGIN: 'invalid' }]) {
    const ctx = setup();
    assert.equal((await handleContactRequest(req(), { ...ctx.env, ...changes }, ctx.options)).status, 503);
    assert.deepEqual(ctx.calls, []);
  }
  const ctx = setup();
  const response = await handleContactRequest(req(undefined, {}, 'GET'), ctx.env, ctx.options);
  assert.equal(response.status, 405); assert.equal(response.headers.get('allow'), 'POST');
});
test('origin and media checks happen before limiter and challenge', async () => {
  for (const headers of [{ origin: 'https://attacker.test' }, { 'content-type': 'text/plain' }, { 'cf-connecting-ip': '' }]) {
    const ctx = setup();
    assert.ok((await handleContactRequest(req(input, headers), ctx.env, ctx.options)).status >= 400);
    assert.deepEqual(ctx.calls, []);
  }
});
test('rate limiting fails closed and does not contact Siteverify or email provider', async () => {
  for (const unavailable of [false, true]) {
    const ctx = setup();
    ctx.env.CONTACT_RATE_LIMITER.limit = async () => { if (unavailable) throw Error('private'); return { success: false }; };
    const response = await handleContactRequest(req(), ctx.env, ctx.options);
    assert.equal(response.status, unavailable ? 503 : 429);
    if (!unavailable) assert.equal(response.headers.get('retry-after'), '60');
    assert.deepEqual(ctx.calls, []);
  }
});
test('invalid input and challenge failure prevent email sending', async () => {
  const ctx = setup();
  assert.equal((await handleContactRequest(req({ ...input, to: 'attacker@example.test' }), ctx.env, ctx.options)).status, 400);
  ctx.options.fetch = async () => Response.json({ success: false, 'error-codes': ['timeout-or-duplicate'] });
  assert.equal((await handleContactRequest(req(), ctx.env, ctx.options)).status, 403);
  assert.equal(ctx.messages.length, 0);
});
test('delivery failure is redacted and never automatically retried', async () => {
  const ctx = setup(); let attempts = 0;
  ctx.env.CONTACT_EMAIL.send = async () => { attempts++; throw Error('SECRET_PROVIDER_ERROR personal-recipient@example.test'); };
  const response = await handleContactRequest(req(), ctx.env, ctx.options);
  assert.equal(response.status, 503); assert.equal(attempts, 1);
  const body = await response.text();
  assert.equal(JSON.parse(body).code, 'CONTACT_DELIVERY_UNKNOWN');
  assert.doesNotMatch(body + ctx.logs.join(''), /SECRET_PROVIDER_ERROR|personal-recipient/);
});
test('delivery timeout returns an uncertain result without retrying the provider', async t => {
  const ctx = setup();
  t.mock.timers.enable({ apis: ['setTimeout'] });
  let began;
  const ready = new Promise(resolve => { began = resolve; });
  ctx.env.CONTACT_EMAIL.send = () => { began(); return new Promise(() => {}); };
  const pending = handleContactRequest(req(), ctx.env, ctx.options);
  await ready;
  t.mock.timers.tick(10000);
  const response = await pending;
  assert.equal(response.status, 503);
  assert.equal((await response.json()).code, 'CONTACT_DELIVERY_UNKNOWN');
  assert.equal(JSON.parse(ctx.logs[0]).stage, 'delivery');
  assert.equal(JSON.parse(ctx.logs[0]).deliveryStatus, 'unknown');
  assert.equal(JSON.parse(ctx.logs[0]).providerCode, undefined);
});

test('coordinator duplicates, global quota and unavailability never reach email', async () => {
  for (const [upstream, expected, code] of [[409, 503, 'CONTACT_DELIVERY_UNKNOWN'], [429, 429, 'CONTACT_RATE_LIMITED'], [500, 503, 'CONTACT_LIMIT_UNAVAILABLE']]) {
    const ctx = setup();
    ctx.env.CONTACT_COORDINATOR.get = () => ({ fetch: async () => new Response(null, { status: upstream }) });
    const result = await handleContactRequest(req(), ctx.env, ctx.options);
    assert.equal(result.status, expected); assert.equal((await result.json()).code, code);
    assert.equal(ctx.messages.length, 0);
  }
});

test('content HMAC deduplicates fresh tokens and never forwards PII to storage', async () => {
  const ctx = setup(), fingerprints = new Set();
  ctx.env.CONTACT_COORDINATOR.get = () => ({ async fetch(url, options) {
    assert.equal(url, 'https://contact.internal/reserve');
    assert.match(options.body, /^[a-f0-9]{64}$/);
    const duplicate = fingerprints.has(options.body); fingerprints.add(options.body);
    return new Response(null, { status: duplicate ? 409 : 201 });
  } });
  assert.equal((await handleContactRequest(req(), ctx.env, ctx.options)).status, 202);
  const retry = await handleContactRequest(req({ ...input, turnstileToken: 'fresh-token' }), ctx.env, ctx.options);
  assert.equal((await retry.json()).code, 'CONTACT_DELIVERY_UNKNOWN');
  assert.equal(ctx.messages.length, 1);
  assert.equal(fingerprints.size, 1);
});

test('coordinator transport failure fails closed', async () => {
  const ctx = setup();
  ctx.env.CONTACT_COORDINATOR.get = () => ({ async fetch() { throw Error('private'); } });
  assert.equal((await handleContactRequest(req(), ctx.env, ctx.options)).status, 503);
  assert.equal(ctx.messages.length, 0);
});

test('contact diagnostics distinguish failure stages without claiming a send was attempted', async () => {
  const cases = [
    ['configuration', ctx => { ctx.env.CONTACT_EMAIL = undefined; }],
    ['request', () => {}, { origin: 'https://other.test' }],
    ['rate_limit', ctx => { ctx.env.CONTACT_RATE_LIMITER.limit = async () => ({ success: false }); }],
    ['validation', () => {}, {}, { ...input, to: 'other@example.test' }],
    ['challenge', ctx => { ctx.options.fetch = async () => Response.json({ success: false }); }],
    ['reservation', ctx => { ctx.env.CONTACT_COORDINATOR.get = () => ({ fetch: async () => new Response(null, { status: 409 }) }); }],
  ];
  for (const [stage, configure, headers, payload] of cases) {
    const ctx = setup(); configure(ctx);
    const result = await handleContactRequest(req(payload, headers), ctx.env, ctx.options);
    assert.ok(result.status >= 400);
    const event = JSON.parse(ctx.logs[0]);
    assert.equal(event.stage, stage);
    assert.equal(event.deliveryStatus, 'not_attempted');
    assert.equal(event.providerCode, undefined);
    assert.equal(ctx.messages.length, 0);
  }
});

test('provider diagnostics allow only fixed error codes and preserve the uncertain public response', async () => {
  for (const code of ['E_SENDER_NOT_VERIFIED', 'E_RECIPIENT_SUPPRESSED', 'E_RATE_LIMIT_EXCEEDED',
    'PRIVATE recipient@example.test', { secret: 'PRIVATE' }, undefined]) {
    const ctx = setup(); let attempts = 0;
    ctx.env.CONTACT_EMAIL.send = async () => {
      attempts++;
      throw Object.assign(new Error('PRIVATE provider body recipient@example.test'), { code, response: { token: 'PRIVATE' } });
    };
    const result = await handleContactRequest(req(), ctx.env, ctx.options);
    const body = await result.text();
    const event = JSON.parse(ctx.logs[0]);
    assert.equal(result.status, 503);
    assert.equal(event.stage, 'delivery');
    assert.equal(event.deliveryStatus, 'unknown');
    assert.equal(event.providerCode, typeof code === 'string' && code.startsWith('E_') ? code : 'OTHER');
    assert.equal(JSON.parse(body).code, 'CONTACT_DELIVERY_UNKNOWN');
    assert.equal(attempts, 1);
    assert.doesNotMatch(body + ctx.logs.join(''), /PRIVATE|recipient@|private-token|private-secret/);
    assert.equal(JSON.parse(body).providerCode, undefined);
  }
});

test('provider error property failures cannot interrupt redacted diagnostics', async () => {
  const ctx = setup();
  ctx.env.CONTACT_EMAIL.send = async () => { throw Object.defineProperty(new Error('PRIVATE'), 'code', { get() { throw Error('PRIVATE getter'); } }); };
  assert.equal((await handleContactRequest(req(), ctx.env, ctx.options)).status, 503);
  assert.equal(JSON.parse(ctx.logs[0]).providerCode, 'OTHER');
  assert.doesNotMatch(ctx.logs.join(''), /PRIVATE/);
  let reads = 0;
  ctx.env.CONTACT_EMAIL.send = async () => { throw Object.defineProperty(new Error('PRIVATE'), 'code', {
    get() { return ++reads === 1 ? 'E_INTERNAL_SERVER_ERROR' : 'PRIVATE address@example.test'; },
  }); };
  assert.equal((await handleContactRequest(req(), ctx.env, ctx.options)).status, 503);
  assert.equal(reads, 1);
  assert.equal(JSON.parse(ctx.logs[1]).providerCode, 'E_INTERNAL_SERVER_ERROR');
  assert.doesNotMatch(ctx.logs.join(''), /PRIVATE|address@/);
});

test('acceptance diagnostics do not claim delivery and optional logger failure cannot change success', async () => {
  const ctx = setup();
  assert.equal((await handleContactRequest(req(), ctx.env, ctx.options)).status, 202);
  const event = JSON.parse(ctx.logs[0]);
  assert.equal(event.stage, 'delivery');
  assert.equal(event.deliveryStatus, 'accepted');
  assert.equal(event.providerCode, undefined);
  assert.doesNotMatch(ctx.logs.join(''), /internal-id|visitor@|personal-recipient/);
  ctx.options.log = () => { throw Error('log unavailable'); };
  assert.equal((await handleContactRequest(req(), ctx.env, ctx.options)).status, 202);
});
