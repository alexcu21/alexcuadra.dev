import assert from 'node:assert/strict';
import { latestActiveVersions } from '../../src/data/contact-readiness.js';

export const stagingWorker = 'alexcuadra-dev-staging';
const uuid = /^[a-f\d]{8}-(?:[a-f\d]{4}-){3}[a-f\d]{12}$/i;
const canonical = value => value && typeof value === 'object'
  ? Array.isArray(value) ? value.map(canonical) : Object.fromEntries(Object.keys(value).sort().map(key => [key, canonical(value[key])])) : value;
const same = (a, b) => JSON.stringify(canonical(a)) === JSON.stringify(canonical(b));

export function activeVersion(deployments) {
  const active = latestActiveVersions(deployments);
  assert.equal(active.length, 1, 'RECOVERY_SPLIT_DEPLOYMENT');
  assert.equal(active[0].percentage, 100, 'RECOVERY_TRAFFIC');
  return active[0].id;
}

export function recoveryPreflight({ worker, expectedCurrent, targetId, deployments, current, target }) {
  assert.equal(worker, stagingWorker, 'RECOVERY_STAGING_ONLY');
  assert.ok(uuid.test(expectedCurrent) && uuid.test(targetId) && expectedCurrent !== targetId, 'RECOVERY_VERSION_IDS');
  assert.equal(activeVersion(deployments), expectedCurrent, 'RECOVERY_CURRENT_CHANGED');
  assert.ok(deployments.some(d => d.versions?.some(v => v.version_id === targetId)), 'RECOVERY_TARGET_NOT_DEPLOYED');
  assert.equal(current?.id, expectedCurrent, 'RECOVERY_CURRENT_METADATA');
  assert.equal(target?.id, targetId, 'RECOVERY_TARGET_METADATA');
  const checks = {};
  for (const [label, version] of [['current', current], ['target', target]]) {
    const bindings = version.resources?.bindings;
    assert.ok(Array.isArray(bindings) && bindings.length > 0, 'RECOVERY_BINDINGS_MISSING');
    assert.equal(new Set(bindings.map(b => b.name)).size, bindings.length, 'RECOVERY_DUPLICATE_BINDING');
    const find = name => bindings.find(b => b.name === name);
    checks[`${label}ContactDisabled`] = find('CONTACT_FORM_ENABLED')?.type === 'plain_text' && find('CONTACT_FORM_ENABLED')?.text === 'false';
    checks[`${label}CmsAuthEnabled`] = find('WPGRAPHQL_AUTH_ENABLED')?.type === 'plain_text' && find('WPGRAPHQL_AUTH_ENABLED')?.text === 'true'
      && find('WPGRAPHQL_APPLICATION_PASSWORD')?.type === 'secret_text'
      && find('WPGRAPHQL_USERNAME')?.type === 'plain_text' && Boolean(find('WPGRAPHQL_USERNAME')?.text);
    checks[`${label}CoordinatorPresent`] = find('CONTACT_COORDINATOR')?.type === 'durable_object_namespace'
      && find('CONTACT_COORDINATOR')?.class_name === 'ContactCoordinator' && !find('CONTACT_COORDINATOR')?.script_name
      && version.resources?.script?.named_handlers?.some(h => h.name === 'ContactCoordinator' && h.handlers?.includes('class')) === true;
  }
  const ordered = v => [...v.resources.bindings].sort((a, b) => a.name.localeCompare(b.name));
  checks.bindingsIdentical = same(ordered(current), ordered(target));
  checks.runtimePresent = current.resources?.script_runtime != null && target.resources?.script_runtime != null;
  checks.runtimeIdentical = checks.runtimePresent && same(current.resources.script_runtime, target.resources.script_runtime);
  checks.namedHandlersIdentical = same(current.resources?.script?.named_handlers, target.resources?.script?.named_handlers);
  assert.ok(Object.values(checks).every(Boolean), 'RECOVERY_INCOMPATIBLE_VERSIONS');
  // Never return metadata values, authors, secrets or private recipients.
  return { worker, current: expectedCurrent, target: targetId, checks, metadataCompatible: true };
}

// The adapter must inspect again immediately before switching. Restore even if the
// switch response is lost; stop if inspection detects a concurrent deployment.
export async function rehearseRecovery({ current, target, inspectActive, switchVersion, probe }) {
  const result = { rollbackAttempted: false, rollbackVerified: false, restored: false, phases: [] };
  const inspect = async () => activeVersion(await inspectActive());
  let phase = 'baseline';
  try {
    assert.equal(await inspect(), current, 'RECOVERY_CURRENT_CHANGED');
    result.phases.push({ phase: 'baseline', version: current, checks: await probe() });
    assert.equal(await inspect(), current, 'RECOVERY_CURRENT_CHANGED');
    phase = 'rollback';
    result.rollbackAttempted = true;
    await switchVersion(target, 'Staging recovery rehearsal: previous compatible version');
    assert.equal(await inspect(), target, 'RECOVERY_TARGET_NOT_ACTIVE');
    result.phases.push({ phase: 'rollback', version: target, checks: await probe() });
    result.rollbackVerified = true;
  } catch {
    result.failure = 'REHEARSAL_FAILED';
    result.failurePhase = phase;
  } finally {
    if (result.rollbackAttempted) {
      try {
        const active = await inspect();
        assert.ok(active === current || active === target, 'RECOVERY_CONCURRENT_DEPLOYMENT');
        if (active === target) await switchVersion(current, 'Staging recovery rehearsal: restore starting version');
        assert.equal(await inspect(), current, 'RECOVERY_RESTORE_NOT_ACTIVE');
        result.restored = true;
        result.phases.push({ phase: 'restored', version: current, checks: await probe() });
      } catch {
        result.restoreFailure = 'RESTORE_OR_VERIFICATION_FAILED';
      }
    }
  }
  result.complete = result.rollbackVerified && result.restored && !result.failure && !result.restoreFailure;
  return result;
}
