// No inline script/style elements or Vite client: only the compiled public asset.
export function GET() {
  return new Response(`<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Motion con CSP</title></head><body>
    <main><h1 data-motion="hero">Animación con la política CSP</h1>
    <p data-motion="reveal">Este contenido permanece visible.</p></main>
    <script type="module" src="/preview/motion-bundle.js"></script></body></html>`, {
    headers: { 'Content-Type': 'text/html', 'Cache-Control': 'no-store',
      'Content-Security-Policy': "script-src 'self' https://challenges.cloudflare.com; style-src 'self' https://fonts.googleapis.com; style-src-attr 'unsafe-inline'; base-uri 'self'; object-src 'none'; form-action 'self'; frame-src https://challenges.cloudflare.com" },
  });
}
