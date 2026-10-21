import { defineConfig } from 'astro/config';
import { fileURLToPath } from 'node:url';
const here = path => fileURLToPath(new URL(path, import.meta.url));
export default defineConfig({
  output: 'server',
  devToolbar: { enabled: false },
  integrations: [{ name: 'actual-reference-routes', hooks: { 'astro:config:setup': ({ injectRoute }) => {
    for (const [pattern, file] of [
      ['/articles', '../../../src/pages/articles.astro'],
      ['/blog', '../../../src/pages/blog.astro'],
      ['/blog/[slug]', '../../../src/pages/blog/[slug].astro'],
      ['/es/blog', '../../../src/pages/es/blog/index.astro'],
      ['/es/blog/[slug]', '../../../src/pages/es/blog/[slug].astro'],
      ['/lab', '../../../src/pages/lab.astro'],
      ['/lab/[slug]', '../../../src/pages/lab/[slug].astro'],
      ['/es/laboratorio', '../../../src/pages/es/laboratorio/index.astro'],
      ['/es/laboratorio/[slug]', '../../../src/pages/es/laboratorio/[slug].astro'],
      ['/articles/category/[...slug]', '../../../src/pages/articles/category/[...slug].astro'],
      ['/articles/category/[categorySlug]/[articleSlug]', '../../../src/pages/articles/category/[categorySlug]/[articleSlug].astro'],
    ]) injectRoute({ pattern, entrypoint: here(file), prerender: false });
  } } }],
  vite: { resolve: { alias: {
    'graphql-request': here('./graphql-fixture.mjs'),
    'cloudflare:workers': here('./worker-env.mjs'),
  } } },
});
