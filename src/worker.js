import { handle } from '@astrojs/cloudflare/handler';
export { ContactCoordinator } from './data/contact-coordinator.js';

export default { fetch: handle };
