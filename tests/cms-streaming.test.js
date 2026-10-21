import { test } from 'node:test';
import assert from 'node:assert/strict';
import { withCmsRequest } from '../src/data/cms-request.js';
import { getHomeInfo } from '../src/data/client.js';

function delayedHtml(action, swallow = false) {
  let first = true;
  return new Response(new ReadableStream({
    async pull(controller) {
      if (first) { first = false; controller.enqueue(new TextEncoder().encode('<html>PRIVATE-PARTIAL')); return; }
      try { await action(); controller.enqueue(new TextEncoder().encode('</html>')); controller.close(); }
      catch (error) { if (swallow) controller.close(); else controller.error(error); }
    },
  }), { headers: { 'Content-Type': 'text/html; charset=utf-8', 'X-Page': 'preserved' } });
}

for (const swallow of [false, true]) {
  test(`late CMS failure replaces partial HTML before committing HTTP200 (caught=${swallow})`, async t => {
    t.mock.method(console, 'error', () => {});
    const fetch = t.mock.method(globalThis, 'fetch', async () => new Response('PRIVATE-UPSTREAM', {
      status: 429, headers: { 'Retry-After': '120' },
    }));
    const response = await withCmsRequest(async () => delayedHtml(() => getHomeInfo(), swallow), { bufferHtml: true });
    assert.equal(response.status, 503);
    assert.equal(response.headers.get('retry-after'), '120');
    assert.equal(response.headers.get('cache-control'), 'no-store');
    assert.equal(response.headers.get('x-robots-tag'), 'noindex');
    assert.doesNotMatch(await response.text(), /PRIVATE|429|graphql|stack/i);
    assert.equal(fetch.mock.callCount(), 1, 'No automatic upstream retry');
  });
}

test('successful buffered HTML retains its complete body and response metadata', async () => {
  const response = await withCmsRequest(async () => delayedHtml(async () => {}), { bufferHtml: true });
  assert.equal(response.status, 200);
  assert.equal(response.headers.get('x-page'), 'preserved');
  assert.equal(await response.text(), '<html>PRIVATE-PARTIAL</html>');
});

test('unrelated late rendering exceptions remain errors instead of becoming CMS503', async () => {
  await assert.rejects(withCmsRequest(async () => delayedHtml(async () => { throw new TypeError('render bug'); }),
    { bufferHtml: true }), /render bug/);
});

test('non-HTML responses are not consumed by the HTML completion guard', async () => {
  const original = Response.json({ code: 'CONTACT_DISABLED' }, { status: 404 });
  const response = await withCmsRequest(async () => original, { bufferHtml: true });
  assert.equal(original.bodyUsed, false);
  assert.equal(response, original);
  assert.deepEqual(await response.json(), { code: 'CONTACT_DISABLED' });
});
