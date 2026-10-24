<?php
namespace AlexCuadra\EditorialSourceExperiment;
function deploy_activation_require($ok,$code){deploy_install_require($ok,$code);}
function deploy_activation_snapshot($config){
    $s=deploy_install_snapshot();$active=array_values(array_diff(get_option('active_plugins',[]),['alexcuadra-editorial-operating/alexcuadra-editorial-operating.php']));sort($active,SORT_STRING);$s['activePlugins']=hash('sha256',serialize($active));
    deploy_activation_require(is_file($config)&&!is_link($config)&&in_array(realpath($config),[realpath(ABSPATH.'wp-config.php'),realpath(dirname(rtrim(ABSPATH,'/')).'/wp-config.php')],true),'ACTIVATION_CONFIG_PATH');
    $tokens=[];foreach(token_get_all(file_get_contents($config)) as $t){if(is_array($t)&&in_array($t[0],[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT],true)){continue;}$tokens[]=is_array($t)?$t[1]:$t;}
    $filtered=[];$count=0;
    for($i=0;$i<count($tokens);$i++){
        if(strtolower($tokens[$i])==='define'&&($tokens[$i+1]??null)==='('&&in_array($tokens[$i+2]??null,["'AC_EDITORIAL_OPERATING_SITE'",'"AC_EDITORIAL_OPERATING_SITE"'],true)){
            deploy_activation_require(array_slice($tokens,$i+3,4)=== [',',"'https://content.alexcuadra.dev'",')',';']||array_slice($tokens,$i+3,4)===[',','"https://content.alexcuadra.dev"',')',';'],'ACTIVATION_CONFIG_VALUE_CHANGED');
            deploy_activation_require(++$count===1,'ACTIVATION_DUPLICATE_OPTIN');$i+=6;continue;
        }$filtered[]=$tokens[$i];
    }
    $s['configCodeWithoutOperatingSite']=hash('sha256',serialize($filtered));$s['configMode']=fileperms($config)&0777;
    return $s;
}
function deploy_activation_no_grants(){
    global $wpdb;$caps=['alexcuadra_apply_project_changes','alexcuadra_recover_project_changes','alexcuadra_approve_public_project_changes'];
    foreach(get_option($wpdb->prefix.'user_roles',[]) as $role){foreach($caps as $cap){deploy_activation_require(empty($role['capabilities'][$cap]),'ACTIVATION_UNEXPECTED_ROLE_SCOPE');}}
    $ids=$wpdb->get_col("SELECT ID FROM $wpdb->users ORDER BY ID");deploy_activation_require(!$wpdb->last_error&&is_array($ids),'ACTIVATION_ACCOUNT_CHECK');
    foreach($ids as $id){$u=get_userdata((int)$id);deploy_activation_require((bool)$u,'ACTIVATION_ACCOUNT_CHECK');foreach($caps as $cap){deploy_activation_require(!$u->has_cap($cap),'ACTIVATION_UNEXPECTED_USER_SCOPE');}}
}
function deploy_activation_compare($before,$config){deploy_activation_require(is_array($before)&&deploy_activation_snapshot($config)===$before,'ACTIVATION_SOURCE_ROLES_LEDGER_OR_CONFIG_CHANGED');}
function deploy_activation_prospective(){
    $p=production_profile();foreach($p['plugins'] as &$r){if($r['file']==='alexcuadra-editorial-operating/alexcuadra-editorial-operating.php'){$r['active']=true;}}unset($r);
    $p['loadedPlugins']['alexcuadra-editorial-operating/alexcuadra-editorial-operating.php']=true;ksort($p['loadedPlugins']);validate_production_profile($p);
}
function deploy_activation_denied_accounts($active){
    $prior=get_current_user_id();$count=0;
    try{foreach([0,1,2] as $id){wp_set_current_user(0);wp_set_current_user($id);
        foreach(operating_ability_specs() as $name=>$spec){$ability=wp_get_ability('alexcuadra/'.$name);
            if(!$active){deploy_activation_require($ability===null,'ACTIVATION_ABILITY_REMAINED_REGISTERED');continue;}
            deploy_activation_require($ability instanceof \WP_Ability,'ACTIVATION_ABILITY_MISSING');$input=[];
            foreach($spec['input']['properties'] as $key=>$schema){$input[$key]=$key==='locale'?'en':($schema['type']==='integer'?1:str_repeat('0',$key==='request_id'?32:64));}
            $permission=$ability->check_permissions($input);deploy_activation_require(is_wp_error($permission)&&$permission->get_error_code()==='editorial_forbidden','ACTIVATION_PERMISSION_NOT_DENIED');$count++;
        }
    }}finally{wp_set_current_user(0);wp_set_current_user($prior);}return $count;
}
$phase=$args[0]??'';$config=$args[1]??'';$slug='alexcuadra-editorial-operating/alexcuadra-editorial-operating.php';
deploy_install_verify_files();
foreach(['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities'] as $module){require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/'.$module.'.php';}
deploy_activation_require(defined('WP_CLI')&&WP_CLI&&get_current_user_id()===1&&current_user_can('manage_options'),'ACTIVATION_CLI_OPERATOR');if(in_array($phase,['before','migrate','after'],true)){deploy_activation_no_grants();}
if($phase==='before'){
    deploy_install_context();deploy_activation_require(!is_plugin_active($slug),'ACTIVATION_ALREADY_ACTIVE');
    echo 'OPERATING_ACTIVATION_BASELINE='.base64_encode(wp_json_encode(deploy_activation_snapshot($config))).PHP_EOL;
}elseif(in_array($phase,['migrate','after'],true)&&count($args)===4){
    $before=json_decode(base64_decode($args[2],true),true,32,JSON_THROW_ON_ERROR);$run=$args[3];deploy_activation_compare($before,$config);
    production_wp_guard();deploy_activation_require(is_plugin_active($slug),'ACTIVATION_PLUGIN_INACTIVE');
    $c=\AlexCuadra\EditorialOperatingPackage\controller();
    if($phase==='migrate'){$marker=$c->install();deploy_activation_require($marker===operating_storage_marker($GLOBALS['wpdb'],'ready'),'ACTIVATION_STORAGE_NOT_READY');echo "OPERATING_MIGRATION_READY\n";return;}
    global $wpdb;require_operating_storage_ready($wpdb);operating_storage_schema($wpdb);(new MariaDbOperation($wpdb))->schema();$bytes=0;
    foreach(['source_events','source_effects','operating_reviews','operating_approvals','operating_requests','operating_apps'] as $suffix){$table=$wpdb->prefix.'ac_exp_'.$suffix;$n=$wpdb->get_var("SELECT COUNT(*) FROM $table");deploy_activation_require(!$wpdb->last_error&&$n!==null&&(int)$n===0,'ACTIVATION_STORAGE_NOT_EMPTY');$size=$wpdb->get_var($wpdb->prepare('SELECT COALESCE(DATA_LENGTH,0)+COALESCE(INDEX_LENGTH,0) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));deploy_activation_require(!$wpdb->last_error&&$size!==null,'ACTIVATION_STORAGE_METADATA');$bytes+=(int)$size;}
    $denials=deploy_activation_denied_accounts(true);deploy_activation_compare($before,$config);
    echo 'OPERATING_ACTIVATION_STORAGE_RESULT='.wp_json_encode(['version'=>OPERATING_VERSION,'candidateSha256'=>DEPLOY_INSTALL_SHA256,'activationRun'=>$run,'active'=>true,'siteOptin'=>OPERATING_SITE,'storageSchemaVerified'=>true,'newStorageEmpty'=>true,'tableCount'=>6,'tableDataAndIndexBytesApproximate'=>$bytes,'permissionDenialsVerified'=>$denials,'projectCount'=>$before['projectCount'],'sourcesAndPrivateProposalsUnchanged'=>true,'userMetadataAndRolesUnchanged'=>true,'otherActivePluginsUnchanged'=>true,'otherConfigCodeUnchanged'=>true,'newGlobalScopesAbsent'=>true,'capabilitiesAdded'=>false,'globalStorageAcceptancePerformed'=>false,'editorialDataWritten'=>false]).PHP_EOL;
}elseif(in_array($phase,['pre-rollback','rolled-back'],true)&&count($args)===4){
    $before=json_decode(base64_decode($args[2],true),true,32,JSON_THROW_ON_ERROR);deploy_activation_prospective();
    deploy_activation_require(!defined('AC_EDITORIAL_OPERATING_LOCAL')&&!defined('AC_EDITORIAL_SOURCE_EXPERIMENT')&&(!defined('AC_EDITORIAL_OPERATING_SITE')||AC_EDITORIAL_OPERATING_SITE===OPERATING_SITE),'ACTIVATION_ROLLBACK_OPTIN_CHANGED');
    if($phase==='pre-rollback'){echo 'OPERATING_ROLLBACK_BASELINE='.base64_encode(wp_json_encode(deploy_activation_snapshot($config))).PHP_EOL;echo 'OPERATING_ROLLBACK_SITE_PRESENT='.(defined('AC_EDITORIAL_OPERATING_SITE')?'1':'0').PHP_EOL;return;}
    deploy_activation_compare($before,$config);
    deploy_activation_require(!is_plugin_active($slug)&&!defined('AC_EDITORIAL_OPERATING_SITE'),'ACTIVATION_ROLLBACK_NOT_DISABLED');deploy_activation_denied_accounts(false);
    global $wpdb;$n=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (%s,%s,%s,%s,%s,%s)",...array_map(static fn($s)=>$wpdb->prefix.'ac_exp_'.$s,['source_events','source_effects','operating_reviews','operating_approvals','operating_requests','operating_apps'])));deploy_activation_require(!$wpdb->last_error&&$n!==null,'ACTIVATION_ROLLBACK_METADATA');
    echo 'OPERATING_DISABLE_RESULT='.wp_json_encode(['version'=>OPERATING_VERSION,'activationRun'=>$args[3],'comparisonBasis'=>'immediately-before-disable','active'=>false,'siteOptinAbsent'=>true,'tablesRetained'=>(int)$n,'sourcesAndPrivateProposalsUnchanged'=>true,'userMetadataAndRolesUnchanged'=>true,'otherConfigCodeUnchanged'=>true,'capabilitiesAdded'=>false,'tablesDropped'=>false]).PHP_EOL;
}else{throw new \RuntimeException('ACTIVATION_EXPECTED_PHASE');}
