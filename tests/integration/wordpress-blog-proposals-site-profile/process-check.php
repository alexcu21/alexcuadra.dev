<?php
require __DIR__.'/process-support.php';
$cases=[];
function process_check($ok,$name){global $cases;if(!$ok){throw new RuntimeException('FAILED:'.$name);}$cases[]=$name;}
foreach(['normal','noisy','buffered']as$mode){
    $code=$mode==='noisy'?'for($i=0;$i<1000;$i++){echo "diagnostic line\n";}fwrite(STDERR,str_repeat("diagnostic\n",20000));':'';
    if($mode==='buffered'){$code.='ob_start();';}
    $code.='echo "READY\n";while(ob_get_level()>0){ob_end_flush();}flush();fgets(STDIN);echo "RESULT={\"ok\":true}\n";';
    $job=site_process_start([PHP_BINARY,'-r',$code],'READY');site_process_go($job);$result=site_process_result($job,'RESULT=');
    process_check($result===['ok'=>true],$mode.'_worker_completes');
    if($mode==='noisy'){process_check(filesize($job['err'])>65536&&substr_count(file_get_contents($job['out']),'diagnostic line')===1000,'diagnostics_retained_without_pipe_deadlock');}
    unlink($job['out']);unlink($job['err']);
}
try{site_process_start([PHP_BINARY,'-r','exit(2);'],'READY');throw new RuntimeException('EXPECTED_EARLY_EXIT');}
catch(RuntimeException $e){process_check($e->getMessage()==='WORKER_EXITED_WITHOUT_MARKER','early_exit_rejected_without_hanging');}
foreach(['duplicate','invalid']as$mode){
    $result=$mode==='duplicate'?"RESULT={}\nRESULT={}\n":"RESULT={\n";
    $code='echo "READY\n";flush();fgets(STDIN);echo '.var_export($result,true).';';
    $job=site_process_start([PHP_BINARY,'-r',$code],'READY');site_process_go($job);
    try{site_process_result($job,'RESULT=');throw new RuntimeException('UNEXPECTED_ACCEPTANCE');}
    catch(Throwable $e){process_check($mode==='duplicate'?$e->getMessage()==='WORKER_RESULT_MISSING':$e instanceof JsonException,$mode.'_result_rejected');}
    unlink($job['out']);unlink($job['err']);
}
echo json_encode(['passed'=>count($cases),'failed'=>0,'cases'=>$cases],JSON_PRETTY_PRINT),"\n";
