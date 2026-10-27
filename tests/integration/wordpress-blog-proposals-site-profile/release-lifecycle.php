<?php
/** Synthetic lifecycle fixture only, never an operator script for the hosting. */
use AlexCuadra\EditorialBlogProposals as B;
if(wp_get_environment_type()!=='local'||DB_HOST!=='db'||getenv('BLOG_SITE_PROFILE_LAB')!=='verified-code-only'){throw new RuntimeException('ISOLATED_LAB_REQUIRED');}
global $wpdb;
$mode=($args??[])[0]??'';$cases=[];
$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED:'.$name);}$cases[]=$name;};
wp_set_current_user(1);$p=$wpdb->prefix.'ac_blog_proposal_';
$snapshot=static function($prefix)use($wpdb){$rows=[];foreach(['owners'=>'owner_id','proposals'=>'id','revisions'=>'proposal_id,version','requests'=>'owner_id,request_id']as$s=>$order){$rows[$s]=$wpdb->get_results("SELECT * FROM {$prefix}{$s} ORDER BY $order",ARRAY_A);if($wpdb->last_error){throw new RuntimeException('SNAPSHOT_FAILED');}}return $rows;};
$sourceHash=static function()use($wpdb){$rows=[];foreach(['posts'=>'ID','postmeta'=>'meta_id','terms'=>'term_id','term_taxonomy'=>'term_taxonomy_id','term_relationships'=>'object_id,term_taxonomy_id','termmeta'=>'meta_id']as$t=>$order){$rows[$t]=$wpdb->get_results("SELECT * FROM {$wpdb->$t} ORDER BY $order",ARRAY_A);}return hash('sha256',serialize($rows));};
if(in_array($mode,['install-storage','enroll','grant','withdraw','poison','repair'],true)){
 $repo=new B\WordPressRepository($wpdb);
 if($mode==='install-storage'){$repo->install();}
 if($mode==='enroll'){$repo->enroll(2);}
 if($mode==='grant'){(new WP_User(2))->add_cap(B\PREPARE_SCOPE);}
 if($mode==='withdraw'){(new WP_User(2))->remove_cap(B\PREPARE_SCOPE);}
 if($mode==='poison'){$wpdb->query("ALTER TABLE {$p}proposals ADD INDEX unreviewed(version)");}
 if($mode==='repair'){$wpdb->query("ALTER TABLE {$p}proposals DROP INDEX unreviewed");}
 $check($mode,true);
}elseif($mode==='profile-negatives'){
 $check('exact_profile_admitted',B\profile_report()['ready']);
 $extra=static fn($a)=>array_merge($a,['unreviewed/extra.php']);add_filter('option_active_plugins',$extra);
 $check('unknown_active_plugin_blocked',!B\profile_report()['ready']);remove_filter('option_active_plugins',$extra);
 $path=WP_PLUGIN_DIR.'/hostinger/hostinger.php';$bytes=file_get_contents($path);
 try{file_put_contents($path,$bytes."\n// synthetic fingerprint drift\n");$check('main_hash_drift_blocked',!B\profile_report()['ready']);}finally{file_put_contents($path,$bytes);}
 $mu=WPMU_PLUGIN_DIR.'/unreviewed.php';try{file_put_contents($mu,"<?php // synthetic\n");$check('unknown_mu_blocked',!B\profile_report()['ready']);}finally{unlink($mu);}
 $path=wp_get_theme()->get_stylesheet_directory().'/style.css';$bytes=file_get_contents($path);
 try{file_put_contents($path,$bytes."\n/* drift */\n");$check('theme_hash_drift_blocked',!B\profile_report()['ready']);}finally{file_put_contents($path,$bytes);}
 $check('profile_restored',B\profile_report()['ready']);
}elseif($mode==='seed-and-backup'){
 foreach([['en','en_US','English'],['es','es_ES','Español']]as$l){if(!in_array($l[0],pll_languages_list(),true)){PLL()->model->add_language(['name'=>$l[2],'slug'=>$l[0],'locale'=>$l[1],'rtl'=>false,'term_group'=>$l[0]==='en'?0:1]);}}
 PLL()->options->set('default_lang','en');PLL()->options->save_all();
 $id=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'Synthetic restore source','post_content'=>'Synthetic only'],true);
 if(is_wp_error($id)){throw new RuntimeException('SOURCE_FIXTURE_FAILED');}pll_set_post_language($id,'en');wp_set_object_terms($id,[],'category');wp_set_object_terms($id,[],'post_tag');
 $before=$sourceHash();wp_set_current_user(2);$repo=new B\WordPressRepository($wpdb);$projection=B\wordpress_projection($id);
 $req=['request_id'=>str_repeat('a',32),'source_id'=>$id,'locale'=>'en','base_hash'=>$projection['base_hash'],'operation'=>'update-post','fields'=>['title'=>'Private intention one']];
 $one=$repo->create($req);$next=$req;$next['request_id']=str_repeat('b',32);$next['fields']['title']='Private intention two';$two=$repo->update($one['proposal_id'],1,$next);
 $check('two_private_versions',$two['version']===2);$check('replay_original_revision',$repo->create($req)===$one);
 $check('proposal_operations_preserve_source',$sourceHash()===$before);
 wp_set_current_user(1);(new WP_User(2))->remove_cap(B\PREPARE_SCOPE);wp_set_current_user(2);
 try{$repo->listing();throw new RuntimeException('SCOPE_NOT_PAUSED');}catch(DomainException $e){$check('writer_paused_by_scope_removal',$e->getMessage()==='blog_proposal_forbidden');}
 $rows=$snapshot($p);$check('backup_exact_counts',array_map('count',$rows)===['owners'=>1,'proposals'=>1,'revisions'=>2,'requests'=>2]);
 $ddl=[];foreach(array_keys($rows)as$s){$ddl[$s]=$wpdb->get_row("SHOW CREATE TABLE {$p}{$s}",ARRAY_N)[1];}
 $backup=['format'=>'synthetic-private-proposal-backup-v1','prefix'=>$p,'ddl'=>$ddl,'rows'=>$rows,'rowsSha256'=>hash('sha256',serialize($rows)),'sourceHash'=>$before];
 $file=ABSPATH.'.rc2-private-backup.json';file_put_contents($file,json_encode($backup,JSON_THROW_ON_ERROR));chmod($file,0600);
 $check('backup_private_mode',(fileperms($file)&0777)===0600);
}elseif($mode==='restore-and-retain'){
 $file=ABSPATH.'.rc2-private-backup.json';$backup=json_decode(file_get_contents($file),true,32,JSON_THROW_ON_ERROR);
 $check('backup_integrity',hash('sha256',serialize($backup['rows']))===$backup['rowsSha256']);
 $check('writers_still_paused',!(new WP_User(2))->has_cap('alexcuadra_prepare_post_changes'));
 $target='restore_ac_blog_proposal_';$old=$snapshot($p);
 foreach(['owners','proposals','revisions','requests']as$s){
  $exists=$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$target.$s));if($exists!=='0'){throw new RuntimeException('RESTORE_TARGET_NOT_EMPTY');}
 }
 foreach($backup['ddl']as$s=>$sql){$query=str_replace($p,$target,$sql);if($wpdb->query($query)===false){throw new RuntimeException('ISOLATED_RESTORE_DDL_FAILED');}}
 $wpdb->query('START TRANSACTION');try{foreach($backup['rows']as$s=>$rows){foreach($rows as$row){if($wpdb->insert($target.$s,$row)===false){throw new RuntimeException('RESTORE_INSERT_FAILED');}}}$wpdb->query('COMMIT');}catch(Throwable $e){$wpdb->query('ROLLBACK');throw $e;}
 $restored=$snapshot($target);$check('isolated_rows_identical',$restored===$backup['rows']);$check('original_private_tables_unchanged',$old===$snapshot($p));
 // schema_report uses only prefix and connection; restore has its own table prefix, not source tables.
 $originalPrefix=$wpdb->prefix;try{$wpdb->prefix='restore_';$check('restored_schema_admitted',B\schema_report($wpdb)['ready']);}finally{$wpdb->prefix=$originalPrefix;}
 foreach($restored['revisions']as$row){$doc=json_decode($row['document'],true,32,JSON_THROW_ON_ERROR);$hash=$doc['document_hash'];unset($doc['document_hash']);$check('restored_document_v'.$row['version'],B\digest($doc)===$hash&&$hash===$row['document_hash']);}
 $check('restored_head_and_owner',$restored['proposals'][0]['version']==='2'&&$restored['owners'][0]['owner_id']==='2');
 $check('restore_preserves_source',$sourceHash()===$backup['sourceHash']);
 $check('reader_permission_retained',(new WP_User(2))->has_cap('alexcuadra_read_public_posts'));
}elseif($mode==='assert-retained'){
 $rows=$snapshot($p);$check('withdrawal_retains_all_private_history',array_map('count',$rows)===['owners'=>1,'proposals'=>1,'revisions'=>2,'requests'=>2]);
 $u=new WP_User(2);$caps=array_keys(array_filter($u->allcaps));sort($caps);
 $baseline=['alexcuadra_apply_project_changes','alexcuadra_prepare_project_changes','alexcuadra_read_public_posts','alexcuadra_read_public_projects','alexcuadra_recover_project_changes','level_0','read','subscriber'];sort($baseline);
 $check('withdrawal_preserves_exact_baseline',$caps===$baseline);$check('reader_remains_active',is_plugin_active('alexcuadra-editorial-blog-read/alexcuadra-editorial-blog-read.php'));
}else{throw new RuntimeException('UNKNOWN_RELEASE_MODE');}
WP_CLI::line('SITE_RELEASE_LIFECYCLE_RESULT='.wp_json_encode(['mode'=>$mode,'passed'=>count($cases),'failed'=>0,'cases'=>$cases,'syntheticOnly'=>true]));
