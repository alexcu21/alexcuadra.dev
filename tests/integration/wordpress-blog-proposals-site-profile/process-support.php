<?php
/** File-backed diagnostics and bounded synchronization; only synthetic CLI workers. */
function site_process_start(array $argv,string $marker):array {
    $out=tempnam(sys_get_temp_dir(),'site-worker-out-');$err=tempnam(sys_get_temp_dir(),'site-worker-err-');
    $process=proc_open($argv,[0=>['pipe','r'],1=>['file',$out,'w'],2=>['file',$err,'w']],$pipes);
    if(!is_resource($process)){throw new RuntimeException('WORKER_START_FAILED');}
    $job=['process'=>$process,'stdin'=>$pipes[0],'out'=>$out,'err'=>$err];
    site_process_marker($job,$marker);return $job;
}
function site_process_marker(array $job,string $marker):void {
    $deadline=microtime(true)+45;
    do{
        clearstatcache(true,$job['out']);clearstatcache(true,$job['err']);
        if(filesize($job['out'])>8388608||filesize($job['err'])>8388608){site_process_abort($job);throw new RuntimeException('WORKER_DIAGNOSTIC_BUDGET');}
        $text=file_get_contents($job['out']);
        foreach(explode("\n",$text)as$line){if(str_starts_with($line,$marker)){return;}}
        $state=proc_get_status($job['process']);
        if(!$state['running']){site_process_abort($job);throw new RuntimeException('WORKER_EXITED_WITHOUT_MARKER');}
        usleep(20000);
    }while(microtime(true)<$deadline);
    site_process_abort($job);throw new RuntimeException('WORKER_MARKER_TIMEOUT');
}
function site_process_go(array $job):void {fwrite($job['stdin'],"GO\n");fflush($job['stdin']);}
function site_process_abort(array $job):void {
    if(is_resource($job['process'])){proc_terminate($job['process'],9);}
    if(is_resource($job['stdin'])){fclose($job['stdin']);}
    if(is_resource($job['process'])){proc_close($job['process']);}
}
function site_process_result(array $job,string $marker):array {
    site_process_marker($job,$marker);$deadline=microtime(true)+30;
    do{$state=proc_get_status($job['process']);if(!$state['running']){break;}usleep(20000);}while(microtime(true)<$deadline);
    if($state['running']){site_process_abort($job);throw new RuntimeException('WORKER_EXIT_TIMEOUT');}
    if(is_resource($job['stdin'])){fclose($job['stdin']);}
    $close=proc_close($job['process']);$exit=$state['exitcode']>=0?$state['exitcode']:$close;
    if($exit!==0){throw new RuntimeException('WORKER_FAILED');}
    $lines=array_values(array_filter(explode("\n",file_get_contents($job['out'])),static fn($l)=>str_starts_with($l,$marker)));
    if(count($lines)!==1){throw new RuntimeException('WORKER_RESULT_MISSING');}
    return json_decode(substr($lines[0],strlen($marker)),true,32,JSON_THROW_ON_ERROR);
}
function workers(int $owner,int $source,string $mode,int $proposal,array $keys,array $titles):array {
    $jobs=[];
    try{
        foreach($keys as$i=>$key){$jobs[]=site_process_start([PHP_BINARY,'/usr/local/bin/wp','--quiet','eval-file',__DIR__.'/worker.php',(string)$owner,$key,$mode,(string)$proposal,(string)$source,$titles[$i]],'BLOG_PROPOSAL_WORKER_READY');}
        foreach($jobs as$job){site_process_go($job);}
        $results=[];foreach($jobs as$i=>$job){$results[]=site_process_result($job,'BLOG_PROPOSAL_WORKER_RESULT=');unset($jobs[$i]);}return $results;
    }finally{foreach($jobs as$job){site_process_abort($job);}}
}
