import { ContactError, assertContactRequestHeaders, readContactRequest, validateContactInput, verifyContactTurnstile } from './contact-security.js';
import { reserveContact } from './contact-reservation.js';

// Only documented fixed codes may enter logs. Never log provider messages,
// response objects, addresses or arbitrary exception properties.
const providerCodes = new Set(['E_VALIDATION_ERROR', 'E_FIELD_MISSING', 'E_TOO_MANY_RECIPIENTS',
  'E_TOO_MANY_ATTACHMENTS', 'E_SENDER_NOT_VERIFIED', 'E_RECIPIENT_NOT_ALLOWED', 'E_RECIPIENT_SUPPRESSED',
  'E_ACCOUNT_SUSPENDED', 'E_SENDER_DOMAIN_NOT_AVAILABLE', 'E_CONTENT_TOO_LARGE', 'E_DELIVERY_FAILED',
  'E_RATE_LIMIT_EXCEEDED', 'E_DAILY_LIMIT_EXCEEDED', 'E_INTERNAL_SERVER_ERROR', 'E_HEADER_NOT_ALLOWED',
  'E_HEADER_USE_API_FIELD', 'E_HEADER_VALUE_INVALID', 'E_HEADER_VALUE_TOO_LONG', 'E_HEADER_NAME_INVALID',
  'E_HEADERS_TOO_LARGE', 'E_HEADERS_TOO_MANY']);
function providerCode(error) {
  try {
    const code = error?.code;
    return typeof code === 'string' && providerCodes.has(code) ? code : 'OTHER';
  }
  catch { return 'OTHER'; }
}

function mailbox(value) {
  try {
    return validateContactInput({ name: 'config', email: value, message: 'config', locale: 'en', turnstileToken: 'config' }).email;
  } catch { throw new ContactError('CONTACT_CONFIG', 503); }
}

function contactConfig(env) {
  const to = mailbox(env.CONTACT_TO_EMAIL);
  const from = mailbox(env.CONTACT_FROM_EMAIL);
  if (!from.endsWith('@forms.alexcuadra.dev') || !env.TURNSTILE_SECRET_KEY?.trim()
    || typeof env.CONTACT_EMAIL?.send !== 'function' || typeof env.CONTACT_RATE_LIMITER?.limit !== 'function'
    || typeof env.CONTACT_DEDUP_SECRET !== 'string' || env.CONTACT_DEDUP_SECRET.length < 32
    || typeof env.CONTACT_COORDINATOR?.idFromName !== 'function' || typeof env.CONTACT_COORDINATOR?.get !== 'function') {
    throw new ContactError('CONTACT_CONFIG', 503);
  }
  let hostname;
  try { hostname = new URL(env.CONTACT_ORIGIN).hostname; }
  catch { throw new ContactError('CONTACT_CONFIG', 503); }
  return { to, from, hostname };
}

function response(status, code, requestId) {
  const headers = { 'Cache-Control': 'no-store', 'X-Content-Type-Options': 'nosniff',
    'X-Robots-Tag': 'noindex', 'X-Request-ID': requestId };
  if (status === 405) headers.Allow = 'POST';
  if (status === 429) headers['Retry-After'] = '60';
  return Response.json({ ok: status === 202, code, requestId }, { status, headers });
}

async function sendWithDeadline(binding, message) {
  let timer;
  try {
    const result = await Promise.race([binding.send(message), new Promise((_, reject) => {
      timer = setTimeout(() => reject(new ContactError('CONTACT_DELIVERY_UNKNOWN', 503)), 10000);
    })]);
    if (!result || typeof result.messageId !== 'string' || !result.messageId) throw new ContactError('CONTACT_DELIVERY_UNKNOWN', 503);
  } finally { clearTimeout(timer); }
}

// No GraphQL, visitor credentials, remote binding defaults or automatic email retries.
export async function handleContactRequest(request, env, { fetch: transport = globalThis.fetch, log = console.info, requestId = String(crypto.randomUUID()) } = {}) {
  if (env.CONTACT_FORM_ENABLED !== 'true') return response(404, 'CONTACT_DISABLED', requestId);
  const started = Date.now();
  let status = 202;
  let code = 'CONTACT_ACCEPTED';
  let stage = 'request';
  let deliveryStatus = 'not_attempted';
  let emailProviderCode;
  let siteverify;
  try {
    if (request.method !== 'POST') throw new ContactError('CONTACT_METHOD', 405);
    stage = 'configuration';
    const config = contactConfig(env);
    stage = 'request';
    assertContactRequestHeaders(request, env.CONTACT_ORIGIN);
    // Cloudflare supplies this header at its trusted edge. No fallback to caller X-Forwarded-For.
    const ip = request.headers.get('cf-connecting-ip');
    if (!ip || ip.length > 64 || !/^[0-9a-f:.]+$/i.test(ip)) throw new ContactError('CONTACT_CLIENT_UNAVAILABLE', 503);
    stage = 'rate_limit';
    let allowed;
    try { allowed = await env.CONTACT_RATE_LIMITER.limit({ key: `contact:${env.CONTACT_ORIGIN}:${ip}` }); }
    catch { throw new ContactError('CONTACT_LIMIT_UNAVAILABLE', 503); }
    if (allowed?.success !== true) throw new ContactError('CONTACT_RATE_LIMITED', 429);
    stage = 'validation';
    const input = await readContactRequest(request, env.CONTACT_ORIGIN);
    stage = 'challenge';
    await verifyContactTurnstile(input.turnstileToken, { secret: env.TURNSTILE_SECRET_KEY, hostname: config.hostname,
      fetch: transport, diagnose: result => { siteverify = result; } });
    stage = 'reservation';
    await reserveContact(input, env);
    stage = 'delivery';
    deliveryStatus = 'unknown';
    try {
      await sendWithDeadline(env.CONTACT_EMAIL, {
        to: config.to, from: config.from, replyTo: input.email,
        subject: 'Portfolio contact', text: `${input.name}\n${input.email}\n\n${input.message}`,
      });
      deliveryStatus = 'accepted';
    } catch (error) {
      if (error instanceof ContactError) throw error;
      emailProviderCode = providerCode(error);
      // A rejected transport does not prove the provider failed before acceptance.
      throw new ContactError('CONTACT_DELIVERY_UNKNOWN', 503);
    }
  } catch (error) {
    status = error instanceof ContactError ? error.status : 503;
    code = error instanceof ContactError ? error.code : 'CONTACT_UNAVAILABLE';
  }
  // Deliberately never serialize the request, payload, provider response or exception.
  try { log(JSON.stringify({ event: 'contact_result', requestId, status, code, stage,
    deliveryStatus, ...(emailProviderCode ? { providerCode: emailProviderCode } : {}),
    ...(siteverify ? { siteverify } : {}),
    durationMs: Date.now() - started })); } catch { /* Logging is optional. */ }
  return response(status, code, requestId);
}
