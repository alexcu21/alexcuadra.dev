import { verifyContactTurnstile } from '../../../src/data/contact-security.js';

// Exercise workerd's actual fetch/URLSearchParams/AbortSignal implementation.
// The integration test intercepts all outbound requests; no real credentials.
export default {
  async fetch() {
    let diagnostic;
    try {
      await verifyContactTurnstile('synthetic-token', { secret: 'synthetic-secret', hostname: 'contact.example.test',
        diagnose: value => { diagnostic = value; } });
      return Response.json({ verified: true, diagnostic });
    } catch (error) {
      return Response.json({ verified: false, code: error.code, diagnostic }, { status: error.status || 500 });
    }
  },
};
