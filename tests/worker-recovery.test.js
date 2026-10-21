import { test } from 'node:test';
import assert from 'node:assert/strict';
import { activeVersion, recoveryPreflight, rehearseRecovery, stagingWorker } from '../scripts/lib/worker-recovery.mjs';

const currentId = '11111111-1111-4111-8111-111111111111';
const targetId = '22222222-2222-4222-8222-222222222222';
const otherId = '33333333-3333-4333-8333-333333333333';
const deployments = id => [{ created_on: '2026-10-20T12:00:00Z', versions: [{ version_id: id, percentage: 100 }] }];
const version = id => ({ id, metadata: { author: 'PRIVATE_AUTHOR' }, resources: {
  bindings: [{ name: 'CONTACT_FORM_ENABLED', type: 'plain_text', text: 'false' },
    { name: 'WPGRAPHQL_AUTH_ENABLED', type: 'plain_text', text: 'true' },
    { name: 'WPGRAPHQL_USERNAME', type: 'plain_text', text: 'PRIVATE_IDENTITY' },
    { name: 'WPGRAPHQL_APPLICATION_PASSWORD', type: 'secret_text' },
    { name: 'CONTACT_COORDINATOR', type: 'durable_object_namespace', class_name: 'ContactCoordinator', namespace_id: 'PRIVATE_NAMESPACE' },
    { name: 'CONTACT_EMAIL', type: 'send_email', destination_address: 'PRIVATE_RECIPIENT' }],
  script: { named_handlers: [{ name: 'ContactCoordinator', handlers: ['class'] }] },
  script_runtime: { exports: { ContactCoordinator: { storage: 'sqlite' } }, compatibility_date: '2026-10-17' },
} });
const fixture = () => ({ worker: stagingWorker, expectedCurrent: currentId, targetId,
  deployments: [...deployments(currentId), { ...deployments(targetId)[0], created_on: '2026-10-19T12:00:00Z' }],
  current: version(currentId), target: version(targetId) });

test('preflight compares configuration regardless of key/binding order and returns only public checks', () => {
  const input = fixture(); input.target.resources.bindings.reverse();
  const result = recoveryPreflight(input);
  assert.equal(result.metadataCompatible, true);
  assert.ok(Object.values(result.checks).every(Boolean));
  assert.doesNotMatch(JSON.stringify(result), /PRIVATE|namespace_id|destination_address|"metadata":/);
});

test('preflight rejects production, unknown targets, changed current state and split traffic', () => {
  for (const change of [x => { x.worker = 'alexcuadra-dev'; }, x => { x.targetId = currentId; },
    x => { x.deployments = deployments(otherId); }, x => { x.deployments = deployments(currentId); },
    x => { x.deployments[0].versions = [{ version_id: currentId, percentage: 50 }, { version_id: targetId, percentage: 50 }]; }]) {
    const input = fixture(); change(input); assert.throws(() => recoveryPreflight(input));
  }
  assert.throws(() => activeVersion([]));
});

test('preflight fails closed on enabled contact, missing auth, private binding differences or runtime/class changes', () => {
  for (const change of [
    x => { x.target.resources.bindings[0].text = 'true'; },
    x => { x.current.resources.bindings[1].text = 'false'; },
    x => { x.target.resources.bindings = x.target.resources.bindings.filter(b => b.type !== 'secret_text'); },
    x => { x.target.resources.bindings.at(-1).destination_address = 'OTHER_PRIVATE_RECIPIENT'; },
    x => { x.target.resources.script_runtime.exports = {}; },
    x => { x.target.resources.script.named_handlers = []; },
    x => { delete x.target.resources.script_runtime; },
    x => { x.target.resources.bindings.push(x.target.resources.bindings[0]); },
  ]) {
    const input = fixture(); change(input); assert.throws(() => recoveryPreflight(input));
  }
});

function rehearsalFixture({ failProbe, failSwitch, concurrentOnProbe } = {}) {
  let active = currentId, probes = 0;
  const switches = [];
  return { switches, getActive: () => active, input: { current: currentId, target: targetId,
    inspectActive: async () => deployments(active),
    switchVersion: async id => { switches.push(id); active = id; if (switches.length === failSwitch) throw Error('PRIVATE_API_ERROR'); },
    probe: async () => { probes++; if (probes === concurrentOnProbe) active = otherId;
      if (probes === failProbe) throw Error('PRIVATE_BODY_OR_HEADERS'); return { passed: true }; },
  } };
}

test('rehearsal verifies baseline, previous version and restored version in order', async () => {
  const ctx = rehearsalFixture(); const result = await rehearseRecovery(ctx.input);
  assert.equal(result.complete, true); assert.equal(ctx.getActive(), currentId);
  assert.deepEqual(ctx.switches, [targetId, currentId]);
  assert.deepEqual(result.phases.map(p => p.phase), ['baseline', 'rollback', 'restored']);
});

test('rollback health failure still restores and verifies the initial version without reporting full success', async () => {
  const ctx = rehearsalFixture({ failProbe: 2 }); const result = await rehearseRecovery(ctx.input);
  assert.equal(result.complete, false); assert.equal(result.restored, true); assert.equal(ctx.getActive(), currentId);
  assert.deepEqual(ctx.switches, [targetId, currentId]);
  assert.doesNotMatch(JSON.stringify(result), /PRIVATE/);
});

test('lost rollback acknowledgement is followed by inspection and restoration', async () => {
  const ctx = rehearsalFixture({ failSwitch: 1 }); const result = await rehearseRecovery(ctx.input);
  assert.equal(result.complete, false); assert.equal(result.restored, true); assert.equal(ctx.getActive(), currentId);
  assert.deepEqual(ctx.switches, [targetId, currentId]);
});

test('baseline failure or concurrent change before rollback performs no mutation', async () => {
  for (const opts of [{ failProbe: 1 }, { concurrentOnProbe: 1 }]) {
    const ctx = rehearsalFixture(opts); const result = await rehearseRecovery(ctx.input);
    assert.equal(result.rollbackAttempted, false); assert.deepEqual(ctx.switches, []);
  }
});

test('a concurrent deployment during the exercise is never overwritten by restoration', async () => {
  const ctx = rehearsalFixture({ concurrentOnProbe: 2 }); const result = await rehearseRecovery(ctx.input);
  assert.equal(result.complete, false); assert.equal(result.restored, false); assert.equal(ctx.getActive(), otherId);
  assert.deepEqual(ctx.switches, [targetId]); assert.equal(result.restoreFailure, 'RESTORE_OR_VERIFICATION_FAILED');
});

test('failed final health check is distinguished from restoring the starting version', async () => {
  const ctx = rehearsalFixture({ failProbe: 3 }); const result = await rehearseRecovery(ctx.input);
  assert.equal(result.restored, true); assert.equal(result.complete, false); assert.equal(ctx.getActive(), currentId);
  assert.equal(result.restoreFailure, 'RESTORE_OR_VERIFICATION_FAILED');
});
