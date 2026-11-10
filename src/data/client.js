import{home,projects,ProviderError}from './emdash-provider.js';
export async function getHomeInfo(locale='en'){const page=await home(locale);if(!page)throw new ProviderError('HOME_NOT_FOUND',404);return[page];}
export const getProjectsInfo=projects;
