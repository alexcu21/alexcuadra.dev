import { test } from 'node:test';
import assert from 'node:assert/strict';
import { cmsAuthHeaders } from '../src/data/cms-auth.js';
import { request, withCmsRequest } from '../src/data/cms-request.js';

const config = { WPGRAPHQL_AUTH_ENABLED: 'true', WPGRAPHQL_USERNAME: 'service',
  WPGRAPHQL_APPLICATION_PASSWORD: 'abcd efgh ijkl' };

test('authentication requires explicit activation and encodes application password', () => {
  assert.equal(cmsAuthHeaders({ ...config, WPGRAPHQL_AUTH_ENABLED: 'false' }).has('authorization'), false);
  assert.equal(cmsAuthHeaders(config).get('authorization'), `Basic ${btoa('service:abcdefghijkl')}`);
  assert.throws(() => cmsAuthHeaders({ WPGRAPHQL_AUTH_ENABLED: 'true' }), /CMS_AUTH_CONFIG/);
  assert.throws(() => cmsAuthHeaders({ ...config, WPGRAPHQL_USERNAME: 'bad:name' }), /CMS_AUTH_CONFIG/);
});

test('service credentials stay isolated to request scope and bypass shared cache', async t => {
  const observed = [];
  t.mock.method(globalThis, 'fetch', async (_url, options) => {
    observed.push(new Headers(options.headers).get('authorization'));
    return new Response(JSON.stringify({ data: { ok: true } }), { headers: { 'content-type': 'application/json' } });
  });
  const edgeCache = { match() { assert.fail('authenticated cache lookup'); }, put() { assert.fail('authenticated cache write'); } };
  const render = async () => {
    await request('https://cms.example/graphql', 'query Public { ok }', undefined,
      { publicCache: true, validatePublicData: () => true });
    return new Response('ok');
  };
  await withCmsRequest(render, { cmsConfig: config, edgeCache });
  await withCmsRequest(render);
  assert.deepEqual(observed, [`Basic ${btoa('service:abcdefghijkl')}`, null]);
});

test('invalid active configuration fails closed without fetching or leaking secrets', async t => {
  const log = t.mock.method(console, 'error', () => {});
  t.mock.method(globalThis, 'fetch', () => assert.fail('must not fetch'));
  for (const [cmsConfig, endpoint] of [
    [{ ...config, WPGRAPHQL_USERNAME: '' }, 'https://cms.example/graphql'],
    [config, 'http://cms.example/graphql'],
  ]) {
    const response = await withCmsRequest(async () => {
      await request(endpoint, 'query Public { ok }');
      return new Response('unexpected');
    }, { cmsConfig });
    assert.equal(response.status, 503);
    assert.doesNotMatch(await response.text(), /abcd|service/);
  }
  assert.doesNotMatch(JSON.stringify(log.mock.calls), /abcd|abcdefghijkl|Basic/);
});
