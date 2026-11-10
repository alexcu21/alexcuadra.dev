// Fail closed if an unported module tries to query WordPress in the preview.
export function request(){throw Object.assign(new Error('WORDPRESS_DISABLED_IN_PREVIEW'),{status:503});}
export function cmsFailure(){return Object.assign(new Error('CMS_UNAVAILABLE'),{status:503});}
export const withCmsRequest=next=>next();
