<?php
require_once '/integration/support.php';
require_once '/experiment/operating-contract.php';
use AlexCuadra\EditorialSourceExperiment\OperatingContract as C;
use AlexCuadra\EditorialProposals as P;
$GLOBALS['operating_checks']=0;
function op_check($ok,$label){$GLOBALS['operating_checks']++;if(!$ok){throw new RuntimeException('OPERATING_CHECK: '.$label);}}
function op_fail(callable $fn,$code){try{$fn();}catch(RuntimeException $e){op_check($e->getMessage()===$code,$code.' got '.$e->getMessage());return;}throw new RuntimeException('Expected '.$code);}
function op_user($id){wp_set_current_user(0);wp_set_current_user($id);}
function op_actor($name){$id=wp_insert_user(['user_login'=>'op_'.$name,'user_pass'=>wp_generate_password(),'role'=>'subscriber','user_email'=>$name.'@example.invalid']);if(is_wp_error($id)){throw new RuntimeException('actor_failed');}$u=get_userdata($id);foreach(['alexcuadra_read_public_projects','alexcuadra_prepare_project_changes',C::APPLY] as $cap){$u->add_cap($cap);}return $id;}
function op_controller(){global $wpdb;return new C($wpdb,source_operation($wpdb),'snapshot','field_schema','cache_clear');}
function op_pair($name){$actor=get_current_user_id();op_user(1);$ids=[];foreach(['en','es'] as $lang){$id=wp_insert_post(['post_type'=>'project','post_status'=>'publish','post_title'=>'Operating '.$name.' '.$lang,'post_content'=>'Before '.$name.' '.$lang],true);pll_set_post_language($id,$lang);update_field('field_676dcb2aec8d4','Before '.$name.' '.$lang,$id);$ids[$lang]=$id;}pll_save_post_translations($ids);op_user($actor);return $ids;}
function op_proposal($id,$patch){$input=['project_id'=>$id,'locale'=>'en','patch'=>$patch,'request_id'=>bin2hex(random_bytes(16))];$r=P\execute($input,'create');if(is_wp_error($r)){throw new RuntimeException('proposal_'.$r->get_error_code());}return [$r,$input];}
function op_prepare_input($p){return ['proposal_id'=>$p['proposal_id'],'locale'=>$p['locale'],'expected_proposal_version'=>$p['version'],'expected_base_version'=>$p['base_version'],'request_id'=>bin2hex(random_bytes(16))];}
function op_apply_input($r){return ['review_id'=>$r['review_id'],'locale'=>'en','expected_review_hash'=>$r['review_hash'],'request_id'=>bin2hex(random_bytes(16))];}
function op_approve($c,$review){$actor=get_current_user_id();op_user(1);try{return $c->approve($review['review_id'],'en',$review['review_hash']);}finally{op_user($actor);}}
function op_rows($id){global $wpdb;return [$wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d",$id),ARRAY_A),$wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id=%d ORDER BY meta_id",$id),ARRAY_A),$wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->term_relationships WHERE object_id=%d ORDER BY term_taxonomy_id",$id),ARRAY_A)];}
function op_request_rows($owner){global $wpdb;return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ac_proposal_requests WHERE owner_id=%d ORDER BY request_id",$owner),ARRAY_A);}
global $wpdb;
$roleBefore=get_role('subscriber')->capabilities;$operator=get_user_by('login','effects_admin')->ID;op_check($operator===1,'known independent synthetic operator');
op_user($operator);$c=op_controller();
$mi=AlexCuadra\EditorialSourceExperiment\operating_storage_marker($wpdb,'ready');
$migration=$c->install();op_check($migration===$mi,'joint migration remains ready');op_check($c->install()===$mi,'ready migration is idempotent');

$executor=op_actor('executor');$other=op_actor('other');op_user($executor);$ids=op_pair('main');
$unicode='<p>'.str_repeat('é',25000).'</p>';[$p,$create]=op_proposal($ids['en'],['body_html'=>$unicode,'tagline'=>'Approved operating tagline']);$prepare=op_prepare_input($p);
$legacyBefore=op_request_rows($executor);$privateBefore=op_rows($p['proposal_id']);$esBefore=op_rows($ids['es']);
wp_get_current_user()->remove_cap(C::APPLY);op_user($executor);op_fail(fn()=>$c->prepare($prepare),'contract_forbidden');wp_get_current_user()->add_cap(C::APPLY);op_user($executor);
op_user($operator);op_fail(fn()=>$c->prepare($prepare),'contract_forbidden');op_user($executor);
$unknown=$prepare;$unknown['post_status']='draft';op_fail(fn()=>$c->prepare($unknown),'contract_invalid_input');
$sourceBefore=op_rows($ids['en']);$r=$c->prepare($prepare);op_check(op_rows($ids['en'])===$sourceBefore,'review preparation never writes source');
op_check(!isset($r['before'])&&!isset($r['after']),'prepare receipt metadata only');op_check($c->prepare($prepare)===$r,'prepare exact replay');
$changed=$prepare;$changed['locale']='es';op_fail(fn()=>$c->prepare($changed),'contract_request_reused');
$apply=op_apply_input($r);op_fail(fn()=>$c->apply($apply),'contract_approval_required');op_check(op_rows($ids['en'])===$sourceBefore,'no approval no source write');
op_user($operator);op_fail(fn()=>$c->approve($r['review_id'],'en',$r['review_hash']),'contract_operator_required');wp_get_current_user()->add_cap(C::APPROVE);op_user($operator);
$inspection=$c->inspect($r['review_id'],'en');op_check($inspection['review_hash']===$r['review_hash']&&$inspection['document']['after']['body_html']===$unicode,'independent operator inspects exact frozen diff');
op_fail(fn()=>$c->approve($r['review_id'],'en',str_repeat('0',64)),'contract_review_changed');
$approval=op_approve($c,$r);op_check(get_current_user_id()===$operator&&$approval['actor_id']===$operator,'approval restores operator context');
op_user($executor);wp_get_current_user()->add_cap('manage_options');wp_get_current_user()->add_cap(C::APPROVE);op_user($executor);
op_fail(fn()=>$c->approve($r['review_id'],'en',$r['review_hash']),'contract_independent_approval_required');wp_get_current_user()->remove_cap('manage_options');wp_get_current_user()->remove_cap(C::APPROVE);op_user($executor);
$wrong=$apply;$wrong['locale']='es';op_fail(fn()=>$c->apply($wrong),'contract_not_found');op_user($other);op_fail(fn()=>$c->apply($apply),'contract_not_found');op_user($executor);
$app=$c->apply($apply);op_check($app['format']==='operating-application-receipt-v1'&&$app['content_state']==='applied'&&$app['effect_state_at_commit']==='pending','versioned application receipt');
op_check($wpdb->get_var($wpdb->prepare("SELECT consumed_event FROM {$wpdb->prefix}ac_exp_operating_approvals WHERE review_id=%d",$r['review_id']))===$app['event_id'],'approval consumed by this exact committed event');
op_check(get_post($ids['en'])->post_content===$unicode&&get_post_meta($ids['en'],'project_tagline',true)==='Approved operating tagline','approved Unicode body and ACF applied');
op_check(op_rows($ids['es'])===$esBefore,'application preserves linked ES rows');op_check(op_request_rows($executor)===$legacyBefore&&op_rows($p['proposal_id'])===$privateBefore,'rc2 receipts and proposal bytes unchanged');
$usage=$c->usage($executor);op_check($usage['reviews']===2&&$usage['requests']===4&&$usage['bytes']>=C::RECOVERY_BYTES,'recovery review requests and bytes reserved before apply');
op_check($c->effectStatus($p['proposal_id'],'en')['effect_state']==='pending','mutable effect status separate');$wpdb->query('START TRANSACTION');try{op_fail(fn()=>$c->prepare($prepare),'contract_outer_transaction');op_fail(fn()=>$c->apply($apply),'contract_outer_transaction');}finally{$wpdb->query('ROLLBACK');}
$sourceAfter=op_rows($ids['en']);op_check($c->apply($apply)===$app&&op_rows($ids['en'])===$sourceAfter,'apply replay never rewrites source');
op_user($operator);$j=source_operation($wpdb);$j->consume($app['event_id'],function($id){do_action('litespeed_purge_post',$id);return 'requested';});op_user($executor);
op_check($c->effectStatus($p['proposal_id'],'en')['effect_state']==='requested'&&$c->apply($apply)===$app&&$app['effect_state_at_commit']==='pending','effect changes never rewrite frozen receipt');
wp_get_current_user()->remove_cap(C::APPLY);op_user($executor);op_fail(fn()=>$c->apply($apply),'contract_forbidden');wp_get_current_user()->add_cap(C::APPLY);op_user($executor);
op_check(!current_user_can('edit_posts')&&!current_user_can('manage_options')&&!current_user_can('edit_post',$ids['en']),'subscriber needs no global source edit or admin');
// Recovery uses a different scope and a fresh independent approval of the inverse.
op_user($executor);$recoverPrepare=['proposal_id'=>$p['proposal_id'],'locale'=>'en','expected_source_version'=>$app['source_version'],'request_id'=>bin2hex(random_bytes(16))];
op_fail(fn()=>$c->prepareRecovery($recoverPrepare),'contract_forbidden');wp_get_current_user()->add_cap(C::RECOVER);op_user($executor);
[$capacityProposal]=op_proposal($ids['en'],['tagline'=>'Capacity-only review']);for($i=0;$i<6;$i++){$c->prepare(op_prepare_input($capacityProposal));}
$recoveryLegacy=op_request_rows($executor);$recoveryPrivate=op_rows($p['proposal_id']);$usageReserved=$c->usage($executor);$rr=$c->prepareRecovery($recoverPrepare);$ri=op_apply_input($rr);
op_check($c->prepareRecovery($recoverPrepare)===$rr,'recovery preparation exact replay');
$inspection=$c->inspect($rr['review_id'],'en');op_check($inspection['document']['operation']==='recover'&&$inspection['document']['after']['body_html']===$sourceBefore[0]['post_content']&&$inspection['document']['application_event']===$app['event_id'],'inverse review bound to original application');
op_fail(fn()=>$c->recover($ri),'contract_approval_required');op_fail(fn()=>$c->apply($ri),'contract_operation_mismatch');op_fail(fn()=>$c->recover($apply),'contract_operation_mismatch');
op_user($other);wp_get_current_user()->add_cap(C::RECOVER);op_user($other);op_fail(fn()=>$c->recover($ri),'contract_not_found');op_user($executor);
$wrong=$ri;$wrong['locale']='es';op_fail(fn()=>$c->recover($wrong),'contract_not_found');op_approve($c,$rr);
// Disconnect during the inverse must retain the original application and approval.
$recoveryBeforeLost=op_rows($ids['en']);$recoveryEsLost=op_rows($ids['es']);$recoverySession=(int)$wpdb->get_var('SELECT CONNECTION_ID()');
$killRecovery=static function($id)use($ids,$recoverySession){if((int)$id===$ids['en']){$killer=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);mysqli_query((new ReflectionProperty(wpdb::class,'dbh'))->getValue($killer),'KILL CONNECTION '.$recoverySession);}};
add_action('save_post_project',$killRecovery,53);try{op_fail(fn()=>$c->recover($ri),'source_transaction_lost');}finally{remove_action('save_post_project',$killRecovery,53);}
op_check(op_rows($ids['en'])===$recoveryBeforeLost&&op_rows($ids['es'])===$recoveryEsLost,'recovery disconnect rolls back both linked sources');
op_check($wpdb->get_var($wpdb->prepare("SELECT consumed_event FROM {$wpdb->prefix}ac_exp_operating_approvals WHERE review_id=%d",$rr['review_id']))===null&&$wpdb->get_var($wpdb->prepare("SELECT recovery_event FROM {$wpdb->prefix}ac_exp_operating_apps WHERE proposal_id=%d",$p['proposal_id']))===null,'recovery disconnect preserves unused approval and applied registry');
$recovered=$c->recover($ri);op_check($recovered['content_state']==='recovered'&&$recovered['application_event']===$app['event_id'],'new immutable recovery receipt');
op_check(get_post($ids['en'])->post_content===$sourceBefore[0]['post_content']&&get_post_meta($ids['en'],'project_tagline',true)==='Before main en','original fields restored through native APIs');
op_check(op_rows($ids['es'])===$esBefore&&op_request_rows($executor)===$recoveryLegacy&&op_rows($p['proposal_id'])===$recoveryPrivate,'recovery preserves linked ES and literal rc2 artifacts');
$recoveryRows=op_rows($ids['en']);op_check($c->recover($ri)===$recovered&&op_rows($ids['en'])===$recoveryRows&&$c->apply($apply)===$app,'both immutable receipts replay without source writes');
op_check($wpdb->get_var($wpdb->prepare("SELECT consumed_event FROM {$wpdb->prefix}ac_exp_operating_approvals WHERE review_id=%d",$rr['review_id']))===$recovered['event_id'],'fresh recovery approval consumed by recovery event');
op_check($c->effectStatus($p['proposal_id'],'en')['event_id']===$recovered['event_id']&&$c->effectStatus($p['proposal_id'],'en')['effect_state']==='pending','effect status follows latest committed recovery');
op_check($c->usage($executor)['reviews']===$usageReserved['reviews']&&$c->usage($executor)['requests']===$usageReserved['requests']&&$c->usage($executor)['bytes']<$usageReserved['bytes'],'reserved capacity converted into retained recovery artifacts');
op_fail(fn()=>$c->recover(op_apply_input($rr)),'contract_application_changed');wp_get_current_user()->remove_cap(C::RECOVER);op_user($executor);op_fail(fn()=>$c->recover($ri),'contract_forbidden');wp_get_current_user()->add_cap(C::RECOVER);op_user($executor);
// Independent executor keeps the following drift fixtures within their own budget.
$executor=op_actor('drift-executor');op_user($executor);
// A changed proposal invalidates an already approved review.
$pair=op_pair('proposal-drift');[$p2]=op_proposal($pair['en'],['tagline'=>'First proposed value']);$r2=$c->prepare(op_prepare_input($p2));op_approve($c,$r2);
$updated=P\execute(['proposal_id'=>$p2['proposal_id'],'locale'=>'en','expected_version'=>$p2['version'],'patch'=>['tagline'=>'Different proposal'],'request_id'=>bin2hex(random_bytes(16))],'update');op_check(!is_wp_error($updated),'rc2 proposal can still change independently');
op_fail(fn()=>$c->apply(op_apply_input($r2)),'contract_proposal_changed');
// A linked editor's change invalidates the frozen source version.
$pair=op_pair('source-drift');[$p3]=op_proposal($pair['en'],['tagline'=>'Proposed value']);$r3=$c->prepare(op_prepare_input($p3));op_approve($c,$r3);update_post_meta($pair['es'],'other_editor','preserve this change');$unchanged=op_rows($pair['en']);
op_fail(fn()=>$c->apply(op_apply_input($r3)),'contract_source_changed');op_check(op_rows($pair['en'])===$unchanged&&get_post_meta($pair['es'],'other_editor',true)==='preserve this change','source conflict preserves both editor states');
// Expiry uses DB time; a session clock fixture avoids modifying signed documents.
$pair=op_pair('expiry');[$p4]=op_proposal($pair['en'],['tagline'=>'Expired']);$r4=$c->prepare(op_prepare_input($p4));op_approve($c,$r4);$wpdb->query('SET timestamp=UNIX_TIMESTAMP()+601');
try{op_fail(fn()=>$c->apply(op_apply_input($r4)),'contract_review_expired');}finally{$wpdb->query('SET timestamp=0');}
// Failure after the callback consumed approval and saved its receipt rolls back all.
$pair=op_pair('rollback');[$p5]=op_proposal($pair['en'],['body_html'=>'<p>Must rollback</p>']);$r5=$c->prepare(op_prepare_input($p5));op_approve($c,$r5);$input5=op_apply_input($r5);$event5=hash('sha256','operating|'.$executor.'|'.$input5['request_id']);
$beforeRollback=op_rows($pair['en']);$observer=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$observer->set_prefix($wpdb->prefix);$effects=$wpdb->prefix.'ac_exp_source_effects';
$inject=static function($id)use($pair,$observer,$effects,$event5){if((int)$id===$pair['en']){$observer->query($observer->prepare("INSERT INTO $effects (event_id,project_id,state,available_at) VALUES (%s,%d,'pending',UNIX_TIMESTAMP())",$event5,$pair['en']));}};
add_action('save_post_project',$inject,51);$wpdb->suppress_errors(true);
try{op_fail(fn()=>$c->apply($input5),'experiment_sql_failed');}finally{remove_action('save_post_project',$inject,51);$wpdb->suppress_errors(false);}
op_check(op_rows($pair['en'])===$beforeRollback,'late intent failure rolls source back');
$approvalTable=$wpdb->prefix.'ac_exp_operating_approvals';$appsTable=$wpdb->prefix.'ac_exp_operating_apps';$requestsTable=$wpdb->prefix.'ac_exp_operating_requests';
op_check($wpdb->get_var($wpdb->prepare("SELECT consumed_event FROM $approvalTable WHERE review_id=%d",$r5['review_id']))===null,'late failure restores approval unused');
op_check((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $appsTable WHERE proposal_id=%d",$p5['proposal_id']))===0&&(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $requestsTable WHERE owner_id=%d AND request_id=%s",$executor,$input5['request_id']))===0,'late failure removes application reservation and receipt');
op_check(source_operation($observer)->consume($event5,fn()=>'requested')==='not_claimed','orphan effect cannot execute without committed event');
// A no-op keeps the source base valid: only consumption can block approval reuse.
$pair=op_pair('no-op');$same=get_post_meta($pair['en'],'project_tagline',true);[$noop]=op_proposal($pair['en'],['tagline'=>$same]);$nr=$c->prepare(op_prepare_input($noop));op_approve($c,$nr);$noChange=op_rows($pair['en']);
$c->apply(op_apply_input($nr));op_check(op_rows($pair['en'])===$noChange,'approved no-op preserves source rows');op_fail(fn()=>$c->apply(op_apply_input($nr)),'contract_approval_consumed');
// Expiration during native save cannot consume approval or commit the source.
$deadlineOwner=op_actor('deadline');op_user($deadlineOwner);$pair=op_pair('deadline');[$dp]=op_proposal($pair['en'],['body_html'=>'<p>Deadline rollback</p>']);$dr=$c->prepare(op_prepare_input($dp));op_approve($c,$dr);$beforeDeadline=op_rows($pair['en']);
$advanceClock=static function($id)use($pair,$wpdb){if((int)$id===$pair['en']){$wpdb->query('SET timestamp=UNIX_TIMESTAMP()+601');}};add_action('save_post_project',$advanceClock,52);
try{op_fail(fn()=>$c->apply(op_apply_input($dr)),'contract_review_expired');}finally{remove_action('save_post_project',$advanceClock,52);$wpdb->query('SET timestamp=0');}
op_check(op_rows($pair['en'])===$beforeDeadline,'expiry during write rolls source back');op_check($wpdb->get_var($wpdb->prepare("SELECT consumed_event FROM $approvalTable WHERE review_id=%d",$dr['review_id']))===null,'expiry during write keeps approval unconsumed');
// Separate owners make each quota boundary independently observable.
$quotaOwner=op_actor('quota-reviews');op_user($quotaOwner);$pair=op_pair('quota-reviews');[$qp]=op_proposal($pair['en'],['tagline'=>'Quota']);$reviews=[];
for($i=0;$i<C::MAX_REVIEWS;$i++){$reviews[]=$c->prepare(op_prepare_input($qp));}
op_fail(fn()=>$c->prepare(op_prepare_input($qp)),'contract_owner_quota');op_approve($c,$reviews[0]);$beforeQuota=op_rows($pair['en']);op_fail(fn()=>$c->apply(op_apply_input($reviews[0])),'contract_owner_quota');op_check(op_rows($pair['en'])===$beforeQuota,'approval cannot bypass required recovery capacity');
$requestOwner=op_actor('quota-requests');op_user($requestOwner);$pair=op_pair('quota-requests');[$qp]=op_proposal($pair['en'],['tagline'=>'Quota']);
for($i=0;$i<C::MAX_REQUESTS;$i++){$wpdb->query($wpdb->prepare("INSERT INTO $requestsTable VALUES (%d,%s,%s,'{}')",$requestOwner,str_pad(dechex($i),32,'0',STR_PAD_LEFT),str_repeat('a',64)));}
op_fail(fn()=>$c->prepare(op_prepare_input($qp)),'contract_owner_quota');[$extra]=op_proposal($pair['en'],['tagline'=>'Independent rc2 budget']);op_check($extra['proposal_id']>0&&P\owner_usage($requestOwner)['requests']===2,'rc2 budget remains independent and unchanged');
$bytesOwner=op_actor('quota-bytes');op_user($bytesOwner);$pair=op_pair('quota-bytes');[$qp]=op_proposal($pair['en'],['tagline'=>'Bytes']);[$reserved]=op_proposal($pair['en'],['tagline'=>'Reserved']);
$wpdb->query($wpdb->prepare("INSERT INTO $appsTable (proposal_id,owner_id,review_id,event_id,source_after,reserved_bytes,reserved_reviews,reserved_requests) VALUES (%d,%d,1,%s,%s,%d,0,0)",$reserved['proposal_id'],$bytesOwner,str_repeat('b',64),str_repeat('c',64),C::MAX_BYTES));op_fail(fn()=>$c->prepare(op_prepare_input($qp)),'contract_owner_quota');
// Real server-side disconnect after body save: no reconnect/replayed ACF write.
$lostOwner=op_actor('lost-session');op_user($lostOwner);wp_get_current_user()->add_cap(C::RECOVER);op_user($lostOwner);$pair=op_pair('lost-session');
[$lp]=op_proposal($pair['en'],['body_html'=>'<p>Must never partially commit</p>','tagline'=>'Must stay before']);$lr=$c->prepare(op_prepare_input($lp));op_approve($c,$lr);$li=op_apply_input($lr);$lostBefore=op_rows($pair['en']);$lostEs=op_rows($pair['es']);$session=(int)$wpdb->get_var('SELECT CONNECTION_ID()');$reporting=(new mysqli_driver)->report_mode;
$kill=static function($id)use($pair,$session){if((int)$id===$pair['en']){$killer=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);mysqli_query((new ReflectionProperty(wpdb::class,'dbh'))->getValue($killer),'KILL CONNECTION '.$session);}};
add_action('save_post_project',$kill,53);try{op_fail(fn()=>$c->apply($li),'source_transaction_lost');}finally{remove_action('save_post_project',$kill,53);}
op_check((new mysqli_driver)->report_mode===$reporting,'mysqli reporting restored after connection loss');
op_check(op_rows($pair['en'])===$lostBefore&&op_rows($pair['es'])===$lostEs,'physical session loss rolls back body and prevents ACF autocommit');
op_check($wpdb->get_var($wpdb->prepare("SELECT consumed_event FROM $approvalTable WHERE review_id=%d",$lr['review_id']))===null&&(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $appsTable WHERE proposal_id=%d",$lp['proposal_id']))===0,'disconnect leaves approval unused and reservation absent');
$lostEvent=hash('sha256','operating|'.$lostOwner.'|'.$li['request_id']);op_check((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ac_exp_source_events WHERE event_id=%s",$lostEvent))===0&&(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $requestsTable WHERE owner_id=%d AND request_id=%s",$lostOwner,$li['request_id']))===0,'disconnect leaves no event receipt or request receipt');
$la=$c->apply($li);op_check($c->apply($li)===$la&&get_post_meta($pair['en'],'project_tagline',true)==='Must stay before','same request safely retried once on a fresh session');
// Recovery conflict cannot overwrite another editor, including a linked-language edit.
$rpi=['proposal_id'=>$lp['proposal_id'],'locale'=>'en','expected_source_version'=>$la['source_version'],'request_id'=>bin2hex(random_bytes(16))];$lrr=$c->prepareRecovery($rpi);op_approve($c,$lrr);update_post_meta($pair['es'],'another_editor','retain');$conflictRows=op_rows($pair['en']);
op_fail(fn()=>$c->recover(op_apply_input($lrr)),'contract_source_changed');op_check(op_rows($pair['en'])===$conflictRows&&get_post_meta($pair['es'],'another_editor',true)==='retain','recovery never forces over a linked editor change');
// An expired recovery review stays immutable; renewal needs another retained slot.
$renewOwner=op_actor('recovery-renewal');op_user($renewOwner);wp_get_current_user()->add_cap(C::RECOVER);op_user($renewOwner);$renewPair=op_pair('recovery-renewal');
[$np]=op_proposal($renewPair['en'],['tagline'=>'Renewal applied']);$npr=$c->prepare(op_prepare_input($np));op_approve($c,$npr);$na=$c->apply(op_apply_input($npr));
$npi=['proposal_id'=>$np['proposal_id'],'locale'=>'en','expected_source_version'=>$na['source_version'],'request_id'=>bin2hex(random_bytes(16))];$nrr=$c->prepareRecovery($npi);op_approve($c,$nrr);$retained=$c->inspect($nrr['review_id'],'en');$beforeRenew=$c->usage($renewOwner);
$wpdb->query('SET timestamp=UNIX_TIMESTAMP()+601');
try{
    op_fail(fn()=>$c->recover(op_apply_input($nrr)),'contract_review_expired');
    $npi['request_id']=bin2hex(random_bytes(16));$fresh=$c->prepareRecovery($npi);
    op_check($fresh['review_id']!==$nrr['review_id']&&$c->inspect($nrr['review_id'],'en')===$retained,'renewal retains original sealed expired review');
    op_check($c->usage($renewOwner)['reviews']===$beforeRenew['reviews']+1&&$c->usage($renewOwner)['requests']===$beforeRenew['requests']+1,'renewal charged without freeing expired artifacts');
    op_approve($c,$fresh);op_user(1);op_fail(fn()=>$c->approve($nrr['review_id'],'en',$nrr['review_hash']),'contract_application_changed');op_user($renewOwner);
    wp_get_current_user()->remove_cap(C::APPLY);op_user($renewOwner);
    $restored=$c->recover(op_apply_input($fresh));op_check($restored['content_state']==='recovered'&&get_post_meta($renewPair['en'],'project_tagline',true)==='Before recovery-renewal en','renewed inverse works with recovery scope alone');
    op_check($c->effectStatus($np['proposal_id'],'en')['event_id']===$restored['event_id'],'recovery-only scope can read its effect status');
}finally{op_user($renewOwner);$wpdb->query('SET timestamp=0');}
// Lose the socket while core _do_query is running, after its final query filter.
$midSession=(int)$wpdb->get_var('SELECT CONNECTION_ID()');$midBefore=op_rows($renewPair['en']);
$timer=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$timerHandle=(new ReflectionProperty(wpdb::class,'dbh'))->getValue($timer);
$routine='ac_exp_disconnect_during_query';
if($timer->query("CREATE PROCEDURE $routine() BEGIN DO SLEEP(0.5); KILL CONNECTION $midSession; END")===false){throw new RuntimeException('disconnect_fixture_routine_failed');}
try{
    op_fail(function()use($wpdb,$timerHandle,$routine,$renewPair){AlexCuadra\EditorialSourceExperiment\owned_transaction($wpdb,function()use($wpdb,$timerHandle,$routine,$renewPair){
        update_post_meta($renewPair['en'],'mid_query_write','must rollback');
        mysqli_report(MYSQLI_REPORT_OFF);
        if(!mysqli_query($timerHandle,"CALL $routine()",MYSQLI_ASYNC)){throw new RuntimeException('disconnect_fixture_schedule_failed');}
        $wpdb->query('SELECT SLEEP(5)');
    });},'source_transaction_lost');
    op_check(op_rows($renewPair['en'])===$midBefore,'socket loss inside core query never reaches automatic reconnect replay');
}finally{
    // Drain the asynchronous fixture before its local-only routine cleanup.
    $read=[$timerHandle];$errors=[$timerHandle];$reject=[];mysqli_poll($read,$errors,$reject,3);
    $result=mysqli_reap_async_query($timerHandle);if($result instanceof mysqli_result){mysqli_free_result($result);}
    while(mysqli_more_results($timerHandle)){mysqli_next_result($timerHandle);$result=mysqli_store_result($timerHandle);if($result instanceof mysqli_result){mysqli_free_result($result);}}
    $timer->query("DROP PROCEDURE $routine");
}
// The final COMMIT boundary is protected even with no following wpdb query.
$endSession=(int)$wpdb->get_var('SELECT CONNECTION_ID()');$endBefore=op_rows($renewPair['en']);$endMode=(new mysqli_driver)->report_mode;
op_fail(function()use($wpdb,$timerHandle,$endSession,$renewPair){AlexCuadra\EditorialSourceExperiment\owned_transaction($wpdb,function()use($timerHandle,$endSession,$renewPair){
    update_post_meta($renewPair['en'],'end_of_callback','must rollback');mysqli_report(MYSQLI_REPORT_OFF);mysqli_query($timerHandle,'KILL CONNECTION '.$endSession);return ['must_not_commit'=>true];
});},'source_transaction_lost');
op_check((new mysqli_driver)->report_mode===$endMode,'driver mode restored after callback-end disconnect');
op_check(op_rows($renewPair['en'])===$endBefore,'callback-end disconnect cannot report a successful empty commit');
// Native callbacks cannot commit the coordinator's transaction themselves.
$controlBefore=op_rows($pair['en']);$reportBefore=(new mysqli_driver)->report_mode;$queryCallbacks=$GLOBALS['wp_filter']['query']->callbacks??[];
op_fail(function()use($wpdb,$pair){AlexCuadra\EditorialSourceExperiment\owned_transaction($wpdb,function()use($wpdb,$pair){update_post_meta($pair['en'],'forbidden_commit','must rollback');$wpdb->query('SET @@autocommit=1');});},'source_transaction_control_denied');
op_check(op_rows($pair['en'])===$controlBefore,'intercepted callback commit cannot leak a write');
op_check((new mysqli_driver)->report_mode===$reportBefore&&($GLOBALS['wp_filter']['query']->callbacks??[])===$queryCallbacks,'transaction driver mode restored after rejected control');
// Storage drift is rejected before exposing a review or starting a source write.
op_user(1);$rt=$wpdb->prefix.'ac_exp_operating_reviews';
$wpdb->query("ALTER TABLE $rt ADD unexpected INT NULL");try{op_fail(fn()=>$c->inspect($rr['review_id'],'en'),'contract_schema_changed');}finally{$wpdb->query("ALTER TABLE $rt DROP unexpected");}
$wpdb->query("ALTER TABLE $rt ADD KEY unexpected(owner_id,proposal_id)");try{op_fail(fn()=>$c->inspect($rr['review_id'],'en'),'contract_schema_changed');}finally{$wpdb->query("ALTER TABLE $rt DROP KEY unexpected");}
$wpdb->query("ALTER TABLE $rt ADD CONSTRAINT unexpected_check CHECK(owner_id>0)");try{op_fail(fn()=>$c->inspect($rr['review_id'],'en'),'contract_schema_changed');}finally{$wpdb->query("ALTER TABLE $rt DROP CONSTRAINT unexpected_check");}
$wpdb->query("ALTER TABLE $rt MODIFY document LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");try{op_fail(fn()=>$c->inspect($rr['review_id'],'en'),'contract_schema_changed');}finally{$wpdb->query("ALTER TABLE $rt MODIFY document LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL");}
$stored=$wpdb->get_row($wpdb->prepare("SELECT document,review_hash FROM $rt WHERE review_id=%d",$rr['review_id']),ARRAY_A);
foreach(['unknown','wrong_type','wrong_fields','wrong_expiry'] as $case){
    $bad=json_decode($stored['document'],true);
    if($case==='unknown'){$bad['force']=true;}elseif($case==='wrong_type'){$bad['owner_id']=(string)$bad['owner_id'];}elseif($case==='wrong_fields'){unset($bad['before']['body_html']);}else{$bad['expires_at']++;}
    $encoded=wp_json_encode($bad);$wpdb->query($wpdb->prepare("UPDATE $rt SET document=%s,review_hash=%s WHERE review_id=%d",$encoded,hash('sha256',$encoded),$rr['review_id']));
    try{op_fail(fn()=>$c->inspect($rr['review_id'],'en'),'contract_review_corrupt');}finally{$wpdb->query($wpdb->prepare("UPDATE $rt SET document=%s,review_hash=%s WHERE review_id=%d",$stored['document'],$stored['review_hash'],$rr['review_id']));}
}
// Corrupt persisted authority/receipts/registry fail before source writes.
$decodeOwner=op_actor('decode-authority');op_user($decodeOwner);$decodePair=op_pair('decode-authority');[$decodeP]=op_proposal($decodePair['en'],['tagline'=>'Decoder approved']);$decodeInput=op_prepare_input($decodeP);$decodeR=$c->prepare($decodeInput);op_approve($c,$decodeR);$decodeApply=op_apply_input($decodeR);$decodeBefore=op_rows($decodePair['en']);
$at=$wpdb->prefix.'ac_exp_operating_approvals';$qt=$wpdb->prefix.'ac_exp_operating_requests';$apt=$wpdb->prefix.'ac_exp_operating_apps';
$approvalRow=$wpdb->get_row($wpdb->prepare("SELECT * FROM $at WHERE review_id=%d",$decodeR['review_id']),ARRAY_A);
foreach(['extra','review_id','actor_type'] as $case){$bad=json_decode($approvalRow['document'],true);if($case==='extra'){$bad['force']=true;}elseif($case==='review_id'){$bad['review_id']++;}else{$bad['actor_id']=(string)$bad['actor_id'];}
 $wpdb->query($wpdb->prepare("UPDATE $at SET document=%s WHERE review_id=%d",wp_json_encode($bad),$decodeR['review_id']));try{op_fail(fn()=>$c->apply($decodeApply),'contract_approval_invalid');}finally{$wpdb->query($wpdb->prepare("UPDATE $at SET document=%s WHERE review_id=%d",$approvalRow['document'],$decodeR['review_id']));}}
op_check(op_rows($decodePair['en'])===$decodeBefore,'malformed approvals cannot write source');
$receipt=$wpdb->get_var($wpdb->prepare("SELECT response FROM $qt WHERE owner_id=%d AND request_id=%s",$decodeOwner,$decodeInput['request_id']));
foreach(['extra','locale','id_type'] as $case){$bad=json_decode($receipt,true);if($case==='extra'){$bad['force']=true;}elseif($case==='locale'){$bad['locale']='es';}else{$bad['review_id']=(string)$bad['review_id'];}
 $wpdb->query($wpdb->prepare("UPDATE $qt SET response=%s WHERE owner_id=%d AND request_id=%s",wp_json_encode($bad),$decodeOwner,$decodeInput['request_id']));try{op_fail(fn()=>$c->prepare($decodeInput),'contract_receipt_corrupt');}finally{$wpdb->query($wpdb->prepare("UPDATE $qt SET response=%s WHERE owner_id=%d AND request_id=%s",$receipt,$decodeOwner,$decodeInput['request_id']));}}
$decodeApp=$c->apply($decodeApply);$wpdb->query($wpdb->prepare("UPDATE $apt SET reserved_requests=0 WHERE proposal_id=%d",$decodeP['proposal_id']));try{op_fail(fn()=>$c->effectStatus($decodeP['proposal_id'],'en'),'contract_application_corrupt');}finally{$wpdb->query($wpdb->prepare("UPDATE $apt SET reserved_requests=2 WHERE proposal_id=%d",$decodeP['proposal_id']));}
op_check($c->apply($decodeApply)===$decodeApp,'validated application receipt still replays identically');
// Committed journal/effect rows must be valid before cache callbacks run.
$eventTable=$wpdb->prefix.'ac_exp_source_events';$effectTable=$wpdb->prefix.'ac_exp_source_effects';$jid=$decodeApp['event_id'];$journal=source_operation($wpdb);$purges=0;
$originalEffect=$wpdb->get_row($wpdb->prepare("SELECT * FROM $effectTable WHERE event_id=%s",$jid),ARRAY_A);
foreach(['state','lease','attempts'] as $case){$sql=$case==='state'?"state='unknown'":($case==='lease'?"lease_token='bad'":"attempts=9");$wpdb->query($wpdb->prepare("UPDATE $effectTable SET $sql WHERE event_id=%s",$jid));
 try{op_fail(function()use($journal,$jid,&$purges){$journal->consume($jid,function()use(&$purges){$purges++;return 'requested';});},'source_effect_corrupt');}finally{$wpdb->update($effectTable,$originalEffect,['event_id'=>$jid]);}}
op_check($purges===0,'malformed effects never call cache API');
$originalEvent=$wpdb->get_row($wpdb->prepare("SELECT * FROM $eventTable WHERE event_id=%s",$jid),ARRAY_A);
$wpdb->query($wpdb->prepare("UPDATE $eventTable SET receipt='null' WHERE event_id=%s",$jid));try{op_fail(fn()=>$journal->consume($jid,fn()=>'requested'),'source_event_corrupt');}finally{$wpdb->update($eventTable,$originalEvent,['event_id'=>$jid]);}
$wpdb->query($wpdb->prepare("UPDATE $effectTable SET project_id=project_id+1 WHERE event_id=%s",$jid));try{op_fail(fn()=>$journal->consume($jid,fn()=>'requested'),'source_event_relation_changed');op_fail(fn()=>$c->effectStatus($decodeP['proposal_id'],'en'),'contract_journal_relation_changed');}finally{$wpdb->update($effectTable,$originalEffect,['event_id'=>$jid]);}
$badReceipt=json_decode($originalEvent['receipt'],true);$badReceipt['review_id']++;$wpdb->query($wpdb->prepare("UPDATE $eventTable SET receipt=%s WHERE event_id=%s",wp_json_encode($badReceipt),$jid));try{op_fail(fn()=>$c->effectStatus($decodeP['proposal_id'],'en'),'contract_journal_relation_changed');}finally{$wpdb->update($eventTable,$originalEvent,['event_id'=>$jid]);}
op_check($c->effectStatus($decodeP['proposal_id'],'en')['event_id']===$jid,'valid linked journal remains readable');
// Whole-owner audit reconstructs inputs instead of trusting stored hashes.
op_fail(fn()=>$c->auditOwner($decodeOwner),'contract_operator_required');op_user(1);$auditBefore=op_rows($decodePair['en']);$audit=$c->auditOwner($decodeOwner);op_check($audit['reviews']===1&&$audit['requests']===2&&$audit['events']===1,'owner audit accepts complete original chain');
$wpdb->query($wpdb->prepare("UPDATE $eventTable SET event_hash=%s WHERE event_id=%s",str_repeat('a',64),$jid));try{op_fail(fn()=>$c->auditOwner($decodeOwner),'contract_audit_inconsistent');}finally{$wpdb->update($eventTable,$originalEvent,['event_id'=>$jid]);}
$requestBefore=$wpdb->get_row($wpdb->prepare("SELECT * FROM $qt WHERE owner_id=%d AND request_id=%s",$decodeOwner,$decodeApply['request_id']),ARRAY_A);
$wpdb->query($wpdb->prepare("UPDATE $qt SET request_hash=%s WHERE owner_id=%d AND request_id=%s",str_repeat('b',64),$decodeOwner,$decodeApply['request_id']));try{op_fail(fn()=>$c->auditOwner($decodeOwner),'contract_audit_inconsistent');}finally{$wpdb->update($qt,$requestBefore,['owner_id'=>$decodeOwner,'request_id'=>$decodeApply['request_id']]);}
$wpdb->query($wpdb->prepare("DELETE FROM $qt WHERE owner_id=%d AND request_id=%s",$decodeOwner,$decodeInput['request_id']));try{op_fail(fn()=>$c->auditOwner($decodeOwner),'contract_audit_inconsistent');}finally{$wpdb->insert($qt,['owner_id'=>$decodeOwner,'request_id'=>$decodeInput['request_id'],'request_hash'=>hash('sha256',(function()use($decodeInput){$i=$decodeInput;ksort($i);return json_encode(['prepare',$i]);})()),'response'=>$receipt]);}
op_check($c->auditOwner($decodeOwner)===$audit&&op_rows($decodePair['en'])===$auditBefore,'audit never repairs or writes source');
op_check($c->auditOwner($renewOwner)['events']===2,'audit accepts renewed recovery retaining expired review');
op_fail(fn()=>$c->auditBatch(0,11),'contract_invalid_input');
op_fail(fn()=>$c->auditBatch(),'contract_audit_orphan');
// Only the fixture removes its previously injected orphan effects temporarily.
$orphans=$wpdb->get_results("SELECT f.* FROM $effectTable f LEFT JOIN $eventTable e ON e.event_id=f.event_id WHERE e.event_id IS NULL",ARRAY_A);
$wpdb->query("DELETE f FROM $effectTable f LEFT JOIN $eventTable e ON e.event_id=f.event_id WHERE e.event_id IS NULL");
try{$batch=$c->auditBatch($decodeOwner-1,1);op_check(count($batch['owners'])===1&&$batch['owners'][0]===$audit&&!$batch['has_more'],'bounded batch visits final owner');op_check($c->auditBatch($decodeOwner,1)['owners']===[],'batch cursor reaches end without duplicate owner');}finally{foreach($orphans as $orphan){$wpdb->insert($effectTable,$orphan);}}
// A ready schema is never rebuilt or silently adopted after drift.
$markerBefore=$wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name='ac_exp_operating_storage'");$migrationSource=op_rows($decodePair['en']);
$wpdb->query("ALTER TABLE $rt ADD migration_drift INT NULL");try{op_fail(fn()=>$c->install(),'contract_schema_changed');}finally{$wpdb->query("ALTER TABLE $rt DROP migration_drift");}
$wpdb->query("UPDATE $wpdb->options SET option_value='future-version' WHERE option_name='ac_exp_operating_storage'");try{op_fail(fn()=>$c->install(),'contract_migration_marker_changed');op_fail(fn()=>$c->auditOwner($decodeOwner),'contract_storage_not_ready');}finally{$wpdb->query($wpdb->prepare("UPDATE $wpdb->options SET option_value=%s WHERE option_name='ac_exp_operating_storage'",$markerBefore));}
op_check($c->install()===$mi&&op_rows($decodePair['en'])===$migrationSource,'migration retry retains source and ready marker');
$migrationLock='ac_op_migrate_'.substr(hash('sha256',DB_NAME.'|'.$wpdb->prefix),0,32);$lockObserver=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$lockObserver->get_var("SELECT GET_LOCK('$migrationLock',0)");try{op_fail(fn()=>$c->install(),'contract_migration_busy');}finally{$lockObserver->query("DO RELEASE_LOCK('$migrationLock')");}
$wpdb->query('START TRANSACTION');try{op_fail(fn()=>$c->install(),'contract_outer_transaction');}finally{$wpdb->query('ROLLBACK');}
$preparing=json_encode(AlexCuadra\EditorialSourceExperiment\operating_storage_marker($wpdb,'preparing'));$wpdb->query($wpdb->prepare("UPDATE $wpdb->options SET option_value=%s WHERE option_name='ac_exp_operating_storage'",$preparing));try{op_fail(fn()=>$c->install(),'contract_migration_untracked_data');}finally{$wpdb->query($wpdb->prepare("UPDATE $wpdb->options SET option_value=%s WHERE option_name='ac_exp_operating_storage'",$markerBefore));}
op_check(get_role('subscriber')->capabilities===$roleBefore,'subscriber global role unchanged');
echo 'SOURCE_OPERATING_CONTRACT_RESULT='.wp_json_encode(['checks'=>$GLOBALS['operating_checks'],'passed'=>true,'realProposalsRc2'=>true,'independentCliApproval'=>true,'approvalSourceReceiptIntentAtomic'=>true,'proposalReceiptsUnchanged'=>true,'recoveryCapacityReserved'=>true,'recoveryExecutorImplemented'=>true,'dbDisconnectRollbackVerified'=>true,'automaticReconnectInsideTransaction'=>false,'abilitiesRegistered'=>false,'perOwnerOperatingLimits'=>['reviews'=>8,'requests'=>64,'bytes'=>8388608],'rc2BudgetSeparate'=>true]).PHP_EOL;
