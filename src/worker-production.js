import { handle } from '@astrojs/cloudflare/handler';
export { CmsOriginCoordinator } from './data/cms-origin-coordinator.js';
export { ContactCoordinator } from './data/contact-coordinator.js';

export default { fetch: handle };
