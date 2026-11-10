import { test } from 'node:test';
import assert from 'node:assert/strict';
import { DatabaseSync } from 'node:sqlite';
import { ContactCoordinator, CONTACT_RETENTION_MS } from '../src/data/contact-coordinator.js';

function setup() {
  const db = new DatabaseSync(':memory:');
  let alarm = null;
  const storage = {
    sql: { exec(query, ...args) {
      const statement = db.prepare(query);
      const rows = statement.columns().length ? statement.all(...args) : (statement.run(...args), []);
      return { toArray: () => rows, one: () => { assert.equal(rows.length, 1); return rows[0]; } };
    } },
    transactionSync(fn) {
      db.exec('BEGIN');
      try { const result = fn(); db.exec('COMMIT'); return result; }
      catch (error) { db.exec('ROLLBACK'); throw error; }
    },
    async getAlarm() { return alarm; }, async setAlarm(value) { alarm = value; },
  };
  const ctx = { storage };
  return { db, ctx, object: new ContactCoordinator(ctx), get alarm() { return alarm; } };
}
const reserve = (object, id) => object.fetch(new Request('https://internal/reserve', { method: 'POST', body: id.toString(16).padStart(64, '0') }));

test('atomic coordinator allows one concurrent reservation and preserves it across instance restart', async t => {
  const ctx = setup(); t.after(() => ctx.db.close());
  const results = await Promise.all(Array.from({ length: 10 }, () => reserve(ctx.object, 1)));
  assert.equal(results.filter(result => result.status === 201).length, 1);
  assert.equal(results.filter(result => result.status === 409).length, 9);
  assert.equal((await reserve(new ContactCoordinator(ctx.ctx), 1)).status, 409);
});

test('sliding hourly and daily budgets count reservations, including uncertain deliveries', async t => {
  const ctx = setup(); t.after(() => ctx.db.close());
  t.mock.timers.enable({ apis: ['Date'], now: 1000000000 });
  for (let hour = 0; hour < 5; hour++) {
    for (let i = 0; i < 20; i++) assert.equal((await reserve(ctx.object, hour * 20 + i)).status, 201);
    assert.equal((await reserve(ctx.object, 1000)).status, 429);
    t.mock.timers.tick(3600000);
  }
  assert.equal((await reserve(ctx.object, 1000)).status, 429);
  t.mock.timers.tick(CONTACT_RETENTION_MS);
  assert.equal((await reserve(ctx.object, 1000)).status, 201);
});

test('alarm removes expired fingerprints while retaining newer reservations', async t => {
  const ctx = setup(); t.after(() => ctx.db.close());
  t.mock.timers.enable({ apis: ['Date'], now: 1000000000 });
  await reserve(ctx.object, 1);
  t.mock.timers.tick(1000); await reserve(ctx.object, 2);
  t.mock.timers.tick(CONTACT_RETENTION_MS - 1000);
  await ctx.object.alarm();
  assert.equal(ctx.db.prepare('SELECT COUNT(*) AS count FROM reservations').get().count, 1);
  assert.equal(ctx.alarm, Date.now() + 1000);
  t.mock.timers.tick(1000); await ctx.object.alarm();
  assert.equal(ctx.db.prepare('SELECT COUNT(*) AS count FROM reservations').get().count, 0);
});

test('invalid coordinator requests never reserve capacity', async t => {
  const ctx = setup(); t.after(() => ctx.db.close());
  assert.equal((await ctx.object.fetch(new Request('https://internal'))).status, 405);
  assert.equal((await ctx.object.fetch(new Request('https://internal', { method: 'POST', body: 'private@example.test' }))).status, 400);
  assert.equal(ctx.db.prepare('SELECT COUNT(*) AS count FROM reservations').get().count, 0);
});
