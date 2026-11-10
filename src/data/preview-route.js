const allowedPath = /^(?:\/|\/es\/?|\/(?:about|privacy|contact)\/?|\/es\/(?:sobre-mi|privacidad|contacto)\/?|\/articles(?:\/category\/[^/]+(?:\/[^/]+)?)?\/?|\/es\/articulos(?:\/categoria\/[^/]+(?:\/[^/]+)?)?\/?|\/(?:work|blog|lab)(?:\/[^/]+)?\/?|\/es\/(?:proyectos|blog|laboratorio)(?:\/[^/]+)?\/?)$/;
export const isPreviewRoute = pathname => allowedPath.test(pathname);
