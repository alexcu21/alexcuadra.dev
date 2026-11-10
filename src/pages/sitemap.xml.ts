import type { APIRoute } from 'astro';
import { env } from 'cloudflare:workers';
import { loadSitemap } from '../data/sitemap.js';

export const GET: APIRoute = async () => {
  const xml = await loadSitemap(env);
  return new Response(xml, { headers: { 'Content-Type': 'application/xml; charset=utf-8', 'Cache-Control': 'no-store' } });
};
