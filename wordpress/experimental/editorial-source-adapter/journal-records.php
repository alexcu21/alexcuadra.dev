<?php
namespace AlexCuadra\EditorialSourceExperiment;
require_once __DIR__.'/operating-storage.php';
function decode_source_event(array $r){
    try{
        if(!operating_keys($r,['event_id','event_hash','project_id','operation','receipt'])||!operating_hex($r['event_id'])||!operating_hex($r['event_hash'])||!operating_sql_uint($r['project_id'])||(int)$r['project_id']<1||!in_array($r['operation'],['apply','recover'],true)||!is_string($r['receipt'])||strlen($r['receipt'])>65536){throw new \Exception();}
        $d=json_decode($r['receipt'],true,32,JSON_THROW_ON_ERROR);if(!is_array($d)){throw new \Exception();}return $d;
    }catch(\Throwable $e){throw new \RuntimeException('source_event_corrupt',0,$e);}
}
function validate_source_effect(array $r){
    $bad=static function(){throw new \RuntimeException('source_effect_corrupt');};
    if(!operating_keys($r,['event_id','project_id','state','attempts','available_at','lease_token','lease_until','last_outcome'])||!operating_hex($r['event_id'])||!in_array($r['state'],['pending','running','retry','requested','attention'],true)){$bad();}
    foreach(['project_id','attempts','available_at'] as $k){if(!operating_sql_uint($r[$k])){$bad();}}
    $n=(int)$r['attempts'];if((int)$r['project_id']<1||(int)$r['available_at']<1||$n>8){$bad();}
    if($r['state']==='running'){
        if($n<1||!operating_hex($r['lease_token'],32)||!operating_sql_uint($r['lease_until'])||(int)$r['lease_until']<1){$bad();}
    }elseif($r['lease_token']!==null||$r['lease_until']!==null){$bad();}
    if($r['last_outcome']!==null&&!in_array($r['last_outcome'],['requested','retry','uncertain','attempt_limit'],true)){$bad();}
    if(($r['state']==='pending'&&($n!==0||$r['last_outcome']!==null))||($r['state']!=='pending'&&$n===0)||($r['state']==='requested'&&$r['last_outcome']!=='requested')||($r['state']==='retry'&&!in_array($r['last_outcome'],['retry','uncertain'],true))||($r['state']==='attention'&&($n!==8||!in_array($r['last_outcome'],['retry','uncertain','attempt_limit'],true)))){$bad();}
    return $r;
}
