import { test } from 'node:test';
import assert from 'node:assert/strict';
import { validateContactInput, readContactRequest, verifyContactTurnstile, ContactError } from '../src/data/contact-security.js';

const payload = { name: ' Alex ', email: 'alex+portfolio@example.test', message: 'Hello\r\nWorld', locale: 'en', turnstileToken: 'synthetic-token' };
const origin = 'https://example.test';
const req = (body = JSON.stringify(payload), headers = {}, method = 'POST') => new Request(origin + '/api/contact', {
  method, ...(method === 'POST' ? { body } : {}), headers: { origin, 'content-type': 'application/json', ...headers },
});
const code = expected => error => error instanceof ContactError && error.code === expected;

test('contact input normalizes plain text without interpreting user markup as HTML', () => {
  const result = validateContactInput({ ...payload, message: '<script>literal text</script>\r\nA < B' });
  assert.equal(result.name, 'Alex');
  assert.equal(result.message, '<script>literal text</script>\nA < B');
  // Delivery must use provider text/plain, and any UI preview must escape this value.
});
test('contact validation rejects extra destinations, header injection and oversized fields', () => {
  for (const input of [{ ...payload, to: 'attacker@example.test' }, { ...payload, name: 'Alex\r\nBcc: attacker' },
    { ...payload, message: 'x'.repeat(5001) }, { ...payload, email: 'a@b.test\r\nBcc: attacker' },
    { ...payload, locale: 'ALL' }, { ...payload, turnstileToken: '' }, { ...payload, message: '\u0000' }]) {
    assert.throws(() => validateContactInput(input), ContactError);
  }
  for (const email of ['a@@b.test', 'a..b@example.test', '.a@example.test', 'a@-bad.test', 'a@localhost']) {
    assert.throws(() => validateContactInput({ ...payload, email }), code('CONTACT_INVALID_EMAIL'));
  }
});
test('request reader enforces method, configured same origin and media type', async () => {
  assert.equal((await readContactRequest(req(), origin)).message, 'Hello\nWorld');
  await assert.rejects(readContactRequest(req(undefined, {}, 'GET'), origin), code('CONTACT_METHOD'));
  for (const value of ['', 'https://attacker.test', 'null']) {
    await assert.rejects(readContactRequest(req(undefined, { origin: value }), origin), code('CONTACT_ORIGIN'));
  }
  await assert.rejects(readContactRequest(req(undefined, { 'content-type': 'text/plain' }), origin), code('CONTACT_MEDIA_TYPE'));
});
test('body size is enforced while reading even without or with a false Content-Length', async () => {
  for (const headers of [{}, { 'content-length': '1' }, { 'content-length': '999999' }]) {
    await assert.rejects(readContactRequest(req('x'.repeat(16385), headers), origin), code('CONTACT_BODY_TOO_LARGE'));
  }
  await assert.rejects(readContactRequest(req('{invalid'), origin), code('CONTACT_INVALID_FIELDS'));
  await assert.rejects(readContactRequest(req(new Uint8Array([255])), origin), code('CONTACT_INVALID_FIELDS'));
});
test('a stalled upload is cancelled within the body-reading deadline', async t => {
  t.mock.timers.enable({ apis: ['setTimeout'] });
  let cancelled = false;
  const stream = new ReadableStream({ cancel() { cancelled = true; } });
  const request = new Request(origin + '/api/contact', { method: 'POST', body: stream, duplex: 'half',
    headers: { origin, 'content-type': 'application/json' } });
  const pending = readContactRequest(request, origin);
  t.mock.timers.tick(5000);
  await assert.rejects(pending, code('CONTACT_BODY_TIMEOUT'));
  assert.equal(cancelled, true);
});
test('Turnstile validation requires success, configured hostname and contact action', async () => {
  const config = { secret: 'synthetic-secret', hostname: 'example.test' };
  for (const result of [{ success: false }, { success: true, hostname: 'other.test', action: 'contact' },
    { success: true, hostname: 'example.test', action: 'login' }, { success: true }]) {
    await assert.rejects(verifyContactTurnstile('token', { ...config, fetch: async () => Response.json(result) }), code('CONTACT_CHALLENGE_REJECTED'));
  }
  assert.equal(await verifyContactTurnstile('token', { ...config, fetch: async (url, options) => {
    assert.equal(url, 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
    assert.equal(options.body.get('response'), 'token');
    assert.equal(options.body.get('secret'), 'synthetic-secret');
    assert.equal(options.redirect, 'error');
    return Response.json({ success: true, hostname: 'example.test', action: 'contact' });
  } }), true);
});
test('challenge outages and missing secrets fail closed without exposing upstream details', async () => {
  await assert.rejects(verifyContactTurnstile('token', {}), code('CONTACT_CONFIG'));
  await assert.rejects(verifyContactTurnstile('token', { secret: 'secret', hostname: 'example.test',
    fetch: async () => { throw new Error('PRIVATE_UPSTREAM_DETAILS'); } }), error => {
    assert.equal(error.status, 503);
    assert.doesNotMatch(error.message, /PRIVATE_UPSTREAM_DETAILS/);
    return code('CONTACT_CHALLENGE_UNAVAILABLE')(error);
  });
});
