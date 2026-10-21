import type { APIRoute } from 'astro';

export const GET: APIRoute = ({ url }) => new Response(
  `User-agent: *\nAllow: /\n${url.hostname === 'alexcuadra.dev' ? '\nSitemap: https://alexcuadra.dev/sitemap.xml\n' : ''}`,
  { headers: { 'Content-Type': 'text/plain; charset=utf-8', 'Cache-Control': 'no-store' } },
);
