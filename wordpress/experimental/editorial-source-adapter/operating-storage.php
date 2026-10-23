<?php
/** Local candidate storage admission; no migration or hooks on load. */
namespace AlexCuadra\EditorialSourceExperiment;

function operating_storage_schema($db) {
    $u='bigint unsigned';$v='varbinary(64)';
    $expected=[
        'reviews'=>[['review_id',$u,'NO',null,'auto_increment'],['owner_id',$u,'NO',null,''],['proposal_id',$u,'NO',null,''],['document','longtext','NO',null,''],['review_hash',$v,'NO',null,'']],
        'approvals'=>[['review_id',$u,'NO',null,''],['actor_id',$u,'NO',null,''],['document','longtext','NO',null,''],['consumed_event',$v,'YES',null,'']],
        'requests'=>[['owner_id',$u,'NO',null,''],['request_id','varbinary(32)','NO',null,''],['request_hash',$v,'NO',null,''],['response','longtext','NO',null,'']],
        'apps'=>[['proposal_id',$u,'NO',null,''],['owner_id',$u,'NO',null,''],['review_id',$u,'NO',null,''],['event_id',$v,'NO',null,''],['source_after',$v,'NO',null,''],['reserved_bytes','int unsigned','NO',null,''],['reserved_reviews','int unsigned','NO',null,''],['reserved_requests','int unsigned','NO',null,''],['recovery_review',$u,'YES',null,''],['recovery_event',$v,'YES',null,'']],
    ];
    foreach($expected as $name=>$columns){
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
