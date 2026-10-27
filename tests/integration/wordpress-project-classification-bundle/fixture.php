<?php
use AlexCuadra\EditorialBlogProposals as B;
if(wp_get_environment_type()!=='local'||DB_HOST!=='db'||getenv('BLOG_SITE_PROFILE_LAB')!=='verified-code-only'){throw new RuntimeException('ISOLATED_BUNDLE_LAB_REQUIRED');}
global $wpdb;$mode=($args??[])[0]??'';wp_set_current_user(1);$cases=[];
$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED:'.$name);}$cases[]=$name;};
$path='/bundle-private/pilot.json';
$fingerprint=static function()use($wpdb){$all=[];foreach(['posts'=>'ID','postmeta'=>'meta_id','terms'=>'term_id','term_taxonomy'=>'term_taxonomy_id','term_relationships'=>'object_id,term_taxonomy_id','termmeta'=>'meta_id']as$t=>$o){$all[$t]=$wpdb->get_results("SELECT * FROM {$wpdb->$t} ORDER BY $o",ARRAY_A);}return hash('sha256',serialize($all));};
if($mode==='configure'){
 if(!function_exists('acf_update_taxonomy')){throw new RuntimeException('ACF_TAXONOMY_API_REQUIRED');}
 foreach(['project_category'=>true,'project_technology'=>false]as$tax=>$hierarchical){$saved=acf_update_taxonomy(['key'=>'taxonomy_synthetic_'.$tax,'title'=>'Synthetic '.$tax,'active'=>true,'taxonomy'=>$tax,'object_type'=>['project'],'public'=>true,'publicly_queryable'=>true,'show_ui'=>true,'show_in_rest'=>true,'hierarchical'=>$hierarchical]);$check('native_acf_definition_'.$tax,is_array($saved)&&$saved['ID']>0);}
 PLL()->options->set('post_types',['project']);PLL()->options->set('taxonomies',['project_category','project_technology']);PLL()->options->save_all();
 $check('synthetic_translation_settings_saved',true);
}elseif($mode==='seed'){
 foreach([['en','en_US','English'],['es','es_ES','Español']]as$l){if(!in_array($l[0],pll_languages_list(),true)){PLL()->model->add_language(['name'=>$l[2],'slug'=>$l[0],'locale'=>$l[1],'rtl'=>false,'term_group'=>$l[0]==='en'?0:1]);}}
 PLL()->options->set('default_lang','en');PLL()->options->save_all();
 $id=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'Synthetic retained source','post_content'=>'Synthetic body only'],true);if(is_wp_error($id)){throw new RuntimeException('POST_FIXTURE_FAILED');}
 pll_set_post_language($id,'en');wp_set_object_terms($id,[],'category');wp_set_object_terms($id,[],'post_tag');
 $projects=[];$terms=[];
 foreach(['en','es']as$lang){$pid=wp_insert_post(['post_type'=>'project','post_status'=>'publish','post_title'=>'Synthetic project '.$lang],true);pll_set_post_language($pid,$lang);$projects[$lang]=$pid;
  foreach(['project_category','project_technology']as$tax){$term=wp_insert_term('Synthetic '.$tax.' '.$lang,$tax);if(is_wp_error($term)){throw new RuntimeException('TERM_FIXTURE_FAILED');}$tid=$term['term_id'];pll_set_term_language($tid,$lang);wp_set_object_terms($pid,[$tid],$tax);$terms[$tax][$lang]=$tid;}
 }
 pll_save_post_translations($projects);foreach($terms as$group){pll_save_term_translations($group);}
 $repo=new B\WordPressRepository($wpdb);$repo->install();$repo->enroll(2);(new WP_User(2))->add_cap(B\PREPARE_SCOPE);wp_set_current_user(2);$projection=B\wordpress_projection($id);
 $request=['request_id'=>str_repeat('ab',16),'source_id'=>$id,'locale'=>'en','base_hash'=>$projection['base_hash'],'operation'=>'update-post','fields'=>['title'=>'Synthetic private revision']];$plan=$repo->create($request);
 $check('seed_has_proposal_one_version_one',$plan['proposal_id']===1&&$plan['version']===1&&$plan['document']['owner_id']===2);
 wp_set_current_user(1);$state=['projects'=>$projects,'terms'=>$terms,'sourceId'=>$id,'request'=>$request,'plan'=>$plan,'sourceHash'=>$fingerprint(),'rolesHash'=>hash('sha256',serialize(wp_roles()->roles)),'caps'=>array_keys(array_filter((new WP_User(2))->allcaps))];sort($state['caps']);
 file_put_contents($path,json_encode($state,JSON_THROW_ON_ERROR));chmod($path,0600);
}elseif($mode==='pause'){
 (new WP_User(2))->remove_cap(B\PREPARE_SCOPE);$check('private_writer_scope_paused',!(new WP_User(2))->has_cap(B\PREPARE_SCOPE));
}elseif($mode==='resume'){
 (new WP_User(2))->add_cap(B\PREPARE_SCOPE);$check('private_writer_scope_resumed',(new WP_User(2))->has_cap(B\PREPARE_SCOPE));
}elseif($mode==='preserve'){
 $state=json_decode(file_get_contents($path),true,32,JSON_THROW_ON_ERROR);
 $check('upgrade_preserves_editorial_source_rows',$fingerprint()===$state['sourceHash']);$check('upgrade_preserves_roles',hash('sha256',serialize(wp_roles()->roles))===$state['rolesHash']);
 $caps=array_keys(array_filter((new WP_User(2))->allcaps));sort($caps);$check('upgrade_preserves_exact_capabilities',$caps===$state['caps']);
 wp_set_current_user(2);$repo=new B\WordPressRepository($wpdb);$check('retained_get_exact',$repo->get(1)===$state['plan']);$check('retained_replay_exact',$repo->create($state['request'])===$state['plan']);$check('retained_inventory_one_version_one',$repo->listing()===['items'=>[['proposal_id'=>1,'version'=>1]],'next_after'=>null]);
}elseif($mode==='readers'){
 $state=json_decode(file_get_contents($path),true,32,JSON_THROW_ON_ERROR);wp_set_current_user(2);
 $project=wp_get_ability('alexcuadra/get-public-project')->execute(['id'=>$state['projects']['en'],'locale'=>'en']);$check('existing_project_reader_coexists',!is_wp_error($project)&&$project['id']===$state['projects']['en']);
 $post=wp_get_ability('alexcuadra/get-public-post')->execute(['id'=>$state['sourceId'],'locale'=>'en']);$check('existing_blog_reader_coexists',!is_wp_error($post)&&$post['id']===$state['sourceId']);
 $classification=wp_get_ability('alexcuadra/get-public-project-classification')->execute(['id'=>$state['projects']['en'],'locale'=>'en']);
 $check('new_assigned_classification_works',!is_wp_error($classification)&&$classification['categories'][0]['id']===$state['terms']['project_category']['en']);
 $check('new_public_translation_works',$classification['categories'][0]['translations'][0]['id']===$state['terms']['project_category']['es']);
 $terms=wp_get_ability('alexcuadra/list-public-project-terms')->execute(['locale'=>'es','taxonomy'=>'project_technology']);$check('new_catalog_works',!is_wp_error($terms)&&$terms['items'][0]['id']===$state['terms']['project_technology']['es']);
}elseif($mode==='profile-negatives'){
 $check('coordinated_profile_ready',B\profile_report()['ready']);
 $file=WP_PLUGIN_DIR.'/alexcuadra-editorial-project-classification/reader.php';$old=file_get_contents($file);
 try{file_put_contents($file,$old."\n// synthetic drift\n");$check('reader_body_drift_blocks_proposals',!B\profile_report()['ready']);}finally{file_put_contents($file,$old);}
 $file=WP_PLUGIN_DIR.'/alexcuadra-editorial-project-classification/extra.php';try{file_put_contents($file,'<?php // synthetic extra');$check('extra_reader_file_blocks_proposals',!B\profile_report()['ready']);}finally{unlink($file);}
 $filter=static fn($p)=>array_merge($p,['unknown/unknown.php']);add_filter('option_active_plugins',$filter);$check('unknown_active_plugin_still_blocked',!B\profile_report()['ready']);remove_filter('option_active_plugins',$filter);
 $check('coordinated_profile_restored',B\profile_report()['ready']);
}elseif($mode==='restore-old-backup'){
 require_once '/bundle-operations/recovery.php';$directory=($args??[])[1]??'';$sha=($args??[])[2]??'';
 $originalPrefix=$wpdb->prefix;
 try{$wpdb->prefix='bundle_restore_';$result=\AlexCuadra\BlogProposalRecovery\restore_empty($directory,$sha,'writers-stopped');$check('rc3_recovers_rc2_artifact',$result['counts']===['owners'=>1,'proposals'=>1,'revisions'=>1,'requests'=>1]);
  $state=json_decode(file_get_contents($path),true,32,JSON_THROW_ON_ERROR);(new WP_User(2))->add_cap(B\PREPARE_SCOPE);wp_set_current_user(2);$repo=new B\WordPressRepository($wpdb);
  $check('old_backup_restored_get_exact',$repo->get(1)===$state['plan']);$check('old_backup_restored_replay_exact',$repo->create($state['request'])===$state['plan']);
 }finally{$wpdb->prefix=$originalPrefix;wp_set_current_user(1);(new WP_User(2))->remove_cap(B\PREPARE_SCOPE);}
}else{throw new RuntimeException('UNKNOWN_BUNDLE_MODE');}
WP_CLI::line('CLASSIFICATION_BUNDLE_FIXTURE_RESULT='.wp_json_encode(['mode'=>$mode,'passed'=>count($cases),'failed'=>0,'cases'=>$cases,'syntheticOnly'=>true]));
