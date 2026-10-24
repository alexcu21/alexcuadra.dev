<?php
namespace AlexCuadra\EditorialSourceExperiment;
// Diagnostic only. Reconstruct fingerprints in memory; never edit or exempt metadata.
function deploy_postmeta_descriptor($row,$types){
    $key=$row['meta_key'];$type=$types[(int)$row['post_id']]??'missing';
    return ['metaId'=>(int)$row['meta_id'],'postId'=>(int)$row['post_id'],
        'postType'=>in_array($type,['project','attachment','post','page','revision'],true)?$type:'other',
        'keyCategory'=>$key==='_edit_lock'?'edit_lock':($key==='_edit_last'?'edit_last':(str_starts_with($key,'_')?'other_private_key':'other_key')),
        'editorLockValueHasExpectedFormat'=>$key==='_edit_lock'?preg_match('/^[0-9]+:[0-9]+$/D',$row['meta_value'])===1:null];
}
function deploy_postmeta_evidence($rows,$expected){
    global $wpdb;$encoded=array_map('serialize',$rows);asort($encoded,SORT_STRING);
    $fingerprint=static function($exclude)use($encoded){$skip=array_fill_keys($exclude,true);$values=[];foreach($encoded as $id=>$value){if(!isset($skip[$id])){$values[]=$value;}}return hash('sha256',serialize($values));};
    $current=$fingerprint([]);$indices=array_keys($rows);usort($indices,static fn($a,$b)=>(int)$rows[$b]['meta_id']<=>(int)$rows[$a]['meta_id']);$recent=array_slice($indices,0,32);$match=null;
    if($current!==$expected){
        foreach($recent as $id){if($fingerprint([$id])===$expected){$match=[$id];break;}}
        if($match===null){$suffix=[];foreach($recent as $id){$suffix[]=$id;if($fingerprint($suffix)===$expected){$match=$suffix;break;}}}
    }
    $types=[];foreach($wpdb->get_results("SELECT ID,post_type FROM $wpdb->posts",ARRAY_A) as $row){$types[(int)$row['ID']]=$row['post_type'];}deploy_install_require(!$wpdb->last_error,'POSTMETA_POST_TYPES_READ');
    $descriptors=[];foreach($match??[] as $id){$descriptors[]=deploy_postmeta_descriptor($rows[$id],$types);}
    $counts=['edit_lock'=>0,'edit_last'=>0,'other'=>0];foreach($rows as $row){$key=$row['meta_key'];$counts[$key==='_edit_lock'?'edit_lock':($key==='_edit_last'?'edit_last':'other')]++;}
    return ['baselinePostmetaMatches'=>$current===$expected,'rowCount'=>count($rows),'keyCategoryCounts'=>$counts,
        'searchLimitNewestRows'=>32,'baselineReconstructedByRemovingCurrentRowsInMemory'=>$match!==null,
        'matchedAddedRowDescriptors'=>$descriptors,
        'interpretation'=>$match!==null?'Remaining current rows exactly reproduce the original baseline fingerprint; writer identity and cause are not established':'No reconstruction found within this bounded addition-only search; changed or deleted prior values cannot be recovered from the baseline hash',
        'rowsDeleted'=>false,'deviationAccepted'=>false];
}
$config=$args[0]??'';$baselinePath=$args[1]??'';$run=$args[2]??'';$mode=$args[3]??'';$observation=$args[4]??'';
deploy_install_require(in_array($mode,['capture','compare'],true)&&is_file($baselinePath)&&!is_link($baselinePath),'POSTMETA_INPUT');
$baseline=json_decode(base64_decode(trim(file_get_contents($baselinePath)),true),true,32,JSON_THROW_ON_ERROR);
deploy_install_require(is_array($baseline['snapshot']??null)&&is_string($baseline['snapshot']['postmeta']??null)&&is_array($baseline['expectedCaps']??null),'POSTMETA_BASELINE_FORMAT');
deploy_install_verify_files();foreach(['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities'] as $module){require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/'.$module.'.php';}
deploy_scope_context(false);
$observed=owned_transaction($GLOBALS['wpdb'],function()use($baseline,$config){
    global $wpdb;$stats=$wpdb->get_row("SELECT COUNT(*) AS n,COALESCE(SUM(OCTET_LENGTH(meta_value)),0) AS bytes FROM $wpdb->postmeta",ARRAY_A);
    deploy_install_require(!$wpdb->last_error&&is_array($stats)&&(int)$stats['n']<=5000&&(int)$stats['bytes']<=16*1024*1024,'POSTMETA_DIAGNOSTIC_SIZE_LIMIT');
    $snapshot=deploy_scope_snapshot($config);$caps=deploy_scope_caps_hash(deploy_scope_caps());
    $rows=$wpdb->get_results("SELECT * FROM $wpdb->postmeta",ARRAY_A);deploy_install_require(!$wpdb->last_error&&is_array($rows),'POSTMETA_READ');
    $changed=[];foreach(array_unique(array_merge(array_keys($baseline['snapshot']),array_keys($snapshot))) as $key){if(($snapshot[$key]??null)!==($baseline['snapshot'][$key]??null)){$changed[]=$key;}}
    $byUser=[];foreach([1,2] as $id){$byUser[$id]=($caps[$id]??null)===($baseline['expectedCaps'][$id]??null);}
    return ['privateObservation'=>['snapshot'=>$snapshot,'caps'=>$caps],
        'public'=>['expectedDirectCapabilitiesMatchByUser'=>$byUser,'changedSnapshotSections'=>$changed,'postmeta'=>deploy_postmeta_evidence($rows,$baseline['snapshot']['postmeta'])]];
},true);
$previous=null;
if($mode==='capture'){
    $bytes=wp_json_encode($observed['privateObservation']);$file=fopen($observation,'x');deploy_install_require(is_resource($file),'POSTMETA_OBSERVATION_CREATE');
    try{deploy_install_require(chmod($observation,0600)&&fwrite($file,$bytes)===strlen($bytes),'POSTMETA_OBSERVATION_WRITE');}finally{fclose($file);}
}else{
    deploy_install_require(is_file($observation)&&!is_link($observation),'POSTMETA_OBSERVATION_PATH');$previous=json_decode(file_get_contents($observation),true,32,JSON_THROW_ON_ERROR);
}
echo 'OPERATING_POSTMETA_DIAGNOSIS='.wp_json_encode(['format'=>'editorial-operating-postmeta-diagnosis-v1','grantRun'=>$run,'observation'=>$mode,
    'comparison'=>$observed['public'],
    'postmetaMatchesFirstObservation'=>$previous===null?null:$observed['privateObservation']['snapshot']['postmeta']===$previous['snapshot']['postmeta'],
    'fullSnapshotAndCapabilitiesMatchFirstObservation'=>$previous===null?null:$observed['privateObservation']===$previous,
    'cmsWritesPerformed'=>false,'acceptancePerformed'=>false,'baselineReplaced'=>false,'privateValuesExported'=>false,
    'externalWriterPauseCertified'=>false]).PHP_EOL;
