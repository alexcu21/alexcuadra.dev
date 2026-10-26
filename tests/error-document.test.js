import { test } from 'node:test';
import assert from 'node:assert/strict';
import { errorDocument, presentDocumentError } from '../src/data/error-document.js';

const navigation = (path, init={}) => new Request(`https://example.test${path}`,{headers:{accept:'text/html'},...init});
for (const locale of ['en','es']) for (const status of [404,503]) {
  test(`document error ${status} in ${locale} keeps its HTTP contract`, async () => {
    const response=presentDocumentError(navigation(locale==='es'?'/es/blog/missing':'/blog/missing'),new Response('PRIVATE_UPSTREAM_ERROR',
      {status,headers:{'Retry-After':'120','X-Request-ID':'fixture','Content-Length':'22','Content-Encoding':'gzip','ETag':'obsolete'}}));
    assert.equal(response.status,status);assert.equal(response.headers.get('retry-after'),'120');
    assert.equal(response.headers.get('x-request-id'),'fixture');assert.equal(response.headers.get('cache-control'),'no-store');
    assert.equal(response.headers.get('x-robots-tag'),'noindex');assert.match(response.headers.get('content-type'),/^text\/html/);
    assert.equal(response.headers.get('content-length'),null);assert.equal(response.headers.get('content-encoding'),null);assert.equal(response.headers.get('etag'),null);
    assert.match(response.headers.get('content-security-policy'),/default-src 'none'/);
    const html=await response.text();assert.match(html,new RegExp(`<html lang="${locale}"`));
    assert.equal((html.match(/<h1>/g)||[]).length,1);assert.doesNotMatch(html,/PRIVATE|<script|http-equiv="refresh"/);
    assert.ok(html.includes(locale==='es'?'/es/proyectos/':'/work/'));
    if(status===503){assert.match(html,/120/);assert.match(html,/href=""/);}else{assert.doesNotMatch(html,/href=""/);}
  });
}
test('document presentation leaves APIs, metadata, JSON, non-navigation and POST responses alone', () => {
  for(const [request,response] of [
    [navigation('/api/contact'),new Response('{}',{status:503})],
    [navigation('/sitemap.xml'),new Response('unavailable',{status:503})],
    [navigation('/robots.txt'),new Response('missing',{status:404})],
    [navigation('/blog/'),new Response('{}',{status:503,headers:{'Content-Type':'application/problem+json'}})],
    [new Request('https://example.test/blog/'),new Response('unavailable',{status:503})],
    [navigation('/blog/',{method:'POST'}),new Response('unavailable',{status:503})],
    [navigation('/blog/'),new Response('forbidden',{status:403})],
  ]) assert.equal(presentDocumentError(request,response),response);
});
test('hostile paths and origin response bodies are never reflected in the error document', async () => {
  const response=presentDocumentError(navigation('/es/%3Cscript%3E?secret=PRIVATE'),new Response('PRIVATE',{status:404}));
  assert.doesNotMatch(await response.text(),/PRIVATE|%3C|<script/);
});
test('HEAD errors remain bodyless and invalid delay text cannot become markup', async () => {
  const response=presentDocumentError(navigation('/es/missing',{method:'HEAD'}),new Response(null,{status:503}));
  assert.equal(await response.text(),'');assert.equal(response.status,503);
  assert.doesNotMatch(errorDocument(503,'es','<script>PRIVATE</script>'),/PRIVATE|<script/);
  assert.match(errorDocument(503,'es','<script>PRIVATE</script>'),/60 segundos/);
});
test('long delays and HTTP-date Retry-After values are never shortened in the UI', async t => {
  t.mock.method(Date,'now',()=>Date.parse('2026-10-25T12:00:00Z'));
  for (const [header,seconds] of [['172800',172800],['Sun, 25 Oct 2026 12:02:00 GMT',120]]) {
    const response=presentDocumentError(navigation('/es/blog/'),new Response('unavailable',{status:503,headers:{'Retry-After':header}}));
    assert.equal(response.headers.get('retry-after'),header);assert.match(await response.text(),new RegExp(`${seconds} segundos`));
  }
});
