import {test} from 'node:test';
import assert from 'node:assert/strict';
import {publicProjectTaxonomies} from '../src/data/project-taxonomies.js';
import {localizedProjection} from '../src/data/localized-content.js';
import {getProjectsInfo} from '../src/data/client.js';
import {withCmsRequest} from '../src/data/cms-request.js';

const connection=nodes=>({nodes,pageInfo:{hasNextPage:false}});
const term=(name,code='ES')=>({name,language:{code},secret:'PRIVATE_TERM_METADATA'});
const node=()=>({status:'publish',isRestricted:false,hasPassword:false,language:{code:'ES'},
  title:'Proyecto',slug:'proyecto',translations:[],categories:connection([term('Soporte técnico')]),
  tags:connection([term('DNS'),term('Seguridad')])});

test('dedicated taxonomy projection is idempotent and strips extra term metadata',()=>{
  const projected=publicProjectTaxonomies(node(),'es');
  assert.equal(projected.categories.nodes[0].name,'Soporte técnico');
  assert.equal(projected.tags.nodes[1].name,'Seguridad');
  assert.deepEqual(publicProjectTaxonomies(projected,'es'),projected);
  assert.doesNotMatch(JSON.stringify(projected),/PRIVATE_TERM_METADATA/);
});

for(const field of ['tags','categories']){
  for(const invalid of [undefined,{nodes:[]},connection([term('English','EN')]),connection([term('Missing',null)]),
    connection([{name:'Missing language'}]),connection([term(' ')]),{nodes:[],pageInfo:{hasNextPage:true}}]){
    test(`${field} rejects missing, truncated or foreign-language terms: ${JSON.stringify(invalid)}`,()=>{
      const value={...node(),[field]:invalid};
      assert.equal(publicProjectTaxonomies(value,'es'),undefined);
      assert.equal(localizedProjection({projects:{nodes:[value],pageInfo:{hasNextPage:false}}},'projects','es'),undefined);
    });
  }
}

test('empty assignments remain explicit and do not borrow terms from another locale',()=>{
  assert.deepEqual(publicProjectTaxonomies({tags:connection([]),categories:connection([])},'es'),
    {tags:connection([]),categories:connection([])});
});

test('localized reads query only dedicated taxonomy fields and never cache mixed-language terms',async t=>{
  t.mock.method(console,'info',()=>{});t.mock.method(console,'error',()=>{});
  let puts=0;
  t.mock.method(globalThis,'fetch',async(_url,init)=>{
    const {query}=JSON.parse(init.body);
    assert.match(query,/categories:\s*projectCategories\(first: 100\)/);
    assert.match(query,/tags:\s*projectTechnologies\(first: 100\)/);
    return Response.json({data:{projects:{nodes:[{...node(),tags:connection([term('Security','EN')])}],
      pageInfo:{hasNextPage:false}}}});
  });
  const response=await withCmsRequest(async()=>Response.json(await getProjectsInfo('es')),
    {cacheOrigin:'https://example.test',cmsConfig:{WPGRAPHQL_AUTH_ENABLED:'true',WPGRAPHQL_USERNAME:'reader',
      WPGRAPHQL_APPLICATION_PASSWORD:'synthetic'},edgeCache:{match:async()=>undefined,put:async()=>{puts++;}}});
  assert.equal(response.status,503);assert.equal(puts,0);
});
