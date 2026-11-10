import { handle } from '@astrojs/cloudflare/handler';
export { ContactCoordinator } from './data/contact-coordinator.js';
export { CmsOriginCoordinator } from './data/cms-origin-coordinator.js';

export default { fetch: handle };
