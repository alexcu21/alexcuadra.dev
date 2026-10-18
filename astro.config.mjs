// @ts-check
import { defineConfig } from 'astro/config';
import dotenv from 'dotenv';
import tailwindcss from '@tailwindcss/vite';
import cloudflare from '@astrojs/cloudflare';

dotenv.config();

export default defineConfig({
  site: 'https://alexcuadra.dev',
  output: 'server',
  session: false,
  adapter: cloudflare({ imageService: 'compile' }),
  vite: { plugins: [tailwindcss()] },
});
