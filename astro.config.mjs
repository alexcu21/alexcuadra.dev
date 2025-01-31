// @ts-check
import { defineConfig } from 'astro/config';
import dotenv from 'dotenv'
import tailwind from '@astrojs/tailwind';
import cloudflare from '@astrojs/cloudflare';

dotenv.config()

// https://astro.build/config
export default defineConfig({
  site: 'https://alexcuadra.dev',
  integrations: [tailwind()],
  output: 'server',
  adapter: cloudflare(),
});