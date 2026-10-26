<?php
require_once '/blog-proposals/wordpress-repository.php';
require_once ABSPATH.'wp-admin/includes/plugin.php';
require '/proposal-lab/http-client.php';
use AlexCuadra\EditorialBlogProposalLab\WordPressRepository;
use const AlexCuadra\EditorialBlogProposalLab\PREPARE_SCOPE;
\AlexCuadra\EditorialBlogProposalLab\wordpress_lab_guard();
global $wpdb;
$marker='privatehttp'.bin2hex(random_bytes(4));
$cap=PREPARE_SCOPE;$report=['environment'=>'isolated-apache-mcp-private-proposals','httpTransportVerified'=>false];
$call=static fn($kind,$input,$auth,$session)=>$execute('alexcuadra-lab/'.$kind,$input,$auth,$session);
$unwrap=static function($response)use($data){$r=$data($response);return ($r['success']??false)?($r['data']??null):null;};
$snapshot=static function(){global $wpdb;$rows=[];foreach(['posts'=>'ID','postmeta'=>'meta_id','terms'=>'term_id','term_taxonomy'=>'term_taxonomy_id','term_relationships'=>'object_id,term_taxonomy_id','termmeta'=>'meta_id']as$t=>$order){$rows[]=$wpdb->get_results("SELECT * FROM {$wpdb->$t} ORDER BY $order",ARRAY_A);}return hash('sha256',serialize($rows));};
try{
    wp_set_current_user(1);$repo=new WordPressRepository($wpdb);
    $makePost=static function($label,$locale='en',$status='publish')use($marker){
        $id=wp_insert_post(['post_type'=>'post','post_status'=>$status,'post_title'=>"$marker $label",'post_content'=>$status==='publish'?'PUBLIC_SOURCE_BODY':'PRIVATE_POST_SECRET'],true);
        if(is_wp_error($id)){throw new RuntimeException('POST_SETUP_FAILED');}pll_set_post_language($id,$locale);wp_set_object_terms($id,[],'category');wp_set_object_terms($id,[],'post_tag');update_post_meta($id,'private_test','PRIVATE_META_SECRET');return $id;
    };
    $source=$makePost('Public');$private=$makePost('Private','en','private');
    $linkedSource=$makePost('Linked source');$linkedTarget=$makePost('Hidden linked target','es','draft');pll_save_post_translations(['en'=>$linkedSource,'es'=>$linkedTarget]);
    foreach(['reader','other']as$name){
        $id=wp_create_user($marker.$name,wp_generate_password(32),$marker.$name.'@example.test');
        if(is_wp_error($id)){throw new RuntimeException('USER_SETUP_FAILED');}$u=new WP_User($id);$u->set_role('subscriber');$users[$name]=$u;$repo->enroll($id);
    }
    $before=$snapshot();$auth=$newCredential($users['reader']->ID);$otherAuth=$newCredential($users['other']->ID);
    $check('http_anonymous_initialization_denied',in_array($initialize()['status'],[401,403],true));
    $badAuth='Basic '.base64_encode($users['reader']->user_login.':synthetic-incorrect');
    $check('http_invalid_password_denied',in_array($initialize($badAuth)['status'],[401,403],true));
    $start=$initialize($auth);$session=$start['session'];$otherStart=$initialize($otherAuth);$otherSession=$otherStart['session'];
    $check('http_real_credentials_initialize_both_users',$start['status']===200&&$otherStart['status']===200&&is_string($session)&&is_string($otherSession));
    foreach([[$auth,$session],[$otherAuth,$otherSession]]as[$a,$s]){$n=$request(['jsonrpc'=>'2.0','method'=>'notifications/initialized'],$a,$s);$check('http_initialized_notification_'.count($cases),in_array($n['status'],[200,202],true));}
    $check('http_missing_session_denied',$denied($rpc('tools/list',new stdClass(),$auth)));
    $check('http_other_user_cannot_reuse_session',$denied($rpc('tools/list',new stdClass(),$otherAuth,$session)));
    $check('http_missing_protocol_denied',$denied($request(['jsonrpc'=>'2.0','id'=>++$sequence,'method'=>'tools/list','params'=>new stdClass()],$auth,$session,'POST',null,null)));
    $check('http_subscriber_session_does_not_grant_body_context',$denied($call('inspect-post-context',['source_id'=>$source],$auth,$session)));
    $users['reader']->add_cap('alexcuadra_read_public_posts');
    $check('http_public_read_scope_not_private_prepare_scope',$denied($call('inspect-post-context',['source_id'=>$source],$auth,$session)));
    $users['reader']->remove_cap('alexcuadra_read_public_posts');
    foreach($users as$user){$user->add_cap($cap);}
    $check('http_prepare_users_stay_subscriber_without_edit_admin',$users['reader']->roles===['subscriber']&&!$users['reader']->has_cap('edit_posts')&&!$users['reader']->has_cap('manage_options'));
    $discover=$data($tool('mcp-adapter-discover-abilities',new stdClass(),$auth,$session));
    $names=array_values(array_filter(array_column($discover['abilities']??[],'name'),fn($n)=>str_starts_with($n,'alexcuadra-lab/')));
    $check('http_discovers_five_lab_abilities_no_apply_or_recover',count($names)===5&&!preg_grep('/apply|recover|approve/',$names));
    $info=$data($tool('mcp-adapter-get-ability-info',['ability_name'=>'alexcuadra-lab/create-post-proposal'],$auth,$session));
    $check('http_closed_request_schema_without_client_owner',($info['input_schema']['additionalProperties']??null)===false&&!isset($info['input_schema']['properties']['owner_id']));
    $ctx=$unwrap($call('inspect-post-context',['source_id'=>$source],$auth,$session));
    $check('http_authorized_context_has_published_body_and_base',($ctx['fields']['content']??null)==='PUBLIC_SOURCE_BODY'&&preg_match('/^[a-f0-9]{64}$/',$ctx['base_hash']??''));
    $check('http_context_excludes_postmeta_and_hidden_bodies',!str_contains(json_encode($ctx),'PRIVATE_META_SECRET')&&!str_contains(json_encode($ctx),'PRIVATE_POST_SECRET'));
    $privateResponse=$call('inspect-post-context',['source_id'=>$private],$auth,$session);
    $check('http_private_source_context_denied_without_body_leak',$denied($privateResponse)&&!str_contains(json_encode($privateResponse),'PRIVATE_POST_SECRET'));
    $hiddenCtx=$unwrap($call('inspect-post-context',['source_id'=>$linkedSource],$auth,$session));
    $check('http_context_reports_existing_locales_without_hidden_target_identity',in_array('es',$hiddenCtx['linked_locales']??[],true)&&!isset($hiddenCtx['translations'])&&!isset($hiddenCtx['source']));
    $r=['request_id'=>str_repeat('a',32),'source_id'=>$source,'locale'=>'en','base_hash'=>$ctx['base_hash'],'operation'=>'update-post','fields'=>['content'=>'OWNER_PRIVATE_PLAN_<script>literal</script> Español 🙂']];
    $one=$unwrap($call('create-post-proposal',$r,$auth,$session));$id=$one['proposal_id']??0;
    $check('http_creates_owned_private_review',($one['version']??null)===1&&($one['document']['owner_id']??null)===$users['reader']->ID&&($one['document']['changes']['content']['after']??null)===$r['fields']['content']);
    $again=$unwrap($call('create-post-proposal',$r,$auth,$session));$check('http_create_replay_returns_exact_original',$again===$one);
    $own=$unwrap($call('get-post-proposal',['proposal_id'=>$id],$auth,$session));$check('http_owned_read_returns_exact_review',$own===$one);
    $cross=$call('get-post-proposal',['proposal_id'=>$id],$otherAuth,$otherSession);
    $check('http_other_owner_read_denied_without_review_leak',$denied($cross)&&!str_contains(json_encode($cross),'OWNER_PRIVATE_PLAN'));
    $crossHistory=$call('get-post-proposal',['proposal_id'=>$id,'version'=>1],$otherAuth,$otherSession);
    $check('http_other_owner_history_denied_without_review_leak',$denied($crossHistory)&&!str_contains(json_encode($crossHistory),'OWNER_PRIVATE_PLAN'));
    $inventory=$unwrap($call('list-post-proposals',new stdClass(),$otherAuth,$otherSession));
    $check('http_other_owner_inventory_empty',($inventory['items']??null)===[]);
    $r2=$r;$r2['request_id']=str_repeat('b',32);$r2['fields']=['title'=>'New proposed title'];
    $update=['proposal_id'=>$id,'expected_version'=>1,'request'=>$r2];
    $check('http_other_owner_update_denied',$denied($call('update-post-proposal',$update,$otherAuth,$otherSession)));
    $two=$unwrap($call('update-post-proposal',$update,$auth,$session));$check('http_updates_expected_version',($two['version']??null)===2);
    $check('http_update_replay_preserves_receipt',$unwrap($call('update-post-proposal',$update,$auth,$session))===$two);
    $check('http_history_version_one_retained',$unwrap($call('get-post-proposal',['proposal_id'=>$id,'version'=>1],$auth,$session))===$one);
    $update['request']['request_id']=str_repeat('c',32);$check('http_stale_expected_version_denied',$denied($call('update-post-proposal',$update,$auth,$session)));
    $bad=$r;$bad['fields']['title']='Different key payload';$check('http_request_key_reuse_denied',$denied($call('create-post-proposal',$bad,$auth,$session)));
    $bad=$r;$bad['owner_id']=$users['other']->ID;$check('http_client_owner_field_denied',$denied($call('create-post-proposal',$bad,$auth,$session)));
    $bad=$r;$bad['fields']['slug']='forbidden';$check('http_unapproved_source_field_denied',$denied($call('create-post-proposal',$bad,$auth,$session)));
    $bad=$r;$bad['source_id']=$private;$bad['request_id']=str_repeat('d',32);$check('http_private_post_proposal_denied',$denied($call('create-post-proposal',$bad,$auth,$session)));
    $tr=['request_id'=>str_repeat('e',32),'source_id'=>$source,'locale'=>'es','base_hash'=>$ctx['base_hash'],'operation'=>'create-translation','fields'=>['title'=>'Traducción propuesta','content'=>'Contenido explícito','excerpt'=>'','categories'=>[],'tags'=>[]]];
    $translation=$unwrap($call('create-post-proposal',$tr,$auth,$session));
    $check('http_translation_is_private_intent_without_post_id',($translation['document']['operation']??null)==='create-translation'&&array_key_exists('target_id',$translation['document']['translation_intent']??[])&&$translation['document']['translation_intent']['target_id']===null);
    $tr['request_id']=str_repeat('f',32);$tr['source_id']=$linkedSource;$tr['base_hash']=$hiddenCtx['base_hash'];
    $check('http_linked_draft_blocks_duplicate_translation',$denied($call('create-post-proposal',$tr,$auth,$session)));
    $check('http_unbounded_inventory_denied',$denied($call('list-post-proposals',['limit'=>21],$auth,$session)));
    $users['reader']->remove_cap($cap);
    $check('http_scope_revocation_blocks_existing_session_read',$denied($call('get-post-proposal',['proposal_id'=>$id],$auth,$session)));
    $check('http_scope_revocation_blocks_existing_session_replay',$denied($call('create-post-proposal',$r,$auth,$session)));
    $users['reader']->add_cap($cap);
    $closed=$request(null,$auth,$session,'DELETE');$check('http_session_delete_succeeds',in_array($closed['status'],[200,204],true));
    $check('http_closed_session_cannot_read_review',$denied($call('get-post-proposal',['proposal_id'=>$id],$auth,$session)));
    $last=$initialize($auth);$lastSession=$last['session'];
    WP_Application_Passwords::delete_application_password($users['reader']->ID,$credentials[0]['uuid']);
    $check('http_revoked_credential_blocks_existing_session',in_array($rpc('tools/list',new stdClass(),$auth,$lastSession)['status'],[401,403],true));
    $check('http_private_proposal_operations_preserve_source_tables',$snapshot()===$before);
    $report['httpTransportVerified']=true;
}catch(Throwable $e){
    $report['failure']=preg_match('/^(FAILED: [a-z0-9_]+|[A-Z_]+)$/',$e->getMessage())?$e->getMessage():'HTTP_PROPOSAL_LAB_FAILED';$report['diagnostic']=$lastDiagnostic;
}finally{
    foreach($users as$user){$user->remove_cap($cap);}
    foreach($users as$user){try{$cleanupAuth=$newCredential($user->ID);foreach($sessions as$stored){if($stored['auth']!==null&&str_starts_with(base64_decode(substr($stored['auth'],6)),$user->user_login.':')){$request(null,$cleanupAuth,$stored['id'],'DELETE');}}}catch(Throwable $e){$report['sessionCleanupError']=true;}}
    foreach($credentials as$c){WP_Application_Passwords::delete_application_password($c['user'],$c['uuid']);}
    $report['syntheticCredentialsRevoked']=true;$report['syntheticCapabilitiesRemoved']=true;
    foreach($users as$user){$report['syntheticCredentialsRevoked']=$report['syntheticCredentialsRevoked']&&WP_Application_Passwords::get_user_application_passwords($user->ID)===[];$report['syntheticCapabilitiesRemoved']=$report['syntheticCapabilitiesRemoved']&&!(new WP_User($user->ID))->has_cap($cap);}
    if(!$report['syntheticCredentialsRevoked']||!$report['syntheticCapabilitiesRemoved']||isset($report['sessionCleanupError'])){$report['failure']=$report['failure']??'SYNTHETIC_CLEANUP_FAILED';$report['httpTransportVerified']=false;}
    else{$cases[]='http_synthetic_credentials_removed';$cases[]='http_synthetic_prepare_scopes_removed';}
}
$report['passed']=count($cases);$report['failed']=isset($report['failure'])?1:0;$report['cases']=$cases;
WP_CLI::line('EDITORIAL_BLOG_PROPOSAL_HTTP_RESULT='.wp_json_encode($report));if($report['failed']){WP_CLI::halt(1);}
