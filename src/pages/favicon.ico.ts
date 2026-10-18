import type { APIRoute } from 'astro';

// Browsers may request this legacy URL even when the head specifies favicon.svg.
// Resolve it without rendering the CMS-backed 404 layout.
export const GET: APIRoute = () => new Response(null, {
  status: 308,
  headers: { Location: '/favicon.svg', 'Cache-Control': 'public, max-age=86400' },
});

export const HEAD = GET;
