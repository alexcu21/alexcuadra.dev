<?php
// Pure fingerprint proof over synthetic rows; no WordPress, database, Docker or SSH.
$code=explode('$config=$args[0]',file_get_contents(__DIR__.'/../../../wordpress/operations/editorial-operating-lock-check.php'),2)[0];eval(substr($code,5));
function lock_test_require($ok){if(!$ok){throw new RuntimeException('LOCK_FINGERPRINT_TEST_FAILED');}}
function lock_test_hash($rows){$values=array_map('serialize',$rows);sort($values,SORT_STRING);return hash('sha256',serialize($values));}
$anchor=1700000300;
$baseline=[
    ['meta_id'=>'9','post_id'=>'12','meta_key'=>'project_tagline','meta_value'=>'SYNTHETIC_PRIVATE_VALUE'],
    ['meta_id'=>'2','post_id'=>'11','meta_key'=>'_edit_lock','meta_value'=>($anchor-45).':7'],
    ['meta_id'=>'3','post_id'=>'12','meta_key'=>'_edit_lock','meta_value'=>($anchor-90).':8'],
];
$hash=lock_test_hash($baseline);$read=static fn($rows,$bytes=536870912)=>AlexCuadra\EditorialSourceExperiment\deploy_lock_fingerprint_evidence($rows,$hash,$anchor,$bytes);
$r=$read($baseline);lock_test_require($r['baselinePostmetaMatchesCurrent']&&!$r['singleLockTimestampDifferenceReconstructsBaselineExactly']&&$r['allOtherPostmetaRowsMatchGrantBaseline']);
$current=$baseline;$current[1]['meta_value']=($anchor+2).':7';$copy=serialize($current);$r=$read($current);
lock_test_require($r['singleLockTimestampDifferenceReconstructsBaselineExactly']&&$r['allOtherPostmetaRowsMatchGrantBaseline']&&$r['matchingRow']===['metaId'=>2,'postId'=>11,'metaKey'=>'_edit_lock','onlyTimestampChangedSameUser'=>true]);
$changed=$current;$changed[0]['meta_value']='SYNTHETIC_CHANGED_CONTENT';$r=$read($changed);lock_test_require(!$r['singleLockTimestampDifferenceReconstructsBaselineExactly']&&!$r['allOtherPostmetaRowsMatchGrantBaseline']);
$changed=$current;$changed[2]['meta_value']=($anchor+4).':8';$r=$read($changed);lock_test_require(!$r['singleLockTimestampDifferenceReconstructsBaselineExactly']);
$changed=$current;$changed[1]['meta_value']=($anchor+2).':9';$r=$read($changed);lock_test_require(!$r['singleLockTimestampDifferenceReconstructsBaselineExactly']);
$outOfRange=$baseline;$outOfRange[1]['meta_value']=($anchor-601).':7';$r=AlexCuadra\EditorialSourceExperiment\deploy_lock_fingerprint_evidence($current,lock_test_hash($outOfRange),$anchor);lock_test_require(!$r['singleLockTimestampDifferenceReconstructsBaselineExactly']);
$r=$read($current,0);lock_test_require($r['boundedSearchStopped']&&$r['attempts']===0&&!$r['singleLockTimestampDifferenceReconstructsBaselineExactly']);
$r=$read($current);$public=json_encode($r,JSON_THROW_ON_ERROR);lock_test_require(serialize($current)===$copy&&!str_contains($public,'SYNTHETIC_PRIVATE_VALUE')&&!str_contains($public,(string)$anchor)&&!$r['cmsWritesPerformed']&&!$r['deviationAccepted']&&!$r['writerIdentityEstablished']);
echo json_encode(['format'=>'editorial-operating-lock-fingerprint-qa-v1','checks'=>8,'passed'=>true,'php'=>PHP_VERSION,'syntheticRowsOnly'=>true,'databaseUsed'=>false,'dockerUsed'=>false,'sshExecuted'=>false,'covers'=>['unchanged-table','exact-single-timestamp-reconstruction','other-content-change-rejected','two-lock-changes-inconclusive','changed-user-inconclusive','outside-window-inconclusive','budget-stop-inconclusive','immutable-input-and-no-value-export']],JSON_THROW_ON_ERROR).PHP_EOL;
