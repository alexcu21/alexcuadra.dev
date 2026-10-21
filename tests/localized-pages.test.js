import { test } from 'node:test';
import assert from 'node:assert/strict';
import { localizedPage } from '../src/data/localized-pages.js';
import { withCmsRequest } from '../src/data/cms-request.js';
import { getHomeInfo, getAboutFields } from '../src/data/client.js';

const node = (code, changes = {}) => ({ status: 'publish', isRestricted: false, hasPassword: false,
  databaseId: 123, id: 'page-id', slug: 'page', language: { code }, translations: [],
  homePageFields: { name_field: `Name ${code}` }, aboutPage: { introText: `Intro ${code}` }, ...changes });
const cmsConfig = { WPGRAPHQL_AUTH_ENABLED: 'true', WPGRAPHQL_USERNAME: 'reader', WPGRAPHQL_APPLICATION_PASSWORD: 'synthetic-only' };
function setup(t, page) {
  t.mock.method(console, 'info', () => {});
  t.mock.method(console, 'error', () => {});
  const fetch = t.mock.method(globalThis, 'fetch', async (_url, options) => {
    const query = JSON.parse(options.body).query;
    const result = query.includes('query LocalizedPageTranslation') ? page.translation
      : { ...page, translations: page.translation ? [page.translation] : page.translations };
    return Response.json({ data: { viewer: { databaseId: 2 }, page: result } });
  });
  return { fetch, run: async action => {
    let result;
    const response = await withCmsRequest(async () => { result = await action(); return Response.json(result); }, { cmsConfig });
    return { response, result };
  } };
}
test('Home and About resolve the real Spanish relation from their existing source IDs', async t => {
  const ctx = setup(t, node('EN', { translation: node('ES', { unknownACF: 'DO_NOT_COPY' }) }));
  const home = await ctx.run(() => getHomeInfo('es'));
  assert.equal(home.result[0].homePageFields.name_field, 'Name ES');
  assert.equal(home.response.headers.get('x-cms-auth'), 'verified');
  const about = await ctx.run(() => getAboutFields('es'));
  assert.equal(about.result.aboutPage.introText, 'Intro ES');
  assert.doesNotMatch(JSON.stringify(about.result), /DO_NOT_COPY/);
  const calls = ctx.fetch.mock.calls.map(call => JSON.parse(call.arguments[1].body));
  assert.deepEqual(calls.map(call => call.variables.id), ['19', '123', '68', '123']);
  assert.ok(calls.every(call => !call.query.includes('translation(language:')));
});
test('missing Spanish translations never fall back to English', async t => {
  const ctx = setup(t, node('EN', { translation: null }));
  assert.equal((await ctx.run(() => localizedPage('about', 'es', 68))).result, null);
  assert.equal((await ctx.run(() => getAboutFields('es'))).response.status, 503);
});
for (const changes of [{ status: 'draft' }, { status: 'private' }, { hasPassword: true },
  { isRestricted: true }, { language: { code: 'EN' } }, { aboutPage: null }]) {
  test(`rejects unavailable or wrong-language page translation: ${JSON.stringify(changes)}`, async t => {
    const ctx = setup(t, node('EN', { translation: node('ES', changes) }));
    const { result, response } = await ctx.run(() => localizedPage('about', 'es', 68));
    if (changes.aboutPage === null) assert.equal(response.status, 503);
    else {
      assert.equal(response.status, 200);
      assert.equal(result, null);
      assert.equal(ctx.fetch.mock.callCount(), 1, 'Do not query ACF of an unavailable target');
    }
  });
}
test('an English page uses its own fields and invalid source IDs never reach fetch', async t => {
  const ctx = setup(t, node('EN', { translation: node('ES') }));
  assert.equal((await ctx.run(() => localizedPage('home', 'en', 19))).result.homePageFields.name_field, 'Name EN');
  await assert.rejects(() => localizedPage('home', 'es', -1), TypeError);
  await assert.rejects(() => localizedPage('unknown', 'es', 19), TypeError);
  assert.equal(ctx.fetch.mock.callCount(), 1);
});
