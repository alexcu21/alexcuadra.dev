// Server-only configuration. Never forward credentials from the visitor.
export function cmsAuthHeaders(config = {}) {
  if (config.WPGRAPHQL_AUTH_ENABLED !== 'true') return new Headers();
  const username = config.WPGRAPHQL_USERNAME;
  const password = config.WPGRAPHQL_APPLICATION_PASSWORD;
  if (typeof username !== 'string' || !username.trim() || username.includes(':')
    || typeof password !== 'string' || !password.trim()) {
    throw new Error('CMS_AUTH_CONFIG');
  }
  const bytes = new TextEncoder().encode(`${username}:${password.replace(/\s/g, '')}`);
  return new Headers({ Authorization: `Basic ${btoa(String.fromCharCode(...bytes))}` });
}
