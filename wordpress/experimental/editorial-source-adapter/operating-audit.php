<?php
/** Bounded operator audit. No repair, export or source writes. */
namespace AlexCuadra\EditorialSourceExperiment;
function audit_operating_owner($db,$owner,$locking=true){
    $lock=$locking?' FOR UPDATE':'';
    $prefix=$db->prefix.'ac_exp_operating_';$fail=static function(){throw new \RuntimeException('contract_audit_inconsistent');};
    $read=static function($table,$limit)use($db,$owner,$fail,$lock){$r=$db->get_results($db->prepare("SELECT * FROM $table WHERE owner_id=%d LIMIT %d$lock",$owner,$limit+1),ARRAY_A);if($db->last_error||count($r??[])>$limit){$fail();}return $r;};
    $reviews=$read($prefix.'reviews',8);$requests=$read($prefix.'requests',64);$apps=$read($prefix.'apps',8);$map=[];$prepare=[];$executed=[];$appEvents=[];$approvals=0;
    foreach($reviews as $r){
        $d=decode_operating_review($r['document']);$id=(int)$r['review_id'];
        if(!operating_sql_uint($r['review_id'])||$id<1||$d['owner_id']!==$owner||$d['proposal_id']!==(int)$r['proposal_id']||hash('sha256',$r['document'])!==$r['review_hash']){$fail();}
        $post=$db->get_row($db->prepare("SELECT post_author,post_type,post_status,post_password FROM {$db->posts} WHERE ID=%d$lock",$d['proposal_id']),ARRAY_A);
        if($db->last_error||!$post||(int)$post['post_author']!==$owner||$post['post_type']!=='ac_private_proposal'||$post['post_status']!=='draft'||$post['post_password']!==''){$fail();}
        $map[$id]=['row'=>$r,'doc'=>$d];
    }
    foreach($requests as $r){
        if(!operating_sql_uint($r['owner_id'])||(int)$r['owner_id']!==$owner||!operating_hex($r['request_id'],32)||!operating_hex($r['request_hash'])){$fail();}
        try{$d=json_decode($r['response'],true,16,JSON_THROW_ON_ERROR);}catch(\Throwable $e){$fail();}
        $id=$d['review_id']??0;if(!is_int($id)||!isset($map[$id])){$fail();}$review=$map[$id];$v=$review['doc'];
        $isPrepare=($d['format']??null)==='operating-review-receipt-v1';$kind=$isPrepare?($v['operation']==='apply'?'prepare':'prepare_recovery'):$v['operation'];
        $input=['locale'=>$v['locale'],'request_id'=>$r['request_id']];
        if($isPrepare){$input['proposal_id']=$v['proposal_id'];if($kind==='prepare'){$input['expected_proposal_version']=$v['proposal_version'];$input['expected_base_version']=$v['base_version'];}else{$input['expected_source_version']=$v['source_version'];}}
        else{$input['review_id']=$id;$input['expected_review_hash']=$review['row']['review_hash'];}
        $d=decode_operating_receipt($r['response'],$kind,$input);ksort($input);
        if(hash('sha256',json_encode([$kind,$input],JSON_THROW_ON_ERROR))!==$r['request_hash']||$d['project_id']!==$v['project_id']||$d['proposal_id']!==$v['proposal_id']){$fail();}
        if($isPrepare){if(isset($prepare[$id])||$d['review_hash']!==$review['row']['review_hash']||$d['expires_at']!==$v['expires_at']){$fail();}$prepare[$id]=true;continue;}
        $eventId=hash('sha256','operating|'.$owner.'|'.$r['request_id']);if($d['event_id']!==$eventId||isset($executed[$id])){$fail();}
        $event=$db->get_row($db->prepare("SELECT * FROM {$db->prefix}ac_exp_source_events WHERE event_id=%s$lock",$eventId),ARRAY_A);
        $effect=$db->get_row($db->prepare("SELECT * FROM {$db->prefix}ac_exp_source_effects WHERE event_id=%s$lock",$eventId),ARRAY_A);
        if($db->last_error||!$event||!$effect){$fail();}
        $journal=decode_source_event($event);validate_source_effect($effect);
        if($event['event_hash']!==$r['request_hash']||$event['operation']!==$kind||(int)$event['project_id']!==$v['project_id']||(int)$effect['project_id']!==$v['project_id']||$journal!==$d){$fail();}
        $executed[$id]=$d;
    }
    foreach($map as $id=>$review){
        if(!isset($prepare[$id])){$fail();}
        $a=$db->get_row($db->prepare("SELECT * FROM {$prefix}approvals WHERE review_id=%d$lock",$id),ARRAY_A);if($db->last_error){$fail();}
        if($a){decode_operating_approval($a,$review['doc'],$id,$owner,$review['row']['review_hash']);$approvals++;if($a['consumed_event']!==($executed[$id]['event_id']??null)){$fail();}}
        elseif(isset($executed[$id])){$fail();}
    }
    foreach($apps as $app){
        validate_operating_application($app);$id=(int)$app['review_id'];$receipt=$executed[$id]??null;$review=$map[$id]['doc']??null;
        if(!$receipt||!$review||$review['operation']!=='apply'||$review['proposal_id']!==(int)$app['proposal_id']||$receipt['event_id']!==$app['event_id']||$receipt['source_version']!==$app['source_after']){$fail();}
        $appEvents[$app['event_id']]=true;
        foreach($map as $rid=>$recovery){$v=$recovery['doc'];if($v['operation']==='recover'&&$v['proposal_id']===(int)$app['proposal_id']){
            if($v['application_event']!==$app['event_id']||$v['application_review_hash']!==$map[$id]['row']['review_hash']||$v['source_version']!==$app['source_after']||$v['schema_version']!==$review['schema_version']||$v['before']!==$review['after']||$v['after']!==$review['before']){$fail();}
            if(isset($executed[$rid])&&((int)$app['recovery_review']!==$rid||$app['recovery_event']!==$executed[$rid]['event_id']||$executed[$rid]['application_event']!==$app['event_id'])){$fail();}
        }}
        if($app['recovery_review']!==null){$rid=(int)$app['recovery_review'];if(!isset($map[$rid])||$map[$rid]['doc']['operation']!=='recover'||$map[$rid]['doc']['application_event']!==$app['event_id']){$fail();}}
        if($app['recovery_event']!==null){$rid=(int)$app['recovery_review'];if(($executed[$rid]['event_id']??null)!==$app['recovery_event']){$fail();}$appEvents[$app['recovery_event']]=true;}
    }
    foreach($executed as $d){if(!isset($appEvents[$d['event_id']])){$fail();}}
    foreach($map as $review){if($review['doc']['operation']==='recover'&&!isset($appEvents[$review['doc']['application_event']])){$fail();}}
    return ['owner_id'=>$owner,'reviews'=>count($reviews),'requests'=>count($requests),'applications'=>count($apps),'approvals'=>$approvals,'events'=>count($executed)];
}
