<?php
require_once (getenv('AC_SOURCE_EFFECTS_PACKAGE')==='local-rc1'?'/operating-package/includes/':'/experiment/').'source-writer.php';
require_once (getenv('AC_SOURCE_EFFECTS_PACKAGE')==='local-rc1'?'/operating-package/includes/':'/experiment/').'mariadb-operation.php';
if(getenv('AC_SOURCE_EFFECTS_FULL')==='verified-backup-v1'){require_once (getenv('AC_SOURCE_EFFECTS_PACKAGE')==='local-rc1'?'/operating-package/includes/':'/experiment/').'polylang-source.php';}
function source_operation($db){$op=new AlexCuadra\EditorialSourceExperiment\MariaDbOperation($db,getenv('AC_SOURCE_EFFECTS_FULL')==='verified-backup-v1'?'AlexCuadra\\EditorialSourceExperiment\\clear_polylang_source_cache':null);if(getenv('AC_SOURCE_EFFECTS_FULL')==='verified-backup-v1'){require_once '/integration/profile-admission.php';full_source_profile_admission();}return $op;}
function tags(){return (new ReflectionProperty(LiteSpeed\Purge::class,'_pub_purge'))->getValue(LiteSpeed\Purge::cls());}
function clear_tags(){(new ReflectionProperty(LiteSpeed\Purge::class,'_pub_purge'))->setValue(LiteSpeed\Purge::cls(),[]);}
function callback_state(){global $wp_filter;return $wp_filter['transition_post_status']->callbacks;}
function snapshot($id,$locale,$fields,$locking){
 global $wpdb;
 if(getenv('AC_SOURCE_EFFECTS_FULL')==='verified-backup-v1'){return AlexCuadra\EditorialSourceExperiment\polylang_source_snapshot($id,$locale,$fields,$locking);}
 if($locale!=='en'){throw new RuntimeException('fixture_locale_only');}
 $lock=$locking?' FOR UPDATE':'';
 $post=$wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d$lock",$id),ARRAY_A);
 $meta=$wpdb->get_results($wpdb->prepare("SELECT meta_key,meta_value FROM $wpdb->postmeta WHERE post_id=%d ORDER BY meta_id$lock",$id),ARRAY_A);
 $terms=$wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->term_relationships WHERE object_id=%d ORDER BY term_taxonomy_id$lock",$id),ARRAY_A);
 if($wpdb->last_error||!$post||$post['post_type']!=='project'||$post['post_status']!=='publish'){throw new RuntimeException('snapshot_failed');}
 $values=[];
 if(array_key_exists('body_html',$fields)){$values['body_html']=$post['post_content'];}
 if(array_key_exists('tagline',$fields)){
  foreach(['project_tagline','_project_tagline'] as $key){$rows=array_values(array_filter($meta,fn($r)=>$r['meta_key']===$key));if(count($rows)>1){throw new RuntimeException('duplicate_meta');}$values['tagline'][$key]=['present'=>count($rows)===1,'value'=>$rows[0]['meta_value']??null];}
 }
 $rows=['post'=>$post,'meta'=>$meta,'terms'=>$terms];
 return ['version'=>hash('sha256',serialize($rows)),'before'=>$values,'rows'=>$rows];
}
function field_schema(){
 if(getenv('AC_SOURCE_EFFECTS_FULL')==='verified-backup-v1'){AlexCuadra\EditorialSourceExperiment\polylang_admission();}
 $field=acf_get_field('field_676dcb2aec8d4');
 if(!$field||$field['name']!=='project_tagline'){throw new RuntimeException('acf_schema_changed');}
 return hash('sha256',serialize($field));
}
function cache_clear($id){clean_post_cache($id);wp_cache_delete($id,'post_meta');acf_flush_value_cache($id,'project_tagline');}
function doc($id,$fields,$locale='en'){
 $s=snapshot($id,$locale,array_fill_keys($fields,''),false);$after=$s['before'];
 if(isset($after['body_html'])){$after['body_html']="<p>Synthetic 'body' \\ path</p>";}
 if(isset($after['tagline'])){$after['tagline']['project_tagline']=['present'=>true,'value'=>"Synthetic 'tagline' \\ path"];$after['tagline']['_project_tagline']=['present'=>true,'value'=>'field_676dcb2aec8d4'];}
 return ['project_id'=>$id,'locale'=>$locale,'operation'=>'apply','source_version'=>$s['version'],'schema_version'=>field_schema(),'before'=>$s['before'],'after'=>$after];
}
function event_for($n,$id,$operation='apply'){return ['event_id'=>hash('sha256','real-sql-event'.$n),'event_hash'=>hash('sha256','real-sql-input'.$n),'project_id'=>$id,'operation'=>$operation];}
