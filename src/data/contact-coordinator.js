// One object per environment: reservations are committed before calling email.
// No visitor payload, IP, email address, token or provider response is persisted.
export const CONTACT_RETENTION_MS = 24 * 60 * 60 * 1000;
export const CONTACT_HOURLY_LIMIT = 20;
export const CONTACT_DAILY_LIMIT = 100;

export class ContactCoordinator {
  constructor(ctx) {
    this.storage = ctx.storage;
    this.storage.sql.exec('CREATE TABLE IF NOT EXISTS reservations (fingerprint TEXT PRIMARY KEY, created INTEGER NOT NULL)');
  }

  async fetch(request) {
    if (request.method !== 'POST') return new Response(null, { status: 405 });
    const fingerprint = await request.text();
    if (!/^[a-f0-9]{64}$/.test(fingerprint)) return new Response(null, { status: 400 });
    const now = Date.now();
    // Schedule cleanup before reserving; failures never permit an email attempt.
    if (await this.storage.getAlarm() === null) await this.storage.setAlarm(now + CONTACT_RETENTION_MS);
    const status = this.storage.transactionSync(() => {
      const sql = this.storage.sql;
      sql.exec('DELETE FROM reservations WHERE created <= ?', now - CONTACT_RETENTION_MS);
      if (sql.exec('SELECT fingerprint FROM reservations WHERE fingerprint = ?', fingerprint).toArray().length) return 409;
      const { daily, hourly } = sql.exec(
        'SELECT COUNT(*) AS daily, COALESCE(SUM(CASE WHEN created > ? THEN 1 ELSE 0 END), 0) AS hourly FROM reservations',
        now - 60 * 60 * 1000,
      ).one();
      if (daily >= CONTACT_DAILY_LIMIT || hourly >= CONTACT_HOURLY_LIMIT) return 429;
      sql.exec('INSERT INTO reservations (fingerprint, created) VALUES (?, ?)', fingerprint, now);
      return 201;
    });
    return new Response(null, { status });
  }

  async alarm() {
    const now = Date.now();
    this.storage.sql.exec('DELETE FROM reservations WHERE created <= ?', now - CONTACT_RETENTION_MS);
    const next = this.storage.sql.exec('SELECT MIN(created) AS created FROM reservations').one().created;
    if (next !== null) await this.storage.setAlarm(next + CONTACT_RETENTION_MS);
  }
}
