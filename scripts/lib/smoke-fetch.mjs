import { setTimeout } from 'node:timers/promises';

export function createSmokeFetch(gap, { fetch: send = globalThis.fetch, now = Date.now,
  wait = setTimeout, timeout = AbortSignal.timeout } = {}) {
  let lastRequest;
  return async (input, { timeoutMs = 30000, ...options } = {}) => {
    const delay = lastRequest === undefined ? 0 : lastRequest + gap - now();
    if (delay > 0) await wait(delay);
    lastRequest = now();
    // Pacing is outside the network timeout: every request gets its full budget.
    return send(input, { ...options, signal: timeout(timeoutMs) });
  };
}
