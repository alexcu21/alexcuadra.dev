import { defineConfig } from 'astro/config';
import tailwindcss from '@tailwindcss/vite';
import { contentSecurityPolicy } from '../../../src/data/content-security-policy.js';

export default defineConfig({
  vite: { plugins: [tailwindcss()] },
  security: { csp: contentSecurityPolicy },
  devToolbar: { enabled: false },
  server: { host: '127.0.0.1', port: 4333 },
});
