<?php
use AlexCuadra\EditorialChanges as Changes;
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { throw new RuntimeException('ISOLATED_QUOTAS_LAB_REQUIRED'); }
global $wpdb;
$cases=[]; $users=[]; $report=['environment'=>'isolated-idempotency-quotas','sharedCmsModified'=>false,'emailSent'=>false];
$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED: '.$name);} $cases[]=$name;};
$error=static function($r,$message,$status){return is_wp_error($r)&&$r->get_error_message()===$message&&($r->get_error_data()['status']??null)===$status;};
$switch=static function($id){wp_set_current_user(0);wp_set_current_user($id);};
$names=['create'=>'create-project-change','update'=>'update-project-change','restore'=>'restore-project-change-revision'];
$call=static function($op,$input)use($names){return wp_get_ability('alexcuadra/'.$names[$op])->execute($input);};
$key=static function(){return bin2hex(random_bytes(16));};
$raw=static function($id)use($wpdb){return $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE post_id=%d AND meta_key=%s",$id,Changes\PAYLOAD));};
try {
    $en=wp_insert_post(['post_type'=>'project','post_status'=>'publish','post_author'=>1,'post_title'=>'Quota source','post_content'=>'UNCHANGED_SOURCE_QUOTA']);
    pll_set_post_language($en,'en');update_post_meta($en,'project_tagline','Original');
    $sourceHash=static function()use($wpdb,$en){return hash('sha256',serialize([
        $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d",$en),ARRAY_A),
        $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id=%d ORDER BY meta_id",$en),ARRAY_A),
        $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->term_relationships WHERE object_id=%d ORDER BY term_taxonomy_id",$en),ARRAY_A)]));};
    $before=$sourceHash();
    foreach(['owner','other','count','bytes','requests','fail']as $name){
        $id=wp_create_user('quota'.bin2hex(random_bytes(6)),wp_generate_password(32));if(is_wp_error($id)){throw new RuntimeException('USER_SETUP_FAILED');}
        $u=new WP_User($id);$u->set_role('subscriber');$u->add_cap('alexcuadra_read_public_projects');$u->add_cap(Changes\CAPABILITY);$users[$name]=$u;
    }
    $switch($users['owner']->ID);
    $input=['project_id'=>$en,'locale'=>'en','request_id'=>$key(),'patch'=>['tagline'=>'Original proposal','body_html'=>'<p>Unicode café \\ "text"</p>']];
    $first=$call('create',$input);$id=$first['proposal_id'];$saved=$raw($id);$usage=Changes\owner_usage($users['owner']->ID);
    $check('create_replay_returns_exact_original_dto',$call('create',$input)===$first);
    $check('create_replay_consumes_no_storage_or_proposals',Changes\owner_usage($users['owner']->ID)===$usage&&$raw($id)===$saved);
    $ordered=['patch'=>array_reverse($input['patch'],true),'request_id'=>$input['request_id'],'locale'=>'en','project_id'=>$en];
    $check('property_order_does_not_change_request_identity',$call('create',$ordered)===$first);
    $changed=$input;$changed['patch']['tagline']='Different intent';
    $check('same_key_different_input_conflicts',$error(Changes\execute($changed,'create'),'request_reused',409));
    foreach([null,123,'bad',str_repeat('A',32)]as $bad){$invalid=$input;$invalid['request_id']=$bad;
        $check('invalid_request_id_'.count($cases),$error(Changes\execute($invalid,'create'),'invalid_input',400));}
    $missing=$input;unset($missing['request_id']);
    $check('missing_request_id_rejected_by_callback',$error(Changes\execute($missing,'create'),'invalid_input',400));
    $framework=$call('create',$missing);
    $check('missing_request_id_rejected_by_schema',is_wp_error($framework)&&$framework->get_error_code()==='ability_invalid_input');
    $up=['proposal_id'=>$id,'locale'=>'en','expected_version'=>$first['version'],'request_id'=>$key(),'patch'=>['tagline'=>'Second']];
    $second=$call('update',$up);$saved=$raw($id);
    $check('update_replay_works_with_old_expected_version',$call('update',$up)===$second&&$raw($id)===$saved);
    $third=$call('update',['proposal_id'=>$id,'locale'=>'en','expected_version'=>$second['version'],'request_id'=>$key(),'patch'=>['tagline'=>'Third']]);
    $check('old_create_replay_does_not_revert_new_head',$call('create',$input)===$first&&Changes\load_owned($id,'en')['payload']['version']===$third['version']);
    $check('old_update_replay_returns_committed_snapshot_without_reverting',$call('update',$up)===$second&&Changes\load_owned($id,'en')['payload']['version']===$third['version']);
    $restore=['proposal_id'=>$id,'locale'=>'en','expected_version'=>$third['version'],'revision_version'=>$first['version'],'request_id'=>$key()];
    $restored=$call('restore',$restore);$saved=$raw($id);
    $check('restore_replay_does_not_append_another_revision',$call('restore',$restore)===$restored&&$raw($id)===$saved);
    $restore['request_id']=$input['request_id'];
    $check('request_namespace_is_shared_across_write_operations',$error(Changes\execute($restore,'restore'),'request_reused',409));
    $users['owner']->remove_cap(Changes\CAPABILITY);$switch($users['owner']->ID);
    $check('replay_still_requires_current_permission',$error(Changes\execute($input,'create'),'forbidden',403));
    $users['owner']->add_cap(Changes\CAPABILITY);$switch($users['owner']->ID);
    update_post_meta($en,'project_tagline','External drift');
    $check('replay_does_not_bypass_source_drift',$error(Changes\execute($input,'create'),'source_changed',409));
    update_post_meta($en,'project_tagline','Original');
    $switch($users['other']->ID);$other=$call('create',$input);
    $check('same_request_key_is_isolated_between_owners',!is_wp_error($other)&&$other['proposal_id']!==$id);
    $check('foreign_owner_replay_cannot_read_original_proposal',$error(Changes\execute($up,'update'),'not_found',404));
    $switch($users['count']->ID);$firstCount=null;$countInput=null;
    for($i=0;$i<Changes\MAX_OWNER_PROPOSALS;$i++){
        $countInput=['project_id'=>$en,'locale'=>'en','request_id'=>$key(),'patch'=>['tagline'=>(string)$i]];
        $last=$call('create',$countInput);if(is_wp_error($last)){throw new RuntimeException('QUOTA_FILL_FAILED');}$firstCount=$firstCount??$last;
    }
    $u=Changes\owner_usage($users['count']->ID);
    $overflow=$input;$overflow['request_id']=$key();
    $check('proposal_limit_rejects_without_consuming_request',$error(Changes\execute($overflow,'create'),'owner_quota',429)&&Changes\owner_usage($users['count']->ID)===$u);
    $check('replay_at_full_proposal_quota_is_free',$call('create',$countInput)===$last&&Changes\owner_usage($users['count']->ID)===$u);
    $edit=$call('update',['proposal_id'=>$firstCount['proposal_id'],'locale'=>'en','expected_version'=>$firstCount['version'],'request_id'=>$key(),'patch'=>['tagline'=>'Still editable']]);
    $check('proposal_count_limit_does_not_prevent_existing_updates',!is_wp_error($edit));
    // Seed only synthetic ledger occupancy to exercise the exact admission boundary without 128 writes.
    $requests=$wpdb->prefix.'ac_change_requests';$switch($users['requests']->ID);
    $reqInput=$input;$reqInput['request_id']=$key();$reqFirst=$call('create',$reqInput);
    for($i=1;$i<Changes\MAX_OWNER_REQUESTS-1;$i++){$wpdb->query($wpdb->prepare("INSERT INTO $requests (owner_id,request_id,request_hash,proposal_id,response) VALUES (%d,%s,%s,%d,%s)",
        $users['requests']->ID,$key(),str_repeat('0',64),$reqFirst['proposal_id'],wp_json_encode($reqFirst)));}
    $lastAllowed=$call('update',['proposal_id'=>$reqFirst['proposal_id'],'locale'=>'en','expected_version'=>$reqFirst['version'],'request_id'=>$key(),'patch'=>['tagline'=>'Last allowed request']]);
    $check('last_request_slot_is_admitted',!is_wp_error($lastAllowed)&&Changes\owner_usage($users['requests']->ID)['requests']===Changes\MAX_OWNER_REQUESTS);
    $reqOverflow=['proposal_id'=>$reqFirst['proposal_id'],'locale'=>'en','expected_version'=>$lastAllowed['version'],'request_id'=>$key(),'patch'=>['tagline'=>'Too many']];$saved=$raw($reqFirst['proposal_id']);
    $check('request_limit_rejects_without_new_revision',$error(Changes\execute($reqOverflow,'update'),'owner_quota',429)&&$raw($reqFirst['proposal_id'])===$saved);
    $check('replay_at_full_request_quota_is_free',$call('create',$reqInput)===$reqFirst);
    $switch($users['bytes']->ID);$byteInput=$input;$byteInput['request_id']=$key();$byteFirst=$call('create',$byteInput);
    $u=Changes\owner_usage($users['bytes']->ID);$filler=$key();
    $wpdb->query($wpdb->prepare("INSERT INTO $requests (owner_id,request_id,request_hash,proposal_id,response) VALUES (%d,%s,%s,%d,%s)",
        $users['bytes']->ID,$filler,str_repeat('0',64),$byteFirst['proposal_id'],str_repeat('x',Changes\MAX_OWNER_BYTES-$u['bytes']-1)));
    $saved=$raw($byteFirst['proposal_id']);$usage=Changes\owner_usage($users['bytes']->ID);
    $check('byte_fixture_is_one_byte_below_limit',$usage['bytes']===Changes\MAX_OWNER_BYTES-1);
    $byteEdit=['proposal_id'=>$byteFirst['proposal_id'],'locale'=>'en','expected_version'=>$byteFirst['version'],'request_id'=>$key(),'patch'=>['tagline'=>'Exceeds aggregate budget']];
    $check('aggregate_byte_limit_rolls_back_head_history_and_receipt',$error(Changes\execute($byteEdit,'update'),'owner_quota',429)&&$raw($byteFirst['proposal_id'])===$saved&&Changes\owner_usage($users['bytes']->ID)===$usage);
    $check('byte_limit_still_allows_free_replay',$call('create',$byteInput)===$byteFirst);
    $switch($users['fail']->ID);$failInput=$input;$failInput['request_id']=$key();
    $usage=Changes\owner_usage($users['fail']->ID);
    $break=static function($q)use($requests){return str_starts_with($q,'INSERT INTO '.$requests.' ') ? 'INSERT INTO missing_receipt_failure (x) VALUES (1)' : $q;};
    $suppress=$wpdb->suppress_errors(true);add_filter('query',$break);
    try{$failed=Changes\execute($failInput,'create');}finally{remove_filter('query',$break);$wpdb->suppress_errors($suppress);}
    $check('receipt_failure_rolls_back_new_proposal',$error($failed,'storage_unavailable',503)&&Changes\owner_usage($users['fail']->ID)===$usage);
    $retried=$call('create',$failInput);
    $check('failed_request_can_retry_same_key_successfully',!is_wp_error($retried)&&Changes\owner_usage($users['fail']->ID)['proposals']===1);
    $usage=Changes\owner_usage($users['fail']->ID);$nextInput=$failInput;$nextInput['request_id']=$key();$attempted=null;
    $capture=static function($id,$post)use(&$attempted){if($post->post_type===Changes\TYPE){$attempted=$id;}};
    $metaFailure=static function($value,$id,$metaKey){return $metaKey===Changes\PAYLOAD?false:$value;};
    add_action('wp_insert_post',$capture,10,2);add_filter('add_post_metadata',$metaFailure,10,3);
    try{$failed=Changes\execute($nextInput,'create');}finally{remove_action('wp_insert_post',$capture,10);remove_filter('add_post_metadata',$metaFailure,10);}
    $check('initial_payload_failure_removes_uncommitted_post_and_cache',$error($failed,'create_failed',503)&&$attempted!==null&&get_post($attempted)===null&&Changes\owner_usage($users['fail']->ID)===$usage);
    $breakCommit=static function($q){return $q==='COMMIT'?'INVALID_COMMIT_TEST':$q;};
    $suppress=$wpdb->suppress_errors(true);add_filter('query',$breakCommit);
    try{$failed=Changes\execute($nextInput,'create');}finally{remove_filter('query',$breakCommit);$wpdb->suppress_errors($suppress);}
    $check('commit_failure_rolls_back_proposal_and_receipt',$error($failed,'storage_unavailable',503)&&Changes\owner_usage($users['fail']->ID)===$usage);
    $stateFailure=static function($q){return $q==='SELECT @@in_transaction'?'SELECT NULL':$q;};
    add_filter('query',$stateFailure);try{$failed=Changes\execute($nextInput,'create');}finally{remove_filter('query',$stateFailure);}
    $check('unknown_transaction_state_fails_closed',$error($failed,'storage_unavailable',503)&&Changes\owner_usage($users['fail']->ID)===$usage);
    $wpdb->query('START TRANSACTION');
    try{$nested=Changes\execute($input+[],'create');$active=(int)$wpdb->get_var('SELECT @@in_transaction');}finally{$wpdb->query('ROLLBACK');}
    $check('existing_transaction_is_rejected_without_ending_it',$error($nested,'transaction_busy',503)&&$active===1);
    $engine=static function($q){return str_contains($q,'information_schema.TABLES')?"SELECT 'MyISAM' AS ENGINE":$q;};
    add_filter('query',$engine);try{$unsafe=Changes\execute($failInput,'create');}finally{remove_filter('query',$engine);}
    $check('nontransactional_storage_is_rejected',$error($unsafe,'storage_unavailable',503));
    $check('all_quota_operations_preserve_sources',$sourceHash()===$before);
    $report['quotaBoundaries']=['proposals'=>Changes\MAX_OWNER_PROPOSALS,'requests'=>Changes\MAX_OWNER_REQUESTS,'bytes'=>Changes\MAX_OWNER_BYTES];
}catch(Throwable $e){$report['failure']=preg_match('/^(FAILED: [a-z0-9_]+|[A-Z_]+)$/',$e->getMessage())?$e->getMessage():'QUOTA_LAB_FAILED';$report['diagnostic']=['class'=>get_class($e),'line'=>$e->getLine()];}
finally{
    $wpdb->query('ROLLBACK');foreach($users as $u){$u->remove_cap(Changes\CAPABILITY);$u->remove_cap('alexcuadra_read_public_projects');}$switch(0);
    $clean=true;foreach($users as $u){$fresh=new WP_User($u->ID);$clean=$clean&&!$fresh->has_cap(Changes\CAPABILITY)&&!$fresh->has_cap('alexcuadra_read_public_projects');}
    if(!$clean){$report['failure']='QUOTA_SCOPE_CLEANUP_FAILED';}else{$cases[]='quota_synthetic_scopes_removed';}
}
$report['passed']=count($cases);$report['failed']=isset($report['failure'])?1:0;$report['cases']=$cases;
WP_CLI::line('EDITORIAL_CHANGES_QUOTAS_RESULT='.wp_json_encode($report));if($report['failed']){WP_CLI::halt(1);}
