export const CONTACT_BODY_LIMIT = 16384;

export class ContactError extends Error {
  constructor(code, status) { super(code); this.name = 'ContactError'; this.code = code; this.status = status; }
}
const fail = (code, status = 400) => { throw new ContactError(code, status); };

function text(value, max, multiline = false) {
  if (typeof value !== 'string') fail('CONTACT_INVALID_FIELDS');
  const normalized = value.normalize('NFC').replace(/\r\n?/g, '\n').trim();
  if (!normalized || normalized.length > max || (multiline ? /[\u0000-\u0008\u000b\u000c\u000e-\u001f\u007f]/ : /[\u0000-\u001f\u007f]/).test(normalized)) {
    fail('CONTACT_INVALID_FIELDS');
  }
  return normalized;
}

export function validateContactInput(input) {
  if (!input || typeof input !== 'object' || Array.isArray(input)
    || Object.keys(input).some(key => !['name', 'email', 'message', 'locale', 'turnstileToken'].includes(key))) fail('CONTACT_INVALID_FIELDS');
  const name = text(input.name, 100);
  const email = text(input.email, 254);
  const message = text(input.message, 5000, true);
  const parts = email.split('@');
  // A conservative public-form mailbox policy; no display names or header syntax.
  const local = parts[0];
  const labels = parts[1]?.split('.') || [];
  if (parts.length !== 2 || local.length > 64 || !/^[a-z0-9.!#$%&'*+/=?^_`{|}~-]+$/i.test(local)
    || local.startsWith('.') || local.endsWith('.') || local.includes('..') || labels.length < 2
    || labels.some(label => !/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i.test(label))) fail('CONTACT_INVALID_EMAIL');
  if (!['en', 'es'].includes(input.locale)) fail('CONTACT_INVALID_FIELDS');
  const turnstileToken = text(input.turnstileToken, 2048);
  return { name, email, message, locale: input.locale, turnstileToken };
}

export function assertContactRequestHeaders(request, expectedOrigin) {
  if (request.method !== 'POST') fail('CONTACT_METHOD', 405);
  // The endpoint must supply its configured origin, never derive this policy from a caller header.
  try {
    if (!expectedOrigin || new URL(expectedOrigin).origin !== expectedOrigin) fail('CONTACT_CONFIG', 503);
  } catch { fail('CONTACT_CONFIG', 503); }
  if (request.headers.get('origin') !== expectedOrigin || new URL(request.url).origin !== expectedOrigin) fail('CONTACT_ORIGIN', 403);
  if (request.headers.get('content-type')?.split(';')[0].trim().toLowerCase() !== 'application/json') fail('CONTACT_MEDIA_TYPE', 415);
  const declared = request.headers.get('content-length');
  if (declared && (!/^\d+$/.test(declared) || Number(declared) > CONTACT_BODY_LIMIT)) fail('CONTACT_BODY_TOO_LARGE', 413);
}

export async function readContactRequest(request, expectedOrigin) {
  assertContactRequestHeaders(request, expectedOrigin);
  if (!request.body) fail('CONTACT_INVALID_FIELDS');
  const reader = request.body.getReader();
  const decoder = new TextDecoder('utf-8', { fatal: true });
  let size = 0;
  let body = '';
  let timedOut = false;
  const timer = setTimeout(() => { timedOut = true; void reader.cancel().catch(() => {}); }, 5000);
  try {
    while (true) {
      const { done, value } = await reader.read();
      if (timedOut) fail('CONTACT_BODY_TIMEOUT', 408);
      if (done) break;
      size += value.byteLength;
      if (size > CONTACT_BODY_LIMIT) { await reader.cancel(); fail('CONTACT_BODY_TOO_LARGE', 413); }
      body += decoder.decode(value, { stream: true });
    }
    body += decoder.decode();
    return validateContactInput(JSON.parse(body));
  } catch (error) {
    if (error instanceof ContactError) throw error;
    fail('CONTACT_INVALID_FIELDS');
  } finally { clearTimeout(timer); reader.releaseLock(); }
}

export async function verifyContactTurnstile(token, { secret, hostname, fetch: transport = globalThis.fetch } = {}) {
  if (typeof secret !== 'string' || !secret.trim() || typeof hostname !== 'string' || !hostname.trim()) fail('CONTACT_CONFIG', 503);
  const checkedToken = text(token, 2048);
  let result;
  try {
    const response = await transport('https://challenges.cloudflare.com/turnstile/v0/siteverify', {
      method: 'POST', redirect: 'error',
      body: new URLSearchParams({ secret, response: checkedToken }), signal: AbortSignal.timeout(5000),
    });
    if (!response.ok) fail('CONTACT_CHALLENGE_UNAVAILABLE', 503);
    result = await response.json();
  } catch { fail('CONTACT_CHALLENGE_UNAVAILABLE', 503); }
  if (result?.success !== true || result.hostname !== hostname || result.action !== 'contact') fail('CONTACT_CHALLENGE_REJECTED', 403);
  return true;
}
