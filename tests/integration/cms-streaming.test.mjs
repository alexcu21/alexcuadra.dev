import { test } from 'node:test';
import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { setTimeout } from 'node:timers/promises';

test('Astro finishes nested HTML before choosing success or CMS503', { timeout: 60000 }, async t => {
  const root = fileURLToPath(new URL('../../', import.meta.url));
  const server = spawn(process.execPath, ['node_modules/astro/bin/astro.mjs', 'dev', '--root',
    'tests/fixtures/cms-streaming', '--host', '127.0.0.1', '--port', '4397', '--ignore-lock'],
  { cwd: root, stdio: ['ignore', 'pipe', 'pipe'], env: { ...process.env, ASTRO_TELEMETRY_DISABLED: '1' } });
  let output = '';
  for (const stream of [server.stdout, server.stderr]) stream.on('data', chunk => { output += chunk; });
  t.after(async () => {
    if (server.exitCode !== null) return;
    const exited = new Promise(resolve => server.once('exit', resolve));
    server.kill('SIGTERM');
    await exited;
  });
  const base = 'http://127.0.0.1:4397';
  let ready = false;
  for (let attempt = 0; attempt < 200; attempt++) {
    if (server.exitCode !== null) throw Error(`Fixture failed to start: ${output.slice(-2000)}`);
    try {
      const response = await fetch(base, { signal: AbortSignal.timeout(1000) });
      if (response.status === 200) { await response.text(); ready = true; break; }
    } catch { /* Local server still starting. */ }
    await setTimeout(100);
  }
  assert.ok(ready, output.slice(-2000));
  const unguarded = await fetch(`${base}/?mode=caught&guard=off`);
  assert.equal(unguarded.status, 200, 'Regression control: unbuffered response commits success before late CMS failure');
  await unguarded.text();
  const success = await fetch(base);
  assert.equal(success.status, 200);
  assert.match(await success.text(), /Completed nested component/);
  for (const mode of ['failure', 'caught']) {
    const response = await fetch(`${base}/?mode=${mode}`);
    assert.equal(response.status, 503, `${mode}: ${output.slice(-5000)}`);
    assert.equal(response.headers.get('cache-control'), 'no-store');
    assert.equal(response.headers.get('x-robots-tag'), 'noindex');
    assert.equal(response.headers.get('retry-after'), mode === 'failure' ? '120' : '60');
    assert.doesNotMatch(await response.text(), /Partial header|Synthetic|429|stack/);
  }
});
