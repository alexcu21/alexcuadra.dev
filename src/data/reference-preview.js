import{read,ProviderError}from'./emdash-provider.js';
export const referenceBase=locale=>locale==='es'?'/es/articulos/':'/articles/';
export const channelPath=(locale,slug)=>referenceBase(locale)+(locale==='es'?'categoria/':'category/')+encodeURIComponent(slug)+'/';
export const referenceList=locale=>read('list','references',locale,{limit:'20'});
export function referenceChannels(entries){
 const channels=new Map();
 for(const entry of entries)for(const term of entry.terms.reference_category){
  if(!channels.has(term.slug))channels.set(term.slug,{...term,ids:new Set()});
  channels.get(term.slug).ids.add(entry.id);
 }
 return [...channels.values()].map(({ids,...term})=>({...term,count:ids.size})).sort((a,b)=>a.slug.localeCompare(b.slug));
}
export async function referencePage(locale,{category,slug}={}){
 const entries=await referenceList(locale),channels=referenceChannels(entries);
 if(category===undefined)return{mode:'archive',entries,channels};
 const channel=channels.find(c=>c.slug===category);
 if(slug===undefined){
  if(channel)return{mode:'category',category:channel,entries:entries.filter(e=>e.terms.reference_category.some(t=>t.slug===category)),channels};
  if(locale==='en'){
   const legacy='/articles/category/'+encodeURIComponent(category)+'/';
   const candidates=[...entries,...await referenceList('es')].filter(e=>e.legacyRoutes.some(path=>path.startsWith(legacy)));
   const targets=new Set(candidates.flatMap(e=>e.terms.reference_category.map(t=>channelPath(e.locale,t.slug))));
   if(targets.size>1)throw new ProviderError('AMBIGUOUS_LEGACY_CATEGORY');
   if(targets.size===1)return{redirect:[...targets][0]};
  }
  return null;
 }
 const entry=channel&&entries.find(e=>e.slug===slug&&e.terms.reference_category.some(t=>t.slug===category));
 if(entry)return{mode:'detail',entry,category:channel,entries:[],channels};
 if(locale==='en'){
  const legacy='/articles/category/'+encodeURIComponent(category)+'/'+encodeURIComponent(slug)+'/';
  const candidates=[...entries,...await referenceList('es')].filter(e=>e.legacyRoutes.includes(legacy));
  if(candidates.length>1)throw new ProviderError('AMBIGUOUS_LEGACY_REFERENCE');
  if(candidates.length===1)return{redirect:candidates[0].route};
 }
 return null;
}
