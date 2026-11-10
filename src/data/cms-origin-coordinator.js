// Shared only within one environment. Stores hashed service partitions and deadlines.
export class CmsOriginCoordinator {
  constructor(ctx) {
    this.storage = ctx.storage;
    this.storage.sql.exec('CREATE TABLE IF NOT EXISTS cooldowns (partition TEXT PRIMARY KEY, until_ms INTEGER NOT NULL)');
  }

  async fetch(request) {
    if (request.method !== 'POST') return new Response(null, { status: 405 });
    const path = new URL(request.url).pathname;
    if (!['/check', '/report'].includes(path)) return new Response(null, { status: 404 });
    const body = await request.text();
    if (body.length > 256) return new Response(null, { status: 413 });
    let input;
    try { input = JSON.parse(body); } catch { return new Response(null, { status: 400 }); }
    const report = path === '/report';
    const now = Date.now();
    if (!input || Array.isArray(input) || typeof input !== 'object'
      || Object.keys(input).sort().join(',') !== (report ? 'key,seconds' : 'key')
      || typeof input.key !== 'string' || !/^[a-f0-9]{64}$/.test(input.key)
      || (report && (!Number.isSafeInteger(input.seconds) || input.seconds < 1
        || !Number.isSafeInteger(now + input.seconds * 1000)))) return new Response(null, { status: 400 });
    if (report && await this.storage.getAlarm() === null) await this.storage.setAlarm(now + 60000);
    const result = this.storage.transactionSync(() => {
      const sql = this.storage.sql;
      sql.exec('DELETE FROM cooldowns WHERE until_ms <= ?', now);
      const existing = sql.exec('SELECT until_ms FROM cooldowns WHERE partition = ?', input.key).toArray()[0];
      if (!existing && sql.exec('SELECT COUNT(*) AS count FROM cooldowns').one().count >= 128) return null;
      if (report) {
        const until = Math.max(existing?.until_ms ?? 0, now + input.seconds * 1000);
        sql.exec('INSERT INTO cooldowns (partition, until_ms) VALUES (?, ?) ON CONFLICT(partition) DO UPDATE SET until_ms = excluded.until_ms', input.key, until);
        return Math.ceil((until - now) / 1000);
      }
      return existing ? Math.ceil((existing.until_ms - now) / 1000) : 0;
    });
    return result === null ? new Response(null, { status: 503 })
      : Response.json({ retryAfter: result }, { headers: { 'Cache-Control': 'no-store' } });
  }

  async alarm() {
    const now = Date.now();
    this.storage.sql.exec('DELETE FROM cooldowns WHERE until_ms <= ?', now);
    const next = this.storage.sql.exec('SELECT MIN(until_ms) AS deadline FROM cooldowns').one().deadline;
    if (next !== null) await this.storage.setAlarm(Math.min(next, now + 60 * 60 * 1000));
  }
}
