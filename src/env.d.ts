/// <reference path="../.astro/types.d.ts" />

declare module 'cloudflare:workers' {
  export const env: {
    WPGRAPHQL_AUTH_ENABLED?: string;
    WPGRAPHQL_USERNAME?: string;
    WPGRAPHQL_APPLICATION_PASSWORD?: string;
  };
}
