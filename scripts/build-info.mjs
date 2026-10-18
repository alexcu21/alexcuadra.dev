import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';

const lock = JSON.parse(readFileSync(new URL('../package-lock.json', import.meta.url), 'utf8'));
let commit = 'unavailable';
try {
  commit = execFileSync('git', ['rev-parse', 'HEAD'], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim();
} catch {
  // Source archives may not contain Git metadata.
}
console.log(`[build-info] commit=${commit} node=${process.version}`);
for (const name of ['astro', '@astrojs/cloudflare', 'wrangler', 'tailwindcss']) {
  console.log(`[build-info] ${name}=${lock.packages[`node_modules/${name}`]?.version ?? 'missing'}`);
}
