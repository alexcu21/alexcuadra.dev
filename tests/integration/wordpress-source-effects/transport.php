<?php
require_once '/integration/support.php';
if(!function_exists('posix_kill')){throw new RuntimeException('POSIX_SIGKILL_REQUIRED');}
$GLOBALS['source_http_checks']=0;
function http_check($ok,$label){$GLOBALS['source_http_checks']++;if(!$ok){throw new RuntimeException('HTTP_CHECK: '.$label);}}
function http_call($case,$phase,$auth,$extraHeaders=[]){
 $args=['headers'=>array_merge(['Content-Type'=>'application/json'],$extraHeaders),'body'=>wp_json_encode(['case'=>$case,'phase'=>$phase]),'timeout'=>12,'redirection'=>0];
 if($auth!==null){$args['headers']['Authorization']='Basic '.base64_encode($auth);}
 return wp_remote_post('http://wordpress/?rest_route=/source-effects-test/v1/run',$args);
}
function http_json($response){http_check(!is_wp_error($response)&&wp_remote_retrieve_response_code($response)===200,'HTTP200');return json_decode(wp_remote_retrieve_body($response),true,512,JSON_THROW_ON_ERROR);}
function sql_value($id){global $wpdb;return $wpdb->get_var($wpdb->prepare("SELECT post_content FROM $wpdb->posts WHERE ID=%d",$id));}
function http_source_version($id){return snapshot($id,'en',['body_html'=>'','tagline'=>''],false)['version'];}
function http_write_count($case){global $wpdb;return (int)$wpdb->get_var($wpdb->prepare("SELECT calls FROM {$wpdb->prefix}ac_exp_http_writes WHERE case_id=%s",$case));}
function http_mark($case){global $wpdb;return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ac_exp_http_marks WHERE case_id=%s",$case),ARRAY_A);}
function http_effect($case,$id){global $wpdb;$j=source_operation($wpdb);return $j->effect(source_http_event($case,$id)['event_id']);}
function http_crash($case,$phase,$expected){
 $response=http_call($case,$phase,$GLOBALS['source_http_auth']);
 http_check(is_wp_error($response),'worker termination closes HTTP transport');
 $mark=http_mark($case);http_check($mark['phase']===$expected&&(int)$mark['pid']>0,'independent marker proves kill point');
}
global $wpdb;
$marks=$wpdb->prefix.'ac_exp_http_marks';$wpdb->query("CREATE TABLE $marks (case_id VARBINARY(64) PRIMARY KEY,phase VARBINARY(32) NOT NULL,pid INT UNSIGNED NOT NULL,api_calls INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB");
$wpdb->query("CREATE TABLE {$wpdb->prefix}ac_exp_http_writes (case_id VARBINARY(64) PRIMARY KEY,calls INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB");
$cases=[];
foreach(['normal','rollback','before-commit','after-commit','before-api','after-api'] as $case){
 $id=wp_insert_post(['post_type'=>'project','post_status'=>'publish','post_title'=>'Synthetic HTTP '.$case,'post_content'=>'HTTP before '.$case],true);
 if(is_wp_error($id)){throw new RuntimeException('fixture_create_failed');}
 update_field('field_676dcb2aec8d4','Before HTTP tagline',$id);$cases[$case]=$id;
 $wpdb->query($wpdb->prepare("INSERT INTO $marks (case_id,phase,pid) VALUES (%s,'ready',0)",$case));
 $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}ac_exp_http_writes (case_id) VALUES (%s)",$case));
}
update_option('ac_source_http_fixture',$cases,false);
$admin=get_user_by('login','effects_admin');
$password=WP_Application_Passwords::create_new_application_password($admin->ID,['name'=>'Synthetic source HTTP']);
if(is_wp_error($password)){throw new RuntimeException('synthetic_app_password_failed');}
$auth=$admin->user_login.':'.$password[0];$GLOBALS['source_http_auth']=$auth;
$subscriber=wp_insert_user(['user_login'=>'http_subscriber','user_pass'=>wp_generate_password(),'user_email'=>'subscriber@example.invalid','role'=>'subscriber']);
$subPassword=WP_Application_Passwords::create_new_application_password($subscriber,['name'=>'Synthetic denied HTTP']);
if(is_wp_error($subPassword)){throw new RuntimeException('subscriber_password_failed');}
// Clear only fixture-generated pending tags/queue before measuring fresh HTTP headers.
foreach(['_pub_purge','_pub_purge2','_priv_purge'] as $prop){(new ReflectionProperty(LiteSpeed\Purge::class,$prop))->setValue(LiteSpeed\Purge::cls(),[]);}
LiteSpeed\Purge::update_option(LiteSpeed\Purge::DB_QUEUE,'-1');LiteSpeed\Purge::update_option(LiteSpeed\Purge::DB_QUEUE2,'-1');
foreach([null,'http_subscriber:'.$subPassword[0]] as $denied){$response=http_call('normal','commit',$denied);http_check(!is_wp_error($response)&&in_array(wp_remote_retrieve_response_code($response),[401,403],true),'anonymous/subscriber denied');}
http_check(http_effect('normal',$cases['normal'])===null,'denied requests do not create intent');
$r=http_json(http_call('normal','commit',$auth));http_check($r['receipt']['purgeHeadersUnchangedDuringWrite']&&$r['receipt']['purgeTagsUnchangedDuringWrite'],'HTTP native purge deferred during source write');http_check($r['effectState']==='pending','HTTP commit leaves durable pending effect');http_check(!$r['nativePurgeHeader'],'HTTP source response emits no purge header');
$body=http_source_version($cases['normal']);$consume=http_call('normal','consume',$auth);$r=http_json($consume);http_check($r['state']==='requested','HTTP consumer completes');http_check($r['nativePurgeHeader']&&wp_remote_retrieve_header($consume,'x-litespeed-purge')!=='','native purge header on consumer response');http_check(http_source_version($cases['normal'])===$body&&http_write_count('normal')===1,'consumer preserves complete source without rewriting');
$before=http_source_version($cases['rollback']);$r=http_call('rollback','abort',$auth);http_check(!is_wp_error($r)&&wp_remote_retrieve_response_code($r)===409,'HTTP abort reported');http_check(http_source_version($cases['rollback'])===$before&&http_effect('rollback',$cases['rollback'])===null&&http_write_count('rollback')===0,'HTTP rollback complete source, counter and intent');http_check(wp_remote_retrieve_header($r,'x-litespeed-purge')==='','HTTP rollback no native purge header');
$before=http_source_version($cases['before-commit']);http_crash('before-commit','crash_before_commit','before-commit');http_check(http_source_version($cases['before-commit'])===$before&&http_effect('before-commit',$cases['before-commit'])===null&&http_write_count('before-commit')===0,'SIGKILL before commit rolls back complete source and intent');
http_crash('after-commit','crash_after_commit','after-commit');$after=sql_value($cases['after-commit']);http_check($after==='<p>HTTP fixture after-commit</p>'&&http_effect('after-commit',$cases['after-commit'])['state']==='pending','SIGKILL after commit preserves source and intent');
$committedVersion=http_source_version($cases['after-commit']);http_check(http_write_count('after-commit')===1,'lost response committed exactly one source execution');
$r=http_json(http_call('after-commit','commit',$auth));http_check(http_source_version($cases['after-commit'])===$committedVersion&&$r['effectState']==='pending'&&http_write_count('after-commit')===1,'lost response replay has no source execution');
foreach(['before-api','after-api'] as $case){
 http_json(http_call($case,'commit',$auth));$before=http_source_version($cases[$case]);
 http_crash($case,'crash_'.str_replace('-','_',$case),$case);
 $effect=http_effect($case,$cases[$case]);http_check($effect['state']==='running'&&(int)$effect['attempts']===1,'killed consumer retains durable lease');
 http_check((int)http_mark($case)['api_calls']===($case==='after-api'?1:0),'native API kill point counted');
 $r=http_json(http_call($case,'consume',$auth));http_check($r['state']==='not_claimed','live lease blocks another consumer');
 // Fixture-only expiry, without waiting30s. Does not alter content/receipts.
 $effects=$wpdb->prefix.'ac_exp_source_effects';$wpdb->query($wpdb->prepare("UPDATE $effects SET lease_until=UNIX_TIMESTAMP()-1 WHERE event_id=%s",source_http_event($case,$cases[$case])['event_id']));
 $r=http_json(http_call($case,'consume',$auth));http_check($r['state']==='requested','expired killed worker lease resumed');
 http_check((int)http_mark($case)['api_calls']===($case==='after-api'?2:1),'at least once API count after recovery');
 http_check(http_source_version($cases[$case])===$before&&http_write_count($case)===1,'consumer crash recovery never rewrites complete source');
}
if(getenv('AC_SOURCE_EFFECTS_FULL')==='verified-backup-v1'){
 $beforePreview=http_source_version($cases['rollback']);
 $preview=http_call('rollback','commit',$auth,['X-Preview-Indicator'=>'1']);
 http_check(!is_wp_error($preview)&&wp_remote_retrieve_response_code($preview)===500,'MU preview rewritten context closes source operation');
 http_check(http_source_version($cases['rollback'])===$beforePreview&&http_write_count('rollback')===0&&http_effect('rollback',$cases['rollback'])===null,'preview rejection leaves source and journal intact');
 $normal=http_json(http_call('normal','commit',$auth));
 http_check($normal['effectState']==='requested'&&http_write_count('normal')===1,'canonical request recovers after MU preview');
}
http_check((int)$wpdb->get_var("SELECT COUNT(*) FROM $marks WHERE case_id IN ('before-commit','after-commit','before-api','after-api') AND pid>0")===4,'four SIGKILL checkpoints reached');
echo 'SOURCE_EFFECTS_HTTP_RESULT='.wp_json_encode(['checks'=>$GLOBALS['source_http_checks'],'passed'=>true,'transport'=>'internal HTTP Apache','authentication'=>'synthetic Application Passwords','sigkillCheckpoints'=>4,'nativeHttpPurgeHeaderVerified'=>true,'sameMariaDbConnection'=>true,'sourceWritesOnRetry'=>false,'hostingCacheDeliveryVerified'=>false,'fullSourceCodeProfileVerified'=>getenv('AC_SOURCE_EFFECTS_FULL')==='verified-backup-v1','fullHostingConfigurationVerified'=>false]).PHP_EOL;
