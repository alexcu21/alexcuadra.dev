<?php
require '/blog-proposals/wordpress-repository.php';
use AlexCuadra\EditorialBlogProposalLab\WordPressRepository;
use function AlexCuadra\EditorialBlogProposalLab\{wordpress_projection,base_hash};
use const AlexCuadra\EditorialBlogProposalLab\PREPARE_SCOPE;
global $wpdb,$wp_version,$cases;
require_once ABSPATH.'wp-admin/includes/plugin.php';
$cases=[];
function check($condition,$name){global $cases;if(!$condition){throw new RuntimeException('FAILED:'.$name);}$cases[]=$name;}
function denied(callable $fn,$code,$name){try{$fn();}catch(DomainException $e){check($e->getMessage()==='blog_proposal_'.$code,$name);return;}throw new RuntimeException('NOT_REJECTED:'.$name);}
function fingerprint():string {global $wpdb;$rows=[];foreach(['posts'=>'ID','postmeta'=>'meta_id','terms'=>'term_id','term_taxonomy'=>'term_taxonomy_id','term_relationships'=>'object_id,term_taxonomy_id','termmeta'=>'meta_id']as$table=>$order){$rows[$table]=$wpdb->get_results("SELECT * FROM {$wpdb->$table} ORDER BY $order",ARRAY_A);}return hash('sha256',serialize($rows));}
function counts():array {global $wpdb;$p=$wpdb->prefix.'ac_blog_lab_';return array_map(fn($suffix)=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $p$suffix"),['proposals','revisions','requests']);}
function requestFor(int $sourceId,string $key,array $fields,string $locale='en',string $operation='update-post'):array {
    $projection=wordpress_projection($sourceId);
    return ['request_id'=>str_pad(dechex(hexdec(substr($key,0,6))),32,'0',STR_PAD_LEFT),'source_id'=>$sourceId,'locale'=>$locale,'base_hash'=>$projection['base_hash'],'operation'=>$operation,'fields'=>$fields];
}
function workers(int $owner,int $source,string $mode,int $proposal,array $keys,array $titles):array {
    $all=[];
    foreach($keys as$i=>$key){
        $process=proc_open([PHP_BINARY,'/usr/local/bin/wp','--quiet','eval-file','/proposal-lab/worker.php',(string)$owner,$key,$mode,(string)$proposal,(string)$source,$titles[$i]],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process)){throw new RuntimeException('WORKER_START_FAILED');}
        $ready=false;
        for($n=0;$n<200&&($line=fgets($pipes[1]))!==false;$n++){if(trim($line)==='BLOG_PROPOSAL_WORKER_READY'){$ready=true;break;}}
        if(!$ready){throw new RuntimeException('WORKER_NOT_READY:'.stream_get_contents($pipes[2]));}
        $all[]=[$process,$pipes];
    }
    foreach($all as[$process,$pipes]){fwrite($pipes[0],"GO\n");fclose($pipes[0]);}
    $results=[];
    foreach($all as[$process,$pipes]){
        $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
        if($exit!==0){throw new RuntimeException('WORKER_FAILED:'.$err);}
        $lines=array_values(array_filter(explode("\n",$out),fn($l)=>str_starts_with($l,'BLOG_PROPOSAL_WORKER_RESULT=')));
        if(count($lines)!==1){throw new RuntimeException('WORKER_RESULT_MISSING');}
        $results[]=json_decode(substr($lines[0],strlen('BLOG_PROPOSAL_WORKER_RESULT=')),true,32,JSON_THROW_ON_ERROR);
    }
    return $results;
}

wp_set_current_user(1);
foreach([['en','en_US','English'],['es','es_ES','Español']]as$lang){
    if(!in_array($lang[0],pll_languages_list(),true)){$r=PLL()->model->add_language(['name'=>$lang[2],'slug'=>$lang[0],'locale'=>$lang[1],'rtl'=>false,'term_group'=>$lang[0]==='en'?0:1]);if(is_wp_error($r)){throw new RuntimeException('LANGUAGE_SETUP_FAILED');}}
}
PLL()->options->set('default_lang','en');PLL()->options->save_all();
$repo=new WordPressRepository($wpdb);$repo->install();
check(counts()===[0,0,0],'dedicated_empty_native_schema');
denied(fn()=>$repo->install(),'storage_not_empty','native_install_does_not_replace_tables');
$p=$wpdb->prefix.'ac_blog_lab_';
$engines=$wpdb->get_col($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (%s,%s,%s,%s)',$p.'owners',$p.'proposals',$p.'revisions',$p.'requests'));
check(count($engines)===4&&array_unique($engines)===['InnoDB'],'all_native_tables_innodb');
$charset=new ReflectionMethod($wpdb,'get_table_charset');
check($charset->invoke($wpdb,$p.'revisions')==='utf8mb4','wpdb_detects_full_utf8mb4_for_document_table');
$owners=[];
foreach(['first','other']as$name){$id=wp_create_user('proposal_'.$name,wp_generate_password(32),$name.'@example.test');if(is_wp_error($id)){throw new RuntimeException('USER_SETUP_FAILED');}$u=new WP_User($id);$u->set_role('subscriber');$repo->enroll($id);$owners[]=$id;}
[$owner,$other]=$owners;
check(!get_userdata($owner)->has_cap(PREPARE_SCOPE)&&!get_role('subscriber')->has_cap(PREPARE_SCOPE),'install_and_enroll_do_not_grant_scope');
$post=static function($title,$lang='en',$status='publish',$password='',$type='post'){
    $id=wp_insert_post(['post_title'=>$title,'post_content'=>'<!-- wp:paragraph --><p>Synthetic body</p><!-- /wp:paragraph -->','post_excerpt'=>'Synthetic excerpt',
        'post_type'=>$type,'post_status'=>$status,'post_password'=>$password,'post_date'=>$status==='future'?gmdate('Y-m-d H:i:s',time()+DAY_IN_SECONDS):current_time('mysql')],true);
    if(is_wp_error($id)){throw new RuntimeException('POST_SETUP_FAILED');}pll_set_post_language($id,$lang);
    wp_set_object_terms($id,[],'category');wp_set_object_terms($id,[],'post_tag');update_post_meta($id,'synthetic_private_meta','NOT_FOR_PROPOSAL');return $id;
};
$en=$post('Synthetic EN');$es=$post('Synthetic ES','es');pll_save_post_translations(['en'=>$en,'es'=>$es]);
$single=$post('Untranslated');$hiddenSource=$post('Source linked to draft');$hidden=$post('Hidden target','es','draft');pll_save_post_translations(['en'=>$hiddenSource,'es'=>$hidden]);
$privateSource=$post('Source linked to private');$private=$post('Private target','es','private');pll_save_post_translations(['en'=>$privateSource,'es'=>$private]);
$excluded=[$post('Draft','en','draft'),$post('Private','en','private'),$post('Protected','en','publish','synthetic'),$post('Future','en','future'),$post('Trash','en','trash'),$post('Page','en','publish','','page'),$post('Project','en','publish','','project'),2147483647];
$makeTerm=static function($taxonomy,$name,$lang){$r=wp_insert_term($name,$taxonomy);if(is_wp_error($r)){throw new RuntimeException('TERM_SETUP_FAILED');}$id=$r['term_id'];pll_set_term_language($id,$lang);return $id;};
$cat=$makeTerm('category','Synthetic category','en');$catEs=$makeTerm('category','Categoría sintética','es');
$tag=$makeTerm('post_tag','Synthetic tag','en');$tagEs=$makeTerm('post_tag','Etiqueta sintética','es');
pll_save_term_translations(['en'=>$cat,'es'=>$catEs]);pll_save_term_translations(['en'=>$tag,'es'=>$tagEs]);
wp_set_object_terms($en,[$cat],'category');wp_set_object_terms($en,[$tag],'post_tag');wp_set_object_terms($es,[$catEs],'category');wp_set_object_terms($es,[$tagEs],'post_tag');
wp_set_current_user(0);denied(fn()=>wordpress_projection($en),'forbidden','native_anonymous_cannot_project_body');
wp_set_current_user(1);denied(fn()=>wordpress_projection($en),'forbidden','native_admin_without_scope_denied');
wp_set_current_user($owner);denied(fn()=>$repo->listing(),'forbidden','native_subscriber_without_scope_denied');
$u=wp_get_current_user();$u->add_cap('alexcuadra_read_public_posts');denied(fn()=>wordpress_projection($en),'forbidden','public_read_scope_cannot_access_private_projection');$u->remove_cap('alexcuadra_read_public_posts');
$u->add_cap('alexcuadra_prepare_project_changes');denied(fn()=>$repo->listing(),'forbidden','project_prepare_scope_does_not_expand');$u->remove_cap('alexcuadra_prepare_project_changes');
$u->add_cap(PREPARE_SCOPE);$otherUser=new WP_User($other);$otherUser->add_cap(PREPARE_SCOPE);
check(!current_user_can('edit_posts')&&!current_user_can('manage_options'),'native_prepare_user_has_no_edit_or_admin');
$projection=wordpress_projection($en);
check($projection['source']['translations']===['en'=>$en,'es'=>$es],'real_reciprocal_polylang_group');
check($projection['source']['content']===get_post_field('post_content',$en)&&!str_contains(json_encode($projection),'NOT_FOR_PROPOSAL'),'private_projection_contains_only_allowed_source_columns');
check($projection['source']['categories']===[$cat]&&$projection['source']['tags']===[$tag],'native_assigned_category_and_tag');
check(count($projection['terms'])>=4,'native_catalog_contains_both_languages');
$cached=get_post($en);$cached->post_content='Stale object-cache content';wp_cache_set($en,$cached,'posts');
check(wordpress_projection($en)['source']['content']===$projection['source']['content'],'native_projection_ignores_stale_cached_body');clean_post_cache($en);
foreach($excluded as$id){denied(fn()=>wordpress_projection($id),'source','excluded_native_source_'.$id);}
$before=fingerprint();$r=requestFor($en,'a1',['title'=>'Proposed native title']);$one=$repo->create($r);$id=$one['proposal_id'];
check($one['document']['owner_id']===$owner&&$one['version']===1,'native_private_proposal_owner_and_version');
check($one['document']['changes']['title']===['before'=>'Synthetic EN','after'=>'Proposed native title'],'native_exact_before_after');
check($repo->create($r)===$one&&counts()===[1,1,1],'native_replay_has_no_duplicate_rows');
check($repo->get($id)===$one&&$repo->get($id,1)===$one,'native_owned_current_and_historic_reads');
$bad=$r;$bad['fields']['title']='Changed key payload';denied(fn()=>$repo->create($bad),'request_reused','native_request_key_conflict');
$bad=$r;$bad['owner_id']=$other;denied(fn()=>$repo->create($bad),'schema','client_cannot_supply_owner');
$r2=requestFor($en,'a2',['excerpt'=>'Updated proposed excerpt']);$two=$repo->update($id,1,$r2);
check($two['version']===2&&isset($two['document']['changes']['excerpt'])&&!isset($two['document']['changes']['title']),'native_update_replaces_patch_and_advances_version');
check($repo->get($id,1)===$one&&$repo->get($id)===$two,'native_old_review_retained');
$bad=$r2;$bad['request_id']=str_repeat('c',32);denied(fn()=>$repo->update($id,1,$bad),'version_conflict','native_version_conflict');
check($repo->update($id,1,$r2)===$two,'native_update_replay_after_head_advanced');
wp_set_current_user($other);denied(fn()=>$repo->get($id),'not_found','native_other_owner_cannot_read');denied(fn()=>$repo->get($id,1),'not_found','native_other_owner_cannot_read_history');denied(fn()=>$repo->update($id,2,$bad),'not_found','native_other_owner_cannot_update');
check($repo->listing()===['items'=>[],'next_after'=>null],'native_other_owner_inventory_empty');
$otherPlan=$repo->create($r);check($otherPlan['document']['owner_id']===$other&&$otherPlan['proposal_id']!==$id,'native_same_key_is_independent_by_owner');
wp_set_current_user($owner);wp_get_current_user()->remove_cap(PREPARE_SCOPE);
denied(fn()=>$repo->get($id),'forbidden','native_revocation_blocks_read');denied(fn()=>$repo->create($r),'forbidden','native_revocation_blocks_replay');
wp_get_current_user()->add_cap(PREPARE_SCOPE);
$translationFields=['title'=>'Traducción propuesta','content'=>'<p>Contenido explícito</p>','excerpt'=>'','categories'=>[$catEs],'tags'=>[$tagEs]];
foreach([$hiddenSource,$privateSource]as$blocked){
    $projected=wordpress_projection($blocked);check(isset($projected['source']['translations']['es']),'full_projection_includes_hidden_target_'.$blocked);
    $bad=requestFor($blocked,'b'.$blocked,$translationFields,'es','create-translation');
    denied(fn()=>$repo->create($bad),'translation_exists','hidden_target_blocks_duplicate_translation_'.$blocked);
}
$tr=requestFor($single,'a3',$translationFields,'es','create-translation');$translation=$repo->create($tr);
check($translation['document']['translation_intent']['target_id']===null&&$translation['document']['locale']==='es','native_translation_stores_private_intent_only');
$bad=requestFor($single,'a4',array_replace($translationFields,['categories'=>[$cat]]),'es','create-translation');
denied(fn()=>$repo->create($bad),'classification','native_translation_cannot_copy_source_language_terms');
$bad=requestFor($en,'a5',['categories'=>[$tag]]);denied(fn()=>$repo->create($bad),'classification','native_tag_not_category');
$sqlText="<script>alert('x')</script>\n'; DROP TABLE wp_posts;-- 🙂";
$literal=$repo->create(requestFor($en,'a6',['content'=>$sqlText]));
check($literal['document']['changes']['content']['after']===$sqlText,'wpdb_preserves_literal_html_quotes_and_unicode_without_execution');
check($before===fingerprint(),'native_proposal_operations_preserve_editorial_tables');
$baseline=counts();$wpdb->query("CREATE TRIGGER blog_lab_fail_receipt BEFORE INSERT ON {$p}requests FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic failure'");
if($wpdb->last_error){throw new RuntimeException('FAILURE_TRIGGER_SETUP_FAILED');}
$fail=requestFor($en,'a7',['title'=>'Will roll back']);
denied(fn()=>$repo->create($fail),'storage_failure','native_receipt_failure_rolls_back_create');
denied(fn()=>$repo->update($id,2,$fail),'storage_failure','native_receipt_failure_rolls_back_update');
check(counts()===$baseline&&$repo->get($id)===$two,'native_no_partial_head_revision_receipt_after_rollback');
$wpdb->query('DROP TRIGGER blog_lab_fail_receipt');
$wpdb->query('START TRANSACTION');denied(fn()=>$repo->create($r),'outer_transaction','native_outer_transaction_rejected');
check((int)$wpdb->get_var('SELECT @@in_transaction')===1,'native_outer_transaction_not_committed');$wpdb->query('ROLLBACK');

// Native source edits below are deliberate fixture changes, outside repository operations.
wp_set_current_user(1);wp_update_post(['ID'=>$en,'post_title'=>'Concurrent native source']);wp_set_current_user($owner);
$afterFixtureEdit=fingerprint();
check($repo->create($r)===$one,'native_replay_returns_original_after_source_changed');
$stale=$r;$stale['request_id']=str_repeat('d',32);denied(fn()=>$repo->update($id,2,$stale),'stale_base','fresh_native_source_invalidates_old_base');
$fresh=requestFor($en,'a8',['title'=>'New reviewed title']);$three=$repo->update($id,2,$fresh);
check($three['version']===3&&$three['document']['changes']['title']['before']==='Concurrent native source','native_rebase_generates_new_review');
check($afterFixtureEdit===fingerprint(),'native_rebase_does_not_write_source');
$newTarget=$post('Newly linked ES','es','draft');pll_save_post_translations(['en'=>$single,'es'=>$newTarget]);
check($repo->create($tr)===$translation,'translation_replay_remains_original_after_link_added');
$trNew=requestFor($single,'a9',$translationFields,'es','create-translation');
denied(fn()=>$repo->update($translation['proposal_id'],1,$trNew),'translation_exists','native_new_link_blocks_new_translation_revision');
$stable=fingerprint();

$concurrent=workers($owner,$en,'create',0,[str_repeat('e',32),str_repeat('e',32)],['Concurrent duplicate','Concurrent duplicate']);
check($concurrent[0]['ok']&&$concurrent[1]['ok']&&$concurrent[0]===$concurrent[1],'two_wordpress_processes_same_key_get_same_revision');
$raceId=$concurrent[0]['result']['proposal_id'];
$race=workers($owner,$en,'update',$raceId,[str_repeat('f',32),str_repeat('0',32)],['Writer A','Writer B']);
$success=array_values(array_filter($race,fn($r)=>$r['ok']));$failure=array_values(array_filter($race,fn($r)=>!$r['ok']));
check(count($success)===1&&count($failure)===1&&$failure[0]['error']==='blog_proposal_version_conflict','two_wordpress_processes_same_version_one_winner');
check($repo->get($raceId)['version']===2,'native_concurrent_head_advances_once');
$page=$repo->listing(0,1);check(count($page['items'])===1&&$page['next_after']===$page['items'][0]['proposal_id'],'native_owned_cursor_listing_bounded');
denied(fn()=>$repo->listing(0,21),'input','native_unbounded_inventory_rejected');
// Fill the owner's proposal quota; another owner remains independent.
$n=0;while(count($repo->listing(0,20)['items'])<16){$repo->create(requestFor($en,dechex(0x100+$n++),['title'=>'Quota plan '.$n]));}
$quota=requestFor($en,'fff1',['title'=>'Over quota']);denied(fn()=>$repo->create($quota),'quota','native_owner_proposal_quota');
check($repo->create($r)===$one,'native_replay_works_when_owner_quota_full');
wp_set_current_user($other);$independent=$repo->create(requestFor($en,'fff2',['title'=>'Other owner independent']));
check($independent['document']['owner_id']===$other,'native_full_owner_quota_does_not_block_other');
for($version=2;$version<=16;$version++){$repo->update($independent['proposal_id'],$version-1,requestFor($en,dechex(0x200+$version),['title'=>'Revision '.$version]));}
denied(fn()=>$repo->update($independent['proposal_id'],16,requestFor($en,'fff3',['title'=>'Over version quota'])),'quota','native_version_quota');
check($stable===fingerprint(),'native_concurrency_and_quotas_preserve_editorial_tables');
wp_set_current_user(1);foreach($owners as$userId){$user=new WP_User($userId);$user->remove_cap(PREPARE_SCOPE);}
check(!get_userdata($owner)->has_cap(PREPARE_SCOPE)&&!get_userdata($other)->has_cap(PREPARE_SCOPE),'synthetic_prepare_scopes_removed');
echo 'EDITORIAL_BLOG_PROPOSAL_WP_RESULT='.json_encode(['environment'=>'isolated-wordpress-mariadb-polylang','wordpress'=>$wp_version,'php'=>PHP_VERSION,
    'mariadb'=>$wpdb->get_var('SELECT VERSION()'),'polylang'=>get_plugin_data(WP_PLUGIN_DIR.'/polylang/polylang.php',false,false)['Version'],
    'passed'=>count($cases),'failed'=>0,'concurrentWordPressProcesses'=>true,'syntheticScopesRemoved'=>true,'sourceFingerprintsPreservedDuringProposalOperations'=>true,
    'httpTransportTested'=>false,'cases'=>$cases],JSON_UNESCAPED_UNICODE),"\n";
