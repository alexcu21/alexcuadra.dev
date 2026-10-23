<?php
// Test-only REST surface, never included in an installable plugin.
require_once '/integration/support.php';
function source_http_headers(){return array_values(array_filter(headers_list(),fn($h)=>stripos($h,'X-LiteSpeed-Purge')===0));}
function source_http_event($case,$id){return ['event_id'=>hash('sha256','http-event|'.$case),'event_hash'=>hash('sha256','http-input|'.$case),'project_id'=>$id,'operation'=>'apply'];}
function source_http_mark($case,$phase,$increment=0){
 $db=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);global $wpdb;$db->set_prefix($wpdb->prefix);
 $table=$db->prefix.'ac_exp_http_marks';
 if($db->query($db->prepare("UPDATE $table SET phase=%s,pid=%d,api_calls=api_calls+%d WHERE case_id=%s",$phase,getmypid(),$increment,$case))===false){throw new RuntimeException('mark_failed');}
 $db->close();
}
function source_http_kill($case,$phase){
 source_http_mark($case,$phase);
 if(!function_exists('posix_kill')||!posix_kill(getmypid(),9)){throw new RuntimeException('sigkill_required');}
 throw new RuntimeException('sigkill_failed');
}
function source_http_route($request){
 global $wpdb;
 $input=$request->get_json_params();$case=$input['case']??'';$phase=$input['phase']??'';
 $cases=get_option('ac_source_http_fixture',[]);
 if(!isset($cases[$case])||!in_array($phase,['commit','abort','crash_before_commit','crash_after_commit','consume','crash_before_api','crash_after_api'],true)){
  return new WP_Error('invalid_fixture','INVALID_FIXTURE',['status'=>400]);
 }
 $id=$cases[$case];$event=source_http_event($case,$id);
 $j=new AlexCuadra\EditorialSourceExperiment\MariaDbOperation($wpdb);
 try{
  if(in_array($phase,['consume','crash_before_api','crash_after_api'],true)){
   $state=$j->consume($event['event_id'],static function($target)use($case,$phase){
    if($phase==='crash_before_api'){source_http_kill($case,'before-api');}
    do_action('litespeed_purge_post',$target);
    source_http_mark($case,'api-invoked',1);
    if($phase==='crash_after_api'){source_http_kill($case,'after-api');}
    return 'requested';
   });
   return ['state'=>$state,'nativePurgeHeader'=>source_http_headers()!==[]];
  }
  $d=doc($id,['body_html','tagline']);$d['after']['body_html']='<p>HTTP fixture '.$case.'</p>';
  $headers=source_http_headers();$tags=tags();
  $receipt=$j->record($event,static function()use($d,$case,$phase,$headers,$tags,$wpdb){
   if($wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ac_exp_http_writes SET calls=calls+1 WHERE case_id=%s",$case))!==1){throw new RuntimeException('write_counter_failed');}
   $after=AlexCuadra\EditorialSourceExperiment\write_source($d,'snapshot','field_schema','cache_clear');
   if($phase==='abort'){throw new RuntimeException('fixture_abort');}
   if($phase==='crash_before_commit'){source_http_kill($case,'before-commit');}
   return ['version'=>$after['version'],'purgeHeadersUnchangedDuringWrite'=>$headers===source_http_headers(),'purgeTagsUnchangedDuringWrite'=>$tags===tags()];
  });
  if($phase==='crash_after_commit'){source_http_kill($case,'after-commit');}
  return ['receipt'=>$receipt,'effectState'=>$j->effect($event['event_id'])['state'],'nativePurgeHeader'=>source_http_headers()!==[]];
 }catch(RuntimeException $e){return new WP_Error('fixture_operation_failed','FIXTURE_OPERATION_FAILED',['status'=>$e->getMessage()==='fixture_abort'?409:500]);}
}
add_action('rest_api_init',static function(){
 if(($_SERVER['HTTP_HOST']??'')!=='wordpress'){return;}
 register_rest_route('source-effects-test/v1','/run',['methods'=>'POST','permission_callback'=>static function(){return current_user_can('manage_options');},'callback'=>'source_http_route']);
});
