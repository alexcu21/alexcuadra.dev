<?php
/** Local candidate storage admission; no migration or hooks on load. */
namespace AlexCuadra\EditorialSourceExperiment;

function operating_storage_schema($db,$only=null) {
    $u='bigint unsigned';$v='varbinary(64)';
    $expected=[
        'reviews'=>[['review_id',$u,'NO',null,'auto_increment'],['owner_id',$u,'NO',null,''],['proposal_id',$u,'NO',null,''],['document','longtext','NO',null,''],['review_hash',$v,'NO',null,'']],
        'approvals'=>[['review_id',$u,'NO',null,''],['actor_id',$u,'NO',null,''],['document','longtext','NO',null,''],['consumed_event',$v,'YES',null,'']],
        'requests'=>[['owner_id',$u,'NO',null,''],['request_id','varbinary(32)','NO',null,''],['request_hash',$v,'NO',null,''],['response','longtext','NO',null,'']],
        'apps'=>[['proposal_id',$u,'NO',null,''],['owner_id',$u,'NO',null,''],['review_id',$u,'NO',null,''],['event_id',$v,'NO',null,''],['source_after',$v,'NO',null,''],['reserved_bytes','int unsigned','NO',null,''],['reserved_reviews','int unsigned','NO',null,''],['reserved_requests','int unsigned','NO',null,''],['recovery_review',$u,'YES',null,''],['recovery_event',$v,'YES',null,'']],
    ];
    foreach($expected as $name=>$columns){
        if($only!==null&&$only!==$name){continue;}
        $table=$db->prefix.'ac_exp_operating_'.$name;$actual=[];
        $rows=$db->get_results("SHOW FULL COLUMNS FROM $table",ARRAY_A);
        if($db->last_error||!$rows){throw new \RuntimeException('contract_schema_changed');}
        foreach($rows as $r){
            $actual[]=[$r['Field'],preg_replace('/^(bigint|int)\(\d+\)/','$1',strtolower($r['Type'])),$r['Null'],$r['Default'],$r['Extra']];
            if($r['Type']==='longtext'&&$r['Collation']!=='utf8mb4_bin'){throw new \RuntimeException('contract_schema_changed');}
        }
        $indexes=$db->get_results("SHOW INDEX FROM $table",ARRAY_A);$keys=[];
        if($db->last_error){throw new \RuntimeException('contract_schema_changed');}
        foreach($indexes??[] as $r){$keys[]=$r['Key_name'].':'.$r['Seq_in_index'].':'.$r['Column_name'].':'.$r['Non_unique'].':'.$r['Index_type'].':'.($r['Sub_part']??'');}
        $wanted=$name==='requests'?['PRIMARY:1:owner_id:0:BTREE:','PRIMARY:2:request_id:0:BTREE:']:['PRIMARY:1:'.($name==='apps'?'proposal_id':'review_id').':0:BTREE:'];
        if(in_array($name,['reviews','apps'],true)){$wanted[]='owner_id:1:owner_id:1:BTREE:';}
        sort($wanted);sort($keys);
        $triggers=$db->get_var($db->prepare('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE=%s',$table));
        $constraints=$db->get_var($db->prepare("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s AND CONSTRAINT_TYPE IN ('CHECK','FOREIGN KEY')",$table));
        if($db->last_error||$actual!==$columns||$keys!==$wanted||$triggers===null||(int)$triggers!==0||$constraints===null||(int)$constraints!==0){throw new \RuntimeException('contract_schema_changed');}
    }
}

function decode_operating_review($encoded) {
    try{
        if(!is_string($encoded)||strlen($encoded)>524288){throw new \Exception();}
        $d=json_decode($encoded,true,32,JSON_THROW_ON_ERROR);
        if(!is_array($d)||!in_array($d['operation']??null,['apply','recover'],true)){throw new \Exception();}
        $keys=['format','owner_id','proposal_id','project_id','locale','operation','source_version','schema_version','before','after','created_at','expires_at'];
        $hashes=['source_version','schema_version'];
        if($d['operation']==='apply'){$keys=array_merge($keys,['proposal_version','base_version']);$hashes=array_merge($hashes,['proposal_version','base_version']);}
        else{$keys=array_merge($keys,['application_event','application_review_hash']);$hashes=array_merge($hashes,['application_event','application_review_hash']);}
        if(array_diff(array_keys($d),$keys)||array_diff($keys,array_keys($d))||$d['format']!=='operating-review-v1'||!in_array($d['locale'],['en','es'],true)){throw new \Exception();}
        foreach(['owner_id','proposal_id','project_id','created_at','expires_at'] as $k){if(!is_int($d[$k])||$d[$k]<1){throw new \Exception();}}
        if($d['expires_at']-$d['created_at']!==600){throw new \Exception();}
        foreach($hashes as $k){if(!is_string($d[$k])||!preg_match('/^[a-f0-9]{64}$/D',$d[$k])){throw new \Exception();}}
        validate_fields($d['before']);validate_fields($d['after']);$a=array_keys($d['before']);$b=array_keys($d['after']);sort($a);sort($b);if($a!==$b){throw new \Exception();}
        return $d;
    }catch(\Throwable $e){throw new \RuntimeException('contract_review_corrupt',0,$e);}
}

