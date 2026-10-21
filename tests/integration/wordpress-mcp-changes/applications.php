<?php
use AlexCuadra\EditorialChanges as C;
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress' || !function_exists('acf_get_field')) { throw new RuntimeException('ISOLATED_APPLICATION_STACK_REQUIRED'); }
global $wpdb;
$cases=[];$users=[];$credentials=[];$sessions=[];$sequence=0;$activeOwner=0;$operator=null;
$report=['environment'=>'isolated-source-application','sharedCmsModified'=>false,'sharedPermissionsModified'=>false,'emailSent'=>false,'hostPortsPublished'=>false];
$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED: '.$name);}$cases[]=$name;};
$error=static function($r,$name,$status){return is_wp_error($r)&&$r->get_error_message()===$name&&($r->get_error_data()['status']??null)===$status;};
$switch=static function($id){wp_set_current_user(0);wp_set_current_user($id);};$key=static function(){return bin2hex(random_bytes(16));};
$call=static function($name,$input){return wp_get_ability('alexcuadra/'.$name)->execute($input);};
$newOwner=static function()use(&$users,&$activeOwner,$switch){$id=wp_create_user('apply'.bin2hex(random_bytes(6)),wp_generate_password(32));if(is_wp_error($id)){throw new RuntimeException('APPLICATION_USER_FAILED');}
    $u=new WP_User($id);$u->set_role('subscriber');foreach(['alexcuadra_read_public_projects',C\CAPABILITY,C\APPLY_CAPABILITY,C\RECOVER_CAPABILITY]as $cap){$u->add_cap($cap);}$users[]=$u;$activeOwner=$id;$switch($id);return $u;};
$setOwner=static function($u)use(&$activeOwner,$switch){$activeOwner=$u->ID;$switch($activeOwner);};
$make=static function($locale='en',$meta='normal'){
    $id=wp_insert_post(['post_type'=>'project','post_status'=>'publish','post_author'=>1,'post_title'=>'Synthetic application source','post_content'=>'<p>Original café "body" \\ final.</p>',
        'post_excerpt'=>'Original excerpt','post_name'=>'application-'.bin2hex(random_bytes(6))],true);if(is_wp_error($id)){throw new RuntimeException('APPLICATION_SOURCE_FAILED');}
    pll_set_post_language($id,$locale);update_field(C\TAGLINE_KEY,'Original tagline',$id);update_post_meta($id,'unrelated_private','PRIVATE_SOURCE_SENTINEL');
    if($meta==='missing'){delete_post_meta($id,'project_tagline');delete_post_meta($id,'_project_tagline');}
    if($meta==='empty'){update_post_meta($id,'project_tagline','');delete_post_meta($id,'_project_tagline');}return $id;
};
$prepare=static function($id,$patch,$locale='en')use($call,$key){$pi=['project_id'=>$id,'locale'=>$locale,'patch'=>$patch,'request_id'=>$key()];$p=$call('create-project-change',$pi);
    if(is_wp_error($p)){throw new RuntimeException('APPLICATION_PROPOSAL_FAILED');}
    $r=$call('prepare-project-application',['proposal_id'=>$p['proposal_id'],'locale'=>$locale,'expected_proposal_version'=>$p['version'],'expected_base_version'=>$p['base_version'],'request_id'=>$key()]);
    if(is_wp_error($r)){throw new RuntimeException('APPLICATION_REVIEW_FAILED');}return [$p,$r,$pi];};
