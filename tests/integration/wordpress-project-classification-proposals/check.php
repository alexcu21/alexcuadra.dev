<?php
require_once '/classification-proposals/wordpress-repository.php';
use AlexCuadra\ProjectClassificationProposalLab\WordPressRepository;
use function AlexCuadra\ProjectClassificationProposalLab\{wordpress_projection,base_hash};
use const AlexCuadra\ProjectClassificationProposalLab\SCOPES;
global $wpdb,$wp_version,$cases;
$cases=[];
function check($ok,$name){global $cases;if(!$ok){throw new RuntimeException('FAILED:'.$name);}$cases[]=$name;}
function denied(callable $fn,$code,$name){try{$fn();}catch(DomainException $e){check($e->getMessage()==='project_classification_proposal_'.$code,$name);return;}throw new RuntimeException('NOT_REJECTED:'.$name);}
function fingerprint():string{global $wpdb;$rows=[];foreach(['posts'=>'ID','postmeta'=>'meta_id','terms'=>'term_id','term_taxonomy'=>'term_taxonomy_id','term_relationships'=>'object_id,term_taxonomy_id','termmeta'=>'meta_id']as$t=>$order){$rows[$t]=$wpdb->get_results("SELECT * FROM {$wpdb->$t} ORDER BY $order",ARRAY_A);}return hash('sha256',serialize($rows));}
function counts():array{global $wpdb;$p=$wpdb->prefix.'ac_classification_lab_';return array_map(fn($s)=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $p$s"),['proposals','revisions','requests']);}
function requestFor(int $id,string $key,array $fields,string $locale='en'):array{
    $p=wordpress_projection($id,$locale);return ['request_id'=>md5($key),'project_id'=>$id,'locale'=>$locale,'base_hash'=>$p['base_hash'],'fields'=>$fields];
}
function workers(int $owner,int $source,string $mode,int $proposal,array $keys,array $values):array {
    $all=[];
    foreach($keys as$i=>$key){
        $process=proc_open([PHP_BINARY,'/usr/local/bin/wp','--quiet','eval-file','/classification-lab/worker.php',(string)$owner,$key,$mode,(string)$proposal,(string)$source,$values[$i]],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process)){throw new RuntimeException('WORKER_START_FAILED');}
        $ready=false;
        for($n=0;$n<200&&($line=fgets($pipes[1]))!==false;$n++){if(trim($line)==='CLASSIFICATION_PROPOSAL_WORKER_READY'){$ready=true;break;}}
        if(!$ready){throw new RuntimeException('WORKER_NOT_READY:'.stream_get_contents($pipes[2]));}
        $all[]=[$process,$pipes];
    }
    foreach($all as[$process,$pipes]){fwrite($pipes[0],"GO\n");fclose($pipes[0]);}
    $results=[];
    foreach($all as[$process,$pipes]){
        $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
        if($exit!==0){throw new RuntimeException('WORKER_FAILED:'.$err);}
        $lines=array_values(array_filter(explode("\n",$out),fn($l)=>str_starts_with($l,'CLASSIFICATION_PROPOSAL_WORKER_RESULT=')));
        if(count($lines)!==1){throw new RuntimeException('WORKER_RESULT_MISSING');}
        $results[]=json_decode(substr($lines[0],strlen('CLASSIFICATION_PROPOSAL_WORKER_RESULT=')),true,32,JSON_THROW_ON_ERROR);
    }
    return $results;
}
wp_set_current_user(1);$repo=new WordPressRepository($wpdb);$repo->install();
denied(fn()=>$repo->install(),'storage_not_empty','installation_refuses_existing_storage');
$p=$wpdb->prefix.'ac_classification_lab_';
foreach(['owners','proposals','revisions','requests']as$s){$row=$wpdb->get_row($wpdb->prepare('SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$p.$s),ARRAY_A);check($row===['ENGINE'=>'InnoDB','TABLE_COLLATION'=>'utf8mb4_bin'],'native_engine_and_collation_'.$s);}
foreach([['en','en_US','English'],['es','es_ES','Español']]as$l){PLL()->model->add_language(['name'=>$l[2],'slug'=>$l[0],'locale'=>$l[1],'rtl'=>false,'term_group'=>$l[0]==='en'?0:1]);}
PLL()->options->set('default_lang','en');PLL()->options->save_all();
$owners=[];foreach(['owner','other','unready']as$n){$id=wp_create_user('classification_'.$n,wp_generate_password(32),$n.'@example.test');if(is_wp_error($id)){throw new RuntimeException('USER_SETUP');}$u=new WP_User($id);$u->set_role('subscriber');$owners[]=$id;}
[$owner,$other,$unready]=$owners;$repo->enroll($owner);$repo->enroll($other);
$post=static function($name,$locale='en',$status='publish',$password='',$type='project'){
    $id=wp_insert_post(['post_type'=>$type,'post_title'=>$name,'post_content'=>'PRIVATE_BODY','post_status'=>$status,'post_password'=>$password],true);
    if(is_wp_error($id)){throw new RuntimeException('POST_SETUP');}pll_set_post_language($id,$locale);update_post_meta($id,'secret','PRIVATE_META');return $id;
};
$term=static function($tax,$name,$locale='en'){$r=wp_insert_term($name,$tax,['description'=>'PRIVATE_DESCRIPTION']);if(is_wp_error($r)){throw new RuntimeException('TERM_SETUP');}$id=$r['term_id'];pll_set_term_language($id,$locale);update_term_meta($id,'secret','PRIVATE_TERM_META');return $id;};
$en=$post('Public EN');$es=$post('Público ES','es');$witness=$post('Other public');$private=$post('Private','es','private');
$cat=$term('project_category','Category');$cat2=$term('project_category','Other category');$catEs=$term('project_category','Categoría','es');
$tech=$term('project_technology','Technology');$tech2=$term('project_technology','Other technology');$techEs=$term('project_technology','Tecnología','es');
$unused=$term('project_category','PRIVATE_UNUSED');$privateOnly=$term('project_category','PRIVATE_ONLY','es');$blogCat=$term('category','Blog category');
pll_save_term_translations(['en'=>$cat,'es'=>$catEs]);pll_save_term_translations(['en'=>$tech,'es'=>$techEs]);
wp_set_object_terms($en,[$cat],'project_category');wp_set_object_terms($en,[$tech],'project_technology');
wp_set_object_terms($es,[$catEs],'project_category');wp_set_object_terms($es,[$techEs],'project_technology');
wp_set_object_terms($witness,[$cat2],'project_category');wp_set_object_terms($witness,[$tech2],'project_technology');wp_set_object_terms($private,[$privateOnly],'project_category');
wp_set_current_user(0);denied(fn()=>wordpress_projection($en,'en'),'forbidden','native_anonymous_denied');
wp_set_current_user(1);denied(fn()=>$repo->listing(),'forbidden','admin_without_scopes_denied');
foreach($owners as$id){$u=new WP_User($id);foreach(SCOPES as$cap){if($cap!=='read'){$u->add_cap($cap);}}}
wp_set_current_user($unready);denied(fn()=>$repo->listing(),'owner_not_ready','unenrolled_scoped_user_denied');
wp_set_current_user($owner);check(!current_user_can('edit_posts')&&!current_user_can('manage_options'),'subscriber_keeps_no_broad_permissions');
denied(fn()=>$repo->install(),'operator','subscriber_cannot_install');
denied(fn()=>$repo->enroll($unready),'operator','subscriber_cannot_enroll');
$context=wordpress_projection($en,'en');
check(array_keys($context)===['source','catalog','base_hash'],'closed_context');
check(array_column($context['catalog']['categories'],'id')===[$cat,$cat2],'public_witness_catalog_excludes_unused_private_blog');
check($context['source']['categories'][0]['translations'][0]['id']===$catEs,'reciprocal_term_translation_preserved');
check(!str_contains(json_encode($context),'PRIVATE'),'no_body_meta_or_private_term_leak');
foreach([$post('Draft','en','draft'),$private,$post('Protected','en','publish','secret'),$post('Blog','en','publish','','post'),PHP_INT_MAX]as$id){denied(fn()=>wordpress_projection($id,'en'),'source','unavailable_source_'.$id);}
denied(fn()=>wordpress_projection($en,'es'),'source','exact_source_language');
$before=fingerprint();$r=requestFor($en,'one',['categories'=>[$cat2]]);$one=$repo->create($r);$id=$one['proposal_id'];
check(counts()===[1,1,1]&&$one['version']===1&&$one['document']['owner_id']===$owner,'atomic_initial_private_revision');
check($one['document']['changes']['categories']['before'][0]['id']===$cat&&$one['document']['changes']['categories']['after'][0]['id']===$cat2,'exact_native_before_after');
check($repo->create($r)===$one&&counts()===[1,1,1],'durable_replay_without_duplicate');
check((new WordPressRepository($wpdb))->get($id)===$one,'new_repository_reads_durable_document');
$bad=$r;$bad['fields']['categories']=[];denied(fn()=>$repo->create($bad),'request_reused','same_key_different_payload');
$bad=$r;$bad['owner_id']=$other;denied(fn()=>$repo->create($bad),'schema','client_owner_rejected');
$r2=requestFor($en,'two',['technologies'=>[]]);$two=$repo->update($id,1,$r2);
check($two['version']===2&&array_keys($two['document']['changes'])===['technologies'],'new_version_replaces_entire_patch');
check($repo->get($id,1)===$one&&$repo->get($id)===$two,'immutable_history_and_current_head');
check($repo->update($id,1,$r2)===$two,'update_replay_before_stale_version_check');
$bad=$r2;$bad['request_id']=md5('conflict');denied(fn()=>$repo->update($id,1,$bad),'version_conflict','version_conflict');
wp_set_current_user($other);denied(fn()=>$repo->get($id),'not_found','other_owner_read_denied');denied(fn()=>$repo->get($id,1),'not_found','other_owner_history_denied');denied(fn()=>$repo->update($id,2,$bad),'not_found','other_owner_update_denied');
check($repo->listing()===['items'=>[],'next_after'=>null],'owner_inventory_isolated');check($repo->create($r)['document']['owner_id']===$other,'same_key_separate_owner');
wp_set_current_user($owner);
foreach(array_slice(SCOPES,1)as$cap){wp_get_current_user()->remove_cap($cap);denied(fn()=>$repo->get($id),'forbidden','revoked_read_'.$cap);denied(fn()=>$repo->create($r),'forbidden','revoked_replay_'.$cap);wp_get_current_user()->add_cap($cap);}
foreach([$catEs,$tech,$unused,$blogCat]as$termId){$bad=requestFor($en,'wrong-'.$termId,['categories'=>[$termId]]);denied(fn()=>$repo->create($bad),'classification','invalid_native_term_'.$termId);}
$spanish=$repo->create(requestFor($es,'spanish',['technologies'=>[]],'es'));
check($spanish['document']['locale']==='es'&&$spanish['document']['changes']['technologies']['before'][0]['id']===$techEs,'Spanish_exact_assignments');
check(fingerprint()===$before,'private_operations_preserve_source_tables');
$baseline=counts();$wpdb->query("CREATE TRIGGER classification_fail_receipt BEFORE INSERT ON {$p}requests FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic failure'");if($wpdb->last_error){throw new RuntimeException('TRIGGER_SETUP');}
$fail=requestFor($en,'fail',['categories'=>[]]);denied(fn()=>$repo->create($fail),'storage_failure','receipt_failure_rolls_back_create');denied(fn()=>$repo->update($id,2,$fail),'storage_failure','receipt_failure_rolls_back_update');
check(counts()===$baseline&&$repo->get($id)===$two,'no_partial_head_revision_or_receipt');$wpdb->query('DROP TRIGGER classification_fail_receipt');
$wpdb->query('START TRANSACTION');denied(fn()=>$repo->create($r),'outer_transaction','outer_transaction_denied');check((int)$wpdb->get_var('SELECT @@in_transaction')===1,'caller_transaction_preserved');$wpdb->query('ROLLBACK');
// Deliberate fixture changes; repository has no source writer.
wp_set_object_terms($en,[],'project_category');$stable=fingerprint();
check($repo->create($r)===$one,'old_replay_survives_source_change');$bad=$r;$bad['request_id']=md5('stale');denied(fn()=>$repo->update($id,2,$bad),'stale_base','assignment_drift_invalidates_base');
$three=$repo->update($id,2,requestFor($en,'fresh',['categories'=>[$cat2]]));check($three['version']===3&&$three['document']['changes']['categories']['before']===[],'explicit_rebase_new_review');
check(fingerprint()===$stable,'rebase_preserves_sources');
$cached=get_post($en);$cached->post_status='publish';wp_cache_set($en,$cached,'posts');$wpdb->update($wpdb->posts,['post_status'=>'draft'],['ID'=>$en]);
denied(fn()=>wordpress_projection($en,'en'),'source','raw_status_overrides_stale_post_cache');$wpdb->update($wpdb->posts,['post_status'=>'publish'],['ID'=>$en]);clean_post_cache($en);
register_taxonomy_for_object_type('project_category','post');denied(fn()=>wordpress_projection($en,'en'),'dependencies','shared_blog_taxonomy_denied');unregister_taxonomy_for_object_type('project_category','post');
wp_set_object_terms($witness,[$cat,$cat2],'project_category');$stable=fingerprint();$race=workers($owner,$en,'create',0,[md5('duplicate'),md5('duplicate')],[(string)$cat2,(string)$cat2]);
check($race[0]['ok']&&$race[0]===$race[1],'concurrent_same_request_returns_exact_document');$raceId=$race[0]['result']['proposal_id'];
$race=workers($owner,$en,'update',$raceId,[md5('writer-a'),md5('writer-b')],[(string)$cat2,(string)$cat]);
$win=array_values(array_filter($race,fn($r)=>$r['ok']));$loss=array_values(array_filter($race,fn($r)=>!$r['ok']));
check(count($win)===1&&count($loss)===1&&$loss[0]['error']==='project_classification_proposal_version_conflict','concurrent_expected_version_one_winner');
check($repo->get($raceId)['version']===2,'concurrent_head_advances_once');
$page=$repo->listing(0,1);check(count($page['items'])===1&&$page['next_after']===$id,'bounded_owner_cursor');denied(fn()=>$repo->listing(0,21),'input','unbounded_listing_denied');
check(fingerprint()===$stable,'concurrent_operations_preserve_sources');
wp_set_current_user(1);foreach($owners as$uid){$u=new WP_User($uid);foreach(array_slice(SCOPES,1)as$cap){$u->remove_cap($cap);}}
foreach($owners as$uid){foreach(array_slice(SCOPES,1)as$cap){check(!get_userdata($uid)->has_cap($cap),'scope_removed_'.$uid.'_'.$cap);}}
check(!get_role('subscriber')->has_cap(SCOPES[1])&&!get_role('subscriber')->has_cap(SCOPES[2]),'no_role_wide_grants');
echo 'CLASSIFICATION_PROPOSAL_WP_RESULT='.json_encode(['wordpress'=>$wp_version,'php'=>PHP_VERSION,'mariadb'=>$wpdb->get_var('SELECT VERSION()'),
    'passed'=>count($cases),'failed'=>0,'concurrentWordPressProcesses'=>true,'syntheticScopesRemoved'=>true,'httpTransportTested'=>false,'sourceFingerprintsPreservedDuringProposalOperations'=>true,'cases'=>$cases]),"\n";
