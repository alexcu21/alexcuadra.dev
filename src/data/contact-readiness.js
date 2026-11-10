const bindingTypes = { CONTACT_EMAIL: 'send_email', CONTACT_RATE_LIMITER: 'ratelimit', CONTACT_COORDINATOR: 'durable_object_namespace' };
export const contactSecretNames = ['CONTACT_TO_EMAIL', 'TURNSTILE_SECRET_KEY', 'CONTACT_DEDUP_SECRET'];

export function assertPrivateBindingsNotInVars(config) {
  const names = [...contactSecretNames, 'WPGRAPHQL_APPLICATION_PASSWORD'];
  for (const scope of [config, ...Object.values(config?.env ?? {})]) {
    if (names.some(name => Object.hasOwn(scope?.vars ?? {}, name))) {
      // Never include the offending values or serialized configuration.
      throw new Error('PRIVATE_BINDING_IN_PLAIN_VARS');
    }
  }
}

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
  const privateBindingStates = Object.fromEntries(contactSecretNames.map(name => {
    const binding = find(name);
    return [name, !binding ? 'absent' : binding.type === 'secret_text' ? 'secret'
      : binding.type === 'plain_text' ? 'plaintext' : 'unexpected_type'];
  }));
  return { checks, privateBindingStates, configurationReady: Object.values(checks).every(Boolean) };
}

export function productionContactReadiness(version, stagingSiteKey, { allowSharedWidget = false } = {}) {
  const result = contactVersionReadiness(version, {
    CONTACT_FORM_ENABLED: 'false', CONTACT_ORIGIN: 'https://alexcuadra.dev',
    CONTACT_FROM_EMAIL: 'contact@forms.alexcuadra.dev',
    CONTACT_PRIVACY_PAGE_ID: '180', CONTACT_PRIVACY_ES_PAGE_ID: '182',
  });
  const bindings = version.resources.bindings;
  const key = bindings.find(item => item.name === 'TURNSTILE_SITE_KEY');
  const limiter = bindings.find(item => item.name === 'CONTACT_RATE_LIMITER');
  const checks = { ...result.checks,
    productionWidget: key?.type === 'plain_text' && typeof key.text === 'string'
      && /^0x[A-Za-z0-9_-]{10,100}$/.test(key.text) && (allowSharedWidget || key.text !== stagingSiteKey),
    productionRateLimit: limiter?.type === 'ratelimit' && String(limiter.namespace_id) === '2026103101'
      && limiter.simple?.limit === 5 && limiter.simple?.period === 60,
  };
  return { checks, privateBindingStates: result.privateBindingStates,
    configurationReady: Object.values(checks).every(Boolean),
    missing: Object.entries(checks).filter(([, ready]) => !ready).map(([name]) => name) };
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
