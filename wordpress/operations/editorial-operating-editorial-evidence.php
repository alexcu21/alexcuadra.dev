<?php
namespace AlexCuadra\EditorialSourceExperiment;
function deploy_recorded_project_snapshot(){
    global $wpdb;$ids=$wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type='project' ORDER BY ID");
    deploy_install_require(!$wpdb->last_error&&is_array($ids)&&count($ids)<=100,'EVIDENCE_PROJECT_IDS');$source=[];
    if($ids){$list=implode(',',array_map('intval',$ids));
        foreach(["SELECT * FROM $wpdb->posts WHERE ID IN ($list) ORDER BY ID","SELECT * FROM $wpdb->postmeta WHERE post_id IN ($list) ORDER BY meta_id","SELECT * FROM $wpdb->term_relationships WHERE object_id IN ($list) ORDER BY object_id,term_taxonomy_id"] as $sql){
            $rows=$wpdb->get_results($sql,ARRAY_A);deploy_install_require(!$wpdb->last_error&&is_array($rows),'EVIDENCE_PROJECT_READ');$source[]=$rows;
        }
    }return ['count'=>count($ids),'hash'=>hash('sha256',serialize($source))];
}
function deploy_recorded_proposal_check($expected,$ledgerUnchanged){
    global $wpdb;$out=['availableAndValid'=>false,'recordedIdentityAndVersionsMatch'=>false,'headAndHistoryPatchesMatchUnchangedLedger'=>false,'historyTimestampsReconstructed'=>false];$operator=get_current_user_id();
    try{
        wp_set_current_user(0);wp_set_current_user(2);
        $loaded=\AlexCuadra\EditorialProposals\load_owned($expected['proposalId'],'en');
        if(is_wp_error($loaded)){return $out;}$out['availableAndValid']=true;$payload=$loaded['payload'];
        $out['recordedIdentityAndVersionsMatch']=$payload['project_id']===$expected['projectId']&&$payload['locale']==='en'&&$payload['base_version']===$expected['baseVersion']&&$payload['version']===end($expected['versions'])&&array_column($payload['history'],'version')===$expected['versions'];
        $rows=$wpdb->get_results($wpdb->prepare('SELECT response FROM '.$wpdb->prefix.'ac_proposal_requests WHERE owner_id=2 AND proposal_id=%d',$expected['proposalId']),ARRAY_A);
        deploy_install_require(!$wpdb->last_error&&is_array($rows),'EVIDENCE_LEDGER_READ');$responses=[];$consistent=count($rows)===count($expected['versions']);
        foreach($rows as $row){$r=json_decode($row['response'],true);if(!is_array($r)||!is_string($r['version']??null)||isset($responses[$r['version']])){$consistent=false;continue;}$responses[$r['version']]=$r;}
        foreach($payload['history'] as $entry){$r=$responses[$entry['version']]??null;
            if(!is_array($r)||($r['proposal_id']??null)!==$expected['proposalId']||($r['project_id']??null)!==$expected['projectId']||($r['locale']??null)!=='en'||($r['base_version']??null)!==$expected['baseVersion']||($r['state']??null)!=='draft'||($r['applied']??null)!==false||\AlexCuadra\EditorialProposals\canonical($r['patch']??null)!==\AlexCuadra\EditorialProposals\canonical($entry['patch'])){$consistent=false;}
        }
        $head=$responses[$payload['version']]??null;
        $out['headAndHistoryPatchesMatchUnchangedLedger']=$ledgerUnchanged&&$out['recordedIdentityAndVersionsMatch']&&$consistent&&is_array($head)&&\AlexCuadra\EditorialProposals\canonical($head['patch']??null)===\AlexCuadra\EditorialProposals\canonical($payload['patch']);return $out;
    }finally{wp_set_current_user(0);wp_set_current_user($operator);}
}
function deploy_editorial_evidence($baseline,$config,$expected){
    $snapshot=deploy_scope_snapshot($config);$changed=[];
    foreach(array_unique(array_merge(array_keys($baseline['snapshot']),array_keys($snapshot))) as $key){if(($snapshot[$key]??null)!==($baseline['snapshot'][$key]??null)){$changed[]=$key;}}
    $projects=deploy_recorded_project_snapshot();$projectMatch=$projects['count']===$expected['projectCount']&&$projects['hash']===$expected['sourceHash'];
    $ledgerUnchanged=isset($baseline['snapshot']['ac_proposal_requests'])&&$snapshot['ac_proposal_requests']===$baseline['snapshot']['ac_proposal_requests'];
    $caps=deploy_scope_caps_hash(deploy_scope_caps());$byUser=[];foreach([1,2] as $id){$byUser[$id]=($caps[$id]??null)===($baseline['expectedCaps'][$id]??null);}
    $proposal=deploy_recorded_proposal_check($expected,$ledgerUnchanged);
    return ['projectCount'=>$projects['count'],'allProjectRowsMetadataAndRelationshipsMatchRecordedEvidence'=>$projectMatch,
        'grantBaselineChangedSections'=>$changed,'expectedDirectCapabilitiesMatchByUser'=>$byUser,'proposalLedgerMatchesGrantBaseline'=>$ledgerUnchanged,'privateProposal'=>$proposal,
        'genericPostmetaDriftResolved'=>!in_array('postmeta',$changed,true),
        'writerIdentityEstablished'=>false,'acceptancePerformed'=>false,'cmsWritesPerformed'=>false,'baselineReplaced'=>false,'privateValuesExported'=>false];
}
$config=$args[0]??'';$baselinePath=$args[1]??'';$run=$args[2]??'';
deploy_install_require(is_file($baselinePath)&&!is_link($baselinePath),'EVIDENCE_BASELINE_PATH');
$baseline=json_decode(base64_decode(trim(file_get_contents($baselinePath)),true),true,32,JSON_THROW_ON_ERROR);
deploy_install_require(is_array($baseline['snapshot']??null)&&is_array($baseline['expectedCaps']??null),'EVIDENCE_BASELINE_FORMAT');
deploy_install_verify_files();foreach(['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities'] as $module){require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/'.$module.'.php';}
deploy_scope_context(false);
$expected=['projectCount'=>14,'sourceHash'=>'c38acd1690f173488f075443d03a80c96b6b483a5bd407040cbbd40a7ff462af','proposalId'=>227,'projectId'=>146,
    'baseVersion'=>'bbca3d9ada7d199ebd4911b39625bcdd0b6d1285c0793562a6bbb06df6074d85',
    'versions'=>['4adb6146536c24eade19dda7799ec4ff13579e9ca1d26ac94e9c6149270bbfb7','811c2dc75e6640e11ba2fd929812c56ecde11b08557a3d7e48c8ff144ccdbab7','737a0558179ee7df25706593918782eefb24297135fa4860090af450e4560b44']];
$evidence=owned_transaction($GLOBALS['wpdb'],static fn()=>deploy_editorial_evidence($baseline,$config,$expected),true);
echo 'OPERATING_EDITORIAL_EVIDENCE='.wp_json_encode(['format'=>'editorial-operating-editorial-evidence-v1','grantRun'=>$run,'checks'=>$evidence]).PHP_EOL;
