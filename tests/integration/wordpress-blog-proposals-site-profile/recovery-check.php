<?php
use AlexCuadra\EditorialBlogProposals as B;
use AlexCuadra\BlogProposalRecovery as R;
if(wp_get_environment_type()!=='local'||DB_HOST!=='db'||getenv('BLOG_SITE_PROFILE_LAB')!=='verified-code-only'){throw new RuntimeException('ISOLATED_LAB_REQUIRED');}
require_once '/release-operations/recovery.php';
$cases=[];$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED:'.$name);}$cases[]=$name;};
$denied=static function($name,callable $fn,$expected)use($check){try{$fn();}catch(DomainException $e){$check($name,$e->getMessage()==='BLOG_RECOVERY_'.$expected);return;}throw new RuntimeException('NOT_REJECTED:'.$name);};
global $wpdb;wp_set_current_user(1);$base='/recovery-private';$prefix=$wpdb->prefix;
$fingerprint=static function()use($wpdb){$all=[];foreach(['posts'=>'ID','postmeta'=>'meta_id','terms'=>'term_id','term_taxonomy'=>'term_taxonomy_id','term_relationships'=>'object_id,term_taxonomy_id','termmeta'=>'meta_id']as$t=>$o){$all[$t]=$wpdb->get_results("SELECT * FROM {$wpdb->$t} ORDER BY $o",ARRAY_A);}return B\digest($all);};
$first=R\export_backup($base,'writers-stopped');$firstPath=$base.'/'.$first['backupDirectory'];
$check('initial_owner_only_backup',$first['counts']===['owners'=>1,'proposals'=>0,'revisions'=>0,'requests'=>0]);
$check('initial_backup_verifies',R\verify_backup($firstPath,$first['manifestSha256'])['tables']['owners']===[['owner_id'=>'2']]);
$denied('export_requires_pause_declaration',fn()=>R\export_backup($base,'not-paused'),'PAUSE_DECLARATION');
$denied('public_output_directory_rejected',fn()=>R\export_backup(ABSPATH,'writers-stopped'),'PRIVATE_DIRECTORY');
wp_set_current_user(2);$denied('subscriber_cannot_export',fn()=>R\export_backup($base,'writers-stopped'),'OPERATOR');wp_set_current_user(1);
$filtered=static function($all,$caps,$args,$user){if($user->ID===2){$all[B\PREPARE_SCOPE]=true;}return $all;};add_filter('user_has_cap',$filtered,99,4);
try{$denied('filtered_writer_permission_blocks_export',fn()=>R\export_backup($base,'writers-stopped'),'SCOPES_NOT_PAUSED');}finally{remove_filter('user_has_cap',$filtered,99);}
(new WP_User(2))->add_cap(B\PREPARE_SCOPE);$denied('active_writer_blocks_export',fn()=>R\export_backup($base,'writers-stopped'),'SCOPES_NOT_PAUSED');
foreach([['en','en_US','English'],['es','es_ES','Español']]as$l){if(!in_array($l[0],pll_languages_list(),true)){PLL()->model->add_language(['name'=>$l[2],'slug'=>$l[0],'locale'=>$l[1],'rtl'=>false,'term_group'=>$l[0]==='en'?0:1]);}}
PLL()->options->set('default_lang','en');PLL()->options->save_all();
$id=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'Recovery synthetic source','post_content'=>'Synthetic body'],true);
if(is_wp_error($id)){throw new RuntimeException('SOURCE_FIXTURE_FAILED');}pll_set_post_language($id,'en');wp_set_object_terms($id,[],'category');wp_set_object_terms($id,[],'post_tag');
$before=$fingerprint();wp_set_current_user(2);$repo=new B\WordPressRepository($wpdb);$projection=B\wordpress_projection($id);
$request=['request_id'=>str_repeat('c',32),'source_id'=>$id,'locale'=>'en','base_hash'=>$projection['base_hash'],'operation'=>'update-post','fields'=>['title'=>'Intención privada ñ 😀']];
$one=$repo->create($request);$next=$request;$next['request_id']=str_repeat('d',32);$next['fields']['title']='Segunda intención';$two=$repo->update($one['proposal_id'],1,$next);
wp_set_current_user(1);(new WP_User(2))->remove_cap(B\PREPARE_SCOPE);
$backup=R\export_backup($base,'writers-stopped');$path=$base.'/'.$backup['backupDirectory'];$sha=$backup['manifestSha256'];
$checked=R\verify_backup($path,$sha);$original=R\rows(R\handle(),$prefix);
$check('populated_backup_exact_counts',$backup['counts']===['owners'=>1,'proposals'=>1,'revisions'=>2,'requests'=>2]);
$check('export_does_not_write_database',$backup['databaseWritesPerformed']===false&&$original===$checked['tables']);
$check('unicode_and_emoji_preserved',str_contains($checked['tables']['revisions'][0]['document'],'ñ 😀'));
$check('private_directory_and_files',(fileperms($path)&0777)===0700&&(fileperms($path.'/tables.json')&0777)===0600&&(fileperms($path.'/manifest.json')&0777)===0600);
$again=R\export_backup($base,'writers-stopped');$check('second_export_does_not_replace_first',$again['backupDirectory']!==$backup['backupDirectory']&&R\verify_backup($path,$sha)['tables']===$original);
$denied('wrong_manifest_hash_rejected',fn()=>R\verify_backup($path,str_repeat('0',64)),'MANIFEST_HASH');
$raw=file_get_contents($path.'/tables.json');try{file_put_contents($path.'/tables.json',$raw.' ');$denied('payload_corruption_rejected',fn()=>R\verify_backup($path,$sha),'PAYLOAD_HASH');}finally{file_put_contents($path.'/tables.json',$raw);}
chmod($path.'/tables.json',0644);try{$denied('public_file_permissions_rejected',fn()=>R\verify_backup($path,$sha),'PRIVATE_FILE');}finally{chmod($path.'/tables.json',0600);}
$alias=$base.'/alias';symlink($path,$alias);try{$denied('symlink_directory_rejected',fn()=>R\verify_backup($alias,$sha),'PRIVATE_DIRECTORY');}finally{unlink($alias);}
file_put_contents($path.'/unexpected','synthetic');try{$denied('unexpected_file_rejected',fn()=>R\verify_backup($path,$sha),'FILE_SET');}finally{unlink($path.'/unexpected');}
$manifest=file_get_contents($path.'/manifest.json');$m=json_decode($manifest,true);$m['packageSha256']=str_repeat('0',64);$changed=R\encode($m);file_put_contents($path.'/manifest.json',$changed);
try{$denied('wrong_package_even_with_new_hash_rejected',fn()=>R\verify_backup($path,hash('sha256',$changed)),'MANIFEST');}finally{file_put_contents($path.'/manifest.json',$manifest);}
$broken=$original;$broken['owners'][0]['owner_id']='3';$denied('wrong_owner_rejected',fn()=>R\validate_rows($broken),'OWNER');
$broken=$original;array_shift($broken['revisions']);$denied('revision_gap_rejected',fn()=>R\validate_rows($broken),'REVISION_GAP');
$broken=$original;array_pop($broken['requests']);$denied('missing_receipt_rejected',fn()=>R\validate_rows($broken),'RECEIPT');
$broken=$original;$broken['proposals'][0]['sql']='DROP TABLE';$denied('unknown_row_field_rejected',fn()=>R\validate_rows($broken),'FIELDS');
$broken=$original;$broken['revisions'][0]['document_hash']=str_repeat('0',64);$denied('document_digest_rejected',fn()=>R\validate_rows($broken),'DOCUMENT');
$denied('occupied_target_never_overwritten',fn()=>R\restore_empty($path,$sha,'writers-stopped'),'RESTORE_TARGET_NOT_EMPTY');
$check('occupied_target_retained',$original===R\rows(R\handle(),$prefix));
$wpdb->query('START TRANSACTION');try{$denied('outer_transaction_rejected',fn()=>R\export_backup($base,'writers-stopped'),'CONNECTION');}finally{$wpdb->query('ROLLBACK');}
try{
 $wpdb->prefix='recovery_ok_';$restored=R\restore_empty($path,$sha,'writers-stopped');
 $check('restore_exact_rows_and_schema',R\rows(R\handle(),$wpdb->prefix)===$original&&B\schema_report($wpdb)['ready']);
 $check('restore_excludes_source_and_permissions',$restored['sourceTablesRestored']===false&&$restored['permissionsRestored']===false);
 (new WP_User(2))->add_cap(B\PREPARE_SCOPE);wp_set_current_user(2);$restoredRepo=new B\WordPressRepository($wpdb);
 $check('restored_get_latest_exact',$restoredRepo->get($one['proposal_id'])===$two);
 $check('restored_create_replay_original',$restoredRepo->create($request)===$one);
 $check('restored_update_replay_original',$restoredRepo->update($one['proposal_id'],1,$next)===$two);
 $check('restored_listing_owned',count($restoredRepo->listing()['items'])===1);
 wp_set_current_user(1);(new WP_User(2))->remove_cap(B\PREPARE_SCOPE);
 $denied('repeat_restore_rejected',fn()=>R\restore_empty($path,$sha,'writers-stopped'),'RESTORE_TARGET_NOT_EMPTY');
 $wpdb->prefix='recovery_fault_';
 $inject=static function($sql){if(str_starts_with($sql,'INSERT INTO `recovery_fault_ac_blog_proposal_requests`')){throw new DomainException('BLOG_RECOVERY_TEST_INSERT');}return $sql;};
 add_filter('query',$inject,99);
 try{$denied('insert_failure_propagates_without_retry',fn()=>R\restore_empty($path,$sha,'writers-stopped'),'TEST_INSERT');}finally{remove_filter('query',$inject,99);}
 $counts=array_map('count',R\rows(R\handle(),$wpdb->prefix));$check('failed_restore_rolls_back_all_rows',array_sum($counts)===0&&B\schema_report($wpdb)['ready']);
 $denied('partial_restore_requires_review_not_retry',fn()=>R\restore_empty($path,$sha,'writers-stopped'),'RESTORE_TARGET_NOT_EMPTY');
}finally{$wpdb->prefix=$prefix;wp_set_current_user(1);(new WP_User(2))->remove_cap(B\PREPARE_SCOPE);}
$check('original_private_rows_unchanged',$original===R\rows(R\handle(),$prefix));
$check('source_tables_preserved',$fingerprint()===$before);
$check('new_scope_removed',!(new WP_User(2))->has_cap(B\PREPARE_SCOPE));
WP_CLI::line('BLOG_PROPOSAL_RECOVERY_TEST_RESULT='.wp_json_encode(['passed'=>count($cases),'failed'=>0,'cases'=>$cases,'syntheticOnly'=>true,'sourceTablesPreserved'=>true,'scopeRemoved'=>true]));
