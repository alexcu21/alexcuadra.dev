<?php
use AlexCuadra\EditorialChanges as C;
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress' || !function_exists('acf_get_field')) { throw new RuntimeException('ISOLATED_REVIEW_STACK_REQUIRED'); }
global $wpdb;
$cases=[]; $users=[]; $credentials=[]; $sessions=[]; $sequence=0;
$report=['environment'=>'isolated-private-reviews','sharedCmsModified'=>false,'sourceWriterRegistered'=>true,'emailSent'=>false];
$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED: '.$name);} $cases[]=$name;};
$error=static function($r,$code,$status){return is_wp_error($r)&&$r->get_error_message()===$code&&($r->get_error_data()['status']??null)===$status;};
$switch=static function($id){wp_set_current_user(0);wp_set_current_user($id);};
$key=static function(){return bin2hex(random_bytes(16));};
$call=static function($name,$input){return wp_get_ability('alexcuadra/'.$name)->execute($input);};
$tables=['reviews'=>$wpdb->prefix.'ac_change_reviews','approvals'=>$wpdb->prefix.'ac_change_approvals','requests'=>$wpdb->prefix.'ac_change_requests'];
$sourceIds=[];
$hashSources=static function()use($wpdb,&$sourceIds){$ids=implode(',',array_map('intval',$sourceIds));return hash('sha256',serialize([
    $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID IN ($ids) ORDER BY ID",ARRAY_A),
    $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id IN ($ids) ORDER BY meta_id",ARRAY_A),
    $wpdb->get_results("SELECT * FROM $wpdb->term_relationships WHERE object_id IN ($ids) ORDER BY object_id,term_taxonomy_id",ARRAY_A)]));};
$approve=static function($review)use($switch,&$users){$switch($users['operator']->ID);try{return C\review_approve($review['review_id'],$review['locale'],$review['review_hash']);}finally{$switch($users['owner']->ID);}};
$prepare=static function($proposal,$extra=[])use($key,$call){return $call('prepare-project-application',array_merge([
    'proposal_id'=>$proposal['proposal_id'],'locale'=>$proposal['locale'],'expected_proposal_version'=>$proposal['version'],
    'expected_base_version'=>$proposal['base_version'],'request_id'=>$key()],$extra));};
