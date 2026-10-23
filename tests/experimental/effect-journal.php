<?php
// Real temporary SQLite persistence and two independent connections. No WP/HTTP.
define('AC_EDITORIAL_SOURCE_EXPERIMENT', true);
function wp_get_environment_type() { global $env; return $env ?? 'local'; }
function home_url() { return 'http://wordpress'; }
require __DIR__.'/../../wordpress/experimental/editorial-source-adapter/effect-journal.php';
use AlexCuadra\EditorialSourceExperiment\EffectJournal;
function db($path) { $db=new PDO('sqlite:'.$path); $db->exec('PRAGMA busy_timeout=1000'); return $db; }
function event($n,$operation='apply') { return ['event_id'=>hash('sha256','event'.$n),'event_hash'=>hash('sha256','input'.$n),'project_id'=>10,'operation'=>$operation]; }
if (($argv[1] ?? '')==='race') {
    $db=db($argv[2]);$j=new EffectJournal($db,fn()=>(int)$argv[3]);
    fgets(STDIN);
    echo $j->claim(event(11)['event_id'])?'claimed':'busy';
    exit(0);
}
if (in_array($argv[1] ?? '', ['crash','crash-after-purge'],true)) {
    $db=db($argv[2]); $j=new EffectJournal($db,fn()=>1000);
    $after=($argv[1]==='crash-after-purge');
    $j->consume(event($after?8:2)['event_id'], function()use($after,$db){
        if($after){$db->exec('UPDATE fixture_external_purges SET calls=calls+1');}
        exit(73);
    }); // process dies with a persisted lease, with or without an external effect
    exit(1);
}
$checks=0;
function check($ok,$name) { global $checks; $checks++; if (!$ok) { throw new RuntimeException('CHECK: '.$name); } }
function fails(callable $fn,$code) { try{$fn();}catch(RuntimeException $e){check($e->getMessage()===$code,$code);return;}throw new RuntimeException('Expected '.$code); }
$path=tempnam(sys_get_temp_dir(),'ac-effect-journal-');chmod($path,0600);
try {
    $now=1000;$a=db($path);$b=db($path);$j=new EffectJournal($a,function()use(&$now){return $now;});$k=new EffectJournal($b,function()use(&$now){return $now;});
    $j->install();
    $a->exec('CREATE TABLE fixture_source (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
    $a->exec("INSERT INTO fixture_source VALUES (10,'before')");
    $a->exec('CREATE TABLE fixture_approvals (id INTEGER PRIMARY KEY, consumed INTEGER NOT NULL)');
    $a->exec('INSERT INTO fixture_approvals VALUES (1,0)');
    $a->exec('CREATE TABLE fixture_external_purges (calls INTEGER NOT NULL)');$a->exec('INSERT INTO fixture_external_purges VALUES (0)');
    $writes=0;$purges=0;
    $write=function($db)use(&$writes){$writes++;$db->exec("UPDATE fixture_source SET value='after' WHERE id=10");$db->exec('UPDATE fixture_approvals SET consumed=1 WHERE id=1');return ['content'=>'applied','version'=>'after'];};
    $receipt=$j->record(event(1),function($db)use($write,$k){check($k->effect(event(1)['event_id'])===null,'other connection cannot see uncommitted intent');return $write($db);});
    check($receipt===['content'=>'applied','version'=>'after'],'receipt persisted');
    check($purges===0,'record never purges');check($k->effect(event(1)['event_id'])['status']==='pending','committed intent visible');
    check($b->query('SELECT value FROM fixture_source')->fetchColumn()==='after','source committed');
    check((int)$b->query('SELECT consumed FROM fixture_approvals')->fetchColumn()===1,'approval consumed together');
    check($j->record(event(1),$write)===$receipt && $writes===1,'duplicate event replays receipt without writing');
    $changed=event(1);$changed['event_hash']=hash('sha256','different');fails(fn()=>$j->record($changed,$write),'event_reused');
    $a->exec("UPDATE fixture_source SET value='before'; UPDATE fixture_approvals SET consumed=0");
    fails(function()use($j){$j->record(event(9),function($db){$db->exec("UPDATE fixture_source SET value='bad'");$db->exec('UPDATE fixture_approvals SET consumed=1');throw new RuntimeException('abort_before_commit');});},'abort_before_commit');
    check($k->effect(event(9)['event_id'])===null,'rollback no executable intent');
    check((int)$b->query("SELECT count(*) FROM exp_events WHERE event_id='".event(9)['event_id']."'")->fetchColumn()===0,'rollback no event receipt');
    check($b->query('SELECT value FROM fixture_source')->fetchColumn()==='before','source rolled back');
    check((int)$b->query('SELECT consumed FROM fixture_approvals')->fetchColumn()===0,'approval rolled back');
    $failedId=event(10)['event_id'];
    $a->exec("CREATE TRIGGER fail_intent BEFORE INSERT ON exp_effects WHEN NEW.event_id='$failedId' BEGIN SELECT RAISE(ABORT,'fixture-intent-failure'); END");
    try {$j->record(event(10),$write);throw new RuntimeException('expected insert failure');}
    catch(PDOException $e){check(str_contains($e->getMessage(),'fixture-intent-failure'),'intent insertion fails');}
    check($b->query('SELECT value FROM fixture_source')->fetchColumn()==='before','intent failure rolls source back');
    check((int)$b->query('SELECT consumed FROM fixture_approvals')->fetchColumn()===0,'intent failure rolls approval back');
    check($k->effect($failedId)===null,'intent failure leaves no effect');
    check((int)$b->query("SELECT count(*) FROM exp_events WHERE event_id='$failedId'")->fetchColumn()===0,'intent failure rolls receipt back');
    $a->exec('DROP TRIGGER fail_intent');
    $j->record(event(2),$write);
    // Destroy this process-local handle; a child claims and exits before acknowledging.
    $process=proc_open([PHP_BINARY,__FILE__,'crash',$path],[['pipe','r'],['pipe','w'],['pipe','w']],$pipes);
    if (!is_resource($process)) {throw new RuntimeException('child unavailable');}
    foreach($pipes as $pipe){fclose($pipe);}check(proc_close($process)===73,'consumer process killed before acknowledgment');
    check($k->effect(event(2)['event_id'])['status']==='running','lease persisted across process exit');
    check($k->claim(event(2)['event_id'])===null,'live lease blocks duplicate claimant');
    $now=1031;$claim=$k->claim(event(2)['event_id']);check((int)$claim['attempts']===2,'expired lease reclaimed');
    check($k->settle($claim,'accepted')==='accepted','reclaimed acknowledgment accepted');
    check($j->consume(event(2)['event_id'],function()use(&$purges){$purges++;return 'accepted';})==='not_claimed','accepted effect not repeated');
    $now=1000;$j->record(event(8),$write);
    $process=proc_open([PHP_BINARY,__FILE__,'crash-after-purge',$path],[['pipe','r'],['pipe','w'],['pipe','w']],$pipes);
    if(!is_resource($process)){throw new RuntimeException('child unavailable');}
    foreach($pipes as $pipe){fclose($pipe);}check(proc_close($process)===73,'process dies after simulated purge');
    check((int)$b->query('SELECT calls FROM fixture_external_purges')->fetchColumn()===1,'external effect happened before lost acknowledgment');
    $now=1031;
    check($k->consume(event(8)['event_id'],function()use($b){$b->exec('UPDATE fixture_external_purges SET calls=calls+1');return 'accepted';})==='accepted','lost acknowledgment allows safe invalidation retry');
    check((int)$b->query('SELECT calls FROM fixture_external_purges')->fetchColumn()===2,'at least once does not pretend exactly once');
    check($j->record(event(1),$write)===$receipt,'receipt preserved independent of effect state');
    $j->record(event(3),$write);$first=$j->claim(event(3)['event_id']);check($k->claim(event(3)['event_id'])===null,'two connections only one lease');
    $now+=31;$second=$k->claim(event(3)['event_id']);check($j->settle($first,'accepted')==='lease_lost','old worker cannot settle new lease');
    check($k->settle($second,'uncertain')==='retry','uncertain is pending retry not rollback');
    check($b->query('SELECT value FROM fixture_source')->fetchColumn()==='after','purge failure preserves content');
    $beforeWrites=$writes;$keys=[];$now+=10;
    check($j->consume(event(3)['event_id'],function($id,$key)use(&$keys){$keys[]=$key;throw new RuntimeException('external timeout');})==='retry','exception records uncertainty');
    $now+=20;
    check($j->consume(event(3)['event_id'],function($id,$key)use(&$keys){$keys[]=$key;check($id===10,'consumer project');return 'accepted';})==='accepted','retry succeeds');
    check(count(array_unique($keys))===1,'stable effect key across attempts');check($writes===$beforeWrites,'consumer never rewrites source');
    $j->record(event(4,'recover'),$write);check($j->effect(event(4)['event_id'])['status']==='pending','recovery gets independent intent');
    check($j->effect(event(3)['event_id'])['status']==='accepted','recovery preserves original event');
    $j->record(event(5),$write);
    for($i=0;$i<8;$i++){$status=$j->consume(event(5)['event_id'],fn()=>'retry');$now+=400;}
    check($status==='attention','bounded retries need attention');check((int)$j->effect(event(5)['event_id'])['attempts']===8,'attempt bound');
    check($j->claim(event(5)['event_id'])===null,'attention not automatically retried');
    $j->record(event(6),$write);check($j->consume(event(6)['event_id'],fn()=>true)==='retry','untyped external outcome never success');
    $j->record(event(11),$write);$workers=[];
    for($i=0;$i<2;$i++){
        $process=proc_open([PHP_BINARY,__FILE__,'race',$path,(string)$now],[['pipe','r'],['pipe','w'],['pipe','w']],$pipes);
        if(!is_resource($process)){throw new RuntimeException('race child unavailable');}
        $workers[]=[$process,$pipes];
    }
    foreach($workers as [$process,$pipes]){fwrite($pipes[0],"go\n");fclose($pipes[0]);}
    $out=[];
    foreach($workers as [$process,$pipes]){$out[]=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);check(proc_close($process)===0 && $err==='','race worker exits cleanly');}
    sort($out);check($out===['busy','claimed'],'two processes only one claim');
    check((int)$j->effect(event(11)['event_id'])['attempts']===1,'race consumes one attempt');
    $j->record(event(12),$write);
    for($i=0;$i<8;$i++){$j->claim(event(12)['event_id']);$now+=31;}
    check($j->claim(event(12)['event_id'])===null,'crashed workers respect attempt limit');
    check($j->effect(event(12)['event_id'])['status']==='attention','expired final lease needs attention');
    $a->beginTransaction();fails(fn()=>$j->record(event(7),$write),'outer_transaction');fails(fn()=>$j->claim(event(7)['event_id']),'outer_transaction');$a->rollBack();
    $env='production';fails(fn()=>new EffectJournal($a,fn()=>1000),'sqlite_experiment_only');fails(fn()=>$j->claim(event(7)['event_id']),'sqlite_experiment_only');$env='local';
    echo json_encode(['format'=>'editorial-effect-journal-sqlite-v1','checks'=>$checks,'passed'=>true,'realSQLite'=>true,'independentConnections'=>2,'processDeathVerified'=>true,'wordpressMariaDbAtomicityVerified'=>false,'liteSpeedPurgeVerified'=>false]).PHP_EOL;
} finally {
    // Only this test-created, synthetic temporary DB; not a backup or retained lab.
    $j=$k=$a=$b=null;
    if(is_file($path)){unlink($path);}
}
