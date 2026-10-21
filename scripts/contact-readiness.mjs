import { readFile, writeFile } from 'node:fs/promises';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { fileURLToPath } from 'node:url';
import dotenv from 'dotenv';
import { request } from 'graphql-request';
import { cmsAuthHeaders } from '../src/data/cms-auth.js';
import { contactVersionReadiness, latestActiveVersions } from '../src/data/contact-readiness.js';
import sanitizeHtml from 'sanitize-html';

const root = fileURLToPath(new URL('../', import.meta.url));
const run = promisify(execFile);
const config = JSON.parse(await readFile(new URL('../wrangler.jsonc', import.meta.url), 'utf8'));
// This audit is deliberately pinned to staging; no arbitrary CLI destinations.
if (config.name !== 'alexcuadra-dev-staging' || config.vars.CONTACT_FORM_ENABLED !== 'false') throw Error('CONTACT_STAGING_DISABLED_REQUIRED');
const expected = Object.fromEntries(['CONTACT_FORM_ENABLED', 'CONTACT_ORIGIN', 'CONTACT_FROM_EMAIL', 'TURNSTILE_SITE_KEY', 'CONTACT_PRIVACY_PAGE_ID'].map(name => [name, config.vars[name]]));
const report = { checkedAt: new Date().toISOString(), worker: config.name,
  readOnly: true, emailSent: false, deploymentChanged: false, runtimeVerified: false,
  activationReady: false, remote: {}, privacy: {} };
async function readHtml(response) {
  let html = '', bytes = 0;
  const decoder = new TextDecoder();
  const reader = response.body?.getReader();
  if (!reader) return html;
  try {
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      bytes += value.byteLength;
      if (bytes > 256 * 1024) { await reader.cancel(); throw Error('AUDIT_BODY_LIMIT'); }
      html += decoder.decode(value, { stream: true });
    }
    return html + decoder.decode();
  } finally { reader.releaseLock(); }
}
async function wrangler(args) {
  // Keep raw stdout/stderr in memory; errors may include account IDs or private vars.
  const { stdout } = await run(process.execPath, [fileURLToPath(new URL('../node_modules/wrangler/bin/wrangler.js', import.meta.url)),
    ...args, '--name', config.name, '--json', '--config', fileURLToPath(new URL('../wrangler.jsonc', import.meta.url))],
  { cwd: root, timeout: 45000, maxBuffer: 2 * 1024 * 1024, env: { ...process.env, CI: 'true', WRANGLER_SEND_METRICS: 'false' } });
  return JSON.parse(stdout);
}

await Promise.allSettled([
  (async () => {
    try {
      const active = latestActiveVersions(await wrangler(['deployments', 'list']));
      const versions = [];
      for (const item of active) versions.push({ trafficPercentage: item.percentage,
        ...contactVersionReadiness(await wrangler(['versions', 'view', item.id]), expected) });
      report.remote = { status: 'inspected', versions, configurationReady: versions.every(item => item.configurationReady) };
    } catch { report.remote = { status: 'unavailable', configurationReady: false }; }
  })(),
  (async () => {
    try {
      const result = await fetch(new URL('/api/contact', config.vars.CONTACT_ORIGIN), { method: 'GET', redirect: 'error', signal: AbortSignal.timeout(15000) });
      const body = await result.json().catch(() => null);
      report.endpoint = { status: result.status, disabledContract: result.status === 404 && body?.code === 'CONTACT_DISABLED',
        noStore: result.headers.get('cache-control') === 'no-store' };
    } catch { report.endpoint = { status: 'unavailable', disabledContract: false }; }
  })(),
  (async () => {
    try {
      const credentials = dotenv.parse(await readFile(new URL('../.dev.vars', import.meta.url)));
      const headers = cmsAuthHeaders(credentials);
      if (!headers.has('authorization')) throw Error('AUTH_REQUIRED');
      const result = await request({ url: 'https://content.alexcuadra.dev/graphql',
        document: 'query ContactPrivacyAudit { page(id: "19", idType: DATABASE_ID) { contactForm { privacy_url } } }',
        requestHeaders: headers, signal: AbortSignal.timeout(15000) });
      const url = new URL(result?.page?.contactForm?.privacy_url);
      if (url.protocol !== 'https:' || url.username || url.password) throw Error('INVALID_PRIVACY_URL');
      // No CMS credentials, visitor data or cookies are sent to the privacy URL.
      const response = await fetch(url, { redirect: 'follow', signal: AbortSignal.timeout(15000) });
      const html = await readHtml(response);
      // Public editorial text only; keep it out of the saved readiness report.
      if (process.argv.includes('--privacy-text')) console.log('PUBLIC_PRIVACY_TEXT\n' + sanitizeHtml(html, { allowedTags: [], allowedAttributes: {} }).replace(/\s+/g, ' ').slice(0, 12000));
      report.privacy = { status: response.status, accessible: response.ok,
        html: response.headers.get('content-type')?.includes('text/html') === true,
        redirected: response.redirected,
        editorialLinkMatchesPortfolio: url.href === 'https://alexcuadra.dev/privacy/',
        editorialReview: 'manual' };
    } catch { report.privacy = { status: 'unavailable', accessible: false, editorialReview: 'pending' }; }
  })(),
  ...[
    ['frontendPrivacy', new URL('/privacy/', config.vars.CONTACT_ORIGIN).href],
    ['productionPrivacy', 'https://alexcuadra.dev/privacy/'],
  ].map(([name, url]) => (async () => {
    try {
      const response = await fetch(url, { redirect: 'error', signal: AbortSignal.timeout(20000) });
      const html = await readHtml(response);
      report[name] = { status: response.status, publishedPolicy: response.ok
        && html.includes('Contact form privacy') && html.includes('mailto:alex@alexcuadra.dev')
        && html.includes('30 days') && !html.includes('[publication date]'),
        noindex: response.headers.get('x-robots-tag')?.includes('noindex') === true };
    } catch { report[name] = { status: 'unavailable', publishedPolicy: false }; }
  })()),
]);

// Metadata presence cannot prove usable secrets, namespace storage type, email
// entitlement, challenge hostname configuration, delivery or privacy coverage.
report.nextChecks = [
  ...(!report.productionPrivacy?.publishedPolicy ? ['Connect privacy in production before changing the Home editorial URL'] : []),
  ...(!report.privacy.editorialLinkMatchesPortfolio ? ['Update the Home editorial privacy URL after verifying production'] : []),
  ...(!report.remote.configurationReady ? ['Resolve staging configuration checks'] : []),
  'Verify runtime bindings and real challenge in staging', 'Authorize and verify a real delivery',
];
await writeFile(new URL('../docs/qa/CONTACT_STAGING_READINESS.json', import.meta.url), JSON.stringify(report, null, 2) + '\n');
console.log(JSON.stringify(report, null, 2));
process.exitCode = report.remote.configurationReady && report.endpoint?.disabledContract
  && report.frontendPrivacy?.publishedPolicy && report.productionPrivacy?.publishedPolicy
  && report.privacy.editorialLinkMatchesPortfolio ? 0 : 2;
