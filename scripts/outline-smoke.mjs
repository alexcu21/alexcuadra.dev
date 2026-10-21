// Focused technical staging acceptance. This is not browser or visual QA.
import assert from 'node:assert/strict';
import {readFile,writeFile} from 'node:fs/promises';
import {setTimeout} from 'node:timers/promises';
import {createHash} from 'node:crypto';
import {parseDocument,DomUtils} from 'htmlparser2';
import {createSmokeFetch} from './lib/smoke-fetch.mjs';

const base='https://alexcuadra-dev-staging.alexcu21.workers.dev';
const output='/private/tmp/alexcuadra-outline-smoke.json';
const phase=process.argv[2];
assert(['baseline','outline'].includes(phase),'OUTLINE_SMOKE_PHASE_REQUIRED');
let report=phase==='baseline'?{origin:base,browserUsed:false,cmsModified:false,contactEnabled:false,emailSent:false,checks:[]}
  :JSON.parse(await readFile(output,'utf8'));
const get=createSmokeFetch(30000);
const all=(nodes,predicate)=>DomUtils.findAll(predicate,nodes);
const tagged=(nodes,name)=>all(nodes,n=>n.type==='tag'&&n.name===name);
const text=node=>DomUtils.textContent(node).replace(/\s+/g,' ').trim();
async function page(route,locale){
  const delay=(report.lastOriginRequestAt??0)+30000-Date.now();if(delay>0)await setTimeout(delay);
  report.lastOriginRequestAt=Date.now();
  const response=await get(new URL(route,base));const html=await response.text();
  report.lastHttp={path:route,status:response.status,requestId:response.headers.get('x-request-id')};
  assert.equal(response.status,200,'OUTLINE_HTTP_STATUS');
  assert.equal(response.headers.get('x-cms-auth'),'verified','OUTLINE_CMS_IDENTITY');
  assert.match(response.headers.get('x-robots-tag')??'',/noindex/,'OUTLINE_STAGING_NOINDEX');
  assert.doesNotMatch(html,/data-contact-form|turnstile\/v0\/api|astro-error-overlay/,'OUTLINE_PUBLIC_FORM_OR_ERROR');
  const nodes=parseDocument(html).children;
  assert.equal(tagged(nodes,'html')[0]?.attribs.lang,locale,'OUTLINE_LOCALE');
  assert.equal(tagged(nodes,'h1').length,1,'OUTLINE_SINGLE_H1');
  assert(tagged(nodes,'main').some(n=>n.attribs.id==='main-content'&&n.attribs.tabindex==='-1'),'OUTLINE_MAIN');
  const links=tagged(nodes,'link');
  assert.equal(links.find(n=>n.attribs.rel==='canonical')?.attribs.href,'https://alexcuadra.dev'+route,'OUTLINE_CANONICAL');
  const policy=response.headers.get('content-security-policy')||tagged(nodes,'meta').find(n=>n.attribs['http-equiv']==='content-security-policy')?.attribs.content;
  assert(policy,'OUTLINE_CSP');
  const scripts=/(?:^|;)\s*script-src\s+([^;]+)/.exec(policy)?.[1]??'';
  assert.doesNotMatch(scripts,/'unsafe-inline'|'unsafe-eval'/,'OUTLINE_UNSAFE_CSP');
  for(const [,attrs,body]of html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/g)){
    if(/\bsrc=|type="application\/(?:ld\+)?json"/.test(attrs)||!body.trim())continue;
    assert(scripts.includes(`'sha256-${createHash('sha256').update(body).digest('base64')}'`),'OUTLINE_CSP_SCRIPT_HASH');
  }
  return {nodes,html,links};
}
try{
  if(phase==='baseline'){
    const {nodes}=await page('/','en');
    const asset=tagged(nodes,'link').find(n=>n.attribs.href?.startsWith('/_astro/')&&n.attribs.href.endsWith('.css'))?.attribs.href;
    assert(asset,'OUTLINE_BASELINE_ASSET');
    const r=await fetch(new URL(asset,base),{signal:AbortSignal.timeout(15000)});assert.equal(r.status,200);await r.arrayBuffer();
    const contact=await fetch(base+'/api/contact',{signal:AbortSignal.timeout(15000)});
    assert.equal(contact.status,404);assert.equal(contact.headers.get('cache-control'),'no-store');assert.equal((await contact.json()).code,'CONTACT_DISABLED');
    report.checks.push({phase,path:'/',status:200,cmsIdentityVerified:true,noindex:true,contactDisabled:true,assetStatus:200});report.baselinePassed=true;
  }else{
    assert(report.baselinePassed,'OUTLINE_BASELINE_REQUIRED');
    const pairs=[['/blog/example-post-structuring-a-test-case','/es/blog/publicacion-de-ejemplo-como-estructurar-un-caso-de-prueba'],
      ['/lab/qa-training-lab','/es/laboratorio/qa-training-lab-2']];
    for(const pair of pairs){for(const [index,route]of pair.entries()){
      const locale=index===0?'en':'es';const {nodes,links}=await page(route,locale);
      if(!report.editorialCssVerified){
        const asset=links.find(n=>/^\/_astro\/WordPressContent\.[\w-]+\.css$/.test(n.attribs.href??''))?.attribs.href;
        assert(asset,'OUTLINE_EDITORIAL_CSS_LINK');
        const response=await fetch(new URL(asset,base),{signal:AbortSignal.timeout(15000)});assert.equal(response.status,200,'OUTLINE_EDITORIAL_CSS_STATUS');
        const css=await response.text();assert(css.includes('.editorial-toc'),'OUTLINE_EDITORIAL_CSS_RULES');
        assert.equal(createHash('sha256').update(css).digest('hex'),createHash('sha256').update(await readFile(new URL('../dist/client'+asset,import.meta.url))).digest('hex'),'OUTLINE_EDITORIAL_CSS_ARTIFACT');
        report.editorialCssVerified=true;
        const contact=await fetch(base+'/api/contact',{signal:AbortSignal.timeout(15000)});
        assert.equal(contact.status,404);assert.equal(contact.headers.get('cache-control'),'no-store');assert.equal((await contact.json()).code,'CONTACT_DISABLED');report.contactDisabledAfter=true;
      }
      assert(links.some(n=>n.attribs.hreflang===(index===0?'es':'en')&&n.attribs.href==='https://alexcuadra.dev'+pair[1-index]),'OUTLINE_RECIPROCAL_ALTERNATE');
      const content=all(nodes,n=>n.type==='tag'&&n.attribs?.class?.split(' ').includes('wordpress-content'));
      assert.equal(content.length,1,'OUTLINE_DOCUMENT');
      const headings=all(content[0].children,n=>n.type==='tag'&&/^h[2-4]$/.test(n.name)&&n.attribs?.id);
      const details=tagged(nodes,'details').filter(n=>n.attribs.class?.split(' ').includes('editorial-toc'));
      assert.equal(details.length,headings.length>=2?1:0,'OUTLINE_CONDITIONAL_TOC');
      let anchorCount=0;
      if(details.length){
        assert.equal(text(tagged(details,'summary')[0]),locale==='es'?'En esta página':'On this page','OUTLINE_LABEL');
        assert.equal(tagged(details,'nav')[0]?.attribs['aria-label'],locale==='es'?'En esta página':'On this page');
        assert('open'in details[0].attribs,'OUTLINE_NATIVE_DISCLOSURE');
        const targets=new Map(headings.map(n=>[n.attribs.id,n]));assert.equal(targets.size,headings.length,'OUTLINE_DUPLICATE_HEADING_IDS');
        const anchors=tagged(details,'a');anchorCount=anchors.length;assert.equal(anchors.length,headings.length,'OUTLINE_COVERAGE');
        const seen=new Set();for(const a of anchors){assert(a.attribs.href.startsWith('#'));const id=decodeURIComponent(a.attribs.href.slice(1));const target=targets.get(id);
          assert(target&&!seen.has(id),'OUTLINE_TARGET');seen.add(id);assert.equal(text(a),text(target),'OUTLINE_TEXT');assert.equal(target.attribs.tabindex,'-1','OUTLINE_FOCUS_TARGET');}
      }
      const result={phase,path:route,status:200,locale,headings:headings.length,tocPresent:details.length===1,anchors:anchorCount,cmsIdentityVerified:true,noindex:true,cspVerified:true,contactFormAbsent:true};
      report.checks.push(result);await writeFile(output,JSON.stringify(report,null,2)+'\n');console.log(JSON.stringify(result));
    }}report.outlinePassed=true;
  }
}catch(e){report.failure=/^[A-Z_]+$/.test(e.message)?e.message:'OUTLINE_ASSERTION_FAILED';report.failedPhase=phase;}
report.finishedAt=new Date().toISOString();await writeFile(output,JSON.stringify(report,null,2)+'\n');console.log(JSON.stringify(report));if(report.failure)process.exitCode=1;
