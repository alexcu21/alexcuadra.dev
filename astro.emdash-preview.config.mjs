import {defineConfig} from 'astro/config';
import cloudflare from '@astrojs/cloudflare';
import tailwindcss from '@tailwindcss/vite';
import {contentSecurityPolicy} from './src/data/content-security-policy.js';
export default defineConfig({site:'https://alexcuadra.dev',output:'server',session:false,
 adapter:cloudflare({configPath:'./wrangler.jsonc',remoteBindings:false,inspectorPort:false,persistState:{path:'./.state'}}),
 security:{csp:contentSecurityPolicy},server:{host:'127.0.0.1',port:4324},
 vite:{plugins:[tailwindcss()],cacheDir:'./.vite-cache',server:{strictPort:true}}});
