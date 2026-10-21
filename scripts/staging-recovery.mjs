import assert from 'node:assert/strict';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { readFile, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { createSmokeFetch } from './lib/smoke-fetch.mjs';
import { recoveryPreflight, rehearseRecovery, stagingWorker } from './lib/worker-recovery.mjs';

const root = fileURLToPath(new URL('../', import.meta.url));
const args = process.argv.slice(2);
const value = name => args[args.indexOf(name) + 1];
assert.ok(args.includes('--current') && args.includes('--target'), 'RECOVERY_EXPLICIT_VERSIONS_REQUIRED');
const current = value('--current');
const target = value('--target');
assert.match(current, /^[a-f\d-]{36}$/i); assert.match(target, /^[a-f\d-]{36}$/i);
for (const flag of args.filter(a => a.startsWith('--'))) {
  assert.ok(['--current', '--target', '--output', '--execute'].includes(flag), 'RECOVERY_UNKNOWN_FLAG');
  assert.equal(args.filter(a => a === flag).length, 1, 'RECOVERY_DUPLICATE_FLAG');
  if (flag !== '--execute') assert.ok(value(flag) && !value(flag).startsWith('--'), 'RECOVERY_OPTION_VALUE_REQUIRED');
}
const config = JSON.parse(await readFile(new URL('../wrangler.jsonc', import.meta.url), 'utf8'));
assert.equal(config.name, stagingWorker, 'RECOVERY_STAGING_ONLY');
assert.equal(config.vars?.CONTACT_FORM_ENABLED, 'false', 'RECOVERY_CONTACT_DISABLED_REQUIRED');
const run = promisify(execFile);
async function wrangler(command, json = true) {
  const { stdout } = await run(process.execPath, [fileURLToPath(new URL('../node_modules/wrangler/bin/wrangler.js', import.meta.url)),
    ...command, '--name', stagingWorker, '--config', fileURLToPath(new URL('../wrangler.jsonc', import.meta.url)), ...(json ? ['--json'] : [])],
  { cwd: root, timeout: 60000, maxBuffer: 2 * 1024 * 1024,
    env: { ...process.env, CI: 'true', WRANGLER_SEND_METRICS: 'false' } });
  return json ? JSON.parse(stdout) : undefined;
}
const report = { checkedAt: new Date().toISOString(), worker: stagingWorker, executed: args.includes('--execute'),
  cmsModified: false, productionModified: false, contactEnabled: false, storageRestorationTested: false };
const get = createSmokeFetch(30000);
async function probe() {
  const base = 'https://alexcuadra-dev-staging.alexcu21.workers.dev';
  const home = await get(base + '/');
  const html = await home.text();
  assert.equal(home.status, 200, 'RECOVERY_HOME_STATUS');
  assert.equal(home.headers.get('x-cms-auth'), 'verified', 'RECOVERY_CMS_IDENTITY');
  assert.match(home.headers.get('x-robots-tag') || '', /noindex/, 'RECOVERY_STAGING_NOINDEX');
  assert.match(html, /<h1[\s>]/, 'RECOVERY_RENDERED_HOME');
  assert.doesNotMatch(html, /data-contact-form|turnstile\/v0\/api/, 'RECOVERY_PUBLIC_CONTACT_DISABLED');
  const path = /(?:href|src)="(\/_astro\/[^"?#]+\.(?:css|js))"/.exec(html)?.[1];
  assert.ok(path, 'RECOVERY_COMPILED_ASSET');
  // These endpoints do not query the CMS; pace only the origin-backed Home probe.
  const asset = await fetch(new URL(path, base), { signal: AbortSignal.timeout(30000) });
  assert.equal(asset.status, 200, 'RECOVERY_ASSET_STATUS');
  assert.match(asset.headers.get('content-type') || '', /text\/css|(?:application|text)\/javascript/, 'RECOVERY_ASSET_TYPE');
  await asset.arrayBuffer();
  const contact = await fetch(base + '/api/contact', { signal: AbortSignal.timeout(15000) });
  assert.equal(contact.status, 404, 'RECOVERY_CONTACT_STATUS');
  assert.equal(contact.headers.get('cache-control'), 'no-store', 'RECOVERY_CONTACT_NOSTORE');
  assert.equal((await contact.json()).code, 'CONTACT_DISABLED', 'RECOVERY_CONTACT_CONTRACT');
  console.log(JSON.stringify({ event: 'recovery_probe', passed: true }));
  return { homeStatus: 200, cmsIdentityVerified: true, noindex: true, publicFormAbsent: true,
    assetStatus: 200, contactStatus: 404, contactDisabledContract: true, noStore: true };
}
try {
  const [deployments, source, previous] = await Promise.all([
    wrangler(['deployments', 'list']), wrangler(['versions', 'view', current]), wrangler(['versions', 'view', target]),
  ]);
  report.preflight = recoveryPreflight({ worker: stagingWorker, expectedCurrent: current, targetId: target,
    deployments, current: source, target: previous });
  if (report.executed) report.rehearsal = await rehearseRecovery({ current, target,
    inspectActive: () => wrangler(['deployments', 'list']), probe,
    switchVersion: async (id, reason) => {
      console.log(JSON.stringify({ event: 'recovery_switch', version: id }));
      await wrangler(['rollback', id, '--yes', '--message', reason], false);
    } });
} catch {
  report.failure = 'RECOVERY_PREFLIGHT_OR_INSPECTION_FAILED';
}
report.finishedAt = new Date().toISOString();
// Raw CLI output and errors can include authors/private values. Save only this projection.
if (args.includes('--output')) await writeFile(value('--output'), JSON.stringify(report, null, 2) + '\n');
console.log(JSON.stringify(report, null, 2));
process.exitCode = report.failure || (report.executed && !report.rehearsal?.complete) ? 1 : 0;
