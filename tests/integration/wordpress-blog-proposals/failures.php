<?php
require_once '/blog-proposals/wordpress-repository.php';
use AlexCuadra\EditorialBlogProposalLab\WordPressRepository;
use function AlexCuadra\EditorialBlogProposalLab\wordpress_projection;
use const AlexCuadra\EditorialBlogProposalLab\PREPARE_SCOPE;
global $wpdb;
$cases=[];$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED: '.$name);}$cases[]=$name;};
$users=[];$report=['environment'=>'isolated-mariadb-connection-and-process-faults','connectionLossVerified'=>false,'processKillVerified'=>false];
$count=static function()use($wpdb){$p=$wpdb->prefix.'ac_blog_lab_';return array_map(fn($s)=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $p$s"),['proposals','revisions','requests']);};
$snapshot=static function()use($wpdb){$data=[];foreach(['posts'=>'ID','postmeta'=>'meta_id','terms'=>'term_id','term_taxonomy'=>'term_taxonomy_id','term_relationships'=>'object_id,term_taxonomy_id','termmeta'=>'meta_id']as$t=>$order){$data[]=$wpdb->get_results("SELECT * FROM {$wpdb->$t} ORDER BY $order",ARRAY_A);}return hash('sha256',serialize($data));};
$startWorker=static function($owner,$key,$mode,$source){
    $process=proc_open([PHP_BINARY,'/usr/local/bin/wp','--quiet','eval-file','/proposal-lab/failure-worker.php',(string)$owner,$key,$mode,(string)$source],
        [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
    if(!is_resource($process)){throw new RuntimeException('WORKER_START_FAILED');}
    $ready=false;for($n=0;$n<200&&($line=fgets($pipes[1]))!==false;$n++){if(trim($line)==='PROPOSAL_FAILURE_READY'){$ready=true;break;}}
    if(!$ready){throw new RuntimeException('WORKER_NOT_READY');}fwrite($pipes[0],"GO\n");fflush($pipes[0]);return [$process,$pipes];
};
try{
    wp_set_current_user(1);$repo=new WordPressRepository($wpdb);
    $owner=wp_create_user('faults'.bin2hex(random_bytes(4)),wp_generate_password(32),'faults'.bin2hex(random_bytes(4)).'@example.test');
    if(is_wp_error($owner)){throw new RuntimeException('USER_SETUP_FAILED');}$user=new WP_User($owner);$user->set_role('subscriber');$repo->enroll($owner);$user->add_cap(PREPARE_SCOPE);$users[]=$user;
    $source=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'Fault fixture source','post_content'=>'SOURCE_UNCHANGED'],true);
    if(is_wp_error($source)){throw new RuntimeException('POST_SETUP_FAILED');}pll_set_post_language($source,'en');wp_set_object_terms($source,[],'category');wp_set_object_terms($source,[],'post_tag');
    wp_set_current_user($owner);$projection=wordpress_projection($source);$beforeSource=$snapshot();$sequence=0;
    $makeRequest=static fn($key)=>['request_id'=>$key,'source_id'=>$source,'locale'=>'en','base_hash'=>$projection['base_hash'],'operation'=>'update-post','fields'=>['title'=>'Fault proposal']];
    foreach(['kill-connection'=>'connection_lost','reset-mode-and-kill'=>'connection_lost','close-handle'=>'connection_lost','replace-handle'=>'connection_lost','transaction-control'=>'transaction_control','guard-order'=>'connection_guard_changed']as$mode=>$code){
        $key=str_pad(dechex(0x5000+$sequence++),32,'0',STR_PAD_LEFT);$before=$count();
        [$process,$pipes]=$startWorker($owner,$key,$mode,$source);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
        $lines=array_values(array_filter(explode("\n",$out),fn($l)=>str_starts_with($l,'PROPOSAL_FAILURE_RESULT=')));
        if($exit!==0||count($lines)!==1){throw new RuntimeException('FAULT_WORKER_FAILED');}$result=json_decode(substr($lines[0],strlen('PROPOSAL_FAILURE_RESULT=')),true,32,JSON_THROW_ON_ERROR);
        $check($mode.'_fails_closed',!$result['ok']&&$result['error']==='blog_proposal_'.$code);
        if(in_array($mode,['kill-connection','reset-mode-and-kill','close-handle'],true)){$check($mode.'_never_reconnects_original_handle',$result['handleUnchanged']===true);}
        $check($mode.'_rolls_back_all_private_records',$count()===$before);
        $recovered=$repo->create($makeRequest($key));$check($mode.'_fresh_request_retry_creates_one_review',$recovered['version']===1&&$count()===array_map(fn($n)=>$n+1,$before));
        $check($mode.'_retry_replays_exact_original',$repo->create($makeRequest($key))===$recovered);
    }
    $report['connectionLossVerified']=true;
    foreach(['crash-before'=>'PROPOSAL_FAILURE_BARRIER','crash-after'=>'PROPOSAL_FAILURE_COMMITTED']as$mode=>$marker){
        $key=str_pad(dechex(0x6000+$sequence++),32,'0',STR_PAD_LEFT);$before=$count();
        [$process,$pipes]=$startWorker($owner,$key,$mode,$source);$seen=false;
        for($n=0;$n<200&&($line=fgets($pipes[1]))!==false;$n++){if(trim($line)===$marker){$seen=true;break;}}
        if(!$seen){throw new RuntimeException('CRASH_BARRIER_MISSING');}proc_terminate($process,9);foreach($pipes as$pipe){fclose($pipe);}proc_close($process);
        // A new owned operation waits for the server to release the killed writer's owner lock.
        $repo->listing();$expected=$mode==='crash-before'?$before:array_map(fn($n)=>$n+1,$before);
        $check($mode.'_has_expected_atomic_persistence',$count()===$expected);
        $recovered=$repo->create($makeRequest($key));$check($mode.'_retry_has_only_one_new_review',$count()===array_map(fn($n)=>$n+1,$before)&&$recovered['version']===1);
        $check($mode.'_replay_is_exact',$repo->create($makeRequest($key))===$recovered);
    }
    $report['processKillVerified']=true;
    $check('connection_and_process_faults_never_change_editorial_tables',$snapshot()===$beforeSource);
}catch(Throwable $e){$report['failure']=preg_match('/^(FAILED: [a-z0-9_-]+|[A-Z_]+)$/',$e->getMessage())?$e->getMessage():'CONNECTION_FAULT_LAB_FAILED';}
finally{
    foreach($users as$user){$user->remove_cap(PREPARE_SCOPE);}
    $report['syntheticCapabilitiesRemoved']=true;foreach($users as$user){$report['syntheticCapabilitiesRemoved']=$report['syntheticCapabilitiesRemoved']&&!(new WP_User($user->ID))->has_cap(PREPARE_SCOPE);}
    if(!$report['syntheticCapabilitiesRemoved']){$report['failure']=$report['failure']??'SYNTHETIC_CLEANUP_FAILED';}
}
$report['passed']=count($cases);$report['failed']=isset($report['failure'])?1:0;$report['cases']=$cases;
WP_CLI::line('EDITORIAL_BLOG_PROPOSAL_FAULT_RESULT='.wp_json_encode($report));if($report['failed']){WP_CLI::halt(1);}
