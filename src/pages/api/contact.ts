import type { APIRoute } from 'astro';
import { env } from 'cloudflare:workers';
import { handleContactRequest } from '../../data/contact-handler.js';

export const prerender = false;
export const ALL: APIRoute = ({ request, locals }) => handleContactRequest(request, env, { requestId: locals.requestId });
