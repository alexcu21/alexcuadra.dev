import { test } from 'node:test';
import assert from 'node:assert/strict';
import { routeBase, contentPath, categoryPath } from '../src/data/site-routes.js';
import { canonicalUrl, socialImageUrl, openGraphLocale } from '../src/data/page-seo.js';

test('localized route bases preserve existing English URLs', () => {
  assert.equal(routeBase('projects', 'en'), '/work/');
  assert.equal(routeBase('about', 'es'), '/es/sobre-mi/');
  assert.equal(contentPath('projects', 'en', 'christianitytoday-com'), '/work/christianitytoday-com');
  assert.equal(contentPath('projects', 'es', 'proyecto-real'), '/es/proyectos/proyecto-real');
  assert.equal(contentPath('articles', 'en', 'referencia', 'wordpress-tv'), '/articles/category/wordpress-tv/referencia');
  assert.equal(categoryPath('es', 'wordpress-tv'), '/es/articulos/categoria/wordpress-tv');
  assert.equal(contentPath('blog', 'es', 'publicacion'), '/es/blog/publicacion');
  assert.equal(contentPath('lab', 'es', 'qa-training-lab'), '/es/laboratorio/qa-training-lab');
});

test('routes require actual CMS slugs and reject path injection', () => {
  for (const slug of [undefined, '', '../other', '..', '%2f%2fevil.test', 'x?preview=1', 'x#part', 'x\\other', '%', 'a\n']) {
    assert.throws(() => contentPath('projects', 'es', slug), TypeError);
  }
  assert.throws(() => routeBase('home', 'fr'), TypeError);
  assert.throws(() => routeBase('__proto__', 'en'), TypeError);
  assert.throws(() => contentPath('home', 'es', 'test'), TypeError);
  assert.equal(contentPath('blog', 'es', 'diseño'), '/es/blog/dise%C3%B1o');
  assert.equal(contentPath('blog', 'es', 'dise%c3%b1o'), '/es/blog/dise%C3%B1o');
});

test('canonical URLs always use production and exclude queries and fragments', () => {
  assert.equal(canonicalUrl('/work/example?preview=1#test'), 'https://alexcuadra.dev/work/example');
  for (const value of ['https://other.test', '//other.test', '/\\other.test', '/a\n']) {
    assert.throws(() => canonicalUrl(value), TypeError);
  }
});

test('social image metadata is optional and excludes unsafe URLs', () => {
  for (const value of [undefined, '', 'javascript:alert(1)', 'http://example.test/a.png', 'https://user:secret@example.test/a.png']) {
    assert.equal(socialImageUrl(value), undefined);
  }
  assert.equal(socialImageUrl('/assets/real-image.png'), 'https://alexcuadra.dev/assets/real-image.png');
  assert.equal(socialImageUrl('https://content.alexcuadra.dev/uploads/real.png'), 'https://content.alexcuadra.dev/uploads/real.png');
});

test('page locales reject incompatible regional metadata instead of assuming Spanish region', () => {
  assert.equal(openGraphLocale('en'), 'en_US');
  assert.equal(openGraphLocale('es'), undefined);
  assert.equal(openGraphLocale('es', 'es_NI'), 'es_NI');
  assert.throws(() => openGraphLocale('es', 'en_US'), TypeError);
  assert.throws(() => openGraphLocale('fr'), TypeError);
});
