<?php
namespace AlexCuadra\EditorialSourceExperiment;
// Bounded counterfactual hash test. No metadata is changed, exempted or exported.
function deploy_lock_fingerprint_evidence($rows,$expected,$anchor,$byteLimit=536870912,$seconds=5){
    $started=microtime(true);$encoded=array_map('serialize',$rows);$ordered=$encoded;sort($ordered,SORT_STRING);$blob=serialize($ordered);$size=strlen($blob);$current=hash('sha256',$blob);unset($blob);
    $locks=[];foreach($rows as $index=>$row){if($row['meta_key']==='_edit_lock'&&preg_match('/^([0-9]{10}):([0-9]+)$/D',$row['meta_value'],$parts)){
        $time=(int)$parts[1];if($time>=$anchor-600&&$time<=$anchor+3600){$locks[]=['index'=>$index,'time'=>$time,'user'=>$parts[2]];}
    }}
    usort($locks,static fn($a,$b)=>abs($a['time']-$anchor)<=>abs($b['time']-$anchor));$candidates=array_slice($locks,0,4);$attempts=0;$bytes=$size;$match=null;$stopped=false;
    if($current!==$expected){
        foreach($candidates as $candidate){
            for($offset=0;$offset<=600;$offset++){
                $stamp=$anchor-$offset;$value=$stamp.':'.$candidate['user'];if($value===$rows[$candidate['index']]['meta_value']){continue;}
                if($bytes+$size>$byteLimit||microtime(true)-$started>=$seconds){$stopped=true;break 2;}
                $row=$rows[$candidate['index']];$row['meta_value']=$value;$trial=$encoded;$trial[$candidate['index']]=serialize($row);sort($trial,SORT_STRING);$text=serialize($trial);$bytes+=strlen($text);$attempts++;
                if(hash('sha256',$text)===$expected){$original=$rows[$candidate['index']];$match=['metaId'=>(int)$original['meta_id'],'postId'=>(int)$original['post_id'],'metaKey'=>'_edit_lock','onlyTimestampChangedSameUser'=>true];break 2;}
            }
        }
    }
    return ['baselinePostmetaMatchesCurrent'=>$current===$expected,'eligibleLockRows'=>count($locks),'testedCandidateLimit'=>4,'timestampSearchSecondsBeforeBaselineFileTime'=>600,
        'timestampReference'=>'baseline-file-mtime-search-anchor-not-an-audit-clock','attempts'=>$attempts,'boundedSearchStopped'=>$stopped,
        'singleLockTimestampDifferenceReconstructsBaselineExactly'=>$match!==null,'matchingRow'=>$match,
        'allOtherPostmetaRowsMatchGrantBaseline'=>$match!==null||$current===$expected,
        'writerIdentityEstablished'=>false,'cmsWritesPerformed'=>false,'deviationAccepted'=>false,'privateValuesExported'=>false];
}
$config=$args[0]??'';$path=$args[1]??'';$run=$args[2]??'';
deploy_install_require(is_file($path)&&!is_link($path),'LOCK_DIAG_BASELINE_PATH');$anchor=filemtime($path);deploy_install_require(is_int($anchor),'LOCK_DIAG_BASELINE_TIME');
$baseline=json_decode(base64_decode(trim(file_get_contents($path)),true),true,32,JSON_THROW_ON_ERROR);
deploy_install_require(is_array($baseline['snapshot']??null)&&is_string($baseline['snapshot']['postmeta']??null)&&is_array($baseline['expectedCaps']??null),'LOCK_DIAG_BASELINE_FORMAT');
deploy_install_verify_files();foreach(['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities'] as $module){require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/'.$module.'.php';}
deploy_scope_context(false);
$result=owned_transaction($GLOBALS['wpdb'],function()use($baseline,$anchor,$config){
    global $wpdb;$stats=$wpdb->get_row("SELECT COUNT(*) AS n,COALESCE(SUM(OCTET_LENGTH(meta_value)),0) AS bytes FROM $wpdb->postmeta",ARRAY_A);
    deploy_install_require(!$wpdb->last_error&&is_array($stats)&&(int)$stats['n']<=5000&&(int)$stats['bytes']<=16*1024*1024,'LOCK_DIAG_SIZE_LIMIT');
    $snapshot=deploy_scope_snapshot($config);$rows=$wpdb->get_results("SELECT * FROM $wpdb->postmeta",ARRAY_A);deploy_install_require(!$wpdb->last_error&&is_array($rows),'LOCK_DIAG_READ');
    $changed=[];foreach(array_unique(array_merge(array_keys($baseline['snapshot']),array_keys($snapshot))) as $key){if(($baseline['snapshot'][$key]??null)!==($snapshot[$key]??null)){$changed[]=$key;}}
    $caps=deploy_scope_caps_hash(deploy_scope_caps());$byUser=[];foreach([1,2] as $id){$byUser[$id]=($caps[$id]??null)===($baseline['expectedCaps'][$id]??null);}
    return ['expectedDirectCapabilitiesMatchByUser'=>$byUser,'changedSnapshotSections'=>$changed,'lockEvidence'=>deploy_lock_fingerprint_evidence($rows,$baseline['snapshot']['postmeta'],$anchor),
        'cmsWritesPerformed'=>false,'acceptancePerformed'=>false,'baselineReplaced'=>false];
},true);
echo 'OPERATING_LOCK_DIAGNOSIS='.wp_json_encode(['format'=>'editorial-operating-lock-diagnosis-v1','grantRun'=>$run,'checks'=>$result]).PHP_EOL;
