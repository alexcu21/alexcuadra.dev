import { AsyncLocalStorage } from 'node:async_hooks';
const context=new AsyncLocalStorage();
const base='http://127.0.0.1:4323';
export class ProviderError extends Error { constructor(code,status=503){super(code);this.status=status;} }
export const withPreviewRequest=(next,cookie)=>context.run({cookie,cache:new Map()},next);
export const withStagingRequest=(next,fetchCatalog,mode='preview')=>context.run({fetchCatalog,mode,cache:new Map()},next);
export function read(operation,collection,locale,extra={}) {
  if(!['en','es'].includes(locale))throw new TypeError('Invalid locale');
  const state=context.getStore();
  if(!state)throw new ProviderError('REQUEST_CONTEXT_REQUIRED');
  const query=new URLSearchParams({operation,collection,locale,...extra});
  const key=query.toString();
  if(!state.cache.has(key))state.cache.set(key,(async()=>{
    let response;
    try{
      if(state.fetchCatalog)response=await state.fetchCatalog(query);
      else if(import.meta.env.DEV)response=await fetch(`${base}/api/preview-v1.json?${query}`,{headers:{Authorization:`Bearer ${import.meta.env.CMS_PREVIEW_READ_KEY}`,Cookie:state.cookie},signal:AbortSignal.timeout(5000)});
      else throw new ProviderError('REQUEST_CONTEXT_REQUIRED');
    }catch{throw new ProviderError('CMS_UNAVAILABLE');}
    if(response.status===404)return null;
    if(!response.ok)throw new ProviderError(response.status===401?'PREVIEW_AUTH_REQUIRED':'CMS_UNAVAILABLE',response.status===401?401:503);
    const result=await response.json();
    const mode=state.mode??'preview';
    if(result.version!==1||result.mode!==mode||typeof result.contentVersion!=='string'||(operation==='list'&&result.pageInfo?.nextCursor))throw new ProviderError('INVALID_CONTRACT');
    const items=Array.isArray(result.data)?result.data:[result.data];
    for(const item of items){
      if(item&&'locale'in item&&item.locale!==locale)throw new ProviderError('LOCALE_MISMATCH');
      if(mode==='published'&&item&&'locale'in item&&item.publicationStatus!=='published')throw new ProviderError('INVALID_PUBLICATION_STATUS');
    }
    return result.data;
  })());
  return state.cache.get(key);
}
export async function home(locale){
  const d=await read('page','pages',locale,{pageKey:'home'});if(!d)return null;
  const f=d.fields,skills={};
  for(let i=0;i<3;i++){skills[`skillName${i+1}`]=f.skills[i]?.name??'';skills[`skillDescription${i+1}`]=f.skills[i]?.description??'';}
  return {id:d.id,slug:d.slug,title:d.title,content:d.contentHtml,locale,publicationStatus:d.publicationStatus,translationLinks:d.translations,
    homePageFields:{name_field:f.name,tagLine_field:f.tagline,role1_field:f.roles[0],role2_field:f.roles[1],role3_field:f.roles[2],photo_field:d.image?{node:{sourceUrl:d.image.url,link:d.image.url}}:null,skills,email:f.email,twitterUrl:f.social.twitter,githubUrl:f.social.github,youtubeUrl:f.social.youtube,linkedinUrl:f.social.linkedin}};
}
const states=['idea','in_progress','beta','released','archived'];
export function lab(d,locale){
  const f=d.fields,index=states.indexOf(f.projectStatus);if(index<0)throw new ProviderError('INVALID_LAB_STATUS');
  return {title:d.title,slug:d.slug,excerpt:d.excerpt,content:d.contentHtml,image:d.image?.url??null,translations:d.translations,
    state:{value:f.projectStatus,label:(locale==='es'?['Idea','En desarrollo','Beta','Publicado','Archivado']:['Idea','In progress','Beta','Released','Archived'])[index]},purpose:f.purpose,learningGoals:f.learningGoals,technicalApproach:f.technicalApproachHtml,qualityStrategy:f.qualityStrategyHtml,devopsNotes:f.devopsNotesHtml,lastReviewedAt:f.lastReviewedAt,repositoryUrl:f.repositoryUrl,demoUrl:f.demoUrl,featured:f.featured,displayOrder:f.displayOrder};
}

export async function catalogPage(kind,locale){
 if(kind==='home')return home(locale);
 const d=await read('page','pages',locale,{pageKey:kind});if(!d)return null;
 return {...common(d),aboutPage:{introText:d.fields.introText,education:d.fields.education,skillsText:d.fields.skillsText}};
}
export function common(d){return{id:d.id,slug:d.slug,title:d.title,locale:d.locale,publicationStatus:d.publicationStatus,language:{code:d.locale.toUpperCase()},content:d.contentHtml,excerpt:d.excerpt,translations:d.translations,translationLinks:d.translations,dateGmt:d.originalPublishedAt,modified:d.originalModifiedAt,modifiedGmt:d.originalModifiedAt,featuredImage:d.image?{node:{sourceUrl:d.image.url}}:null};}
export async function projects(locale){return(await read('list','projects',locale,{limit:'20'})).map(d=>({...common(d),projects:{projectTagline:d.fields.projectTagline,githuburl:d.fields.githubUrl,websiteurl:d.fields.websiteUrl},categories:{nodes:d.terms.project_category},tags:{nodes:d.terms.project_technology}}));}
