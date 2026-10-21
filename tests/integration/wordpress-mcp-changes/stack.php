<?php
use AlexCuadra\EditorialChanges as Changes;
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { throw new RuntimeException('ISOLATED_GRAPHQL_STACK_REQUIRED'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
global $wpdb;
$cases=[];$users=[];$credentials=[];$sessions=[];$sequence=0;$lastDiagnostic=[];
$report=['environment'=>'isolated-editorial-acf-graphql','sharedCmsModified'=>false,'sharedPermissionsModified'=>false,'hostPortsPublished'=>false,'emailSent'=>false];
$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED: '.$name);} $cases[]=$name;};
$request=static function($payload,$auth=null,$session=null,$path='/?graphql',$method='POST')use(&$lastDiagnostic){
    $h=['Content-Type'=>'application/json','Accept'=>'application/json, text/event-stream'];
    if($auth!==null){$h['Authorization']=$auth;}
    if($session!==null){$h['Mcp-Session-Id']=$session;$h['MCP-Protocol-Version']='2025-11-25';}
    $r=wp_remote_request('http://wordpress'.$path,['method'=>$method,'headers'=>$h,'body'=>$payload===null?null:wp_json_encode($payload),'timeout'=>25,'redirection'=>0]);
    if(is_wp_error($r)){throw new RuntimeException('STACK_HTTP_FAILED');}
    $j=json_decode(wp_remote_retrieve_body($r),true);$status=wp_remote_retrieve_response_code($r);
    $lastDiagnostic=['status'=>$status,'json'=>is_array($j)];
    $notification=isset($payload['method'])&&str_starts_with($payload['method'],'notifications/');
    if(!is_array($j)&&$method!=='DELETE'&&!($notification&&$status===202)){throw new RuntimeException('STACK_NON_JSON');}
    return ['status'=>$status,'json'=>$j,'session'=>wp_remote_retrieve_header($r,'mcp-session-id')];
};
$graphql=static function($query,$variables=[],$auth=null)use($request){return $request(['query'=>$query,'variables'=>$variables?:new stdClass()],$auth);};
$schemaError=static function($r){return preg_match('/Cannot query field|Unknown argument|Unknown type|Syntax Error|not defined by type/i',wp_json_encode($r['json']['errors']??[]));};
$data=static function($r){$result=$r['json']['result']??[];if(isset($result['structuredContent'])){return $result['structuredContent'];}
    foreach($result['content']??[]as $c){if(($c['type']??null)==='text'){$v=json_decode($c['text'],true);if(is_array($v)){return $v;}}}return null;};
$rpc=static function($method,$params,$auth,$session=null)use($request,&$sequence){return $request(['jsonrpc'=>'2.0','id'=>++$sequence,'method'=>$method,'params'=>$params],$auth,$session,'/?rest_route=/mcp/mcp-adapter-default-server');};
$execute=static function($kind,$params,$auth,$session)use($rpc){
    $names=['create'=>'create-project-change','get'=>'get-project-change','update'=>'update-project-change','restore'=>'restore-project-change-revision'];
    if(in_array($kind,['create','update','restore'],true)){$params['request_id']=$params['request_id']??bin2hex(random_bytes(16));}
    return $rpc('tools/call',['name'=>'mcp-adapter-execute-ability','arguments'=>['ability_name'=>'alexcuadra/'.$names[$kind],'parameters'=>$params]],$auth,$session);
};
$appError=static function($r,$marker)use($data){return (isset($r['json']['error'])||($r['json']['result']['isError']??false)||($data($r)['success']??null)===false)
    &&str_contains(wp_json_encode($r['json']),$marker);};
$credential=static function($id)use(&$credentials){$p=WP_Application_Passwords::create_new_application_password($id,['name'=>'synthetic-editorial-stack']);
    if(is_wp_error($p)){throw new RuntimeException('STACK_CREDENTIAL_FAILED');}$credentials[]=['user'=>$id,'uuid'=>$p[1]['uuid']];
    return 'Basic '.base64_encode(get_userdata($id)->user_login.':'.$p[0]);};
try{
    $expected=['wp-graphql'=>'2.24.2','advanced-custom-fields'=>'6.8.10','wpgraphql-acf'=>'3.0.0','polylang'=>'3.9.0','wp-graphql-polylang'=>'0.7.1','mcp-adapter'=>'0.7.1'];
    $versions=[];foreach(get_plugins()as $path=>$plugin){$slug=explode('/',$path)[0];if(isset($expected[$slug])){$versions[$slug]=$plugin['Version'];}}
    $check('stack_versions_match_recorded_profile',count($versions)===count($expected)&&!array_diff_assoc($expected,$versions));$report['versions']=$versions;
    $field=acf_get_field(Changes\TAGLINE_KEY);$group=acf_get_field_group('group_676d40f7bb646');
    $check('acf_original_group_and_tagline_key_loaded',$group&&$group['graphql_field_name']==='projects'&&$field['name']==='project_tagline'&&$field['type']==='textarea'&&$field['graphql_field_name']==='projectTagline');
    $check('proposal_type_is_not_translated',!pll_is_translated_post_type(Changes\TYPE));
    foreach(['owner','other']as $name){$id=wp_create_user('stack'.bin2hex(random_bytes(6)),wp_generate_password(32));if(is_wp_error($id)){throw new RuntimeException('STACK_USER_FAILED');}
        $u=new WP_User($id);$u->set_role('subscriber');$u->add_cap('alexcuadra_read_public_projects');$u->add_cap(Changes\CAPABILITY);$users[$name]=$u;}
    $auth=$credential($users['owner']->ID);$otherAuth=$credential($users['other']->ID);$adminAuth=$credential(1);
    $check('stack_preparer_has_no_global_editor_permissions',!$users['owner']->has_cap('edit_pages')&&!$users['owner']->has_cap('publish_pages')&&!$users['owner']->has_cap('manage_options'));
    $make=static function($locale,$state='publish',$password=''){
        $id=wp_insert_post(['post_type'=>'project','post_status'=>$state,'post_author'=>1,'post_title'=>'Synthetic stack '.$locale.' '.$state,
            'post_content'=>'SOURCE_STACK_BODY_'.$locale,'post_password'=>$password],true);
        if(is_wp_error($id)){throw new RuntimeException('STACK_SOURCE_FAILED');}pll_set_post_language($id,$locale);
        update_field(Changes\TAGLINE_KEY,'SOURCE_STACK_TAGLINE_'.$locale,$id);
        update_field('field_676dcd0783231','https://example.test/repo-'.$locale,$id);update_field('field_676dcd1383232','https://example.test/demo-'.$locale,$id);
        update_post_meta($id,'private_contact','PRIVATE_STACK_META');return $id;
    };
    $en=$make('en');$es=$make('es');$draft=$make('en','draft');$private=$make('en','private');$protected=$make('en','publish','synthetic-only');$otherEs=$make('es');
    pll_save_post_translations(['en'=>$en,'es'=>$es]);$ids=implode(',',[$en,$es,$draft,$private,$protected,$otherEs]);
    $hash=static function()use($wpdb,$ids){return hash('sha256',serialize([
        $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID IN ($ids) ORDER BY ID",ARRAY_A),
        $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id IN ($ids) ORDER BY meta_id",ARRAY_A),
        $wpdb->get_results("SELECT * FROM $wpdb->term_relationships WHERE object_id IN ($ids) ORDER BY object_id,term_taxonomy_id",ARRAY_A)]));};
    $before=$hash();
    $check('acf_seed_uses_original_field_reference',get_post_meta($en,'_project_tagline',true)===Changes\TAGLINE_KEY&&get_field(Changes\TAGLINE_KEY,$en)==='SOURCE_STACK_TAGLINE_en');
    $anonymous=$graphql('query { viewer { databaseId } projects { nodes { databaseId } } }');
    $check('graphql_anonymous_rejected',!empty($anonymous['json']['errors'])&&empty($anonymous['json']['data']['projects'])&&str_contains(strtolower(wp_json_encode($anonymous['json']['errors'])),'authenticat'));
    $viewer=$graphql('query { viewer { databaseId } }',[],$auth);
    $check('graphql_application_password_authenticates_subscriber',empty($viewer['json']['errors'])&&($viewer['json']['data']['viewer']['databaseId']??null)===$users['owner']->ID);
    $query='query ($id:ID!){ project(id:$id,idType:DATABASE_ID){ id databaseId content language{code} translation(language:ES){databaseId language{code}} translations{databaseId language{code}} projects{projectTagline githuburl websiteurl} } }';
    $read=$graphql($query,['id'=>(string)$en],$auth);$node=$read['json']['data']['project']??null;
    $check('graphql_real_acf_fields_resolve',empty($read['json']['errors'])&&$node['projects']===['projectTagline'=>'SOURCE_STACK_TAGLINE_en','githuburl'=>'https://example.test/repo-en','websiteurl'=>'https://example.test/demo-en']);
    $globalSourceId=$node['id'];
    $check('graphql_polylang_translation_is_reciprocal_en_es',$node['language']['code']==='EN'&&$node['translation']['databaseId']===$es&&$node['translation']['language']['code']==='ES');
    $spanish=$graphql('query($id:ID!){project(id:$id,idType:DATABASE_ID){databaseId language{code} translation(language:EN){databaseId} projects{projectTagline}}}',['id'=>(string)$es],$auth);
    $check('graphql_spanish_acf_and_reverse_relation',empty($spanish['json']['errors'])&&$spanish['json']['data']['project']['projects']['projectTagline']==='SOURCE_STACK_TAGLINE_es'&&$spanish['json']['data']['project']['translation']['databaseId']===$en);
    foreach(['EN'=>$en,'ES'=>$es]as $locale=>$wanted){$r=$graphql('query {projects(first:100,where:{language:'.$locale.',status:PUBLISH,hasPassword:false}){nodes{databaseId language{code}}}}',[],$auth);$nodes=$r['json']['data']['projects']['nodes']??[];
        $check('graphql_public_collection_exact_'.$locale,empty($r['json']['errors'])&&in_array($wanted,array_column($nodes,'databaseId'),true)&&!in_array($locale==='EN'?$es:$en,array_column($nodes,'databaseId'),true)
            &&!array_diff(array_column(array_column($nodes,'language'),'code'),[$locale])&&!array_intersect([$draft,$private,$protected],array_column($nodes,'databaseId')));}
    $schema=$graphql('query {root:__type(name:"RootQuery"){fields{name}} mutation:__type(name:"RootMutation"){fields{name}} project:__type(name:"Project"){fields{name}} updateInput:__type(name:"UpdateProjectInput"){inputFields{name}} __schema{types{name}}}',[],$auth);
    $s=$schema['json']['data']??[];
    $check('graphql_schema_has_working_project_and_generic_routes',empty($schema['json']['errors'])&&!array_diff(['project','projects','node','contentNode','contentNodes'],array_column($s['root']['fields']??[],'name')));
    $check('graphql_schema_has_real_project_mutations',!array_diff(['createProject','updateProject'],array_column($s['mutation']['fields']??[],'name'))&&in_array('language',array_column($s['updateInput']['inputFields']??[],'name'),true));
    $types=array_column($s['__schema']['types']??[],'name');$publicFields=array_column($s['root']['fields']??[],'name');$mutations=array_column($s['mutation']['fields']??[],'name');
    $check('graphql_has_no_proposal_types_or_root_fields',!preg_grep('/acProjectChange|projectChange|ac_change|editorialChange/i',array_merge($types,$publicFields,$mutations)));
    $check('graphql_project_has_no_arbitrary_meta_or_payload_field',!preg_grep('/private_contact|editorial_change_payload|metaData/i',array_column($s['project']['fields'],'name')));
    $init=$rpc('initialize',['protocolVersion'=>'2025-11-25','capabilities'=>new stdClass(),'clientInfo'=>['name'=>'synthetic-stack','version'=>'1']],$auth);
    $session=$init['session'];$check('stack_mcp_session_initialized',$init['status']===200&&is_string($session)&&$session!=='');$sessions[]=$session;
    $request(['jsonrpc'=>'2.0','method'=>'notifications/initialized'],$auth,$session,'/?rest_route=/mcp/mcp-adapter-default-server');
    $created=$data($execute('create',['project_id'=>$en,'locale'=>'en','patch'=>['body_html'=>'<p>PROPOSED_STACK_PRIVATE_BODY</p>','tagline'=>'PROPOSED_STACK_PRIVATE_TAGLINE']],$auth,$session));
    $check('stack_mcp_creates_private_proposal',($created['success']??false)&&$created['data']['applied']===false);$proposal=$created['data'];$pid=$proposal['proposal_id'];
    $check('stack_mcp_dto_excludes_source_and_private_meta',!str_contains(wp_json_encode($created),'SOURCE_STACK')&&!str_contains(wp_json_encode($created),'PRIVATE_STACK_META'));
    $esProposal=$data($execute('create',['project_id'=>$es,'locale'=>'es','patch'=>['tagline'=>'Propuesta privada ES']],$auth,$session));
    $check('stack_mcp_spanish_source_is_exact',($esProposal['success']??false)&&$esProposal['data']['locale']==='es'&&$esProposal['data']['project_id']===$es);
    foreach(['draft'=>$draft,'private'=>$private,'password'=>$protected]as $label=>$id){
        $check('stack_mcp_rejects_'.$label.'_source',$appError($execute('create',['project_id'=>$id,'locale'=>'en','patch'=>['tagline'=>'Disallowed source']],$auth,$session),'not_found'));}
    $check('stack_mcp_rejects_wrong_source_language',$appError($execute('create',['project_id'=>$en,'locale'=>'es','patch'=>['tagline'=>'Wrong locale']],$auth,$session),'not_found'));
    $privateIds=[$pid,$esProposal['data']['proposal_id']];
    foreach(['owner'=>$auth,'other'=>$otherAuth,'admin'=>$adminAuth]as $label=>$identity){
        foreach(['contentNode'=>'query($id:ID!){contentNode(id:$id,idType:DATABASE_ID){databaseId __typename}}','node'=>'query($id:ID!){node(id:$id){id __typename}}']as $route=>$document){
            foreach($privateIds as $i=>$id){$r=$graphql($document,['id'=>$route==='node'?base64_encode('post:'.$id):(string)$id],$identity);
                $check('graphql_'.$label.'_'.$route.'_hides_proposal_'.$i,!$schemaError($r)&&array_key_exists($route,$r['json']['data']??[])&&$r['json']['data'][$route]===null);}
        }
        $r=$graphql('query {contentNodes(first:100){nodes{databaseId __typename}}}',[],$identity);
        $check('graphql_'.$label.'_collection_excludes_proposals',empty($r['json']['errors'])&&!array_intersect($privateIds,array_column($r['json']['data']['contentNodes']['nodes']??[],'databaseId')));
    }
    $unknown=$graphql('query {acProjectChanges{nodes{databaseId}}}',[],$auth);
    $check('graphql_proposal_collection_is_not_a_schema_route',$schemaError($unknown)&&empty($unknown['json']['data']));
    $mutation=$graphql('mutation($id:ID!){updateProject(input:{id:$id,title:"UNAUTHORIZED_STACK",language:ES}){project{databaseId}}}',['id'=>$globalSourceId],$auth);
    $check('graphql_prepare_scope_does_not_grant_source_edit',!$schemaError($mutation)&&!empty($mutation['json']['errors'])&&empty($mutation['json']['data']['updateProject']['project']));
    $mutation=$graphql('mutation {createProject(input:{title:"UNAUTHORIZED_STACK",status:DRAFT,language:ES}){project{databaseId}}}',[],$auth);
    $check('graphql_prepare_scope_does_not_grant_project_creation',!$schemaError($mutation)&&!empty($mutation['json']['errors'])&&empty($mutation['json']['data']['createProject']['project']));
    $updated=$data($execute('update',['proposal_id'=>$pid,'locale'=>'en','expected_version'=>$proposal['version'],'patch'=>['tagline'=>'Updated private proposal']],$auth,$session));
    $check('stack_mcp_update_changes_only_proposal',($updated['success']??false)&&$updated['data']['version']!==$proposal['version']);
    $restored=$data($execute('restore',['proposal_id'=>$pid,'locale'=>'en','expected_version'=>$updated['data']['version'],'revision_version'=>$proposal['version']],$auth,$session));
    $check('stack_mcp_restore_preserves_original_snapshot',($restored['success']??false)&&$restored['data']['patch']===$proposal['patch']);
    $again=$graphql($query,['id'=>(string)$en],$auth);
    $check('graphql_still_reads_original_body_and_acf_after_proposals',empty($again['json']['errors'])&&$again['json']['data']['project']===$node);
    $check('all_source_records_unchanged_by_stack_operations',$hash()===$before);
    // Explicit harness drift using ACF's real field-key API; not an ability's mutation.
    update_field(Changes\TAGLINE_KEY,'External ACF drift',$en);
    $check('real_acf_update_invalidates_proposal',$appError($execute('get',['proposal_id'=>$pid,'locale'=>'en'],$auth,$session),'source_changed'));
    update_field(Changes\TAGLINE_KEY,'SOURCE_STACK_TAGLINE_en',$en);
    update_post_meta($en,'_project_tagline','field_unexpected_reference');
    $check('acf_reference_change_invalidates_recovery',$appError($execute('restore',['proposal_id'=>$pid,'locale'=>'en','expected_version'=>$restored['data']['version'],'revision_version'=>$proposal['version']],$auth,$session),'source_changed'));
    update_post_meta($en,'_project_tagline',Changes\TAGLINE_KEY);
    $check('acf_drift_fixtures_restored_to_original_hash',$hash()===$before);
    pll_save_post_translations(['en'=>$en,'es'=>$otherEs]);
    $check('real_polylang_relation_change_invalidates_proposal',$appError($execute('get',['proposal_id'=>$pid,'locale'=>'en'],$auth,$session),'source_changed'));
    pll_save_post_translations(['en'=>$en,'es'=>$es]);
    $recover=$data($execute('get',['proposal_id'=>$pid,'locale'=>'en'],$auth,$session));
    $check('restored_polylang_semantics_allow_original_proposal',($recover['success']??false)&&$recover['data']['version']===$restored['data']['version']&&pll_get_post_translations($en)['es']===$es&&pll_get_post_translations($es)['en']===$en);
    $users['owner']->remove_cap(Changes\CAPABILITY);
    $check('stack_scope_revocation_blocks_mcp',$appError($execute('get',['proposal_id'=>$pid,'locale'=>'en'],$auth,$session),'forbidden'));
    $stillRead=$graphql($query,['id'=>(string)$en],$auth);
    $check('revocation_does_not_grant_or_remove_public_graphql_read',empty($stillRead['json']['errors'])&&$stillRead['json']['data']['project']===$node);
    $report['sourceSnapshotHashBeforeHarnessDrift']=$before;$report['sourceSnapshotPreserved']=true;$report['graphqlPrivacyVerified']=true;
}catch(Throwable $e){$report['failure']=preg_match('/^(FAILED: [a-z0-9_]+|[A-Z_]+)$/',$e->getMessage())?$e->getMessage():'STACK_LAB_FAILED';$report['diagnostic']=['class'=>get_class($e),'line'=>$e->getLine(),'lastHttp'=>$lastDiagnostic];}
finally{
    if(isset($en)&&function_exists('update_field')){update_field(Changes\TAGLINE_KEY,'SOURCE_STACK_TAGLINE_en',$en);update_post_meta($en,'_project_tagline',Changes\TAGLINE_KEY);}
    if(isset($en,$es)){pll_save_post_translations(['en'=>$en,'es'=>$es]);}
    foreach($users as $u){$u->remove_cap(Changes\CAPABILITY);$u->remove_cap('alexcuadra_read_public_projects');}
    foreach($sessions as $s){try{$request(null,$auth,$s,'/?rest_route=/mcp/mcp-adapter-default-server','DELETE');}catch(Throwable $ignored){$report['sessionCleanupError']=true;}}
    foreach($credentials as $c){WP_Application_Passwords::delete_application_password($c['user'],$c['uuid']);}
    $clean=true;foreach($credentials as $c){foreach(WP_Application_Passwords::get_user_application_passwords($c['user'])as $p){if($p['uuid']===$c['uuid']){$clean=false;}}}
    foreach($users as $u){$fresh=new WP_User($u->ID);$clean=$clean&&!$fresh->has_cap(Changes\CAPABILITY)&&!$fresh->has_cap('alexcuadra_read_public_projects');}
    if(!$clean||isset($report['sessionCleanupError'])){$report['failure']=$report['failure']??'STACK_CLEANUP_FAILED';}else{$cases[]='stack_synthetic_credentials_and_scopes_removed';}
}
$report['passed']=count($cases);$report['failed']=isset($report['failure'])?1:0;$report['cases']=$cases;
WP_CLI::line('EDITORIAL_CHANGES_STACK_RESULT='.wp_json_encode($report));if($report['failed']){WP_CLI::halt(1);}
