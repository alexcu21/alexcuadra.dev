import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { Miniflare, convertV4MiniflareOptions } from 'miniflare';

test('workerd SQLite coordinator serializes concurrent requests across stubs', async t => {
  // Only the class is a Worker entrypoint export; constants stay module-local.
  const source = readFileSync(new URL('../../src/data/contact-coordinator.js', import.meta.url), 'utf8').replaceAll('export const ', 'const ');
  const mf = new Miniflare(convertV4MiniflareOptions({ workers: [{ name: 'contact-test', modules: true, compatibilityDate: '2026-10-17',
    script: source + '\nexport default { fetch(request, env) { return env.COORDINATOR.get(env.COORDINATOR.idFromName("global")).fetch(request); } };',
    durableObjects: { COORDINATOR: { className: 'ContactCoordinator', useSQLite: true } },
  }] }));
  t.after(() => mf.dispose());
  const send = body => mf.dispatchFetch('https://internal/reserve', { method: 'POST', body });
  const results = await Promise.all(Array.from({ length: 12 }, () => send('a'.repeat(64))));
  assert.equal(results.filter(result => result.status === 201).length, 1);
  assert.equal(results.filter(result => result.status === 409).length, 11);
  for (let i = 0; i < 19; i++) assert.equal((await send(i.toString(16).padStart(64, '0'))).status, 201);
  assert.equal((await send('b'.repeat(64))).status, 429);
});
