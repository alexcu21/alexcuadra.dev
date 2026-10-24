<?php
// Validate public diagnostic receipts, never read private CMS values here.
$text=file_get_contents($argv[1]);$mode=$argv[2];
preg_match_all('/^OPERATING_POSTMETA_DIAGNOSIS=(.+)$/m',$text,$matches);
if(count($matches[1])!==2||!str_contains($text,'END_EDITORIAL_OPERATING_POSTMETA_DIAGNOSIS_V1')){throw new RuntimeException('POSTMETA_RECEIPTS');}
foreach($matches[1] as $line){
    $r=json_decode($line,true,32,JSON_THROW_ON_ERROR);$c=$r['comparison'];$m=$c['postmeta'];
    if($r['cmsWritesPerformed']||$r['acceptancePerformed']||$r['baselineReplaced']||$r['privateValuesExported']||$r['externalWriterPauseCertified']||$m['rowsDeleted']||$m['deviationAccepted']){throw new RuntimeException('POSTMETA_SAFETY_FIELDS');}
    if(!$c['expectedDirectCapabilitiesMatchByUser'][1]||!$c['expectedDirectCapabilitiesMatchByUser'][2]){throw new RuntimeException('POSTMETA_CAPABILITIES_CHANGED');}
    if($r['observation']==='compare'&&(!$r['postmetaMatchesFirstObservation']||!$r['fullSnapshotAndCapabilitiesMatchFirstObservation'])){throw new RuntimeException('POSTMETA_UNEXPECTED_DRIFT');}
    if($mode==='matching'){
        if(!$m['baselinePostmetaMatches']||$c['changedSnapshotSections']||$m['baselineReconstructedByRemovingCurrentRowsInMemory']){throw new RuntimeException('POSTMETA_MATCHING');}
    }elseif($mode==='single-added'||$mode==='multiple-added'){
        if($m['baselinePostmetaMatches']||!$m['baselineReconstructedByRemovingCurrentRowsInMemory']||$c['changedSnapshotSections']!==['postmeta']||count($m['matchedAddedRowDescriptors'])!==($mode==='single-added'?1:2)){throw new RuntimeException('POSTMETA_ADDED');}
        $d=$m['matchedAddedRowDescriptors'];$locks=array_values(array_filter($d,static fn($v)=>$v['keyCategory']==='edit_lock'));
        if(count($locks)!==1||!$locks[0]['editorLockValueHasExpectedFormat']){throw new RuntimeException('POSTMETA_LOCK_DESCRIPTOR');}
        if(str_contains($line,'synthetic_sensitive_label')||str_contains($line,'SYNTHETIC_PRIVATE_VALUE')){throw new RuntimeException('POSTMETA_PRIVATE_VALUE_LEAK');}
    }elseif($mode==='updated'){
        if($m['baselinePostmetaMatches']||$m['baselineReconstructedByRemovingCurrentRowsInMemory']||$c['changedSnapshotSections']!==['postmeta']){throw new RuntimeException('POSTMETA_UPDATE_NOT_ADDITION');}
    }else{throw new RuntimeException('POSTMETA_TEST_MODE');}
}
