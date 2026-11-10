import { fileURLToPath } from 'node:url';
import { defineConfig } from 'astro/config';
import cloudflare from '@astrojs/cloudflare';
import local from './astro.emdash-preview.config.mjs';

export default defineConfig({
  ...local,
  outDir: './public-candidate-dist',
  adapter: cloudflare({ configPath: './wrangler.emdash-public.jsonc', remoteBindings: false, imageService: 'compile' }),
  vite: {
    ...local.vite,
    plugins: [...local.vite.plugins, {
      name: 'catalog-public-middleware',
      enforce: 'pre',
      load(id) {
        if (id === fileURLToPath(new URL('./src/middleware.ts', import.meta.url))) {
          return 'export const onRequest = (_context, next) => next();';
        }
      },
    }],
  },
});
