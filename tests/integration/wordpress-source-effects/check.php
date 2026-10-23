<?php
require_once (getenv('AC_SOURCE_EFFECTS_PACKAGE_NORMAL')==='1'?WP_CONTENT_DIR.'/plugins/alexcuadra-editorial-operating/includes/':(in_array(getenv('AC_SOURCE_EFFECTS_PACKAGE'),['local-rc1','local-rc3'],true)?'/operating-package/includes/':'/experiment/')).'source-writer.php';
require_once (getenv('AC_SOURCE_EFFECTS_PACKAGE_NORMAL')==='1'?WP_CONTENT_DIR.'/plugins/alexcuadra-editorial-operating/includes/':(in_array(getenv('AC_SOURCE_EFFECTS_PACKAGE'),['local-rc1','local-rc3'],true)?'/operating-package/includes/':'/experiment/')).'mariadb-operation.php';
use AlexCuadra\EditorialSourceExperiment\MariaDbOperation;
use function AlexCuadra\EditorialSourceExperiment\write_source;
use function AlexCuadra\EditorialSourceExperiment\deferred_publish;
$GLOBALS['source_effect_checks']=0;
function check($yes,$name){$GLOBALS['source_effect_checks']++;if(!$yes){throw new RuntimeException('CHECK: '.$name);}}
function failure(callable $fn,$code){try{$fn();}catch(RuntimeException $e){check($e->getMessage()===$code,$code);return;}throw new RuntimeException('Expected '.$code);}
require_once '/integration/support.php';
global $wpdb;
check(get_bloginfo('version')==='7.1.3','WP version');check(defined('LSCWP_V')&&LSCWP_V==='7.9.2','LiteSpeed version');check(acf_get_setting('version')==='6.8.10','ACF version');
check(in_array(wp_using_ext_object_cache(),[false,null],true)&&get_class($GLOBALS['wp_object_cache'])==='WP_Object_Cache'&&!file_exists(WP_CONTENT_DIR.'/object-cache.php'),'core object cache and no dropin');
$id=wp_insert_post(['post_type'=>'project','post_status'=>'publish','post_title'=>'Synthetic target','post_content'=>'Before body'],true);
$other=wp_insert_post(['post_type'=>'project','post_status'=>'publish','post_title'=>'Synthetic unrelated'],true);
update_field('field_676dcb2aec8d4','Before tagline',$id);update_post_meta($id,'unrelated','keep');
check(is_int($id)&&$id>0,'fixture created');
$j=source_operation($wpdb);$j->install();$j->schema();
$observer=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$observer->set_prefix($wpdb->prefix);$k=source_operation($observer);
failure(fn()=>$k->record(event_for(99,$id),fn()=>[]),'source_connection_mismatch');
$approval=$wpdb->prefix.'ac_exp_approval_fixture';$wpdb->query("CREATE TABLE $approval (id INT PRIMARY KEY,consumed INT NOT NULL) ENGINE=InnoDB");$wpdb->query("INSERT INTO $approval VALUES (1,0)");
$bodyCalls=0;$watch=static function($new,$old,$post)use($id,&$bodyCalls){if((int)$post->ID===$id){$bodyCalls++;}};
add_action('transition_post_status',$watch,9,3);
$baseline=callback_state();clear_tags();
$d=doc($id,['body_html','tagline']);$event=event_for(1,$id);
$write=static function()use($d,$id,$k,$event,$wpdb,$approval){
 $after=write_source($d,'snapshot','field_schema','cache_clear');
 check(tags()===[],'native target purge deferred during write');
 check($k->effect($event['event_id'])===null,'observer cannot see uncommitted effect');
 check($wpdb->query("UPDATE $approval SET consumed=1 WHERE id=1")===1,'approval fixture consumed');
 return ['content'=>'applied','version'=>$after['version']];
};
$receipt=$j->record($event,$write);
check($bodyCalls===1,'combined one post transition');check(tags()===[],'no purge before or at commit');check(callback_state()===$baseline,'callback identity order and args restored');
check($k->effect($event['event_id'])['state']==='pending','committed effect visible');
check((int)$observer->get_var("SELECT consumed FROM $approval WHERE id=1")===1,'approval committed same connection');
check(get_post_meta($id,'project_tagline',true)===$d['after']['tagline']['project_tagline']['value'],'real ACF raw text');
check(get_post($id)->post_content===$d['after']['body_html'],'real WP raw body');
check(get_post_meta($id,'unrelated',true)==='keep','unrelated metadata unchanged');
check($j->record($event,function(){throw new RuntimeException('must_not_rewrite');})===$receipt,'replay does not rewrite');
$purge=function($target)use($observer){check((int)$observer->get_var('SELECT @@in_transaction')===0,'consumer outside transaction');do_action('litespeed_purge_post',$target);return 'requested';};
check($k->consume($event['event_id'],$purge)==='requested','native API invoked after commit');check(tags()!==[],'native purge tags produced');
check($j->consume($event['event_id'],fn()=>throw new RuntimeException('must_not_repeat'))==='not_claimed','requested not repeated');
clear_tags();$beforeCalls=$bodyCalls;$d=doc($id,['tagline']);$d['after']['tagline']['project_tagline']['value']='Metadata only';
$j->record(event_for(2,$id),fn()=>['version'=>write_source($d,'snapshot','field_schema','cache_clear')['version']]);
check($bodyCalls===$beforeCalls,'real tagline skips post transition');check(tags()===[],'tagline no early native purge');check(get_post_meta($id,'project_tagline',true)==='Metadata only','tagline persisted');
$k->consume(event_for(2,$id)['event_id'],$purge);check(tags()!==[],'tagline gets explicit postcommit invalidation');
clear_tags();$before=get_post($id)->post_content;$beforeTag=get_post_meta($id,'project_tagline',true);$wpdb->query("UPDATE $approval SET consumed=0");$d=doc($id,['body_html','tagline']);$d['after']['body_html']='<p>Rollback body</p>';
failure(function()use($j,$id,$d,$wpdb,$approval){$j->record(event_for(3,$id),function()use($d,$wpdb,$approval){write_source($d,'snapshot','field_schema','cache_clear');$wpdb->query("UPDATE $approval SET consumed=1");throw new RuntimeException('abort_after_source');});},'abort_after_source');
check(get_post($id)->post_content===$before,'WP body rolled back fresh cache');check(get_post_meta($id,'project_tagline',true)===$beforeTag,'ACF meta rolled back');check((int)$observer->get_var("SELECT consumed FROM $approval")===0,'approval rolled back');check($k->effect(event_for(3,$id)['event_id'])===null,'rollback no intent');check(tags()===[],'rollback no deferred purge emitted');check(callback_state()===$baseline,'exception restores exact hook');
// Intention insertion failure after all source writes must roll back the source too.
$effects=$wpdb->prefix.'ac_exp_source_effects';$events=$wpdb->prefix.'ac_exp_source_events';
$wpdb->suppress_errors(true);
failure(function()use($j,$id,$d,$observer,$effects){$j->record(event_for(4,$id),function()use($d,$observer,$effects,$id){
 $after=write_source($d,'snapshot','field_schema','cache_clear');
 // Fixture fault: another connection inserts a conflicting orphan intent.
 $observer->query($observer->prepare("INSERT INTO $effects (event_id,project_id,state,available_at) VALUES (%s,%d,'pending',UNIX_TIMESTAMP())",event_for(4,$id)['event_id'],$id));
 return ['version'=>$after['version']];
});},'experiment_sql_failed');
$wpdb->suppress_errors(false);
check(get_post($id)->post_content===$before,'intent insertion failure rolls body back');
check($k->consume(event_for(4,$id)['event_id'],fn()=>throw new RuntimeException('orphan_must_not_purge'))==='not_claimed','orphan intent without committed event cannot execute');
// Other Project transitions keep native behavior inside the temporary scope.
clear_tags();deferred_publish($id,function()use($other){do_action('transition_post_status','publish','publish',get_post($other));});check(tags()!==[],'unrelated Project keeps native purge');check(callback_state()===$baseline,'unrelated call preserves hooks');
clear_tags();failure(fn()=>deferred_publish($id,fn()=>deferred_publish($id,fn()=>null)),'nested_effect_scope');check(callback_state()===$baseline,'nested rejection restores hook');
// Recover is a distinct event, leaving original receipt and effect untouched.
$d=doc($id,['body_html']);$d['operation']='recover';$d['after']['body_html']='Before body';
$j->record(event_for(5,$id,'recover'),fn()=>['version'=>write_source($d,'snapshot','field_schema','cache_clear')['version']]);
check($j->effect(event_for(5,$id)['event_id'])['state']==='pending','recovery independent intent');check($j->effect($event['event_id'])['state']==='requested','original effect retained');
$version=get_post($id)->post_content;$k->consume(event_for(5,$id)['event_id'],fn()=>throw new RuntimeException('simulated purge failure'));
check($j->effect(event_for(5,$id)['event_id'])['state']==='retry','purge failure queued');check(get_post($id)->post_content===$version,'purge failure no source rollback');
$wpdb->query('START TRANSACTION');failure(fn()=>$j->record(event_for(6,$id),fn()=>[]),'outer_transaction');$wpdb->query('ROLLBACK');
$wpdb->query("ALTER TABLE $effects ADD extra INT NULL");failure(fn()=>$j->record(event_for(6,$id),fn()=>[]),'experiment_schema_changed');$wpdb->query("ALTER TABLE $effects DROP extra");
$wpdb->query("ALTER TABLE $effects ADD CONSTRAINT unexpected_check CHECK (attempts < 100)");failure(fn()=>$j->record(event_for(6,$id),fn()=>[]),'experiment_schema_changed');$wpdb->query("ALTER TABLE $effects DROP CONSTRAINT unexpected_check");
$d=doc($id,['tagline']);$j->record(event_for(7,$id),fn()=>['version'=>write_source($d,'snapshot','field_schema','cache_clear')['version']]);
check($k->consume(event_for(7,$id)['event_id'],function()use($j,$id){check($j->consume(event_for(7,$id)['event_id'],fn()=>'requested')==='not_claimed','other connection cannot consume leased effect');return 'requested';})==='requested','first consumer settles lease');
$d=doc($id,['tagline']);$j->record(event_for(8,$id),fn()=>['version'=>write_source($d,'snapshot','field_schema','cache_clear')['version']]);
check($k->consume(event_for(8,$id)['event_id'],function()use($wpdb,$effects,$id){$wpdb->query($wpdb->prepare("UPDATE $effects SET lease_token=%s WHERE event_id=%s",str_repeat('b',32),event_for(8,$id)['event_id']));return 'requested';})==='lease_lost','stale MariaDB token cannot settle');
$wpdb->query($wpdb->prepare("UPDATE $effects SET lease_until=UNIX_TIMESTAMP()-1 WHERE event_id=%s",event_for(8,$id)['event_id']));
check($j->consume(event_for(8,$id)['event_id'],fn()=>'requested')==='requested','expired MariaDB lease reclaimed');
$d=doc($id,['tagline']);$j->record(event_for(9,$id),fn()=>['version'=>write_source($d,'snapshot','field_schema','cache_clear')['version']]);
$sourceBefore=get_post_meta($id,'project_tagline',true);
for($attempt=0;$attempt<8;$attempt++){$state=$k->consume(event_for(9,$id)['event_id'],fn()=>'retry');$wpdb->query($wpdb->prepare("UPDATE $effects SET available_at=UNIX_TIMESTAMP()-1 WHERE event_id=%s",event_for(9,$id)['event_id']));}
check($state==='attention','MariaDB retry limit needs attention');check($j->consume(event_for(9,$id)['event_id'],fn()=>'requested')==='not_claimed','attention not automatically retried');check(get_post_meta($id,'project_tagline',true)===$sourceBefore,'retries preserve source');
$originalVersion=$GLOBALS['wp_version'];$GLOBALS['wp_version']='7.2-unsupported';
try{failure(fn()=>source_operation($wpdb),'local_wp_experiment_only');}finally{$GLOBALS['wp_version']=$originalVersion;}
$originalCache=$GLOBALS['_wp_using_ext_object_cache']??null;$GLOBALS['_wp_using_ext_object_cache']=true;
try{failure(fn()=>source_operation($wpdb),'local_wp_experiment_only');}finally{$GLOBALS['_wp_using_ext_object_cache']=$originalCache;}
check(callback_state()===$baseline,'final exact hook state');remove_action('transition_post_status',$watch,9);
echo 'SOURCE_EFFECTS_MARIADB_RESULT='.wp_json_encode(['checks'=>$GLOBALS['source_effect_checks'],'passed'=>true,'wordpress'=>get_bloginfo('version'),'mariadb'=>$wpdb->get_var('SELECT VERSION()'),'acf'=>acf_get_setting('version'),'litespeed'=>LSCWP_V,'sameConnectionAtomicity'=>true,'nativePurgeApiInvoked'=>true,'hostingCacheDeliveryVerified'=>false,'httpContextVerified'=>false]).PHP_EOL;
