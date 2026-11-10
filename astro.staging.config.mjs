import { fileURLToPath } from 'node:url';
import { defineConfig } from 'astro/config';
import cloudflare from '@astrojs/cloudflare';
import local from './astro.emdash-preview.config.mjs';

export default defineConfig({
  ...local,
  outDir: process.env.STAGING_CACHE_BUILD === '1' ? './cache-dist' : './staging-dist',
  vite: {
    ...local.vite,
    plugins: [...(local.vite?.plugins ?? []), {
      name: 'catalog-staging-middleware',
      enforce: 'pre',
      load(id) {
        if (id === fileURLToPath(new URL('./src/middleware.ts', import.meta.url))) {
          return "export { onRequest } from './staging-middleware.ts';";
        }
      },
    }],
  },
  adapter: cloudflare({ configPath: './wrangler.staging.jsonc', remoteBindings: false }),
});
