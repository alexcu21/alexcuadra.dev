<?php
namespace AlexCuadra\EditorialSourceExperiment;

// The inspection contract uses SELECT FOR UPDATE. Run that existing method
// separately from the READ ONLY snapshots, with a SELECT-only wpdb guard.
function deploy_operator_read_queries($sql){
    deploy_install_require((bool)preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)\b/i',$sql),'OPERATOR_NON_READ_QUERY');
    return $sql;
}
function deploy_operator_observe($config){
    global $wpdb;
    return owned_transaction($wpdb,function()use($wpdb,$config){
        $counts=[];
        foreach(['operating_reviews'=>1,'operating_requests'=>1,'operating_approvals'=>0,'operating_apps'=>0,'source_events'=>0,'source_effects'=>0] as $suffix=>$expected){
            $n=$wpdb->get_var('SELECT COUNT(*) FROM '.$wpdb->prefix.'ac_exp_'.$suffix);
            deploy_install_require(!$wpdb->last_error&&$n!==null&&(int)$n===$expected,'OPERATOR_UNEXPECTED_STORAGE_COUNTS');
            $counts[$suffix]=(int)$n;
        }
        $audit=audit_operating_owner($wpdb,2,false);
        deploy_install_require($audit===['owner_id'=>2,'reviews'=>1,'requests'=>1,'applications'=>0,'approvals'=>0,'events'=>0],'OPERATOR_OWNER_AUDIT');
        $usage=\AlexCuadra\EditorialOperatingPackage\controller()->usage(2);
        deploy_install_require($usage['reviews']===1&&$usage['requests']===1&&$usage['bytes']>0&&$usage['bytes']<=OperatingContract::MAX_BYTES,'OPERATOR_OWNER_QUOTA');
        return ['snapshot'=>deploy_scope_snapshot($config),'caps'=>deploy_scope_caps_hash(deploy_scope_caps()),'counts'=>$counts,'audit'=>$audit,'usage'=>$usage];
    },true);
}
function deploy_operator_check($config){
    deploy_scope_context(false);
    $before=deploy_operator_observe($config);
    $permissions=deploy_scope_permissions();
    $prior=get_current_user_id();$inspections=[];
    add_filter('query',__NAMESPACE__.'\\deploy_operator_read_queries',0,1);
    try{
        foreach([1,2] as $id){
            wp_set_current_user(0);wp_set_current_user($id);
            $r=\AlexCuadra\EditorialOperatingPackage\controller()->inspect(1,'en');
            deploy_install_require(($r['review_hash']??null)==='ecaf5b7835e98fb157e70e852aa718322e8df5f6d82c807040b1489f673e9738','OPERATOR_REVIEW_HASH');
            $d=$r['document']??[];
            deploy_install_require(($d['owner_id']??null)===2&&($d['proposal_id']??null)===227&&($d['project_id']??null)===146&&($d['locale']??null)==='en'&&($d['operation']??null)==='apply','OPERATOR_REVIEW_IDENTITY');
            $inspections[$id]=$r;
        }
        deploy_install_require($inspections[1]===$inspections[2],'OPERATOR_INSPECTION_MISMATCH');
    }finally{
        wp_set_current_user(0);wp_set_current_user($prior);
        remove_filter('query',__NAMESPACE__.'\\deploy_operator_read_queries',0);
    }
    $after=deploy_operator_observe($config);
    deploy_install_require($before===$after,'OPERATOR_OBSERVATION_CHANGED');
    return ['format'=>'editorial-operating-operator-check-v1','version'=>OPERATING_VERSION,
        'candidateSha256'=>DEPLOY_INSTALL_SHA256,'reviewId'=>1,'ownerId'=>2,'operatorId'=>1,
        'operatorCliInspectionMatchesOwner'=>true,'allowedPermissionChecks'=>$permissions['allowed'],
        'deniedPermissionChecks'=>$permissions['denied'],'counts'=>$after['counts'],'ownerAudit'=>$after['audit'],
        'ownerLogicalUsage'=>$after['usage'],'beforeAfterSnapshotAndCapabilitiesMatch'=>true,
        'auditSnapshot'=>'READ ONLY/REPEATABLE READ','inspectionContext'=>'server-side CLI with SELECT-only wpdb guard',
        'authenticatedOtherOwnerHttpVerified'=>false,'historicalPostmetaDriftResolved'=>false,
        'approvalPerformed'=>false,'editorialDataWritten'=>false,'privateValuesExported'=>false];
}
