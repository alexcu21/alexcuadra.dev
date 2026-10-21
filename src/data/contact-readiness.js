const bindingTypes = { CONTACT_EMAIL: 'send_email', CONTACT_RATE_LIMITER: 'ratelimit', CONTACT_COORDINATOR: 'durable_object_namespace' };
export const contactSecretNames = ['CONTACT_TO_EMAIL', 'TURNSTILE_SECRET_KEY', 'CONTACT_DEDUP_SECRET'];

// Project metadata immediately: never return raw vars, secrets, recipients or authors.
export function contactVersionReadiness(version, expected) {
  const bindings = version?.resources?.bindings;
  if (!Array.isArray(bindings)) throw new Error('CONTACT_METADATA_UNAVAILABLE');
  const find = name => bindings.find(binding => binding.name === name);
  const checks = {};
  for (const [name, type] of Object.entries(bindingTypes)) checks[name] = find(name)?.type === type;
  for (const name of contactSecretNames) checks[name] = find(name)?.type === 'secret_text';
  for (const [name, value] of Object.entries(expected)) {
    checks[name] = find(name)?.type === 'plain_text' && find(name)?.text === value;
  }
  const email = find('CONTACT_EMAIL');
  checks.senderRestriction = email?.allowed_sender_addresses?.length === 1
    && email.allowed_sender_addresses[0] === expected.CONTACT_FROM_EMAIL;
  const coordinator = find('CONTACT_COORDINATOR');
  checks.localCoordinatorClass = coordinator?.class_name === 'ContactCoordinator' && !coordinator?.script_name;
  return { checks, configurationReady: Object.values(checks).every(Boolean) };
}

export function latestActiveVersions(deployments) {
  if (!Array.isArray(deployments) || !deployments.length) throw new Error('CONTACT_DEPLOYMENT_UNAVAILABLE');
  const ordered = [...deployments].sort((a, b) => Date.parse(b.created_on) - Date.parse(a.created_on));
  const versions = ordered[0]?.versions;
  if (!Array.isArray(versions) || !versions.length || versions.some(item =>
    !/^[a-f0-9-]{36}$/i.test(item.version_id) || !Number.isFinite(item.percentage) || item.percentage < 0)) {
    throw new Error('CONTACT_DEPLOYMENT_UNAVAILABLE');
  }
  const active = versions.filter(item => item.percentage > 0);
  if (Math.abs(active.reduce((sum, item) => sum + item.percentage, 0) - 100) > 0.001) throw new Error('CONTACT_DEPLOYMENT_UNAVAILABLE');
  return active.map(item => ({ id: item.version_id, percentage: item.percentage }));
}
