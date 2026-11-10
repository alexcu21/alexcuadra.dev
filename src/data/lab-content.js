import {read,lab} from './emdash-provider.js';
export async function hasLabProjects(locale){return (await read('availability','lab_projects',locale)).available;}
export async function getLabProjects(locale){return (await read('list','lab_projects',locale,{limit:'20'})).map(d=>lab(d,locale)).sort((a,b)=>a.displayOrder-b.displayOrder);}
export async function getLabProject(slug,locale){const d=await read('detail','lab_projects',locale,{slug});return d?lab(d,locale):null;}
export async function getHomeLabProjects(locale){return (await getLabProjects(locale)).filter(d=>d.featured).slice(0,3);}
