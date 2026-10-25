// Isolated browser check of the actual compiled client chunk under CSP.
import { readdir, readFile } from 'node:fs/promises';
const directory = new URL('../../../../../../dist/client/_astro/', import.meta.url);
export async function GET() {
  const files = (await readdir(directory)).filter(name => /^PageMotion\..*\.js$/.test(name));
  if (files.length !== 1) return new Response('Build the portfolio before this check.', { status: 503 });
  return new Response(await readFile(new URL(files[0], directory), 'utf8'), {
    headers: { 'Content-Type': 'application/javascript', 'Cache-Control': 'no-store' },
  });
}
