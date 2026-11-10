import { canStoreCatalog, catalogExpiryHeader, catalogTtlMs } from './catalog-cache.js';

const maxBytes = 512 * 1024;
const superseded = Symbol('superseded catalog fill');

async function readBounded(body) {
  const reader = body.getReader();
  const chunks = [];
  let size = 0;
  try {
    while (true) {
      const { value, done } = await reader.read();
      if (done) break;
      size += value.byteLength;
      if (size > maxBytes) {
        await reader.cancel();
        return null;
      }
      chunks.push(value);
    }
  } finally {
    reader.releaseLock();
  }
  const bytes = new Uint8Array(size);
  let offset = 0;
  for (const chunk of chunks) {
    bytes.set(chunk, offset);
    offset += chunk.byteLength;
  }
  return bytes;
}

// Runs inside a single Durable Object, never sharing response streams between requests.
export class CatalogFill {
  #generation = 0;
  #pending;
  #ready;

  invalidate() {
    this.#generation++;
    this.#pending = undefined;
    this.#ready = undefined;
  }

  async get(render, now = Date.now) {
    for (let attempt = 0; attempt < 2; attempt++) {
      if (this.#ready?.expiresAt > now()) return this.#response(this.#ready);
      this.#ready = undefined;
      if (!this.#pending) {
        const generation = this.#generation;
        const pending = this.#render(render, generation, now).finally(() => {
          if (this.#pending === pending) this.#pending = undefined;
        });
        this.#pending = pending;
      }
      const result = await this.#pending;
      if (result === superseded || (result && result.generation !== this.#generation)) continue;
      return result ? this.#response(result) : null;
    }
    throw new Error('Catalog changed while rendering');
  }

  async #render(render, generation, now) {
    const expiresAt = now() + catalogTtlMs;
    const response = await render();
    if (!canStoreCatalog(response) || !response.body) {
      await response.body?.cancel();
      return null;
    }
    const bytes = await readBounded(response.body);
    if (generation !== this.#generation) return superseded;
    if (!bytes || expiresAt <= now()) return null;
    this.#ready = { bytes, headers: [...response.headers], expiresAt, generation };
    return this.#ready;
  }

  #response(result) {
    const headers = new Headers(result.headers);
    headers.set(catalogExpiryHeader, String(result.expiresAt));
    headers.set('X-Catalog-Fill', 'coordinated');
    return new Response(result.bytes, { headers });
  }
}
