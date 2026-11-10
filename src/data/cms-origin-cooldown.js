// Local to one Worker isolate. No response data, shared promises or timers.
// This reduces follow-up attempts after a confirmed429, not simultaneous misses.
export class CmsOriginCooldown {
  constructor({ now = Date.now, capacity = 128 } = {}) {
    if (!Number.isSafeInteger(capacity) || capacity < 1) throw new TypeError('Invalid cooldown capacity');
    this.now = now;
    this.capacity = capacity;
    this.entries = new Map();
  }

  remaining(key) {
    const until = this.entries.get(key);
    if (until === undefined) return 0;
    const seconds = Math.ceil((until - this.now()) / 1000);
    if (seconds <= 0) { this.entries.delete(key); return 0; }
    return seconds;
  }

  block(key, seconds = 60) {
    const now = this.now();
    // Ignore invalid/overflowing values; no upstream Retry-After means a local60s pause.
    const duration = Number.isSafeInteger(seconds) && seconds > 0
      && Number.isSafeInteger(now + seconds * 1000) ? seconds : 60;
    for (const [entry, until] of this.entries) if (until <= now) this.entries.delete(entry);
    const until = Math.max(this.entries.get(key) ?? 0, now + duration * 1000);
    this.entries.delete(key);
    if (this.entries.size >= this.capacity) this.entries.delete(this.entries.keys().next().value);
    this.entries.set(key, until);
  }
}

export const cmsOriginCooldown = new CmsOriginCooldown();
