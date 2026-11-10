import { test } from 'node:test';
import assert from 'node:assert/strict';
import { sitemapPaths, sitemapXml } from '../src/data/sitemap.js';

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
