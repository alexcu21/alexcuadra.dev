/// <reference path="../.astro/types.d.ts" />

declare namespace App { interface Locals { requestId?: string; } }

declare module 'cloudflare:workers' {
  export const env: {
    WPGRAPHQL_AUTH_ENABLED?: string;
    WPGRAPHQL_USERNAME?: string;
    WPGRAPHQL_APPLICATION_PASSWORD?: string;
    CONTACT_FORM_ENABLED?: string;
    CONTACT_ORIGIN?: string;
    CONTACT_FROM_EMAIL?: string;
    CONTACT_TO_EMAIL?: string;
    CONTACT_PRIVACY_PAGE_ID?: string;
    CONTACT_PRIVACY_ES_PAGE_ID?: string;
    CONTACT_DEDUP_SECRET?: string;
    CONTACT_COORDINATOR?: {
      idFromName(name: string): unknown;
      get(id: unknown): { fetch(url: string, init: RequestInit): Promise<Response> };
    };
    TURNSTILE_SECRET_KEY?: string;
    TURNSTILE_SITE_KEY?: string;
    CONTACT_EMAIL?: { send(message: { to: string; from: string; replyTo: string; subject: string; text: string }): Promise<{ messageId: string }> };
    CONTACT_RATE_LIMITER?: { limit(input: { key: string }): Promise<{ success: boolean }> };
  };
}
