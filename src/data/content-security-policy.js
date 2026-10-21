// Astro hashes its generated scripts/styles. No inline script execution allowance.
/** @type {NonNullable<import('astro').AstroUserConfig['security']>['csp']} */
export const contentSecurityPolicy = {
  scriptDirective: { resources: ["'self'", 'https://challenges.cloudflare.com'] },
  styleDirective: { resources: ["'self'", 'https://fonts.googleapis.com', { resource: "'unsafe-inline'", kind: 'attribute' }] },
  directives: ["base-uri 'self'", "object-src 'none'", "form-action 'self'", 'frame-src https://challenges.cloudflare.com'],
};
