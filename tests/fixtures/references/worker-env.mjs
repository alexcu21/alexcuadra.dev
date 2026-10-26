import { scenario } from './scenario.mjs';
// Synthetic environment only; no secrets, email binding or real send endpoint.
export const env = { get CONTACT_FORM_ENABLED() { return scenario.getStore() === 'contact-enabled' ? 'true' : 'false'; },
  TURNSTILE_SITE_KEY: 'test-only' };
