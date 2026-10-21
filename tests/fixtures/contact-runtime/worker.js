// Test-only entrypoint. Never included by src/worker.js or public Astro routes.
import { handleContactRequest } from '../../../src/data/contact-handler.js';
export { ContactCoordinator } from '../../../src/data/contact-coordinator.js';

const stats = { challenges: 0, emailAttempts: 0, messages: [], logs: [] };
export default {
  async fetch(request, env) {
    if (new URL(request.url).pathname === '/__test/stats') return Response.json(stats);
    const runtime = { ...env, CONTACT_EMAIL: { async send(message) {
      stats.emailAttempts++;
      const result = await env.CONTACT_EMAIL.send(message);
      stats.messages.push(message);
      if (env.TEST_DELIVERY_UNKNOWN === 'true') throw Error('Simulated lost provider acknowledgement');
      return result;
    } } };
    return handleContactRequest(request, runtime, {
      log: value => stats.logs.push(value),
      fetch: async url => {
        if (url !== 'https://challenges.cloudflare.com/turnstile/v0/siteverify') throw Error('Unexpected egress');
        stats.challenges++;
        return Response.json({ success: env.TEST_CHALLENGE_FAILURE !== 'true', hostname: 'contact.example.test', action: 'contact' });
      },
    });
  },
};
