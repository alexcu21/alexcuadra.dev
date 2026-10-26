import { defineConfig } from 'astro/config';
import { fileURLToPath } from 'node:url';
const here = path => fileURLToPath(new URL(path, import.meta.url));
export default defineConfig({
  output: 'server',
  devToolbar: { enabled: false },
  integrations: [{ name: 'actual-reference-routes', hooks: { 'astro:config:setup': ({ injectRoute }) => {
    for (const [pattern, file] of [
      ['/404', '../../../src/pages/404.astro'],
      ['/contact', '../../../src/pages/contact.astro'],
      ['/es/contacto', '../../../src/pages/es/contacto.astro'],
      ['/work', '../../../src/pages/work.astro'],
      ['/work/[...slug]', '../../../src/pages/work/[...slug].astro'],
      ['/es/proyectos', '../../../src/pages/es/proyectos/index.astro'],
      ['/es/proyectos/[...slug]', '../../../src/pages/es/proyectos/[...slug].astro'],
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
  } } }, { name: 'local-contact-preview', hooks: { 'astro:config:setup': ({ injectScript }) => {
    injectScript('head-inline', `if (new URL(location.href).searchParams.get('mode') === 'contact-enabled') {
      window.turnstile = { render(element) { element.textContent = document.documentElement.lang === 'es'
        ? 'Verificación simulada: vista local sin envío.' : 'Simulated verification: local preview without delivery.';
        return 'local-preview'; }, reset() {} };
    }`);
  } } }],
  vite: { resolve: { alias: {
    'graphql-request': here('./graphql-fixture.mjs'),
    'cloudflare:workers': here('./worker-env.mjs'),
  } } },
});
