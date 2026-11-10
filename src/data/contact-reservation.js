import { ContactError } from './contact-security.js';

export async function reserveContact(input, env) {
  try {
    const key = await crypto.subtle.importKey('raw', new TextEncoder().encode(env.CONTACT_DEDUP_SECRET),
      { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);
    // Token excluded so a fresh challenge cannot bypass duplicate detection.
    const content = JSON.stringify([input.name, input.email, input.message, input.locale]);
    const signature = await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(content));
    const fingerprint = Array.from(new Uint8Array(signature), byte => byte.toString(16).padStart(2, '0')).join('');
    const id = env.CONTACT_COORDINATOR.idFromName('contact-global-v1');
    const result = await env.CONTACT_COORDINATOR.get(id).fetch('https://contact.internal/reserve', {
      method: 'POST', body: fingerprint, signal: AbortSignal.timeout(5000),
    });
    if (result.status === 409) throw new ContactError('CONTACT_DELIVERY_UNKNOWN', 503);
    if (result.status === 429) throw new ContactError('CONTACT_RATE_LIMITED', 429);
    if (result.status !== 201) throw new ContactError('CONTACT_LIMIT_UNAVAILABLE', 503);
  } catch (error) {
    if (error instanceof ContactError) throw error;
    throw new ContactError('CONTACT_LIMIT_UNAVAILABLE', 503);
  }
}
