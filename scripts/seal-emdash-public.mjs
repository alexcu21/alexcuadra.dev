import assert from 'node:assert/strict';
import { readFile, writeFile } from 'node:fs/promises';

const load = async name => JSON.parse(await readFile(new URL(name, import.meta.url), 'utf8'));
const generated = await load('../public-candidate-dist/server/wrangler.json');
const config = await load('../wrangler.emdash-public.jsonc');
assert.equal(generated.name, config.name);
assert.equal(generated.main, 'entry.mjs');
assert.equal(generated.assets?.directory, '../client');
assert.equal(generated.assets?.run_worker_first, true);
assert.deepEqual(config.services, [{ binding: 'CMS', service: 'alexcuadra-cms-prod' }]);
assert.deepEqual(config.durable_objects.bindings.map(binding => binding.class_name).sort(),
  ['CmsOriginCoordinator', 'ContactCoordinator']);
assert(config.send_email.some(binding => binding.name === 'CONTACT_EMAIL'));
assert(config.ratelimits.some(binding => binding.name === 'CONTACT_RATE_LIMITER'));

const manifest = {
  ...generated,
  services: config.services,
  durable_objects: config.durable_objects,
  exports: config.exports,
  send_email: config.send_email,
  ratelimits: config.ratelimits,
  vars: config.vars,
  keep_vars: config.keep_vars,
  observability: config.observability,
};
await writeFile(new URL('../public-candidate-dist/server/wrangler.candidate.json', import.meta.url),
  JSON.stringify(manifest, null, 2) + '\n');
