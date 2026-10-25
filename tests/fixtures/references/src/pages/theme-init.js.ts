// Serve the production initializer in the isolated preview too.
import script from '../../../../../public/theme-init.js?raw';
export const GET = () => new Response(script, { headers: { 'Content-Type': 'application/javascript' } });