$get=static function($r)use($call){return $call('get-project-application',['review_id'=>$r['review_id'],'locale'=>$r['locale']]);};
$http=static function($payload,$auth=null,$session=null,$path='/?rest_route=/mcp/mcp-adapter-default-server',$method='POST'){
    $headers=['Content-Type'=>'application/json','Accept'=>'application/json, text/event-stream'];
    if($auth!==null){$headers['Authorization']=$auth;}if($session!==null){$headers['Mcp-Session-Id']=$session;$headers['MCP-Protocol-Version']='2025-11-25';}
    $r=wp_remote_request('http://wordpress'.$path,['method'=>$method,'headers'=>$headers,'body'=>$payload===null?null:wp_json_encode($payload),'timeout'=>25,'redirection'=>0]);
    if(is_wp_error($r)){throw new RuntimeException('REVIEW_HTTP_FAILED');}
    return ['status'=>wp_remote_retrieve_response_code($r),'json'=>json_decode(wp_remote_retrieve_body($r),true),'session'=>wp_remote_retrieve_header($r,'mcp-session-id')];
};
$rpc=static function($method,$params,$auth,$session=null)use($http,&$sequence){return $http(['jsonrpc'=>'2.0','id'=>++$sequence,'method'=>$method,'params'=>$params],$auth,$session);};
$data=static function($r){$v=$r['json']['result']??[];if(isset($v['structuredContent'])){return $v['structuredContent'];}foreach($v['content']??[]as $c){if(($c['type']??null)==='text'){$j=json_decode($c['text'],true);if(is_array($j)){return $j;}}}return null;};
$tool=static function($name,$input,$auth,$session)use($rpc){return $rpc('tools/call',['name'=>'mcp-adapter-execute-ability','arguments'=>['ability_name'=>'alexcuadra/'.$name,'parameters'=>$input]],$auth,$session);};
try {
    foreach(['owner','other','operator','quota','bytes']as $label){$id=wp_create_user('review'.bin2hex(random_bytes(6)),wp_generate_password(32));if(is_wp_error($id)){throw new RuntimeException('REVIEW_USER_FAILED');}
        $u=new WP_User($id);$u->set_role('subscriber');$users[$label]=$u;
        if($label==='operator'){$u->add_cap(C\APPROVE_CAPABILITY);}else{foreach(['alexcuadra_read_public_projects',C\CAPABILITY,C\APPLY_CAPABILITY]as $cap){$u->add_cap($cap);}}
    }
    $make=static function($label,$locale='en')use(&$sourceIds){$id=wp_insert_post(['post_type'=>'project','post_status'=>'publish','post_author'=>1,'post_title'=>'Review '.$label,'post_content'=>'ORIGINAL_REVIEW_BODY'],true);
        if(is_wp_error($id)){throw new RuntimeException('REVIEW_SOURCE_FAILED');}pll_set_post_language($id,$locale);update_field(C\TAGLINE_KEY,'ORIGINAL_REVIEW_TAGLINE',$id);
        update_post_meta($id,'private_contact','PRIVATE_REVIEW_SENTINEL');$sourceIds[]=$id;return $id;};
    $en=$make('EN');$es=$make('ES','es');pll_save_post_translations(['en'=>$en,'es'=>$es]);$missing=$make('missing');
    delete_post_meta($missing,'project_tagline');delete_post_meta($missing,'_project_tagline');$before=$hashSources();
    $switch($users['owner']->ID);
    $proposalInput=['project_id'=>$en,'locale'=>'en','patch'=>['body_html'=>'<h2>Café "prueba"</h2><p>Ruta \\ y ñ.</p>','tagline'=>'Subtítulo "exacto" \\ final'],'request_id'=>$key()];
    $proposal=$call('create-project-change',$proposalInput);
    $check('new_review_abilities_registered',wp_get_ability('alexcuadra/prepare-project-application')&&wp_get_ability('alexcuadra/get-project-application'));
    $check('approval_private_apply_and_recovery_registered',!wp_get_ability('alexcuadra/approve-project-application')&&wp_get_ability('alexcuadra/apply-project-change')&&wp_get_ability('alexcuadra/recover-project-change'));
    $check('new_scopes_not_granted_by_activation',!get_role('subscriber')->has_cap(C\APPLY_CAPABILITY)&&!get_role('administrator')->has_cap(C\APPROVE_CAPABILITY));
    $switch(0);$check('anonymous_review_denied',$error(C\review_prepare([]),'forbidden',403));$switch($users['owner']->ID);
    $users['owner']->remove_cap(C\APPLY_CAPABILITY);$switch($users['owner']->ID);
    $check('prepare_scope_alone_cannot_inspect_source_diff',$error(C\review_prepare([]),'forbidden',403));
    $users['owner']->add_cap(C\APPLY_CAPABILITY);$switch($users['owner']->ID);
    $input=['proposal_id'=>$proposal['proposal_id'],'locale'=>'en','expected_proposal_version'=>$proposal['version'],'expected_base_version'=>$proposal['base_version'],'request_id'=>$key()];
    $check('callback_rejects_client_approval_boolean',$error(C\review_prepare($input+['approved'=>true]),'invalid_input',400));
    $bad=$input;$bad['request_id']='BAD';$check('invalid_review_request_key_denied',$error(C\review_prepare($bad),'invalid_input',400));
    $bad=$input;$bad['expected_proposal_version']=str_repeat('0',64);$check('stale_proposal_version_rejected',$error(C\review_prepare($bad),'proposal_changed',409));
    $bad=$input;$bad['expected_base_version']=str_repeat('0',64);$check('stale_base_version_rejected',$error(C\review_prepare($bad),'source_changed',409));
    $bad=$input;$bad['locale']='es';$check('wrong_review_locale_rejected',$error(C\review_prepare($bad),'not_found',404));
    $review=$call('prepare-project-application',$input);
    $check('closed_review_dto_has_exact_body_and_acf_values',!is_wp_error($review)&&$review['before']['body_html']==='ORIGINAL_REVIEW_BODY'
        &&$review['after']['body_html']===$proposal['patch']['body_html']&&$review['after']['tagline']['project_tagline']['value']===$proposal['patch']['tagline']
        &&$review['before']['tagline']['_project_tagline']['value']===C\TAGLINE_KEY);
    $check('review_expiry_and_public_effect_are_explicit',$review['expires_at']-$review['created_at']===600&&$review['public_effect']==='would_modify_published_project'&&$review['applied']===false);
    $check('snapshot_excludes_arbitrary_source_meta',!str_contains(wp_json_encode($review),'PRIVATE_REVIEW_SENTINEL')&&!str_contains(wp_json_encode($review),'private_contact'));
    $usage=C\owner_usage($users['owner']->ID);
    $check('review_replay_is_exact_and_free',$call('prepare-project-application',$input)===$review&&C\owner_usage($users['owner']->ID)===$usage);
    $ordered=array_reverse($input,true);$check('review_request_property_order_is_canonical',$call('prepare-project-application',$ordered)===$review);
    $bad=$input;$bad['expected_proposal_version']=str_repeat('1',64);$check('same_review_key_different_intent_conflicts',$error(C\review_prepare($bad),'request_reused',409));
    $bad=$input;$bad['request_id']=$proposalInput['request_id'];$check('legacy_receipt_key_cannot_be_reused_for_review',$error(C\review_prepare($bad),'request_reused',409));
    $bad=$proposalInput;$bad['request_id']=$input['request_id'];$check('review_receipt_key_cannot_be_reused_for_proposal',$error(C\execute($bad,'create'),'request_reused',409));
    $ledgerBefore=hash('sha256',serialize($wpdb->get_results("SELECT * FROM {$tables['requests']} ORDER BY owner_id,request_id",ARRAY_A)));
    update_option('ac_changes_storage_version','0.3');C\install_storage();
    $ledgerAfter=hash('sha256',serialize($wpdb->get_results("SELECT * FROM {$tables['requests']} ORDER BY owner_id,request_id",ARRAY_A)));
    $check('additive_03_migration_preserves_receipt_bytes_and_hashes',$ledgerBefore===$ledgerAfter&&get_option('ac_changes_storage_version')==='0.5');
    $check('legacy_proposal_replay_remains_unchanged',$call('create-project-change',$proposalInput)===$proposal);
    $switch($users['other']->ID);$check('foreign_owner_cannot_read_review',$error(C\review_get(['review_id'=>$review['review_id'],'locale'=>'en']),'not_found',404));
    $check('foreign_owner_cannot_prepare_review_of_proposal',$error(C\review_prepare($input),'not_found',404));$switch($users['owner']->ID);
    $users['owner']->add_cap(C\APPROVE_CAPABILITY);$switch($users['owner']->ID);
    $check('self_approval_denied_even_with_both_scopes',$error(C\review_approve($review['review_id'],'en',$review['review_hash']),'independent_approval_required',403));
    $users['owner']->remove_cap(C\APPROVE_CAPABILITY);$switch(1);
    $check('admin_role_without_approval_scope_denied',$error(C\review_approve($review['review_id'],'en',$review['review_hash']),'forbidden',403));
    $switch($users['operator']->ID);
    $check('tampered_hash_cannot_be_approved',$error(C\review_approve($review['review_id'],'en',str_repeat('a',64)),'review_changed',409));
    $check('wrong_approval_locale_denied',$error(C\review_approve($review['review_id'],'es',$review['review_hash']),'not_found',404));
    $approval=$approve($review);$check('independent_operator_approves_exact_review',!is_wp_error($approval)&&$approval['review_hash']===$review['review_hash']&&$approval['expires_at']===$review['expires_at']);
    $check('operator_identity_restored_after_approval',get_current_user_id()===$users['owner']->ID);
    $check('get_reports_current_approved_state',$get($review)['state']==='approved'&&$get($review)['current']===true);
    $usage=C\owner_usage($users['owner']->ID);$check('same_operator_approval_retry_is_free',$approve($review)===$approval&&C\owner_usage($users['owner']->ID)===$usage);
    $check('prepare_replay_does_not_misreport_historical_approval_state',$call('prepare-project-application',$input)===$review);
    $savedApproval=json_decode($wpdb->get_var($wpdb->prepare("SELECT payload FROM {$tables['approvals']} WHERE approval_id=%d",$approval['approval_id'])),true);
    $check('approval_binds_actor_executor_operation_source_and_schema',$savedApproval['actor_id']===$users['operator']->ID&&$savedApproval['executor_id']===$users['owner']->ID
        &&$savedApproval['operation']==='apply'&&$savedApproval['source_version']===$review['source_version']&&$savedApproval['schema_version']===$review['schema_version']&&$savedApproval['consumed']===false);
    $corrupt=$savedApproval;$corrupt['executor_id']=$users['other']->ID;
    $wpdb->query($wpdb->prepare("UPDATE {$tables['approvals']} SET payload=%s WHERE approval_id=%d",wp_json_encode($corrupt),$approval['approval_id']));
    $check('corrupt_approval_cannot_report_approved_state',$error(C\review_get(['review_id'=>$review['review_id'],'locale'=>'en']),'review_unavailable',503));
    $wpdb->query($wpdb->prepare("UPDATE {$tables['approvals']} SET payload=%s WHERE approval_id=%d",wp_json_encode($savedApproval),$approval['approval_id']));
    $users['owner']->remove_cap(C\APPLY_CAPABILITY);$switch($users['owner']->ID);
    $check('revoked_executor_cannot_read_review',$error(C\review_get(['review_id'=>$review['review_id'],'locale'=>'en']),'forbidden',403));
    $check('revoked_executor_blocks_operator_approval',$error($approve($review),'forbidden',403));
    $users['owner']->add_cap(C\APPLY_CAPABILITY);$switch($users['owner']->ID);
    foreach(['body_only'=>['body_html'=>''],'tagline_only'=>['tagline'=>''],'missing_tagline'=>['tagline'=>'Nueva ñ']]as $label=>$patch){
        $p=$call('create-project-change',['project_id'=>$label==='missing_tagline'?$missing:$en,'locale'=>'en','patch'=>$patch,'request_id'=>$key()]);$r=$prepare($p);
        $check($label.'_snapshot_changes_only_present_patch_fields',!is_wp_error($r)&&array_keys($r['before'])===array_keys($patch)&&array_keys($r['after'])===array_keys($patch));
        if($label==='missing_tagline'){$check('missing_meta_distinct_from_empty_value',$r['before']['tagline']['project_tagline']===['present'=>false,'value'=>null]&&$r['before']['tagline']['_project_tagline']===['present'=>false,'value'=>null]);}
    }
    // Controlled drift belongs to the test harness; no ability writes a source record.
    update_post_meta($en,'website_url','https://example.test/drift');
    $check('unrelated_meta_edit_invalidates_full_source_fingerprint',$get($review)['current']===false&&$error($approve($review),'source_changed',409));
    delete_post_meta($en,'website_url');
    $check('restored_source_is_current_again',$get($review)['current']===true);
    $fieldFilter=static function($field){$field['instructions']='Schema drift';return $field;};add_filter('acf/load_field/key='.C\TAGLINE_KEY,$fieldFilter);
    // ACF field store must be cleared to test real subsequent resolution, not a cached array.
    acf_get_store('fields')->reset();
    try{$check('acf_schema_change_invalidates_review',$get($review)['current']===false&&$error($approve($review),'schema_changed',409));}
    finally{remove_filter('acf/load_field/key='.C\TAGLINE_KEY,$fieldFilter);acf_get_store('fields')->reset();}
    $check('original_schema_restored',$get($review)['current']===true);
    add_post_meta($en,'project_tagline','duplicate');
    $check('duplicate_acf_rows_cannot_be_reviewed',$error($prepare($proposal),'source_changed',409)||$error($prepare($proposal),'ambiguous_meta',409));
    $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE post_id=%d AND meta_key='project_tagline' AND meta_value='duplicate'",$en));C\review_clear_cache($en);
    $updated=$call('update-project-change',['proposal_id'=>$proposal['proposal_id'],'locale'=>'en','expected_version'=>$proposal['version'],'patch'=>['tagline'=>'New proposal head'],'request_id'=>$key()]);
    $check('new_proposal_version_invalidates_old_review',$get($review)['current']===false&&$error($approve($review),'proposal_changed',409));
    $check('old_prepare_replay_does_not_change_new_head',$call('prepare-project-application',$input)===$review&&C\load_owned($proposal['proposal_id'],'en')['payload']['version']===$updated['version']);
    $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_author=%d WHERE ID=%d",$users['other']->ID,$proposal['proposal_id']));C\review_clear_cache($proposal['proposal_id']);
    $check('proposal_owner_change_blocks_review_replay',$error(C\review_prepare($input),'not_found',404));
    $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_author=%d WHERE ID=%d",$users['owner']->ID,$proposal['proposal_id']));C\review_clear_cache($proposal['proposal_id']);
    $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_password='synthetic-protection' WHERE ID=%d",$en));C\review_clear_cache($en);
    $check('protected_source_blocks_review_get',$error(C\review_get(['review_id'=>$review['review_id'],'locale'=>'en']),'not_found',404));
    $check('protected_source_blocks_confirmed_review_replay',$error(C\review_prepare($input),'not_found',404));
    $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_password='' WHERE ID=%d",$en));C\review_clear_cache($en);
    $fresh=$prepare($updated);
    $raw=$wpdb->get_var($wpdb->prepare("SELECT payload FROM {$tables['reviews']} WHERE review_id=%d",$fresh['review_id']));
    $stored=json_decode($raw,true);$stored['document']['created_at']-=601;$stored['document']['expires_at']-=601;
    $stored['review_hash']=hash('sha256',wp_json_encode(C\canonical($stored['document'])));
    $wpdb->query($wpdb->prepare("UPDATE {$tables['reviews']} SET payload=%s WHERE review_id=%d",wp_json_encode($stored),$fresh['review_id']));
    $expired=$fresh;$expired['review_hash']=$stored['review_hash'];
    $check('server_clock_marks_expired_review',$get($expired)['state']==='expired');
    $check('expired_review_cannot_be_approved',$error($approve($expired),'review_expired',409));
    $wpdb->query($wpdb->prepare("UPDATE {$tables['reviews']} SET payload=%s WHERE review_id=%d",$raw,$fresh['review_id']));
    $stored=json_decode($raw,true);$stored['document']['after']['tagline']['project_tagline']['value']='tampered';
    $wpdb->query($wpdb->prepare("UPDATE {$tables['reviews']} SET payload=%s WHERE review_id=%d",wp_json_encode($stored),$fresh['review_id']));
    $check('corrupt_frozen_diff_fails_closed',$error(C\review_get(['review_id'=>$fresh['review_id'],'locale'=>'en']),'review_unavailable',503));
    $wpdb->query($wpdb->prepare("UPDATE {$tables['reviews']} SET payload=%s WHERE review_id=%d",$raw,$fresh['review_id']));
    foreach(['snapshot','receipt','commit']as $point){
        $usage=C\owner_usage($users['owner']->ID);$ri=$input;$ri['request_id']=$key();$ri['expected_proposal_version']=$updated['version'];
        $break=static function($q)use($point,$tables){$hit=$point==='snapshot'?str_starts_with($q,'UPDATE '.$tables['reviews'].' SET payload='):
            ($point==='receipt'?str_starts_with($q,'INSERT INTO '.$tables['requests'].' '):$q==='COMMIT');return $hit?'INVALID_REVIEW_TEST_SQL':$q;};
        $suppress=$wpdb->suppress_errors(true);add_filter('query',$break);try{$failed=C\review_prepare($ri);}finally{remove_filter('query',$break);$wpdb->suppress_errors($suppress);}
        $check($point.'_failure_rolls_back_review_and_receipt',$error($failed,'storage_unavailable',503)&&C\owner_usage($users['owner']->ID)===$usage);
    }
    $usage=C\owner_usage($users['owner']->ID);$break=static function($q)use($tables){return str_starts_with($q,'INSERT INTO '.$tables['approvals'].' ')?'INVALID_APPROVAL_SQL':$q;};
    $suppress=$wpdb->suppress_errors(true);add_filter('query',$break);try{$failed=$approve($fresh);}finally{remove_filter('query',$break);$wpdb->suppress_errors($suppress);}
    $check('approval_storage_failure_leaves_no_partial_approval',$error($failed,'storage_unavailable',503)&&C\owner_usage($users['owner']->ID)===$usage&&$get($fresh)['state']==='prepared');
    $check('failed_approval_can_retry_safely',!is_wp_error($approve($fresh)));
    $wpdb->query('START TRANSACTION');try{$nested=C\review_prepare($input);$active=(int)$wpdb->get_var('SELECT @@in_transaction');}finally{$wpdb->query('ROLLBACK');}
    $check('review_rejects_nested_transaction_without_ending_it',$error($nested,'transaction_busy',503)&&$active===1);
    $switch($users['quota']->ID);$qp=$call('create-project-change',['project_id'=>$en,'locale'=>'en','patch'=>['tagline'=>'Quota review'],'request_id'=>$key()]);
    $lastInput=null;for($i=0;$i<C\MAX_OWNER_REVIEWS;$i++){$lastInput=['proposal_id'=>$qp['proposal_id'],'locale'=>'en','expected_proposal_version'=>$qp['version'],'expected_base_version'=>$qp['base_version'],'request_id'=>$key()];$last=$call('prepare-project-application',$lastInput);if(is_wp_error($last)){throw new RuntimeException('REVIEW_QUOTA_FILL_FAILED');}}
    $usage=C\owner_usage($users['quota']->ID);$check('retained_review_limit_rejects_without_partial_receipt',$error(C\review_prepare(array_merge($lastInput,['request_id'=>$key()])),'owner_quota',429)&&C\owner_usage($users['quota']->ID)===$usage);
    $check('review_replay_at_full_retention_quota_is_free',$call('prepare-project-application',$lastInput)===$last&&C\owner_usage($users['quota']->ID)===$usage);
    $switch($users['bytes']->ID);$bp=$call('create-project-change',['project_id'=>$en,'locale'=>'en','patch'=>['tagline'=>'Bytes'],'request_id'=>$key()]);$usage=C\owner_usage($users['bytes']->ID);
    $wpdb->query($wpdb->prepare("INSERT INTO {$tables['requests']} (owner_id,request_id,request_hash,proposal_id,response) VALUES (%d,%s,%s,%d,%s)",$users['bytes']->ID,$key(),str_repeat('0',64),$bp['proposal_id'],str_repeat('x',C\MAX_OWNER_BYTES-$usage['bytes']-1)));
    $usage=C\owner_usage($users['bytes']->ID);$check('review_snapshot_and_receipt_share_aggregate_byte_budget',$error($prepare($bp),'owner_quota',429)&&C\owner_usage($users['bytes']->ID)===$usage);
    $switch($users['owner']->ID);
    $check('all_prepare_get_approve_operations_preserve_source_rows',$hashSources()===$before);
    foreach(['owner','other','operator','admin']as $label){$id=$label==='admin'?1:$users[$label]->ID;$pair=WP_Application_Passwords::create_new_application_password($id,['name'=>'synthetic-review-http']);if(is_wp_error($pair)){throw new RuntimeException('REVIEW_PASSWORD_FAILED');}
        $credentials[$label]=['user'=>$id,'uuid'=>$pair[1]['uuid'],'auth'=>'Basic '.base64_encode(get_userdata($id)->user_login.':'.$pair[0])];}
    foreach(['owner','other','operator']as $label){$init=$rpc('initialize',['protocolVersion'=>'2025-11-25','capabilities'=>new stdClass(),'clientInfo'=>['name'=>'review-lab','version'=>'1']],$credentials[$label]['auth']);
        $sessions[$label]=$init['session'];$check('review_http_session_'.$label,$init['status']===200&&is_string($init['session'])&&$init['session']!=='');
        $http(['jsonrpc'=>'2.0','method'=>'notifications/initialized'],$credentials[$label]['auth'],$sessions[$label]);}
    $auth=$credentials['owner']['auth'];$session=$sessions['owner'];
    $discover=$data($rpc('tools/call',['name'=>'mcp-adapter-discover-abilities','arguments'=>new stdClass()],$auth,$session));$names=array_column($discover['abilities']??[],'name');
    $check('http_inventory_contains_reviews_and_execution_without_approval',in_array('alexcuadra/prepare-project-application',$names,true)&&in_array('alexcuadra/get-project-application',$names,true)&&in_array('alexcuadra/apply-project-change',$names,true)&&in_array('alexcuadra/prepare-project-recovery',$names,true)&&in_array('alexcuadra/recover-project-change',$names,true)&&!preg_grep('/approve-project/',$names));
    $r=$data($tool('get-project-application',['review_id'=>$fresh['review_id'],'locale'=>'en'],$auth,$session));
    $check('http_owned_review_matches_current_dto',($r['success']??false)&&$r['data']===$get($fresh));
    $r=$data($tool('prepare-project-application',$input,$auth,$session));$check('http_prepare_replay_matches_original_dto',($r['success']??false)&&$r['data']===$review);
    foreach(['other'=>'not_found','operator'=>'forbidden']as $label=>$message){$r=$tool('get-project-application',['review_id'=>$fresh['review_id'],'locale'=>'en'],$credentials[$label]['auth'],$sessions[$label]);
        $check('http_review_rejects_'.$label,str_contains(wp_json_encode($r['json']),$message)&&!str_contains(wp_json_encode($r['json']),'ORIGINAL_REVIEW_BODY'));}
    $r=$tool('prepare-project-application',$input+['approved'=>true],$auth,$session);$check('http_schema_rejects_approval_boolean',!($data($r)['success']??false)&&!str_contains(wp_json_encode($r['json']),'ORIGINAL_REVIEW_BODY'));
    $init=$rpc('initialize',['protocolVersion'=>'2025-11-25','capabilities'=>new stdClass(),'clientInfo'=>['name'=>'review-lab-second','version'=>'1']],$auth);
    $sessions['owner_second']=$init['session'];$http(['jsonrpc'=>'2.0','method'=>'notifications/initialized'],$auth,$sessions['owner_second']);
    $parallel=static function($parameters,$label)use($auth,$session,$sessions,&$sequence,$wpdb,$data,$check){
        $race=bin2hex(random_bytes(16));$requests=[];
        foreach([$session,$sessions['owner_second']]as $i=>$s){$requests[]=['url'=>'http://wordpress/?rest_route=/mcp/mcp-adapter-default-server','type'=>'POST',
            'headers'=>['Content-Type'=>'application/json','Accept'=>'application/json, text/event-stream','Authorization'=>$auth,'Mcp-Session-Id'=>$s,'MCP-Protocol-Version'=>'2025-11-25','X-Ac-Changes-Race'=>$race],
            'data'=>wp_json_encode(['jsonrpc'=>'2.0','id'=>++$sequence,'method'=>'tools/call','params'=>['name'=>'mcp-adapter-execute-ability',
                'arguments'=>['ability_name'=>'alexcuadra/prepare-project-application','parameters'=>$parameters[$i]]]])];}
        $responses=\WpOrg\Requests\Requests::request_multiple($requests,['timeout'=>25,'connect_timeout'=>5,'follow_redirects'=>false,'transport'=>new \WpOrg\Requests\Transport\Curl()]);
        $success=[];$errors=[];
        foreach($responses as $r){if(!$r instanceof \WpOrg\Requests\Response){throw new RuntimeException('REVIEW_RACE_HTTP_FAILED');}$j=['json'=>json_decode($r->body,true)];$d=$data($j);
            if(($d['success']??false)===true){$success[]=$d['data'];}else{$text=wp_json_encode($j);$found=false;foreach(['request_reused','owner_quota']as $m){if(str_contains($text,$m)){$errors[]=$m;$found=true;break;}}if(!$found){throw new RuntimeException('REVIEW_RACE_UNEXPECTED_FAILURE');}}}
        $table=$wpdb->prefix.'ac_change_http_races';$rows=$wpdb->get_results($wpdb->prepare("SELECT worker_id,arrived,released FROM $table WHERE race_key=%s",$race),ARRAY_A);
        $overlap=count($rows)===2&&count(array_unique(array_column($rows,'worker_id')))===2&&max(array_column($rows,'arrived'))<=min(array_column($rows,'released'));
        $check('review_race_'.$label.'_independent_workers_overlap',$overlap);
        return [$success,$errors,['scenario'=>$label,'independentWorkers'=>count($rows),'overlapVerified'=>$overlap,'successes'=>count($success),'errors'=>$errors]];
    };
    $ri=$input;$ri['expected_proposal_version']=$updated['version'];$ri['request_id']=$key();$usage=C\owner_usage($users['owner']->ID);
    [$success,$errors,$proof]=$parallel([$ri,$ri],'duplicate_prepare');$report['raceProofs'][]=$proof;
    $check('parallel_prepare_returns_two_identical_results',count($success)===2&&$errors===[]&&$success[0]===$success[1]);
    $check('parallel_prepare_consumes_one_receipt_and_review',C\owner_usage($users['owner']->ID)['requests']===$usage['requests']+1);
    $ri['request_id']=$key();$different=$ri;$different['expected_proposal_version']=str_repeat('a',64);
    // Key reuse is checked after a committed request; no scheduler-order assumption.
    $r=$call('prepare-project-application',$ri);$different['request_id']=$ri['request_id'];
    $response=$tool('prepare-project-application',$different,$auth,$session);
    $check('http_review_key_reuse_conflicts_after_commit',!is_wp_error($r)&&str_contains(wp_json_encode($response['json']),'request_reused'));
    $ri['request_id']=$key();$second=$ri;$second['request_id']=$key();$usage=C\owner_usage($users['owner']->ID);
    [$success,$errors,$proof]=$parallel([$ri,$second],'last_review_slot');$report['raceProofs'][]=$proof;
    $check('parallel_last_review_slot_admits_only_one',count($success)===1&&$errors===['owner_quota']);
    $check('parallel_review_limit_never_exceeded',(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['reviews']} WHERE owner_id=%d",$users['owner']->ID))===C\MAX_OWNER_REVIEWS&&C\owner_usage($users['owner']->ID)['requests']===$usage['requests']+1);
    $query='query {__schema{types{name}} root:__type(name:"RootQuery"){fields{name}} mutation:__type(name:"RootMutation"){fields{name}}}';
    foreach(['owner','other','admin']as $label){$g=$http(['query'=>$query],$credentials[$label]['auth'],null,'/?graphql');$j=$g['json']['data']??[];
        $names=array_merge(array_column($j['__schema']['types']??[],'name'),array_column($j['root']['fields']??[],'name'),array_column($j['mutation']['fields']??[],'name'));
        $check('graphql_'.$label.'_has_no_review_or_approval_routes',empty($g['json']['errors'])&&count($names)>20&&!preg_grep('/acChangeReview|sourceReview|projectApplication|ac_change_review|sourceApproval/i',$names));
        $r=$http(null,$credentials[$label]['auth'],null,'/?rest_route=/wp/v2/ac_change_reviews','GET');$check('rest_'.$label.'_has_no_review_collection',$r['status']===404);
    }
    $r=$http(null,$auth,null,'/?rest_route=/wp-abilities/v1/abilities','GET');
    $check('core_rest_ability_discovery_hides_review_tools',$r['status']===200&&!str_contains(wp_json_encode($r['json']),'alexcuadra/prepare-project-application')&&!str_contains(wp_json_encode($r['json']),'alexcuadra/get-project-application'));
    $report['sourceRowsPreserved']=true;$report['reviewLimit']=C\MAX_OWNER_REVIEWS;$report['approvalLifetimeSeconds']=C\REVIEW_LIFETIME;
}catch(Throwable $e){$report['failure']=preg_match('/^(FAILED: [a-z0-9_]+|[A-Z_]+)$/',$e->getMessage())?$e->getMessage():'REVIEW_LAB_FAILED';$report['diagnostic']=['class'=>get_class($e),'line'=>$e->getLine()];}
finally{
    $wpdb->query('ROLLBACK');
    foreach($sessions as $label=>$session){try{$http(null,$credentials[$label==='owner_second'?'owner':$label]['auth'],$session,'/?rest_route=/mcp/mcp-adapter-default-server','DELETE');}catch(Throwable $ignored){$report['sessionCleanupError']=true;}}
    foreach($credentials as $c){WP_Application_Passwords::delete_application_password($c['user'],$c['uuid']);}
    foreach($users as $u){foreach(['alexcuadra_read_public_projects',C\CAPABILITY,C\APPLY_CAPABILITY,C\APPROVE_CAPABILITY]as $cap){$u->remove_cap($cap);}}
    $clean=true;foreach($users as $u){$freshUser=new WP_User($u->ID);foreach(['alexcuadra_read_public_projects',C\CAPABILITY,C\APPLY_CAPABILITY,C\APPROVE_CAPABILITY]as $cap){$clean=$clean&&!$freshUser->has_cap($cap);}}
    foreach($credentials as $c){foreach(WP_Application_Passwords::get_user_application_passwords($c['user'])as $p){$clean=$clean&&$p['uuid']!==$c['uuid'];}}
    $switch(0);if(!$clean||isset($report['sessionCleanupError'])){$report['failure']=$report['failure']??'REVIEW_CLEANUP_FAILED';}else{$cases[]='review_synthetic_scopes_and_credentials_removed';}
}
$report['passed']=count($cases);$report['failed']=isset($report['failure'])?1:0;$report['cases']=$cases;
WP_CLI::line('EDITORIAL_CHANGES_REVIEWS_RESULT='.wp_json_encode($report));if($report['failed']){WP_CLI::halt(1);}
