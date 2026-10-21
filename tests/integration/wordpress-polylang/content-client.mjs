import assert from 'node:assert/strict';
import { readFile, writeFile } from 'node:fs/promises';
import { getProjectsInfo, getArticlesCategories, getArticlesByCategory, getArticleBySlug } from '../../../src/data/client.js';
import { localizedPage } from '../../../src/data/localized-pages.js';
import { withCmsRequest } from '../../../src/data/cms-request.js';

const manifest = JSON.parse(await readFile(new URL('../../../.wrangler/polylang-lab.json', import.meta.url), 'utf8'));
const nativeFetch = globalThis.fetch;
const results = [];
let calls = 0;
// Exercise the actual application client, redirecting only its fixed CMS URL to this isolated fixture.
// No .dev.vars or production credentials are read. Reject every other destination.
globalThis.fetch = async (url, options) => {
  assert.equal(String(url), 'https://content.alexcuadra.dev/graphql');
  calls++;
  const response = await nativeFetch('http://127.0.0.1:8087/graphql', { ...options, redirect: 'error' });
  const payload = await response.clone().json();
  for (const error of payload.errors || []) {
    const safe = error.message?.match(/Cannot query field "[\w]+" on type "[\w]+"|Unknown argument "[\w]+"|Unknown type "[\w]+"/);
    if (safe) console.log(`LAB_SCHEMA: ${safe[0]}`);
  }
  return response;
};
const entries = new Map();
const edgeCache = { match: async key => entries.get(key.url)?.clone(),
  put: async (key, response) => { entries.set(key.url, response.clone()); } };
const cmsConfig = { WPGRAPHQL_AUTH_ENABLED: 'true', WPGRAPHQL_USERNAME: manifest.username,
  WPGRAPHQL_APPLICATION_PASSWORD: manifest.password };
async function run(action) {
  let result;
  const response = await withCmsRequest(async () => {
    result = await action();
    return Response.json(result);
  }, { cmsConfig, edgeCache, cacheOrigin: 'https://lab.example.test' });
  assert.equal(response.status, 200, 'Actual application query must succeed against the local schema');
  return { result, response };
}
async function check(name, action) {
  try { await action(); results.push({ name, status: 'PASS' }); console.log(`PASS ${name}`); }
  catch (error) { results.push({ name, status: 'FAIL', reason: error.code || error.name }); console.log(`FAIL ${name}: ${error.code || error.name}`); }
}
try {
  for (const locale of ['en', 'es']) {
    const code = locale.toUpperCase();
    await check(`actual project client ${code}, ACF and cache hit`, async () => {
      const { result } = await run(() => getProjectsInfo(locale));
      assert.ok(result.length > 0);
      assert.ok(result.every(node => node.language.code === code && node.projects.projectTagline === `SYNTHETIC_TAGLINE_${code}`));
      const before = calls;
      assert.deepEqual((await run(() => getProjectsInfo(locale))).result, result);
      assert.equal(calls, before, 'Second request must read the shared projected cache');
      assert.ok(result.every(node => node.translations.every(target => !target.hasPassword && target.status === 'publish')));
    });
    await check(`actual categories and article clients ${code}`, async () => {
      const { result: categories } = await run(() => getArticlesCategories(locale));
      const category = categories.find(item => item.slug === `lab-category-${locale}`);
      assert.ok(category);
      const { result: articles } = await run(() => getArticlesByCategory(category.slug, locale));
      assert.equal(articles.length, 1);
      assert.equal(articles[0].language.code, code);
      assert.equal(articles[0].articlesFields.quote, `SYNTHETIC_QUOTE_${code}`);
      const { result: detail } = await run(() => getArticleBySlug(articles[0].slug, locale));
      assert.equal(detail.slug, articles[0].slug);
      assert.equal((await run(() => getArticleBySlug(articles[0].slug, locale === 'en' ? 'es' : 'en'))).result, null);
    });
    const fixture = manifest.fixtures.find(item => item.type === 'page' && item.state === 'publish');
    for (const kind of ['home', 'about']) {
      await check(`actual ${kind} client ${code} follows page translation`, async () => {
        const { result, response } = await run(() => localizedPage(kind, locale, fixture.en));
        assert.equal(result.language.code, code);
        if (kind === 'home') {
          assert.equal(result.homePageFields.name_field, `SYNTHETIC_NAME_${code}`);
          assert.equal(response.headers.get('x-cms-auth'), 'verified');
        } else assert.equal(result.aboutPage.introText, `SYNTHETIC_INTRO_${code}`);
      });
    }
  }
  await check('actual project cache stores only the public application projection', async () => {
    assert.equal(entries.size, 4, 'Project and article-list entries are separated by EN/ES');
    for (const response of entries.values()) {
      assert.doesNotMatch(await response.clone().text(), /securityFixture|LAB_.*_(PRIVATE|DRAFT|FUTURE|PASSWORD)_SPANISH_CONTENT/i);
    }
  });
  for (const state of ['draft', 'private', 'future']) {
    await check(`actual page client does not fall back when Spanish translation is ${state}`, async () => {
      const fixture = manifest.fixtures.find(item => item.type === 'page' && item.state === state);
      assert.equal((await run(() => localizedPage('about', 'es', fixture.en))).result, null);
    });
  }
  await check('actual page client rejects password-protected translated ACF', async () => {
    const fixture = manifest.fixtures.find(item => item.type === 'page' && item.state === 'password');
    const response = await withCmsRequest(async () => Response.json(await localizedPage('about', 'es', fixture.en)), { cmsConfig });
    assert.equal(response.status, 200);
    assert.equal(await response.text(), 'null');
  });
} finally {
  globalThis.fetch = nativeFetch;
}
const failed = results.filter(item => item.status === 'FAIL').length;
await writeFile(new URL('../../../docs/wordpress/POLYLANG_CLIENT_RESULTS.json', import.meta.url),
  JSON.stringify({ environment: 'isolated-local-fixtures', versions: manifest.versions,
    checks: results.length, passed: results.length - failed, failed, results }, null, 2) + '\n');
if (failed) process.exitCode = 1;
