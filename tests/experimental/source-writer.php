<?php
// Synthetic WP API boundary, no database, WordPress installation or network.
define('AC_EDITORIAL_SOURCE_EXPERIMENT', true);
require __DIR__ . '/../../wordpress/experimental/editorial-source-adapter/source-writer.php';
use function AlexCuadra\EditorialSourceExperiment\write_source;
const KEY = 'field_676dcb2aec8d4';
class WP_Error {}
function is_wp_error($v) { return $v instanceof WP_Error; }
function wp_get_environment_type() { global $env; return $env; }
function home_url() { global $origin; return $origin; }
function wp_slash($v) { return is_array($v) ? array_map('wp_slash',$v) : addslashes($v); }
function wp_update_post($post,$errors) {
    global $calls,$state,$fault;
    $calls[] = ['body',$post];
    if ($fault === 'body-error') { return new WP_Error; }
    $state['post']['post_content'] = stripslashes($post['post_content']);
    $state['post']['post_modified'] = 'new';
    if ($fault === 'throw') { throw new RuntimeException('api_exception'); }
    return $post['ID'];
}
function put_meta($key,$value) {
    global $state,$fault;
    if ($fault === 'silent-meta') { return false; }
    $state['meta'] = array_values(array_filter($state['meta'],fn($r)=>$r['meta_key']!==$key));
    $state['meta'][] = ['meta_key'=>$key,'meta_value'=>$value];
    return false; // valid APIs may report an unchanged value this way.
}
function update_field($key,$value,$id) { global $calls; $calls[]=['acf-update',$id]; put_meta('project_tagline',stripslashes($value)); put_meta('_project_tagline',KEY); }
function delete_field($key,$id) { global $calls; $calls[]=['acf-delete',$id]; delete_post_meta($id,'project_tagline'); delete_post_meta($id,'_project_tagline'); }
function update_post_meta($id,$key,$value) { global $calls; $calls[]=['meta-update',$id]; return put_meta($key,stripslashes($value)); }
function delete_post_meta($id,$key) { global $state,$calls; $calls[]=['meta-delete',$id]; $state['meta']=array_values(array_filter($state['meta'],fn($r)=>$r['meta_key']!==$key)); }
function meta($value,$ref=true) { return ['project_tagline'=>['present'=>$value!==null,'value'=>$value], '_project_tagline'=>['present'=>$ref,'value'=>$ref?KEY:null]]; }
function read_state($id,$locale,$fields,$locking) {
    global $state,$fault,$reads;
    $reads++;
    if ($reads>1 && $fault==='unrelated') { $state['post']['post_title']='other'; }
    if ($reads>1 && $fault==='timestamp') { $state['post']['post_modified']='other'; }
    if ($reads>1 && $fault==='terms') { $state['terms'][]=['term_taxonomy_id'=>99]; }
    $values=[];
    if (isset($fields['body_html'])) { $values['body_html']=$state['post']['post_content']; }
    if (isset($fields['tagline'])) {
        foreach (['project_tagline','_project_tagline'] as $key) {
            $rows=array_values(array_filter($state['meta'],fn($r)=>$r['meta_key']===$key));
            $values['tagline'][$key]=['present'=>count($rows)===1,'value'=>count($rows)===1?$rows[0]['meta_value']:null];
        }
    }
    return ['version'=>hash('sha256',serialize($state)),'before'=>$values,'rows'=>$state];
}
$checks=0;
function check($yes,$name) { global $checks; $checks++; if (!$yes) { throw new RuntimeException('CHECK: '.$name); } }
function setup($fields=['tagline']) {
    global $env,$origin,$calls,$state,$fault,$reads,$cleared;
    $env='local';$origin='http://wordpress';$calls=[];$fault='';$reads=0;$cleared=0;
    $state=['post'=>['ID'=>10,'post_content'=>'old body','post_title'=>'original','post_modified'=>'old','post_modified_gmt'=>'old'],
        'meta'=>[['meta_key'=>'project_tagline','meta_value'=>'old'],['meta_key'=>'_project_tagline','meta_value'=>KEY],['meta_key'=>'unrelated','meta_value'=>'keep']],
        'terms'=>[['term_taxonomy_id'=>2]]];
    $snapshot=read_state(10,'en',array_fill_keys($fields,''),true);$reads=0;
    $after=$snapshot['before'];
    if (isset($after['body_html'])) {$after['body_html']="New 'body' \\ path";}
    if (isset($after['tagline'])) {$after['tagline']=meta("New 'tagline' \\ path");}
    return ['project_id'=>10,'locale'=>'en','operation'=>'apply','source_version'=>$snapshot['version'],'schema_version'=>'schema-v1','before'=>$snapshot['before'],'after'=>$after];
}
function execute($d) { return write_source($d,'read_state',function(){global $fault,$reads;return $fault==='schema-after' && $reads>1?'changed':'schema-v1';},function($id){global $cleared;$cleared++;}); }
function denied($d,$code) { try {execute($d);} catch(RuntimeException $e) {check($e->getMessage()===$code,$code.' got '.$e->getMessage());return;} throw new RuntimeException('Expected '.$code); }
$d=setup();$result=execute($d);check($result['before']===$d['after'],'tagline persisted raw');check(!in_array('body',array_column($calls,0),true),'tagline skips body');check($state['post']['post_modified']==='old','metadata keeps timestamp');check($cleared===1,'cache cleared');
$d=setup(['body_html']);$result=execute($d);check($result['before']===$d['after'],'body raw');check(array_column($calls,0)===['body'],'body only no ACF');
$d=setup(['body_html','tagline']);execute($d);check(count(array_filter($calls,fn($c)=>$c[0]==='body'))===1,'combined one body call');check($cleared===1,'combined one cleanup');
$d=setup(['body_html']);$d['after']['body_html']=str_repeat('é',25000);$result=execute($d);check($result['before']===$d['after'],'UTF8 body follows character limit not byte limit');check(count($calls)===1,'Unicode body one API call');
$d=setup(['body_html']);$d['after']['body_html']=chr(255);denied($d,'invalid_body');check($calls===[],'invalid UTF8 rejected before API');
$d=setup(['body_html']);$d['after']['body_html']=str_repeat('é',40001);denied($d,'invalid_body');check($calls===[],'over character limit rejected before API');
$d=setup();$d['locale']='es';execute($d);check(!in_array('body',array_column($calls,0),true),'ES tagline also skips body');
$d=setup();$d['after']['tagline']=meta('',true);execute($d);check(array_values(array_filter($state['meta'],fn($r)=>$r['meta_key']==='project_tagline'))[0]['meta_value']==='', 'empty string is retained, not absence');
$d=setup();$d['operation']='recover';$d['after']['tagline']=meta(null,false);execute($d);check(count($state['meta'])===1,'recover absence keeps unrelated');
$d=setup();$d['after']=$d['before'];execute($d);check($cleared===1,'no-op false return allowed by snapshot');
foreach (['production','staging'] as $bad) {$d=setup();$env=$bad;denied($d,'local_experiment_only');check($calls===[],'environment rejects before API');}
$d=setup();$origin='https://content.alexcuadra.dev';denied($d,'local_experiment_only');check($calls===[],'real origin rejected');
$d=setup();$d['after']['post_title']='not allowed';denied($d,'invalid_fields');check($calls===[],'unknown field no write');
$d=setup();$d['after']['tagline']['_project_tagline']['value']='other';denied($d,'invalid_acf_reference');check($calls===[],'reference no write');
$d=setup();$d['source_version']='stale';denied($d,'source_changed');check($calls===[],'conflict no write');
$d=setup();$d['schema_version']='stale';denied($d,'schema_changed');check($calls===[],'schema no write');
foreach (['unrelated','terms','timestamp'] as $bad) {$d=setup();$fault=$bad;denied($d,'unrelated_source_changed');check($cleared===1,'failure clears cache');}
$d=setup();$fault='silent-meta';denied($d,'postcondition_failed');
$d=setup();$fault='schema-after';denied($d,'schema_changed');
$d=setup(['body_html','tagline']);$fault='body-error';denied($d,'body_write_failed');check($cleared===1,'API error clears cache');check(array_column($calls,0)===['body'],'body failure does not continue to metadata');
$d=setup(['body_html']);$fault='throw';denied($d,'api_exception');check($cleared===1,'exception clears cache');
echo json_encode(['format'=>'editorial-source-writer-boundary-v1','checks'=>$checks,'passed'=>true,'realWordPress'=>false,'transactionOrPurgeVerified'=>false]).PHP_EOL;
