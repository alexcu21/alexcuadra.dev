import{referenceList,referenceChannels}from './reference-preview.js';
export async function getReferenceCategoryCards(locale='en'){return referenceChannels(await referenceList(locale));}
