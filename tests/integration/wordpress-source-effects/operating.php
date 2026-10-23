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
op_user($operator);$c=op_controller();$c->install();
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
$wpdb->query($wpdb->prepare("INSERT INTO $appsTable VALUES (%d,%d,1,%s,%s,%d,0,0)",$reserved['proposal_id'],$bytesOwner,str_repeat('b',64),str_repeat('c',64),C::MAX_BYTES));op_fail(fn()=>$c->prepare(op_prepare_input($qp)),'contract_owner_quota');
op_check(get_role('subscriber')->capabilities===$roleBefore,'subscriber global role unchanged');
echo 'SOURCE_OPERATING_CONTRACT_RESULT='.wp_json_encode(['checks'=>$GLOBALS['operating_checks'],'passed'=>true,'realProposalsRc2'=>true,'independentCliApproval'=>true,'approvalSourceReceiptIntentAtomic'=>true,'proposalReceiptsUnchanged'=>true,'recoveryCapacityReserved'=>true,'recoveryExecutorImplemented'=>false,'abilitiesRegistered'=>false,'perOwnerOperatingLimits'=>['reviews'=>8,'requests'=>64,'bytes'=>8388608],'rc2BudgetSeparate'=>true]).PHP_EOL;
