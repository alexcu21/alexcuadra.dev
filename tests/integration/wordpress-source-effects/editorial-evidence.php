<?php
namespace AlexCuadra\EditorialSourceExperiment;
$run=$args[0];$config=$args[1];
$text=file_get_contents('/check-editorial-evidence.sh');$code=explode("<<'OPERATING_EVIDENCE_PHP'\n",$text,2)[1];$code=explode('$config=$args[0]',$code,2)[0];eval(substr($code,5));
foreach(['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities'] as $module){require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/'.$module.'.php';}
deploy_scope_context(false);global $wpdb;
// This empty synthetic database has no production ACF post-type configuration.
// Reproduce the recorded public type in this fixture process only; never in the shipped checker.
if(!post_type_exists('project')){register_post_type('project',['public'=>true,'supports'=>['title','editor','custom-fields'],'show_in_graphql'=>true,'graphql_single_name'=>'project','graphql_plural_name'=>'projects']);}
$baseline=json_decode(base64_decode(trim(file_get_contents(getenv('HOME').'/.ac-editorial-deploy/'.$run.'/baseline.base64'))),true,32,JSON_THROW_ON_ERROR);
$proposal=(int)$wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_type='ac_private_proposal' AND post_author=2 ORDER BY ID LIMIT 1");
$prior=get_current_user_id();wp_set_current_user(0);wp_set_current_user(2);$loaded=\AlexCuadra\EditorialProposals\load_owned($proposal,'en');wp_set_current_user(0);wp_set_current_user($prior);
deploy_install_require(!is_wp_error($loaded),'EVIDENCE_FIXTURE_PAYLOAD_'.(is_wp_error($loaded)?$loaded->get_error_code():'ok'));$payload=$loaded['payload'];$source=deploy_recorded_project_snapshot();
$expected=['projectCount'=>$source['count'],'sourceHash'=>$source['hash'],'proposalId'=>$proposal,'projectId'=>$payload['project_id'],'baseVersion'=>$payload['base_version'],'versions'=>array_column($payload['history'],'version')];
$read=static fn()=>owned_transaction($GLOBALS['wpdb'],static fn()=>deploy_editorial_evidence($baseline,$config,$expected),true);
$check=static function($r,$sourceMatch,$ledgerMatch,$proposalMatch,$metaResolved){
    deploy_install_require($r['allProjectRowsMetadataAndRelationshipsMatchRecordedEvidence']===$sourceMatch&&$r['proposalLedgerMatchesGrantBaseline']===$ledgerMatch&&$r['privateProposal']['headAndHistoryPatchesMatchUnchangedLedger']===$proposalMatch&&$r['genericPostmetaDriftResolved']===$metaResolved,'EVIDENCE_RESULT');
    deploy_install_require(!$r['cmsWritesPerformed']&&!$r['acceptancePerformed']&&!$r['baselineReplaced']&&!$r['writerIdentityEstablished']&&!$r['privateProposal']['historyTimestampsReconstructed'],'EVIDENCE_CLAIMS');
};
$check($read(),true,true,true,true);
// A lock on the private proposal must not be treated as a resolved global drift.
deploy_install_require(!get_post_meta($proposal,'_edit_lock',false),'EVIDENCE_FIXTURE_LOCK');add_post_meta($proposal,'_edit_lock',time().':1');$check($read(),true,true,true,false);delete_post_meta($proposal,'_edit_lock');
// Protected metadata change must fail the independently pinned project fingerprint.
$old=get_post_meta($expected['projectId'],'project_tagline',true);update_post_meta($expected['projectId'],'project_tagline','Synthetic protected metadata drift');$r=$read();deploy_install_require(!$r['allProjectRowsMetadataAndRelationshipsMatchRecordedEvidence'],'EVIDENCE_PROJECT_DRIFT_MISSED');update_post_meta($expected['projectId'],'project_tagline',$old);
// Same opaque version cannot authenticate a changed head/history patch: compare receipts.
$raw=$loaded['row']['meta_value'];$modified=$payload;$modified['patch']['body_html']='Synthetic changed private patch';$modified['history'][count($modified['history'])-1]['patch']=$modified['patch'];
$wpdb->update($wpdb->postmeta,['meta_value'=>wp_json_encode($modified)],['meta_id'=>$loaded['row']['meta_id']]);$check($read(),true,true,false,false);$wpdb->update($wpdb->postmeta,['meta_value'=>$raw],['meta_id'=>$loaded['row']['meta_id']]);
// Ledger changes must invalidate corroboration even if payload and head version are valid.
$ledger=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->prefix.'ac_proposal_requests WHERE owner_id=2 AND proposal_id=%d LIMIT 1',$proposal),ARRAY_A);
$response=json_decode($ledger['response'],true);$response['synthetic_extra']='untrusted';$where=['owner_id'=>2,'request_id'=>$ledger['request_id']];
$wpdb->update($wpdb->prefix.'ac_proposal_requests',['response'=>wp_json_encode($response)],$where);$check($read(),true,false,false,true);$wpdb->update($wpdb->prefix.'ac_proposal_requests',['response'=>$ledger['response']],$where);
$check($read(),true,true,true,true);
echo 'EDITORIAL_EVIDENCE_SYNTHETIC_RESULT='.wp_json_encode(['checks'=>5,'passed'=>true,'projectDriftDetected'=>true,'sameVersionPatchTamperingDetected'=>true,'ledgerTamperingDetected'=>true,'unrelatedMetaNotSilentlyAccepted'=>true]).PHP_EOL;
