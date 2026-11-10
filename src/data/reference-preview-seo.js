import{canonicalUrl}from'./page-seo.js';
import{referenceBase,channelPath}from'./reference-preview.js';
export function referencePreviewBreadcrumbs(locale,{category,entry}){
 const items=[{name:locale==='es'?'Artículos y menciones':'Articles & Mentions',path:referenceBase(locale)}];
 if(category)items.push({name:category.name,path:channelPath(locale,category.slug)});
 if(entry)items.push({name:entry.title,path:entry.route});
 return JSON.stringify({'@context':'https://schema.org','@type':'BreadcrumbList',itemListElement:items.map((item,index)=>({'@type':'ListItem',position:index+1,name:item.name,item:canonicalUrl(item.path)}))}).replace(/</g,'\\u003c');
}
