import { test } from 'node:test';
import assert from 'node:assert/strict';
import { sitemapPaths, sitemapXml, sitemapReferences, loadSitemap } from '../src/data/sitemap.js';
import { withCmsRequest } from '../src/data/cms-request.js';

const visible = { status: 'publish', isRestricted: false, hasPassword: false };
const inventory = () => ({ pages: { '/': {}, '/es/': null, '/es/sobre-mi/': {} },
  projects: { en: [{ ...visible, slug: 'english' }], es: [{ ...visible, slug: 'espanol-2' }] },
  references: [{ ...visible, slug: 'mention', categories: { nodes: [{ slug: 'wordpress-tv' }] } }] });

test('sitemap maps actual localized slugs and legacy reference categories without invented routes', () => {
  const paths = sitemapPaths(inventory());
  for (const path of ['/', '/es/sobre-mi/', '/work/english', '/es/proyectos/espanol-2',
    '/articles/category/wordpress-tv', '/articles/category/wordpress-tv/mention']) assert.ok(paths.includes(path));
  for (const path of ['/es/', '/blog/', '/lab/', '/es/articulos/', '/work/espanol-2']) assert.ok(!paths.includes(path));
});

test('unsafe or nonpublic inventory cannot produce a partial sitemap', () => {
  for (const changes of [{ status: 'draft' }, { hasPassword: true }, { isRestricted: true }]) {
    const data = inventory(); data.projects.en[0] = { ...data.projects.en[0], ...changes };
    assert.throws(() => sitemapPaths(data), TypeError);
  }
  const data = inventory(); data.references[0].categories.nodes[0].slug = '../admin';
  assert.throws(() => sitemapPaths(data), TypeError);
});

test('blog sitemap includes only nonempty localized archives and actual CMS slugs', () => {
  const data = inventory();
  data.blog = { en: [], es: [{ slug: 'entrada-real' }] };
  const paths = sitemapPaths(data);
  assert.ok(paths.includes('/es/blog/'));
  assert.ok(paths.includes('/es/blog/entrada-real'));
  assert.ok(!paths.includes('/blog/'));
  assert.ok(!paths.includes('/blog/entrada-real'));
  data.blog.es[0].slug = '../private';
  assert.throws(() => sitemapPaths(data), TypeError);
  data.blog = { en: [] };
  assert.throws(() => sitemapPaths(data), TypeError);
});

test('LAB sitemap uses its own localized archives and rejects incomplete or unsafe inventory', () => {
  const data = inventory();
  data.lab = { en: [{ slug: 'personal-lab' }], es: [] };
  const paths = sitemapPaths(data);
  assert.ok(paths.includes('/lab/'));
  assert.ok(paths.includes('/lab/personal-lab'));
  assert.ok(!paths.includes('/es/laboratorio/'));
  assert.ok(!paths.includes('/work/personal-lab'));
  data.lab.en[0].slug = '../private';
  assert.throws(() => sitemapPaths(data), TypeError);
  data.lab = { es: [] };
  assert.throws(() => sitemapPaths(data), TypeError);
});

test('XML is canonical, escaped, deduplicated and has no fabricated dates', () => {
  const xml = sitemapXml(['/work/a&b', '/work/a&b', '/es/']);
  assert.equal([...xml.matchAll(/<url>/g)].length, 2);
  assert.match(xml, /https:\/\/alexcuadra.dev\/work\/a&amp;b/);
  assert.doesNotMatch(xml, /workers\.dev|lastmod|priority|changefreq/);
  assert.throws(() => sitemapXml(['//other.test']), TypeError);
  assert.throws(() => sitemapXml(Array(50001).fill('/')), TypeError);
});

test('reference inventory paginates and preserves the current category relation', async t => {
  const after = [];
  t.mock.method(globalThis, 'fetch', async (_url, options) => {
    const cursor = JSON.parse(options.body).variables.after; after.push(cursor);
    return Response.json({ data: { articles: { nodes: [{ ...visible, slug: cursor ? 'second' : 'first',
      categories: { nodes: [{ slug: 'medium' }], pageInfo: { hasNextPage: false } } }],
    pageInfo: { hasNextPage: !cursor, endCursor: cursor ? null : 'cursor-1' } } } });
  });
  assert.equal((await sitemapReferences()).length, 2);
  assert.deepEqual(after, [null, 'cursor-1']);
});

test('CMS failures return non-cacheable503 rather than an empty successful sitemap', async t => {
  t.mock.method(console, 'error', () => {});
  t.mock.method(globalThis, 'fetch', async () => new Response('private', { status: 429 }));
  const response = await withCmsRequest(async () => new Response(sitemapXml(sitemapPaths({
    ...inventory(), references: await sitemapReferences(),
  })), { headers: { 'Content-Type': 'application/xml' } }));
  assert.equal(response.status, 503);
  assert.equal(response.headers.get('cache-control'), 'no-store');
  assert.doesNotMatch(await response.text(), /<urlset|private/);
});

test('repeated pagination cursors and truncated category connections fail closed', async t => {
  t.mock.method(console, 'error', () => {});
  let categoriesComplete = true;
  t.mock.method(globalThis, 'fetch', async () => Response.json({ data: { articles: {
    nodes: [{ ...visible, slug: 'a', categories: { nodes: [], pageInfo: { hasNextPage: !categoriesComplete } } }],
    pageInfo: { hasNextPage: true, endCursor: 'repeat' },
  } } }));
  await assert.rejects(sitemapReferences(), { code: 'CMS_PAGINATION' });
  categoriesComplete = false;
  await assert.rejects(sitemapReferences(), { code: 'CMS_SITEMAP_CONTRACT' });
});

for (const fail of [false, true]) {
  test(`full sitemap bounds origin concurrency and stops after a failure: ${fail}`, async t => {
    t.mock.method(console, 'error', () => {});
    let active = 0, peak = 0;
    const calls = [];
    const connection = { nodes: [], pageInfo: { hasNextPage: false } };
    t.mock.method(globalThis, 'fetch', async (_url, init) => {
      const { query } = JSON.parse(init.body);
      const operation = /query (\w+)/.exec(query)[1];
      calls.push(operation);
      peak = Math.max(peak, ++active);
      await new Promise(resolve => setImmediate(resolve));
      active--;
      if (fail && operation === 'BlogPosts') return new Response('', { status: 429 });
      let data;
      if (operation === 'LocalizedHome') data = { viewer: { databaseId: 1 }, page: {
        ...visible, language: { code: 'EN' }, translations: [], homePageFields: {},
      } };
      else if (operation === 'LocalizedAbout') data = { page: null };
      else if (operation === 'LocalizedProjects') data = { projects: connection };
      else if (operation === 'SitemapReferences') data = { articles: connection };
      else if (operation === 'BlogPosts') data = { posts: connection };
      else if (operation === 'LabProjects') data = { labProjects: connection };
      else assert.fail(`Unexpected operation ${operation}`);
      return Response.json({ data });
    });
    const response = await withCmsRequest(async () => new Response(await loadSitemap({})));
    assert.equal(peak, 1, 'The sitemap must not burst parallel CMS queries');
    assert.equal(calls.filter(item => item === 'LocalizedHome').length, 1, 'Shared source remains deduplicated');
    if (fail) {
      assert.equal(response.status, 503);
      assert.ok(!calls.includes('LabProjects'), 'No later inventory queries after failure');
      assert.doesNotMatch(await response.text(), /<urlset/);
    } else {
      assert.equal(response.status, 200);
      assert.match(await response.text(), /https:\/\/alexcuadra.dev\//);
    }
  });
}
