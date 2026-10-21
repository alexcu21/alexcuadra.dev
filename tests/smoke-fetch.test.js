import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createSmokeFetch } from '../scripts/lib/smoke-fetch.mjs';

test('pacing does not consume the next request timeout, even when gap equals timeout', async () => {
  let clock = 0;
  const events = [];
  const get = createSmokeFetch(30000, { now: () => clock,
    wait: async delay => { events.push(['wait', delay]); clock += delay; },
    timeout: ms => { events.push(['timeout', clock, ms]); return { expiresAt: clock + ms }; },
    fetch: async (url, init) => { events.push(['fetch', url, init.signal.expiresAt - clock]); return 'ok'; },
  });
  await get('/first');
  clock += 1000;
  assert.equal(await get('/second'), 'ok');
  assert.deepEqual(events, [ ['timeout', 0, 30000], ['fetch', '/first', 30000], ['wait', 29000],
    ['timeout', 30000, 30000], ['fetch', '/second', 30000] ]);
});

test('slow requests need no extra pacing; custom timeout and options are preserved', async () => {
  let clock = 0;
  const budgets = [];
  const get = createSmokeFetch(10000, { now: () => clock, wait: () => assert.fail('no wait needed'),
    timeout: ms => { budgets.push(ms); return { createdAt: clock }; },
    fetch: async (_url, init) => { assert.equal(init.redirect, 'error'); assert.equal(init.signal.createdAt, clock); clock += 15000; return true; },
  });
  await get('/first', { redirect: 'error', timeoutMs: 15000 });
  await get('/second', { redirect: 'error' });
  assert.deepEqual(budgets, [15000, 30000]);
});

test('a network failure is propagated once without retrying', async () => {
  let calls = 0;
  const get = createSmokeFetch(30000, { fetch: async () => { calls++; throw Error('synthetic timeout'); } });
  await assert.rejects(get('/only'), /synthetic timeout/);
  assert.equal(calls, 1);
});
