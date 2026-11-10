import { catalogTtlMs } from './catalog-cache.js';

const leasePrefix = 'lease:';
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

export class CatalogWriteFence {
  #purging = false;
  #storage;
  #now;
  #delay;
  #notify;

  constructor(storage, now = Date.now, pause = delay) {
    this.#storage = storage;
    this.#now = now;
    this.#delay = pause;
  }

  async begin() {
    if (this.#purging) return null;
    const lease = { id: crypto.randomUUID(), expiresAt: this.#now() + catalogTtlMs };
    await this.#storage.put(leasePrefix + lease.id, lease.expiresAt);
    if (this.#purging) {
      await this.finish(lease.id);
      return null;
    }
    return lease;
  }

  async finish(id) {
    await this.#storage.delete(leasePrefix + id);
    this.#notify?.();
  }

  async purge(invalidate, purgeEdge) {
    if (this.#purging) return false;
    this.#purging = true;
    try {
      await invalidate();
      let timedOut = false;
      let timeout;
      while (true) {
        const progress = new Promise(resolve => { this.#notify = resolve; });
        const leases = await this.#storage.list({ prefix: leasePrefix });
        const expired = [...leases].filter(([, expires]) => expires <= this.#now());
        if (expired.length) await this.#storage.delete(expired.slice(0, 100).map(([key]) => key));
        if (leases.size === expired.length) return await purgeEdge();
        if (timedOut) return false;
        timeout ??= this.#delay(1500).then(() => { timedOut = true; });
        await Promise.race([progress, timeout]);
      }
    } finally {
      this.#notify = undefined;
      this.#purging = false;
    }
  }
}
