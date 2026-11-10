import {isPreviewRoute} from './data/preview-route.js';
import {defineMiddleware}from'astro:middleware';
import {withPreviewRequest}from'./data/emdash-provider.js';
const cms='http://127.0.0.1:4323';
const headers={'Cache-Control':'private, no-store','X-Robots-Tag':'noindex, nofollow'};
export const onRequest=defineMiddleware(async(context,next)=>{
  if(!import.meta.env.DEV)return new Response('Local preview only',{status:404,headers});
  if(!['GET','HEAD'].includes(context.request.method))return new Response('Read-only preview',{status:405,headers});
  if(context.url.pathname.startsWith('/_astro/')||context.url.pathname.startsWith('/@'))return next();
  const session=/(?:^|;)\s*(alexcuadra-real-session=[^;]+)/.exec(context.request.headers.get('cookie')??'')?.[1]??'';
  if(!session)return new Response('<a href="http://127.0.0.1:4323/_emdash/api/auth/dev-bypass?redirect=/frontend-preview">Entrar a la vista previa local</a>',{status:401,headers:{...headers,'Content-Type':'text/html;charset=utf-8'}});
  let auth;
  try{auth=await fetch(`${cms}/api/preview-session.json`,{headers:{Cookie:session+'; emdash-edit-mode=true'},signal:AbortSignal.timeout(5000)});}catch{return new Response('CMS unavailable',{status:503,headers});}
  if(!auth.ok)return new Response(auth.status===401?'Entra de nuevo al admin local de la muestra':'CMS unavailable',{status:auth.status===401?401:503,headers});
  if(!isPreviewRoute(context.url.pathname))return new Response('Esta ruta está fuera del alcance de la vista previa local.',{status:501,headers});
  try{
    const response=await withPreviewRequest(next,session+'; emdash-edit-mode=true');
    const out=new Headers(response.headers);for(const[k,v]of Object.entries(headers))out.set(k,v);
    return new Response(response.body,{status:response.status,headers:out});
  } catch (error) {
    const status = typeof error === 'object' && error !== null && 'status' in error ? error.status : undefined;
    return new Response('No se pudo leer el contenido del CMS', {
      status: status === 401 || status === 404 || status === 501 ? status : 503,
      headers,
    });
  }
});
