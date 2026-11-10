// @ts-check
import { defineConfig } from 'astro/config';
import dotenv from 'dotenv';
import tailwindcss from '@tailwindcss/vite';
import cloudflare from '@astrojs/cloudflare';
import { contentSecurityPolicy } from './src/data/content-security-policy.js';
import { fileURLToPath } from 'node:url';

dotenv.config();

export default defineConfig({
  site: 'https://alexcuadra.dev',
  output: 'server',
  session: false,
  security: { csp: contentSecurityPolicy },
  adapter: cloudflare({ imageService: 'compile' }),
  integrations: [{
    name: 'isolated-vite-command-cache',
    hooks: {
      'astro:config:setup': ({ command, updateConfig }) => {
        // Build/type generation must not replace a running dev server's SSR dependencies.
        updateConfig({ vite: { cacheDir: fileURLToPath(new URL(`./node_modules/.vite/${command}/`, import.meta.url)) } });
      },
    },
  }],
  vite: { plugins: [tailwindcss()] },
});