function operating_keys($d,array $keys){return is_array($d)&&!array_diff(array_keys($d),$keys)&&!array_diff($keys,array_keys($d));}
function operating_hex($v,$n=64){return is_string($v)&&preg_match('/^[a-f0-9]{'.$n.'}$/D',$v)===1;}
function operating_positive($v){return is_int($v)&&$v>0;}
function operating_sql_uint($v){return (is_int($v)&&$v>=0)||(is_string($v)&&preg_match('/^(0|[1-9][0-9]*)$/D',$v)&&strlen($v)<=strlen((string)PHP_INT_MAX)&&(strlen($v)<strlen((string)PHP_INT_MAX)||strcmp($v,(string)PHP_INT_MAX)<=0));}
function decode_operating_approval(array $row,array $review,$reviewId,$owner,$hash){
    try{
        if(!is_string($row['document']??null)||strlen($row['document'])>3904){throw new \Exception();}
        $a=json_decode($row['document'],true,16,JSON_THROW_ON_ERROR);
        if(!operating_keys($a,['format','review_id','review_hash','executor_id','actor_id','approved_at'])||$a['format']!=='operating-approval-v1'){throw new \Exception();}
        foreach(['review_id','executor_id','actor_id','approved_at'] as $k){if(!operating_positive($a[$k])){throw new \Exception();}}
        if(!operating_sql_uint($row['review_id']??null)||!operating_sql_uint($row['actor_id']??null)||$a['review_id']!==$reviewId||(int)$row['review_id']!==$reviewId||$a['executor_id']!==$owner||$a['actor_id']===$owner||(int)$row['actor_id']!==$a['actor_id']||!operating_hex($a['review_hash'])||$a['review_hash']!==$hash||$a['approved_at']<$review['created_at']||$a['approved_at']>=$review['expires_at']||!array_key_exists('consumed_event',$row)||($row['consumed_event']!==null&&!operating_hex($row['consumed_event']))){throw new \Exception();}
        return $a;
    }catch(\Throwable $e){throw new \RuntimeException('contract_approval_invalid',0,$e);}
}
function decode_operating_receipt($encoded,$kind,array $input){
    try{
        if(!is_string($encoded)||strlen($encoded)>4096){throw new \Exception();}$d=json_decode($encoded,true,16,JSON_THROW_ON_ERROR);
        $keys=['format','review_id','proposal_id','project_id','locale'];
        if($kind==='prepare'||$kind==='prepare_recovery'){$keys=array_merge($keys,['review_hash','expires_at']);$format='operating-review-receipt-v1';}
        else{$keys=array_merge($keys,['event_id','content_state','source_version','effect_state_at_commit']);$format=$kind==='apply'?'operating-application-receipt-v1':'operating-recovery-receipt-v1';if($kind==='recover'){$keys[]='application_event';}}
        if(!operating_keys($d,$keys)||$d['format']!==$format||$d['locale']!==$input['locale']){throw new \Exception();}
        foreach(['review_id','proposal_id','project_id'] as $k){if(!operating_positive($d[$k])){throw new \Exception();}}
        foreach(['review_id','proposal_id'] as $k){if(isset($input[$k])&&$input[$k]!==$d[$k]){throw new \Exception();}}
        foreach(['review_hash','source_version','event_id','application_event'] as $k){if(isset($d[$k])&&!operating_hex($d[$k])){throw new \Exception();}}
        if(isset($d['expires_at'])&&!operating_positive($d['expires_at'])){throw new \Exception();}
        if(isset($d['content_state'])&&($d['content_state']!==($kind==='apply'?'applied':'recovered')||$d['effect_state_at_commit']!=='pending')){throw new \Exception();}
        return $d;
    }catch(\Throwable $e){throw new \RuntimeException('contract_receipt_corrupt',0,$e);}
}
function validate_operating_application(array $r){
    $keys=['proposal_id','owner_id','review_id','event_id','source_after','reserved_bytes','reserved_reviews','reserved_requests','recovery_review','recovery_event'];
    if(!operating_keys($r,$keys)){throw new \RuntimeException('contract_application_corrupt');}
    foreach(['proposal_id','owner_id','review_id','reserved_bytes','reserved_reviews','reserved_requests'] as $k){if(!operating_sql_uint($r[$k])||(in_array($k,['proposal_id','owner_id','review_id'],true)&&(int)$r[$k]===0)){throw new \RuntimeException('contract_application_corrupt');}}
    $state=[(int)$r['reserved_bytes'],(int)$r['reserved_reviews'],(int)$r['reserved_requests']];
    if(!operating_hex($r['event_id'])||!operating_hex($r['source_after'])||($r['recovery_review']!==null&&(!operating_sql_uint($r['recovery_review'])||(int)$r['recovery_review']<1))||($r['recovery_event']!==null&&!operating_hex($r['recovery_event']))){throw new \RuntimeException('contract_application_corrupt');}
    $expected=$r['recovery_review']===null?[573440,1,2]:($r['recovery_event']===null?[12416,0,1]:[8192,0,0]);
    if($state!==$expected||($r['recovery_event']!==null&&$r['recovery_review']===null)){throw new \RuntimeException('contract_application_corrupt');}
    return $r;
}