$approve=static function($review)use(&$operator,&$activeOwner,$switch){$switch($operator->ID);try{return C\review_approve($review['review_id'],$review['locale'],$review['review_hash']);}finally{$switch($activeOwner);}};
$commitInput=static function($r,$a)use($key){return ['review_id'=>$r['review_id'],'locale'=>$r['locale'],'expected_review_hash'=>$r['review_hash'],'approval_id'=>$a['approval_id'],'request_id'=>$key()];};
$recoverReview=static function($app)use($call,$key){return $call('prepare-project-recovery',['application_id'=>$app['application_id'],'locale'=>$app['locale'],'expected_source_version'=>$app['source_version'],'request_id'=>$key()]);};
$snapshot=static function($id)use($wpdb){return ['post'=>$wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d",$id),ARRAY_A),
    'meta'=>$wpdb->get_results($wpdb->prepare("SELECT meta_key,meta_value FROM $wpdb->postmeta WHERE post_id=%d ORDER BY meta_id",$id),ARRAY_A),
    'terms'=>$wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->term_relationships WHERE object_id=%d ORDER BY term_taxonomy_id",$id),ARRAY_A)];};
$withoutTimes=static function($s){unset($s['post']['post_modified'],$s['post']['post_modified_gmt']);return $s;};
$tables=['apps'=>$wpdb->prefix.'ac_change_applications','reviews'=>$wpdb->prefix.'ac_change_reviews','approvals'=>$wpdb->prefix.'ac_change_approvals','requests'=>$wpdb->prefix.'ac_change_requests'];
$dbState=static function($id,$owner)use($wpdb,$snapshot,$tables){$rows=[];foreach($tables as $table){$rows[]=$wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE owner_id=%d",$owner),ARRAY_A);}return hash('sha256',serialize([$snapshot($id),$rows,C\owner_usage($owner)]));};
$http=static function($payload,$auth=null,$session=null,$path='/?rest_route=/mcp/mcp-adapter-default-server',$method='POST'){
    $headers=['Content-Type'=>'application/json','Accept'=>'application/json, text/event-stream'];if($auth!==null){$headers['Authorization']=$auth;}if($session!==null){$headers['Mcp-Session-Id']=$session;$headers['MCP-Protocol-Version']='2025-11-25';}
    $r=wp_remote_request('http://wordpress'.$path,['method'=>$method,'headers'=>$headers,'body'=>$payload===null?null:wp_json_encode($payload),'timeout'=>25,'redirection'=>0]);
    if(is_wp_error($r)){throw new RuntimeException('APPLICATION_HTTP_FAILED');}return ['status'=>wp_remote_retrieve_response_code($r),'json'=>json_decode(wp_remote_retrieve_body($r),true),'session'=>wp_remote_retrieve_header($r,'mcp-session-id')];};
$rpc=static function($method,$params,$auth,$session=null)use($http,&$sequence){return $http(['jsonrpc'=>'2.0','id'=>++$sequence,'method'=>$method,'params'=>$params],$auth,$session);};
$data=static function($r){$v=$r['json']['result']??[];if(isset($v['structuredContent'])){return $v['structuredContent'];}foreach($v['content']??[]as $c){if(($c['type']??null)==='text'){$j=json_decode($c['text'],true);if(is_array($j)){return $j;}}}return null;};
try{
    $id=wp_create_user('operator'.bin2hex(random_bytes(6)),wp_generate_password(32));if(is_wp_error($id)){throw new RuntimeException('APPLICATION_OPERATOR_FAILED');}
    $operator=new WP_User($id);$operator->set_role('subscriber');$operator->add_cap(C\APPROVE_CAPABILITY);$users[]=$operator;
    $check('all_three_source_abilities_registered',wp_get_ability('alexcuadra/apply-project-change')&&wp_get_ability('alexcuadra/prepare-project-recovery')&&wp_get_ability('alexcuadra/recover-project-change'));
    $profiles=['body_only'=>['body_html'=>'<h2>Nuevo "café"</h2><p>Ruta \\ final.</p>'],'tagline_only'=>['tagline'=>'Subtítulo ñ "exacto" \\ final'],
        'both'=>['body_html'=>'<p>Ambos campos</p>','tagline'=>'Ambos ñ'],'empty'=>['body_html'=>'','tagline'=>''],
        'missing'=>['tagline'=>'Se puede recuperar ausencia'],'empty_reference'=>['tagline'=>'Valor posterior'],'spanish'=>['body_html'=>'<h2>Contenido ES</h2>','tagline'=>'Subtítulo ES']];
    foreach($profiles as $label=>$patch){
        $owner=$newOwner();$locale=$label==='spanish'?'es':'en';$source=$make($locale,$label==='missing'?'missing':($label==='empty_reference'?'empty':'normal'));
        $before=$snapshot($source);[$p,$r,$pi]=$prepare($source,$patch,$locale);$a=$approve($r);$check($label.'_approval_ready',!is_wp_error($a));$input=$commitInput($r,$a);
        $applied=$call('apply-project-change',$input);
        $check($label.'_applies_exact_diff',!is_wp_error($applied)&&$applied['state']==='applied'&&$applied['applied']===true
            &&C\review_source($source,$locale,$patch)['before']===$r['after']);
        $check($label.'_proposal_reports_applied_and_is_frozen',$call('get-project-change',['proposal_id'=>$p['proposal_id'],'locale'=>$locale])['state']==='applied'
            &&$error(C\execute(['proposal_id'=>$p['proposal_id'],'locale'=>$locale,'expected_version'=>$p['version'],'patch'=>['tagline'=>'Disallowed edit'],'request_id'=>$key()],'update'),'proposal_frozen',409));
        $check($label.'_apply_retry_is_one_event',$call('apply-project-change',$input)===$applied&&count(C\application_read($applied['application_id'],$locale)['payload']['events'])===1);
        $get=$call('get-project-application',['review_id'=>$r['review_id'],'locale'=>$locale]);$check($label.'_review_consumption_visible',$get['state']==='consumed'&&$get['applied']===true&&!$get['current']);
        $newInput=$input;$newInput['request_id']=$key();$check($label.'_consumed_approval_rejects_new_key',$error(C\application_commit($newInput,'apply'),'approval_consumed',409));
        $rr=$recoverReview($applied);$check($label.'_recovery_diff_is_exact_reverse',!is_wp_error($rr)&&$rr['before']===$r['after']&&$rr['after']===$r['before']);
        $ra=$approve($rr);$check($label.'_requires_new_independent_recovery_approval',!is_wp_error($ra)&&$ra['approval_id']!==$a['approval_id']);
        $ri=$commitInput($rr,$ra);$recovered=$call('recover-project-change',$ri);
        if(is_wp_error($recovered)||$withoutTimes($snapshot($source))!==$withoutTimes($before)){
            $afterSnapshot=$snapshot($source);$report['profileDiagnostic']=['profile'=>$label,'error'=>is_wp_error($recovered)?$recovered->get_error_message():null,
                'changedPostColumns'=>array_keys(array_diff_assoc($withoutTimes($afterSnapshot)['post'],$withoutTimes($before)['post'])),
                'metaCountBefore'=>count($before['meta']),'metaCountAfter'=>count($afterSnapshot['meta']),'termsEqual'=>$before['terms']===$afterSnapshot['terms']];
        }
        $check($label.'_recovery_restores_only_original_values',!is_wp_error($recovered)&&$recovered['state']==='recovered'&&$withoutTimes($snapshot($source))===$withoutTimes($before));
        $check($label.'_recovery_event_and_proposal_state_are_new',$recovered['version']!==$applied['version']&&count(C\application_read($applied['application_id'],$locale)['payload']['events'])===2
            &&$call('get-project-change',['proposal_id'=>$p['proposal_id'],'locale'=>$locale])['state']==='recovered');
        $usage=C\owner_usage($activeOwner);$check($label.'_both_event_receipts_replay_after_recovery',$call('apply-project-change',$input)===$applied&&$call('recover-project-change',$ri)===$recovered&&C\owner_usage($activeOwner)===$usage);
        $check($label.'_legacy_receipt_remains_historical_draft',$call('create-project-change',$pi)===$p);
        $app=C\application_read($applied['application_id'],$locale);$check($label.'_recovery_releases_reservations',(int)$app['row']['reserved_bytes']===0&&(int)$app['row']['reserved_requests']===0&&(int)$app['row']['reserved_reviews']===0);
    }
    // Denials and failures reuse one approved review; none consumes its approval or changes source.
    $owner=$newOwner();$source=$make();[$p,$r]=$prepare($source,['body_html'=>'<p>Failure candidate</p>','tagline'=>'Failure tagline']);
    $fake=['approval_id'=>PHP_INT_MAX];$input=$commitInput($r,$fake);$check('apply_without_approval_denied',$error(C\application_commit($input,'apply'),'approval_required',403));
    $a=$approve($r);$input=$commitInput($r,$a);$before=$dbState($source,$activeOwner);$originalBody=$snapshot($source)['post']['post_content'];
    $bad=$input;$bad['approved']=true;$check('apply_callback_rejects_client_approval_boolean',$error(C\application_commit($bad,'apply'),'invalid_input',400));
    $bad=$input;$bad['expected_review_hash']=str_repeat('0',64);$check('apply_rejects_tampered_review_hash',$error(C\application_commit($bad,'apply'),'review_changed',409));
    $bad=$input;$bad['locale']='es';$check('apply_rejects_wrong_locale',$error(C\application_commit($bad,'apply'),'not_found',404));
    $switch(0);$check('anonymous_source_apply_denied',$error(C\application_commit($input,'apply'),'forbidden',403));$switch($activeOwner);
    $owner->remove_cap(C\APPLY_CAPABILITY);$switch($activeOwner);$check('revoked_apply_scope_denied',$error(C\application_commit($input,'apply'),'forbidden',403));$owner->add_cap(C\APPLY_CAPABILITY);$switch($activeOwner);
    $operator->remove_cap(C\APPROVE_CAPABILITY);$check('revoked_approver_scope_denied',$error(C\application_commit($input,'apply'),'forbidden',403));$operator->add_cap(C\APPROVE_CAPABILITY);
    $fieldFilter=static function($field){$field['instructions']='Changed after approval';return $field;};add_filter('acf/load_field/key='.C\TAGLINE_KEY,$fieldFilter);acf_get_store('fields')->reset();
    try{$check('apply_rechecks_schema_after_approval',$error(C\application_commit($input,'apply'),'schema_changed',409));}finally{remove_filter('acf/load_field/key='.C\TAGLINE_KEY,$fieldFilter);acf_get_store('fields')->reset();}
    $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_author=%d WHERE ID=%d",$owner->ID,$source));C\review_clear_cache($source);
    $check('apply_rechecks_source_identity_outside_patch',$error(C\application_commit($input,'apply'),'source_changed',409));
    $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_author=1 WHERE ID=%d",$source));C\review_clear_cache($source);
    $proposalRaw=$wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE post_id=%d AND meta_key=%s",$p['proposal_id'],C\PAYLOAD));
    $changedKey=$key();$newHead=$call('update-project-change',['proposal_id'=>$p['proposal_id'],'locale'=>'en','expected_version'=>$p['version'],'patch'=>['tagline'=>'Changed proposal'],'request_id'=>$changedKey]);
    $check('apply_rechecks_proposal_after_approval',!is_wp_error($newHead)&&$error(C\application_commit($input,'apply'),'proposal_changed',409));
    $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value=%s WHERE post_id=%d AND meta_key=%s",$proposalRaw,$p['proposal_id'],C\PAYLOAD));C\review_clear_cache($p['proposal_id']);
    $wpdb->query($wpdb->prepare("DELETE FROM {$tables['requests']} WHERE owner_id=%d AND request_id=%s",$activeOwner,$changedKey));
    $before=$dbState($source,$activeOwner);
    $wpdb->query('START TRANSACTION');try{$nested=C\application_commit($input,'apply');$active=(int)$wpdb->get_var('SELECT @@in_transaction');}finally{$wpdb->query('ROLLBACK');}
    $check('source_apply_rejects_outer_transaction_without_committing_it',$error($nested,'transaction_busy',503)&&$active===1);
    foreach(['post_api','acf_api','event','approval','receipt','commit','hook']as $point){
        $break=static function($q)use($point,$tables,$wpdb,$source){$hit=($point==='post_api'&&str_starts_with($q,'UPDATE `'.$wpdb->posts.'`'))
            ||($point==='event'&&str_starts_with($q,'INSERT INTO '.$tables['apps'].' '))||($point==='approval'&&str_starts_with($q,'UPDATE '.$tables['approvals'].' SET payload='))
            ||($point==='receipt'&&str_starts_with($q,'INSERT INTO '.$tables['requests'].' '))||($point==='commit'&&$q==='COMMIT');return $hit?'INVALID_APPLICATION_TEST_SQL':$q;};
        $acfFailure=static function($value,$id,$name)use($source){return $id===$source&&$name==='project_tagline'?false:$value;};
        $hook=static function($id)use($source){if($id===$source){update_post_meta($id,'unexpected_hook_side_effect','must_rollback');}};
        $suppress=$wpdb->suppress_errors(true);add_filter('query',$break);
        if($point==='acf_api'){add_filter('update_post_metadata',$acfFailure,10,3);}if($point==='hook'){add_action('wp_after_insert_post',$hook);}
        try{$failed=C\application_commit($input,'apply');}finally{remove_filter('query',$break);remove_filter('update_post_metadata',$acfFailure,10);remove_action('wp_after_insert_post',$hook);$wpdb->suppress_errors($suppress);}
        $check($point.'_failure_is_atomic',is_wp_error($failed)&&$dbState($source,$activeOwner)===$before);
        $check($point.'_rollback_invalidates_post_and_acf_caches',get_post($source)->post_content===$originalBody&&get_field(C\TAGLINE_KEY,$source)==='Original tagline');
    }
    $applied=$call('apply-project-change',$input);$check('failed_apply_retries_same_key_successfully',!is_wp_error($applied));
    $rr=$recoverReview($applied);$ra=$approve($rr);$ri=$commitInput($rr,$ra);
    update_post_meta($source,'external_editor','changed');$before=$dbState($source,$activeOwner);
    $check('recovery_rejects_any_external_meta_edit',$error(C\application_commit($ri,'recover'),'source_changed',409)&&$dbState($source,$activeOwner)===$before);delete_post_meta($source,'external_editor');
    $bad=$ri;$bad['approval_id']=$a['approval_id'];$check('apply_approval_cannot_authorize_recovery',$error(C\application_commit($bad,'recover'),'approval_required',403));
    $owner->remove_cap(C\RECOVER_CAPABILITY);$switch($activeOwner);$check('recovery_requires_distinct_scope',$error(C\application_commit($ri,'recover'),'forbidden',403));$owner->add_cap(C\RECOVER_CAPABILITY);$switch($activeOwner);
    $before=$dbState($source,$activeOwner);$break=static function($q){return $q==='COMMIT'?'INVALID_RECOVERY_COMMIT':$q;};$suppress=$wpdb->suppress_errors(true);add_filter('query',$break);
    try{$failed=C\application_commit($ri,'recover');}finally{remove_filter('query',$break);$wpdb->suppress_errors($suppress);}
    $check('recovery_commit_failure_restores_source_approval_event_and_reservation',is_wp_error($failed)&&$dbState($source,$activeOwner)===$before);
    $check('recovery_failure_retries_same_key_successfully',!is_wp_error($call('recover-project-change',$ri)));
    // Fill the free budgets AFTER apply. The reserved path still fits at the limits.
    $owner=$newOwner();$source=$make();[$p,$r]=$prepare($source,['tagline'=>'Reserved recovery']);$a=$approve($r);$input=$commitInput($r,$a);$applied=$call('apply-project-change',$input);
    $check('apply_reserves_two_requests_one_review_and_bytes',!is_wp_error($applied)&&C\owner_usage($activeOwner)['requests']===5&&C\application_read($applied['application_id'],'en')['row']['reserved_bytes']==C\RECOVERY_BYTES);
    $usage=C\owner_usage($activeOwner);for($i=$usage['requests'];$i<C\MAX_OWNER_REQUESTS;$i++){$wpdb->query($wpdb->prepare("INSERT INTO {$tables['requests']} (owner_id,request_id,request_hash,proposal_id,response) VALUES (%d,%s,%s,%d,'x')",$activeOwner,$key(),str_repeat('0',64),$p['proposal_id']));}
    $usage=C\owner_usage($activeOwner);$wpdb->query($wpdb->prepare("INSERT INTO {$tables['requests']} (owner_id,request_id,request_hash,proposal_id,response) VALUES (%d,%s,%s,%d,%s)",$activeOwner,$key(),str_repeat('0',64),$p['proposal_id'],str_repeat('x',C\MAX_OWNER_BYTES-$usage['bytes']-1)));
    // The filler above occupies one extra request; remove a one-byte synthetic receipt to preserve128.
    $wpdb->query($wpdb->prepare("DELETE FROM {$tables['requests']} WHERE owner_id=%d AND response='x' LIMIT 1",$activeOwner));
    $check('reserved_recovery_fixture_fills_free_quotas',C\owner_usage($activeOwner)['requests']===C\MAX_OWNER_REQUESTS&&C\owner_usage($activeOwner)['bytes']<=C\MAX_OWNER_BYTES);
    $rr=$recoverReview($applied);$check('reserved_recovery_review_works_at_full_request_and_byte_quotas',!is_wp_error($rr));
    $ra=$approve($rr);$check('reserved_recovery_approval_works_without_free_bytes',!is_wp_error($ra));
    $ri=$commitInput($rr,$ra);$done=$call('recover-project-change',$ri);$check('reserved_recovery_commits_at_full_quota',!is_wp_error($done)&&C\owner_usage($activeOwner)['requests']===C\MAX_OWNER_REQUESTS);
    // No capacity to reserve recovery: apply must roll back its source write too.
    $owner=$newOwner();$source=$make();[$p,$r]=$prepare($source,['tagline'=>'No capacity']);$a=$approve($r);$input=$commitInput($r,$a);$usage=C\owner_usage($activeOwner);
    $wpdb->query($wpdb->prepare("INSERT INTO {$tables['requests']} (owner_id,request_id,request_hash,proposal_id,response) VALUES (%d,%s,%s,%d,%s)",$activeOwner,$key(),str_repeat('0',64),$p['proposal_id'],str_repeat('x',C\MAX_OWNER_BYTES-$usage['bytes']-1000)));
    $before=$dbState($source,$activeOwner);$check('apply_denies_when_recovery_byte_reservation_does_not_fit',$error(C\application_commit($input,'apply'),'owner_quota',429)&&$dbState($source,$activeOwner)===$before);
    // Lost acknowledgement: COMMIT succeeds, but the driver reports failure. Same key resolves it.
    $owner=$newOwner();$source=$make();[$p,$r]=$prepare($source,['tagline'=>'Lost confirmation']);$a=$approve($r);$input=$commitInput($r,$a);
    $lost=static function($q)use($wpdb){static $inside=false;if($q==='COMMIT'&&!$inside){$inside=true;$wpdb->query('COMMIT');return 'INVALID_LOST_ACKNOWLEDGEMENT';}return $q;};
    $suppress=$wpdb->suppress_errors(true);add_filter('query',$lost);try{$uncertain=C\application_commit($input,'apply');}finally{remove_filter('query',$lost);$wpdb->suppress_errors($suppress);}
    $check('lost_commit_acknowledgement_returns_retryable_error',$error($uncertain,'storage_unavailable',503));
    $confirmed=$call('apply-project-change',$input);$check('same_request_resolves_committed_event_without_second_effect',!is_wp_error($confirmed)&&count(C\application_read($confirmed['application_id'],'en')['payload']['events'])===1);
    // Recovery review expiration never renews an old approval or overwrites a retained diff.
    $rr=$recoverReview($confirmed);$raw=$wpdb->get_var($wpdb->prepare("SELECT payload FROM {$tables['reviews']} WHERE review_id=%d",$rr['review_id']));$expired=json_decode($raw,true);
    $expired['document']['created_at']-=601;$expired['document']['expires_at']-=601;$expired['review_hash']=hash('sha256',wp_json_encode(C\canonical($expired['document'])));
    $wpdb->query($wpdb->prepare("UPDATE {$tables['reviews']} SET payload=%s WHERE review_id=%d",wp_json_encode($expired),$rr['review_id']));
    $replacement=$recoverReview($confirmed);$check('expired_recovery_review_requires_new_retained_diff',!is_wp_error($replacement)&&$replacement['review_id']!==$rr['review_id']&&$replacement['review_hash']!==$rr['review_hash']);
    $check('expired_diff_is_retained',$wpdb->get_var($wpdb->prepare("SELECT payload FROM {$tables['reviews']} WHERE review_id=%d",$rr['review_id']))===wp_json_encode($expired));
    $ra=$approve($replacement);$check('replacement_recovery_requires_and_accepts_new_approval',!is_wp_error($ra)&&!is_wp_error($call('recover-project-change',$commitInput($replacement,$ra))));
    // HTTP sessions use synthetic Application Passwords. Nothing is copied from the real CMS.
    $sessionFor=static function($u)use(&$credentials,&$sessions,$rpc,$http){$pair=WP_Application_Passwords::create_new_application_password($u->ID,['name'=>'synthetic-application-http']);
        if(is_wp_error($pair)){throw new RuntimeException('APPLICATION_CREDENTIAL_FAILED');}$auth='Basic '.base64_encode($u->user_login.':'.$pair[0]);$credentials[]=['user'=>$u->ID,'uuid'=>$pair[1]['uuid']];
        $init=$rpc('initialize',['protocolVersion'=>'2025-11-25','capabilities'=>new stdClass(),'clientInfo'=>['name'=>'application-lab','version'=>'1']],$auth);$session=$init['session'];
        if(!is_string($session)||$session===''){throw new RuntimeException('APPLICATION_SESSION_FAILED');}$sessions[]=['auth'=>$auth,'session'=>$session];
        $http(['jsonrpc'=>'2.0','method'=>'notifications/initialized'],$auth,$session);return ['auth'=>$auth,'session'=>$session];};
    $parallel=static function($names,$inputs,$identities,$label)use(&$sequence,$wpdb,$data,$check){
        $race=bin2hex(random_bytes(16));$requests=[];
        foreach($identities as $i=>$identity){$requests[]=['url'=>'http://wordpress/?rest_route=/mcp/mcp-adapter-default-server','type'=>'POST',
            'headers'=>['Content-Type'=>'application/json','Accept'=>'application/json, text/event-stream','Authorization'=>$identity['auth'],'Mcp-Session-Id'=>$identity['session'],'MCP-Protocol-Version'=>'2025-11-25','X-Ac-Changes-Race'=>$race],
            'data'=>wp_json_encode(['jsonrpc'=>'2.0','id'=>++$sequence,'method'=>'tools/call','params'=>['name'=>'mcp-adapter-execute-ability','arguments'=>['ability_name'=>'alexcuadra/'.$names[$i],'parameters'=>$inputs[$i]]]])];}
        $responses=\WpOrg\Requests\Requests::request_multiple($requests,['timeout'=>25,'connect_timeout'=>5,'follow_redirects'=>false,'transport'=>new \WpOrg\Requests\Transport\Curl()]);
        $success=[];$errors=[];
        foreach($responses as $i=>$response){if(!$response instanceof \WpOrg\Requests\Response){throw new RuntimeException('APPLICATION_RACE_NETWORK_FAILED');}$parsed=['json'=>json_decode($response->body,true)];$d=$data($parsed);
            if(($d['success']??false)===true){$success[$i]=$d['data'];}else{$found=false;foreach(['source_changed','proposal_frozen','approval_consumed']as $code){if(str_contains(wp_json_encode($parsed['json']),$code)){$errors[]=$code;$found=true;break;}}if(!$found){$json=wp_json_encode($parsed['json']);preg_match('/ac_change_([a-z_]+)/',$json,$m);throw new RuntimeException('APPLICATION_RACE_UNEXPECTED_'.strtoupper($m[1]??'TRANSPORT'));}}}
        $table=$wpdb->prefix.'ac_change_http_races';$rows=$wpdb->get_results($wpdb->prepare("SELECT worker_id,arrived,released FROM $table WHERE race_key=%s",$race),ARRAY_A);
        $overlap=count($rows)===2&&count(array_unique(array_column($rows,'worker_id')))===2&&max(array_column($rows,'arrived'))<=min(array_column($rows,'released'));
        $check($label.'_two_independent_workers_overlap',$overlap);
        return [$success,$errors,['scenario'=>$label,'independentWorkers'=>count($rows),'overlapVerified'=>$overlap,'successes'=>count($success),'errors'=>$errors]];
    };
    $first=$newOwner();$source=$make();[$p,$r]=$prepare($source,['tagline'=>'Winner first']);$a=$approve($r);$inFirst=$commitInput($r,$a);$identityFirst=$sessionFor($first);
    $second=$newOwner();[$p2,$r2]=$prepare($source,['tagline'=>'Winner second']);$a2=$approve($r2);$inSecond=$commitInput($r2,$a2);$identitySecond=$sessionFor($second);
    $check('foreign_owner_cannot_apply_review',$error(C\application_commit($inFirst,'apply'),'not_found',404));
    [$success,$errors,$proof]=$parallel(['apply-project-change','apply-project-change'],[$inFirst,$inSecond],[$identityFirst,$identitySecond],'cross_owner_apply');$report['raceProofs'][]=$proof;
    $check('cross_owner_apply_has_one_effect_one_source_conflict',count($success)===1&&$errors===['source_changed']);
    $winner=array_key_first($success);$winningUser=$winner===0?$first:$second;$losingUser=$winner===0?$second:$first;$winningProposal=$winner===0?$p:$p2;
    $setOwner($winningUser);C\review_clear_cache($source);$app=$success[$winner];$check('source_lock_preserves_winning_fields',get_post_meta($source,'project_tagline',true)===($winner===0?'Winner first':'Winner second'));
    $setOwner($losingUser);$check('losing_owner_has_no_partial_application',C\application_load_proposal(($winner===0?$p2:$p)['proposal_id'])===null);
    // Duplicate commit from two sessions of the same owner returns one immutable event.
    $dupe=$newOwner();$source2=$make();[$pd,$rd]=$prepare($source2,['body_html'=>'<p>Duplicate HTTP apply</p>']);$ad=$approve($rd);$inDupe=$commitInput($rd,$ad);
    $di1=$sessionFor($dupe);$di2=$sessionFor($dupe);$usage=C\owner_usage($activeOwner);
    [$success,$errors,$proof]=$parallel(['apply-project-change','apply-project-change'],[$inDupe,$inDupe],[$di1,$di2],'duplicate_apply');$report['raceProofs'][]=$proof;
    $check('duplicate_http_apply_returns_same_event',count($success)===2&&$errors===[]&&$success[0]===$success[1]);
    C\review_clear_cache($source2);$dupeApp=$success[0];$check('duplicate_http_apply_consumes_one_receipt',C\owner_usage($activeOwner)['requests']===$usage['requests']+3&&count(C\application_read($dupeApp['application_id'],'en')['payload']['events'])===1);
    // Prepare reverse for the winner, then a different owner's new proposal on that exact source.
    $setOwner($winningUser);C\review_clear_cache($source);$rr=$recoverReview($app);$ra=$approve($rr);$reverse=$commitInput($rr,$ra);
    $third=$newOwner();[$pt,$rt]=$prepare($source,['tagline'=>'A later owner']);$at=$approve($rt);$later=$commitInput($rt,$at);$identityThird=$sessionFor($third);
    $winnerIdentity=$winner===0?$identityFirst:$identitySecond;
    [$success,$errors,$proof]=$parallel(['recover-project-change','apply-project-change'],[$reverse,$later],[$winnerIdentity,$identityThird],'recover_against_other_apply');$report['raceProofs'][]=$proof;
    $check('recover_apply_race_has_one_effect_and_one_source_conflict',count($success)===1&&$errors===['source_changed']);
    $setOwner($dupe);C\review_clear_cache($source2);$rr=$recoverReview($dupeApp);$ra=$approve($rr);$reverse=$commitInput($rr,$ra);$usage=C\owner_usage($activeOwner);
    [$success,$errors,$proof]=$parallel(['recover-project-change','recover-project-change'],[$reverse,$reverse],[$di1,$di2],'duplicate_recover');$report['raceProofs'][]=$proof;
    $check('duplicate_http_recover_returns_same_event',count($success)===2&&$errors===[]&&$success[0]===$success[1]);
    C\review_clear_cache($source2);$check('duplicate_http_recover_consumes_reservation_once',count(C\application_read($dupeApp['application_id'],'en')['payload']['events'])===2&&C\owner_usage($activeOwner)['requests']===$usage['requests']);
    $replay=$rpc('tools/call',['name'=>'mcp-adapter-execute-ability','arguments'=>['ability_name'=>'alexcuadra/apply-project-change','parameters'=>$inDupe]],$di1['auth'],$di1['session']);
    $check('http_apply_replay_after_recovery_is_historical',($data($replay)['data']??null)===$dupeApp);
    // New source records are custom tables, never registered as REST/GraphQL content types.
    $adminIdentity=$sessionFor(new WP_User(1));
    foreach(['owner'=>$di1['auth'],'other'=>$identityThird['auth'],'admin'=>$adminIdentity['auth']]as $label=>$auth){
        $g=$http(['query'=>'query {__schema{types{name}} root:__type(name:"RootQuery"){fields{name}} mutation:__type(name:"RootMutation"){fields{name}}}'],$auth,null,'/?graphql');$j=$g['json']['data']??[];
        $names=array_merge(array_column($j['__schema']['types']??[],'name'),array_column($j['root']['fields']??[],'name'),array_column($j['mutation']['fields']??[],'name'));
        $check('graphql_'.$label.'_has_no_source_application_routes',empty($g['json']['errors'])&&count($names)>20&&!preg_grep('/sourceApplication|acChangeApplication|ac_change_application|sourceApproval|sourceReview/i',$names));
        $rest=$http(null,$auth,null,'/?rest_route=/wp/v2/ac_change_applications','GET');$check('rest_'.$label.'_has_no_application_collection',$rest['status']===404);
    }
    $report['transactionalSourceRecoveryVerified']=true;
}catch(Throwable $e){$report['failure']=preg_match('/^(FAILED: [a-z0-9_]+|[A-Z_]+)$/',$e->getMessage())?$e->getMessage():'APPLICATION_LAB_FAILED';$report['diagnostic']=['class'=>get_class($e),'line'=>$e->getLine()];}
finally{
    $wpdb->query('ROLLBACK');foreach($sessions as $s){try{$http(null,$s['auth'],$s['session'],'/?rest_route=/mcp/mcp-adapter-default-server','DELETE');}catch(Throwable $ignored){$report['sessionCleanupError']=true;}}
    foreach($credentials as $c){WP_Application_Passwords::delete_application_password($c['user'],$c['uuid']);}
    foreach($users as $u){foreach(['alexcuadra_read_public_projects',C\CAPABILITY,C\APPLY_CAPABILITY,C\RECOVER_CAPABILITY,C\APPROVE_CAPABILITY]as $cap){$u->remove_cap($cap);}}
    $clean=true;foreach($users as $u){$fresh=new WP_User($u->ID);foreach(['alexcuadra_read_public_projects',C\CAPABILITY,C\APPLY_CAPABILITY,C\RECOVER_CAPABILITY,C\APPROVE_CAPABILITY]as $cap){$clean=$clean&&!$fresh->has_cap($cap);}}
    foreach($credentials as $c){foreach(WP_Application_Passwords::get_user_application_passwords($c['user'])as $saved){$clean=$clean&&$saved['uuid']!==$c['uuid'];}}
    $switch(0);if(!$clean){$report['failure']=$report['failure']??'APPLICATION_CLEANUP_FAILED';}else{$cases[]='application_synthetic_scopes_removed';}
}
$report['passed']=count($cases);$report['failed']=isset($report['failure'])?1:0;$report['cases']=$cases;
WP_CLI::line('EDITORIAL_CHANGES_APPLICATIONS_RESULT='.wp_json_encode($report));if($report['failed']){WP_CLI::halt(1);}
