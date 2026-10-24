<?php
namespace AlexCuadra\EditorialSourceExperiment;
function deploy_scope_map(){return [1=>[OperatingContract::APPROVE],2=>[OperatingContract::APPLY,OperatingContract::RECOVER]];}
function deploy_scope_caps($lock=false){
    global $wpdb;$out=[];
    foreach(deploy_scope_map() as $id=>$scopes){$rows=$wpdb->get_results($wpdb->prepare("SELECT umeta_id,meta_value FROM $wpdb->usermeta WHERE user_id=%d AND meta_key=%s".($lock?' FOR UPDATE':''),$id,$wpdb->prefix.'capabilities'),ARRAY_A);
        deploy_install_require(!$wpdb->last_error&&count($rows??[])===1,'SCOPES_CAPABILITY_ROW');$caps=unserialize($rows[0]['meta_value'],['allowed_classes'=>false]);deploy_install_require(is_array($caps),'SCOPES_CAPABILITY_VALUE');foreach($caps as $key=>$value){deploy_install_require(is_string($key)&&is_bool($value),'SCOPES_CAPABILITY_VALUE');}$out[$id]=$caps;
    }return $out;
}
function deploy_scope_caps_hash($maps){$out=[];foreach($maps as $id=>$caps){ksort($caps);$out[$id]=hash('sha256',serialize($caps));}return $out;}
function deploy_scope_snapshot($config){
    global $wpdb;$s=deploy_activation_snapshot($config);$s['configFullSha256']=hash_file('sha256',$config);$rows=$wpdb->get_results("SELECT * FROM $wpdb->usermeta",ARRAY_A);deploy_install_require(!$wpdb->last_error&&is_array($rows),'SCOPES_METADATA_READ');
    foreach($rows as &$row){$id=(int)$row['user_id'];if(isset(deploy_scope_map()[$id])&&$row['meta_key']===$wpdb->prefix.'capabilities'){$caps=unserialize($row['meta_value'],['allowed_classes'=>false]);deploy_install_require(is_array($caps),'SCOPES_CAPABILITY_VALUE');foreach(deploy_scope_map()[$id] as $cap){unset($caps[$cap]);}ksort($caps);$row['meta_value']=serialize($caps);}}unset($row);
    $encoded=array_map('serialize',$rows);sort($encoded,SORT_STRING);$s['usermeta']=hash('sha256',serialize($encoded));
    foreach(['source_events','source_effects','operating_reviews','operating_approvals','operating_requests','operating_apps'] as $suffix){$rows=$wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'ac_exp_'.$suffix,ARRAY_A);deploy_install_require(!$wpdb->last_error&&is_array($rows),'SCOPES_STORAGE_READ');$encoded=array_map('serialize',$rows);sort($encoded,SORT_STRING);$s[$suffix]=hash('sha256',serialize($encoded));}
    $s['storageMarker']=hash('sha256',serialize(get_option('ac_exp_operating_storage')));return $s;
}
function deploy_scope_context($empty){
    global $wpdb;deploy_install_verify_files();production_wp_guard();deploy_install_require(defined('WP_CLI')&&WP_CLI&&get_current_user_id()===1&&current_user_can('manage_options'),'SCOPES_OPERATOR');require_operating_storage_ready($wpdb);operating_storage_schema($wpdb);(new MariaDbOperation($wpdb))->schema();
    $u=get_userdata(2);deploy_install_require($u&&$u->roles===['subscriber'],'SCOPES_TARGET_CHANGED');foreach(['read','alexcuadra_read_public_projects','alexcuadra_prepare_project_changes'] as $cap){deploy_install_require($u->has_cap($cap),'SCOPES_TARGET_CHANGED');}
    foreach(['manage_options','edit_posts','edit_pages','install_plugins','activate_plugins','edit_users','promote_users','publish_posts','publish_pages'] as $cap){deploy_install_require(!$u->has_cap($cap),'SCOPES_TARGET_ESCALATED');}
    if($empty){foreach(['source_events','source_effects','operating_reviews','operating_approvals','operating_requests','operating_apps'] as $suffix){$n=$wpdb->get_var('SELECT COUNT(*) FROM '.$wpdb->prefix.'ac_exp_'.$suffix);deploy_install_require(!$wpdb->last_error&&$n!==null&&(int)$n===0,'SCOPES_EXPECTED_INITIAL_EMPTY_STORAGE');}}
}
function deploy_scope_expected($before,$grant){$out=$before;foreach(deploy_scope_map() as $id=>$scopes){foreach($scopes as $cap){if($grant){deploy_install_require(!array_key_exists($cap,$out[$id]),'SCOPES_ALREADY_DEFINED');$out[$id][$cap]=true;}elseif(($out[$id][$cap]??null)===true){unset($out[$id][$cap]);}}}return $out;}
function deploy_scope_mutate($grant,$baseline,$config){
    global $wpdb;$operator=get_current_user_id();
    try{return owned_transaction($wpdb,function()use($grant,$baseline,$config){
        $caps=deploy_scope_caps(true);$expected=deploy_scope_expected($caps,$grant);deploy_install_require(deploy_scope_snapshot($config)===$baseline['snapshot'],'SCOPES_BASELINE_CHANGED');
        deploy_install_require(deploy_scope_caps_hash($expected)===$baseline['expectedCaps'],'SCOPES_EXPECTED_CAPS_CHANGED');
        foreach(deploy_scope_map() as $id=>$scopes){clean_user_cache($id);$u=get_userdata($id);foreach($scopes as $cap){if($grant){$u->add_cap($cap,true);}elseif(($u->caps[$cap]??null)===true){$u->remove_cap($cap);}}clean_user_cache($id);}
        deploy_install_require(deploy_scope_caps_hash(deploy_scope_caps())===$baseline['expectedCaps'],'SCOPES_UNEXPECTED_DIRECT_CHANGE');
        deploy_install_require(deploy_scope_snapshot($config)===$baseline['snapshot'],'SCOPES_UNEXPECTED_OTHER_CHANGE');return true;
    });}finally{foreach([1,2] as $id){clean_user_cache($id);}wp_set_current_user(0);wp_set_current_user($operator);}
}
function deploy_scope_permissions(){
    $prior=get_current_user_id();$allowed=0;$denied=0;
    try{foreach([0,1,2] as $id){wp_set_current_user(0);wp_set_current_user($id);foreach(operating_ability_specs() as $name=>$spec){$ability=wp_get_ability('alexcuadra/'.$name);deploy_install_require($ability instanceof \WP_Ability,'SCOPES_ABILITY_MISSING');$r=$ability->check_permissions(null);if($id===2){deploy_install_require($r===true,'SCOPES_TARGET_PERMISSION_NOT_GRANTED');$allowed++;}else{deploy_install_require(is_wp_error($r)&&$r->get_error_code()==='editorial_forbidden','SCOPES_OTHER_ACCOUNT_ALLOWED');$denied++;}}
        if($id===2){deploy_install_require(!current_user_can(OperatingContract::APPROVE)&&!current_user_can('manage_options')&&!current_user_can('edit_posts'),'SCOPES_APPROVER_OR_EDITOR_LEAK');}
        if($id===1){deploy_install_require(current_user_can(OperatingContract::APPROVE)&&current_user_can('manage_options')&&!current_user_can(OperatingContract::APPLY)&&!current_user_can(OperatingContract::RECOVER),'SCOPES_OPERATOR_LEAK');}
    }}finally{wp_set_current_user(0);wp_set_current_user($prior);}return ['allowed'=>$allowed,'denied'=>$denied];
}
$phase=$args[0]??'';$config=$args[1]??'';$run=$args[3]??'';
deploy_install_verify_files();foreach(['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities'] as $module){require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/'.$module.'.php';}
$revoke=in_array($phase,['before-revoke','revoke','revoked'],true);
if(!$revoke){deploy_install_require(($args[4]??null)==='external-writers-stopped','SCOPES_EXTERNAL_PAUSE_REQUIRED');}
deploy_scope_context(!$revoke);
if(in_array($phase,['before','before-revoke'],true)){
    if(!$revoke){deploy_activation_no_grants();}$caps=deploy_scope_caps();$expected=deploy_scope_expected($caps,!$revoke);
    $baseline=['snapshot'=>deploy_scope_snapshot($config),'expectedCaps'=>deploy_scope_caps_hash($expected)];
    echo 'OPERATING_SCOPES_BASELINE='.base64_encode(wp_json_encode($baseline)).PHP_EOL;
}elseif(in_array($phase,['grant','revoke'],true)){
    $baseline=json_decode(base64_decode($args[2],true),true,32,JSON_THROW_ON_ERROR);deploy_scope_mutate(!$revoke,$baseline,$config);echo "OPERATING_SCOPES_MUTATION_COMMITTED\n";
}elseif(in_array($phase,['after','revoked'],true)){
    $baseline=json_decode(base64_decode($args[2],true),true,32,JSON_THROW_ON_ERROR);deploy_install_require(deploy_scope_caps_hash(deploy_scope_caps())===$baseline['expectedCaps']&&deploy_scope_snapshot($config)===$baseline['snapshot'],'SCOPES_POSTFLIGHT_CHANGED');
    if(!$revoke){$p=deploy_scope_permissions();echo 'OPERATING_SCOPES_RESULT='.wp_json_encode(['version'=>OPERATING_VERSION,'candidateSha256'=>DEPLOY_INSTALL_SHA256,'grantRun'=>$run,'user2Added'=>[OperatingContract::APPLY,OperatingContract::RECOVER],'operator1Added'=>[OperatingContract::APPROVE],'onlyExpectedDirectCapabilitiesChanged'=>true,'subscriberRoleUnchanged'=>true,'otherRolesAndUserMetadataUnchanged'=>true,'sourcesAndPrivateProposalsUnchanged'=>true,'configurationAndActivePluginsUnchanged'=>true,'operatingStorageUnchanged'=>true,'allowedPermissionChecks'=>$p['allowed'],'deniedPermissionChecks'=>$p['denied'],'sourceEditAdminDeniedForUser2'=>true,'approvalOrProposalCreated'=>false,'editorialDataWritten'=>false,'globalStorageAcceptancePerformed'=>false]).PHP_EOL;}
    else{echo 'OPERATING_SCOPES_REVOKED='.wp_json_encode(['version'=>OPERATING_VERSION,'grantRun'=>$run,'onlyThisRunTrueDirectScopesRemoved'=>true,'otherPermissionsAndDataUnchanged'=>true,'tablesAndHistoryRetained'=>true,'editorialDataWritten'=>false]).PHP_EOL;}
}elseif($phase==='accept'){
    $before=deploy_scope_snapshot($config);$p=deploy_scope_permissions();$accepted=\AlexCuadra\EditorialOperatingPackage\controller()->acceptStorage(true,100);deploy_install_require($accepted['accepted']===true&&deploy_scope_snapshot($config)===$before,'SCOPES_ACCEPTANCE_CHANGED_DATA');
    echo 'OPERATING_STORAGE_ACCEPTANCE_RESULT='.wp_json_encode(['version'=>OPERATING_VERSION,'candidateSha256'=>DEPLOY_INSTALL_SHA256,'acceptance'=>$accepted,'allowedPermissionChecks'=>$p['allowed'],'deniedPermissionChecks'=>$p['denied'],'sourcesRolesPermissionsAndStorageUnchanged'=>true,'editorialDataWritten'=>false,'externalPause'=>'operator-attested','softwareEnforcesExternalPause'=>false]).PHP_EOL;
}else{throw new \RuntimeException('SCOPES_EXPECTED_PHASE');}
