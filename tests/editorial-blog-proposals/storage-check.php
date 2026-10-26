<?php
define('AC_BLOG_PROPOSALS_STORAGE_LAB',true);
require __DIR__.'/../../wordpress/experimental/editorial-blog-proposals/repository.php';
require __DIR__.'/storage-fixture.php';
use AlexCuadra\EditorialBlogProposalLab\Repository;
use function AlexCuadra\EditorialBlogProposalLab\base_hash;
use const AlexCuadra\EditorialBlogProposalLab\PREPARE_SCOPE;
$cases=[];
function check($ok,$name){global $cases;if(!$ok){throw new RuntimeException('FAILED:'.$name);}$cases[]=$name;}
function denied(callable $fn,$code,$name){try{$fn();}catch(DomainException $e){check($e->getMessage()==='blog_proposal_'.$code,$name);return;}throw new RuntimeException('NOT_REJECTED:'.$name);}
function counts(PDO $db):array {return array_map(fn($t)=>(int)$db->query('SELECT COUNT(*) FROM '.$t)->fetchColumn(),['blog_proposals','blog_revisions','blog_requests']);}
$root=sys_get_temp_dir().'/blog-proposal-storage-'.bin2hex(random_bytes(8));mkdir($root,0700);
function connection(string $path):PDO {if(!file_exists($path)){touch($path);chmod($path,0600);}return new PDO('sqlite:'.$path);}
function pair(string $path,string $mode,array $keys,array $titles=[]):array {
    $workers=[];
    foreach($keys as$i=>$key){
        $process=proc_open([PHP_BINARY,__DIR__.'/storage-worker.php',$path,$mode,$key,$titles[$i]??''],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process)||trim(fgets($pipes[1]))!=='READY'){throw new RuntimeException('WORKER_START_FAILED');}
        $workers[]=[$process,$pipes];
    }
    foreach($workers as[$process,$pipes]){fwrite($pipes[0],"GO\n");fclose($pipes[0]);}
    $results=[];
    foreach($workers as[$process,$pipes]){
        $output=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
        if($exit!==0||$error!==''){throw new RuntimeException('WORKER_FAILED:'.$error);}
        $results[]=json_decode(trim($output),true,32,JSON_THROW_ON_ERROR);
    }
    return $results;
}
try {
    [$actor,$source,$terms,$request]=fixture();
    $path=$root.'/main.sqlite';$db=connection($path);$repo=new Repository($db);$repo->install();
    check(counts($db)===[0,0,0],'fresh_empty_dedicated_schema');
    denied(fn()=>$repo->install(),'storage_not_empty','installation_never_replaces_existing_tables');
    $one=$repo->create($actor,$source,$terms,$request);$id=$one['proposal_id'];
    check($one['version']===1&&$one['document']['owner_id']===2,'create_private_version_one');
    check(counts($db)===[1,1,1],'create_commits_head_revision_request_together');
    check($repo->get($actor,$id)===$one,'get_owned_head');
    check($repo->get($actor,$id,1)===$one,'get_owned_immutable_revision');
    check($repo->create($actor,$source,$terms,$request)===$one&&counts($db)===[1,1,1],'same_request_returns_original_without_inserts');
    $reopened=new Repository(connection($path));
    check($reopened->create($actor,$source,$terms,$request)===$one,'replay_survives_new_database_connection');
    $drift=$source;$drift['title']='Changed source';
    check($reopened->create($actor,$drift,[],$request)===$one,'replay_does_not_replan_against_changed_source_or_catalog');
    $r=$request;$r['fields']['title']='Different payload';
    denied(fn()=>$repo->create($actor,$source,$terms,$r),'request_reused','same_key_different_payload_rejected');
    $r=$request;$r['request_id']=str_repeat('b',32);$r['fields']=['excerpt'=>'Proposed excerpt'];
    $two=$repo->update($actor,$id,1,$source,$terms,$r);
    check($two['version']===2&&count($two['document']['changes'])===1&&isset($two['document']['changes']['excerpt']),'update_replaces_patch_does_not_inherit_old_fields');
    check($two['document']['document_hash']!==$one['document']['document_hash'],'version_has_new_document_hash');
    check($repo->get($actor,$id,1)===$one&&$repo->get($actor,$id)===$two,'old_revision_retained_while_head_advances');
    check($repo->update($actor,$id,1,$drift,[],$r)===$two,'update_replay_returns_original_after_expected_version_is_old');
    denied(fn()=>$repo->update($actor,$id,2,$source,$terms,$r),'request_reused','retry_cannot_change_expected_version_with_same_key');
    $r2=$r;$r2['request_id']=str_repeat('c',32);
    denied(fn()=>$repo->update($actor,$id,1,$source,$terms,$r2),'version_conflict','stale_version_rejected');
    check(counts($db)===[1,2,2],'failed_update_keeps_head_history_requests_unchanged');
    denied(fn()=>$repo->update($actor,$id,2,$source,$terms,$request),'request_reused','request_key_cannot_cross_create_update');
    $other=$actor;$other['id']=9;
    denied(fn()=>$repo->get($other,$id),'not_found','other_owner_cannot_read_head');
    denied(fn()=>$repo->get($other,$id,1),'not_found','other_owner_cannot_read_revision');
    denied(fn()=>$repo->update($other,$id,2,$source,$terms,$r2),'not_found','other_owner_cannot_update');
    denied(fn()=>$repo->get($other,999),'not_found','missing_and_other_owner_share_same_error');
    check($repo->listing($other)===['items'=>[],'next_after'=>null],'other_owner_inventory_excludes_private_proposals');
    $otherOne=$repo->create($other,$source,$terms,$request);
    check($otherOne['proposal_id']!==$id&&$otherOne['document']['owner_id']===9,'same_request_key_is_independent_per_owner');
    check($otherOne['document']['document_hash']!==$one['document']['document_hash'],'document_hash_binds_proposal_and_owner_identity');
    foreach(['anonymous','revoked','reader-only','admin-only'] as$mode){
        $a=$actor;
        if($mode==='anonymous'){$a['authenticated']=false;}
        else{$a['capabilities']=$mode==='reader-only'?['alexcuadra_read_public_posts'=>true]:($mode==='admin-only'?['manage_options'=>true]:[]);}
        denied(fn()=>$repo->get($a,$id),'forbidden','read_denied_'.$mode);
        denied(fn()=>$repo->create($a,$source,$terms,$request),'forbidden','replay_checks_current_scope_'.$mode);
        denied(fn()=>$repo->listing($a),'forbidden','listing_denied_'.$mode);
    }
    $r=$request;$r['request_id']=str_repeat('d',32);$r['locale']='es';
    denied(fn()=>$repo->update($actor,$id,2,$source,$terms,$r),'identity_change','proposal_cannot_switch_language');
    $r=$request;$r['request_id']=str_repeat('e',32);$r['source_id']=241;
    denied(fn()=>$repo->update($actor,$id,2,$source,$terms,$r),'identity_change','proposal_cannot_switch_source');
    $r=$request;$r['request_id']=str_repeat('f',32);$r['operation']='create-translation';
    denied(fn()=>$repo->update($actor,$id,2,$source,$terms,$r),'identity_change','proposal_cannot_switch_operation');
    $r=$request;$r['request_id']=str_repeat('1',32);
    denied(fn()=>$repo->update($actor,$id,2,$drift,$terms,$r),'stale_base','source_drift_rejected_before_new_revision');
    $r['base_hash']=base_hash($drift,$terms);
    $three=$repo->update($actor,$id,2,$drift,$terms,$r);
    check($three['version']===3&&$three['document']['changes']['title']['before']==='Changed source','explicit_new_base_records_new_review');
    check($repo->get($actor,$id,1)===$one,'rebase_does_not_rewrite_old_review');
    $r=$request;$r['request_id']=str_repeat('2',32);$new=$repo->create($actor,$source,$terms,$r);
    $page=$repo->listing($actor,0,1);
    check($page===['items'=>[['proposal_id'=>$id,'version'=>3]],'next_after'=>$id],'owned_cursor_first_page_is_bounded_metadata_only');
    check($repo->listing($actor,$id,1)===['items'=>[['proposal_id'=>$new['proposal_id'],'version'=>1]],'next_after'=>null],'owned_cursor_excludes_other_owner_and_finishes');
    check($repo->listing($actor,PHP_INT_MAX)===['items'=>[],'next_after'=>null],'empty_cursor_page');
    denied(fn()=>$repo->listing($actor,0,21),'input','unbounded_listing_denied');
    denied(fn()=>$repo->get($actor,$id,99),'not_found','missing_revision_denied');

    $before=counts($db);$head=$repo->get($actor,$id);
    $db->exec("CREATE TRIGGER fail_request BEFORE INSERT ON blog_requests BEGIN SELECT RAISE(ABORT,'synthetic failure'); END");
    $r=$request;$r['request_id']=str_repeat('3',32);
    denied(fn()=>$repo->create($actor,$source,$terms,$r),'storage_failure','failed_request_insert_rolls_back_new_proposal');
    denied(fn()=>$repo->update($actor,$id,3,$source,$terms,$r),'storage_failure','failed_request_insert_rolls_back_head_and_revision');
    check(counts($db)===$before&&$repo->get($actor,$id)===$head,'rollback_leaves_no_partial_head_revision_or_receipt');
    $db->exec('DROP TRIGGER fail_request');
    check($repo->update($actor,$id,3,$source,$terms,$r)['version']===4,'same_request_can_retry_after_rolled_back_failure');
    $db->beginTransaction();
    denied(fn()=>$repo->create($actor,$source,$terms,$request),'outer_transaction','repository_never_commits_callers_transaction');
    check($db->inTransaction(),'outer_transaction_still_active');$db->rollBack();

    foreach(['proposals','revisions','requests','versions','bytes','document'] as$limit){
        $qdb=connection($root.'/quota-'.$limit.'.sqlite');$limits=[$limit=>in_array($limit,['bytes','document'],true)?100:1];$q=new Repository($qdb,$limits);$q->install();
        if(in_array($limit,['bytes','document'],true)){
            denied(fn()=>$q->create($actor,$source,$terms,$request),'quota','quota_'.$limit);
            check(counts($qdb)===[0,0,0],'quota_'.$limit.'_rollback');
        }else{
            $first=$q->create($actor,$source,$terms,$request);$r=$request;$r['request_id']=str_repeat('4',32);
            $fn=$limit==='proposals'?fn()=>$q->create($actor,$source,$terms,$r):fn()=>$q->update($actor,$first['proposal_id'],1,$source,$terms,$r);
            denied($fn,'quota','quota_'.$limit);check(counts($qdb)===[1,1,1],'quota_'.$limit.'_rollback');
            check($q->create($actor,$drift,[],$request)===$first,'replay_works_at_'.$limit.'_quota');
            if($limit==='proposals'){check($q->create($other,$source,$terms,$request)['document']['owner_id']===9,'full_owner_quota_does_not_block_other_owner');}
        }
        $q=null;$qdb=null;
    }
    denied(fn()=>new Repository($db,['proposals'=>17]),'limits','quota_ceiling_cannot_be_raised');
    denied(fn()=>new Repository($db,['bytes'=>0]),'limits','zero_quota_denied');

    $tdb=connection($root.'/translation.sqlite');$t=new Repository($tdb);$t->install();
    $r=$request;$r['locale']='es';$r['operation']='create-translation';$r['fields']=['title'=>'Traducción','content'=>'Contenido','excerpt'=>'','categories'=>[160],'tags'=>[]];
    $translation=$t->create($actor,$source,$terms,$r);
    check($translation['document']['translation_intent']['target_id']===null&&counts($tdb)===[1,1,1],'translation_stores_only_private_intent_not_a_post');
    check($tdb->query("SELECT COUNT(*) FROM sqlite_master WHERE name IN ('posts','wp_posts')")->fetchColumn()==0,'repository_has_no_source_post_tables');
    $linked=$source;$linked['translations']['es']=228;
    check($t->create($actor,$linked,[],$r)===$translation,'translation_replay_returns_old_intent_without_linking_again');
    $r['request_id']=str_repeat('5',32);$r['base_hash']=base_hash($linked,$terms);
    denied(fn()=>$t->update($actor,$translation['proposal_id'],1,$linked,$terms,$r),'translation_exists','new_translation_revision_rejects_newly_linked_target');
    $t=null;$tdb=null;

    $cpath=$root.'/concurrent.sqlite';$cdb=connection($cpath);$c=new Repository($cdb);$c->install();
    $concurrent=pair($cpath,'create',[str_repeat('6',32),str_repeat('6',32)]);
    check($concurrent[0]['ok']&&$concurrent[1]['ok']&&$concurrent[0]===$concurrent[1],'two_process_same_request_returns_exact_same_revision');
    check(counts($cdb)===[1,1,1],'two_process_same_request_creates_one_proposal');
    $concurrent=pair($cpath,'update',[str_repeat('7',32),str_repeat('8',32)],['Writer A','Writer B']);
    $success=array_values(array_filter($concurrent,fn($r)=>$r['ok']));$failure=array_values(array_filter($concurrent,fn($r)=>!$r['ok']));
    check(count($success)===1&&count($failure)===1&&$failure[0]['error']==='blog_proposal_version_conflict','two_process_same_version_has_one_winner');
    check(counts($cdb)===[1,2,2]&&$c->get($actor,1)['version']===2,'concurrent_update_no_lost_update_or_duplicate_revision');
    $c=null;$cdb=null;

    foreach(['crash-before'=>'BARRIER','crash-after'=>'COMMITTED'] as$mode=>$marker){
        $kpath=$root.'/'.$mode.'.sqlite';$kdb=connection($kpath);$k=new Repository($kdb);$k->install();
        $process=proc_open([PHP_BINARY,__DIR__.'/storage-worker.php',$kpath,$mode,str_repeat('9',32),''],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process)||trim(fgets($pipes[1]))!=='READY'){throw new RuntimeException('CRASH_WORKER_START_FAILED');}
        fwrite($pipes[0],"GO\n");fflush($pipes[0]);
        if(trim(fgets($pipes[1]))!==$marker){throw new RuntimeException('CRASH_BARRIER_FAILED');}
        proc_terminate($process,9);foreach($pipes as$pipe){fclose($pipe);}proc_close($process);
        $expected=$mode==='crash-before'?[0,0,0]:[1,1,1];
        $recoveryDb=connection($kpath);$recovery=new Repository($recoveryDb);
        check(counts($recoveryDb)===$expected,'sigkill_'.$mode.'_atomic_persistence');
        $r=$request;$r['request_id']=str_repeat('9',32);
        $recovered=$recovery->create($actor,$source,$terms,$r);
        check(counts($recoveryDb)===[1,1,1]&&$recovered['version']===1,'sigkill_'.$mode.'_retry_has_one_revision');
        check($recovery->create($actor,[],[],$r)===$recovered,'sigkill_'.$mode.'_replay_without_replanning');
        $recovery=null;$recoveryDb=null;$k=null;$kdb=null;
    }

    foreach(['missing-opt-in','wordpress-bootstrap'] as$mode){
        $entry=var_export(__DIR__.'/../../wordpress/experimental/editorial-blog-proposals/repository.php',true);
        $prefix=$mode==='wordpress-bootstrap'?"define('AC_BLOG_PROPOSALS_STORAGE_LAB',true);define('ABSPATH','/synthetic/');":'';
        $script=$prefix.'require '.$entry.';try{new \\AlexCuadra\\EditorialBlogProposalLab\\Repository(new PDO("sqlite::memory:"));exit(2);}catch(DomainException $e){echo $e->getMessage();}';
        $process=proc_open([PHP_BINARY,'-r',$script],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
        check($exit===0&&$err===''&&$out==='blog_proposal_storage_lab_only','repository_guard_'.$mode);
    }

    $raw=$db->query('SELECT document FROM blog_revisions WHERE proposal_id=1 AND version=1')->fetchColumn();
    $db->exec("UPDATE blog_revisions SET document='{}' WHERE proposal_id=1 AND version=1");
    denied(fn()=>$repo->get($actor,1,1),'corrupt_document','tampered_revision_never_returned');
    $restore=$db->prepare('UPDATE blog_revisions SET document=? WHERE proposal_id=1 AND version=1');$restore->execute([$raw]);
    check($repo->get($actor,1,1)===$one,'restored_synthetic_document_matches_original');
    check((fileperms($path)&0777)===0600&&(fileperms($root)&0777)===0700,'test_files_and_directory_private');
    check($db->query('PRAGMA integrity_check')->fetchColumn()==='ok','sqlite_integrity_after_transactions_and_failures');
    echo json_encode(['environment'=>'isolated-sqlite-file-lab','php'=>PHP_VERSION,'sqlite'=>$db->query('SELECT sqlite_version()')->fetchColumn(),
        'passed'=>count($cases),'failed'=>0,'concurrentProcesses'=>true,'wordpress'=>false,'network'=>false,'sourceWrites'=>false,
        'cases'=>$cases],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),"\n";
} finally {
    $repo=null;$reopened=null;$db=null;
    foreach(glob($root.'/*') as$file){unlink($file);}rmdir($root);
}
