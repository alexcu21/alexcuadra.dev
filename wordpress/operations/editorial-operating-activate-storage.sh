#!/bin/sh
# Prepared for separate authorization: site opt-in + activation + six empty tables, no grants.
set -eu
umask 077
cd "$HOME/domains/alexcuadra.dev/public_html/content"
test -d "$HOME/.ac-editorial-deploy"
work="$(mktemp -d "$HOME/.ac-editorial-deploy/operating-activate.XXXXXXXX")"
run="$(basename "$work")"
printf '%s\n' EDITORIAL_OPERATING_ACTIVATE_STORAGE_V1
cat > "$work/check.php" <<'OPERATING_ACTIVATION_CHECK_PHP'
<?php
// Appended to the sealed admission module in a private deployment workspace.
namespace AlexCuadra\EditorialSourceExperiment;
function deploy_install_require($ok,$code){if(!$ok){throw new \RuntimeException($code);}}
function deploy_install_snapshot(){
    global $wpdb;
    deploy_install_require(preg_match('/^[a-zA-Z0-9_]+$/D',$wpdb->prefix)===1,'INSTALL_PREFIX');
    $hashes=[];
    foreach(['posts','postmeta','usermeta','terms','term_taxonomy','term_relationships','ac_proposal_owners','ac_proposal_requests'] as $name){
        $rows=$wpdb->get_results('SELECT * FROM '.$wpdb->prefix.$name,ARRAY_A);
        deploy_install_require(!$wpdb->last_error&&is_array($rows),'INSTALL_SNAPSHOT_FAILED');
        $encoded=array_map('serialize',$rows);sort($encoded,SORT_STRING);$hashes[$name]=hash('sha256',serialize($encoded));
    }
    $active=get_option('active_plugins',[]);sort($active,SORT_STRING);
    $hashes['activePlugins']=hash('sha256',serialize($active));
    $hashes['roles']=hash('sha256',serialize(get_option($wpdb->prefix.'user_roles')));
    $hashes['projectCount']=(int)$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='project'");
    deploy_install_require(!$wpdb->last_error,'INSTALL_SNAPSHOT_FAILED');
    return $hashes;
}
function deploy_install_context(){
    global $wpdb;
    deploy_install_require(defined('WP_CLI')&&WP_CLI&&!defined('AC_EDITORIAL_OPERATING_SITE')&&!defined('AC_EDITORIAL_OPERATING_LOCAL')&&!defined('AC_EDITORIAL_SOURCE_EXPERIMENT'),'INSTALL_OPTIN_OR_CONTEXT_CHANGED');
    // Installed inactive is represented only prospectively for this read-only check.
    $profile=production_profile();$profile['plugins']=array_values(array_filter($profile['plugins'],static fn($r)=>$r['file']!=='alexcuadra-editorial-operating/alexcuadra-editorial-operating.php'));
    $profile['plugins'][]=['file'=>'alexcuadra-editorial-operating/alexcuadra-editorial-operating.php','version'=>OPERATING_VERSION,'active'=>true,'sha256'=>'planned-only'];
    $profile['loadedPlugins']['alexcuadra-editorial-operating/alexcuadra-editorial-operating.php']=true;ksort($profile['loadedPlugins']);validate_production_profile($profile);
    foreach(['source_events','source_effects','operating_reviews','operating_approvals','operating_requests','operating_apps'] as $name){
        $n=$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$wpdb->prefix.'ac_exp_'.$name));
        deploy_install_require(!$wpdb->last_error&&$n!==null&&(int)$n===0,'INSTALL_STORAGE_ALREADY_PRESENT');
    }
    $n=$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->options WHERE option_name='ac_exp_operating_storage'");
    deploy_install_require(!$wpdb->last_error&&$n!==null&&(int)$n===0,'INSTALL_MARKER_ALREADY_PRESENT');
    $u=get_userdata(2);deploy_install_require($u&&$u->roles===['subscriber'],'INSTALL_TARGET_CHANGED');
    foreach(['read','alexcuadra_read_public_projects','alexcuadra_prepare_project_changes'] as $cap){deploy_install_require($u->has_cap($cap),'INSTALL_TARGET_CHANGED');}
    foreach(['alexcuadra_apply_project_changes','alexcuadra_recover_project_changes','alexcuadra_approve_public_project_changes','edit_posts','manage_options'] as $cap){deploy_install_require(!$u->has_cap($cap),'INSTALL_TARGET_CHANGED');}
    foreach(['prepare-project-application','inspect-project-review','apply-project-review','prepare-project-recovery','recover-project-review','get-project-effect-state'] as $name){deploy_install_require(wp_get_ability('alexcuadra/'.$name)===null,'INSTALL_UNEXPECTED_ABILITY');}
}
function deploy_install_verify_files(){
    $root=WP_PLUGIN_DIR.'/alexcuadra-editorial-operating';$expected=json_decode(DEPLOY_INSTALL_FILES,true,32,JSON_THROW_ON_ERROR);$actual=[];
    deploy_install_require(!is_link($root)&&is_dir($root),'INSTALL_PACKAGE_DIRECTORY');
    $iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::SELF_FIRST);
    foreach($iterator as $entry){
        deploy_install_require(!$entry->isLink(),'INSTALL_PACKAGE_SYMLINK');$relative=substr($entry->getPathname(),strlen($root)+1);
        if($entry->isDir()){deploy_install_require($relative==='includes','INSTALL_UNEXPECTED_DIRECTORY');continue;}
        deploy_install_require($entry->isFile()&&isset($expected[$relative]),'INSTALL_UNEXPECTED_FILE');
        deploy_install_require($entry->getSize()===$expected[$relative]['bytes']&&hash_file('sha256',$entry->getPathname())===$expected[$relative]['sha256'],'INSTALL_PACKAGE_HASH');$actual[]=$relative;
    }
    sort($actual,SORT_STRING);$names=array_keys($expected);sort($names,SORT_STRING);deploy_install_require($actual===$names,'INSTALL_MISSING_FILE');
}

const DEPLOY_INSTALL_SHA256 = 'd0b09a1612fa95606915b33fa8664ae29589008abed4ca77c3c35a5259b8ed30';
const DEPLOY_INSTALL_FILES = <<<'PACKAGE_FILES'
{"README.md":{"bytes":1094,"sha256":"a0583e489f4562186fdc48de26e209134502baf84cb3a9a6ff69b673580fe241"},"alexcuadra-editorial-operating.php":{"bytes":2109,"sha256":"77cf118d953992a0d99e20ace6c5a31278ea637f0bfaa07ed1ee9970e0c4a3bf"},"files.json":{"bytes":2956,"sha256":"c28e67e953f935a71f7b6b4151e0a9ec3cdb2448c3cc62f0ed446d6ecd38beb1"},"includes/journal-records.php":{"bytes":2049,"sha256":"777661d86cfe9606442d9564e22640b10077e9440f34f598367e9bab2e9dbe76"},"includes/mariadb-operation.php":{"bytes":17587,"sha256":"099f3e686542a760dbee51de13cc52579da52b32ca0793e0aba9ec5ee5a15f8a"},"includes/operating-abilities.php":{"bytes":6077,"sha256":"b477451d092715d8728b6799603fb6d3b5010d1d9dcf2051599638b9549d0d37"},"includes/operating-audit.php":{"bytes":5992,"sha256":"174871a23be28c6c0668664dfb469d20fb925534a9c1dceb26927ede8e5d7a3f"},"includes/operating-contract.php":{"bytes":32766,"sha256":"7c91e7e2c76f6b6a2fb60ad48473628179a2c9632f65867eea421f4c9f74ed84"},"includes/operating-export.php":{"bytes":7297,"sha256":"e08307517ad64772685c78c13d005cd007eb28eeb1727bb4f8ce0197690f8dc7"},"includes/operating-migration.php":{"bytes":6510,"sha256":"b1883c3fce564210191dd1256c3cc99dd5bd6fa90121928010a2311ee5e8ed72"},"includes/operating-storage.php":{"bytes":9313,"sha256":"7b671b656e5c66fcf4d8a450af36430626e80e022969718fff0b224d7e49224f"},"includes/polylang-source.php":{"bytes":5926,"sha256":"12d35ab703d8ad911302be7764bd5b29ca99dcc472af4a944354e3421f8a3c61"},"includes/production-admission.php":{"bytes":10217,"sha256":"328015ef52f95261ef7d567386114c69e222829e5b76cade9b4a974f339afc16"},"includes/source-writer.php":{"bytes":5382,"sha256":"407cdd8b6126a0fdb25ce0b321a2900654db06cc4847919301af2ed25d873b49"},"includes/storage-barrier.php":{"bytes":1218,"sha256":"db14c339368631753b75d5ebeebb3f8b918cdd4c1ef4f0f17877d63f21e42338"}}
PACKAGE_FILES;
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
OPERATING_ACTIVATION_CHECK_PHP
config_path="$(wp --path=. config path)"
before="$(wp --path=. --user=1 eval-file "$work/check.php" before "$config_path")"
baseline="$(printf '%s\n' "$before" | sed -n 's/^OPERATING_ACTIVATION_BASELINE=//p')"
test -n "$baseline"
printf '%s\n' "$baseline" > "$work/baseline.base64"
printf '%s\n' OPERATING_ACTIVATION_BASELINE_CAPTURED
printf 'OPERATING_ACTIVATION_RUN=%s\n' "$run"
wp --path=. config set AC_EDITORIAL_OPERATING_SITE 'https://content.alexcuadra.dev' --type=constant
wp --path=. plugin activate alexcuadra-editorial-operating
migration="$(wp --path=. --user=1 eval-file "$work/check.php" migrate "$config_path" "$baseline" "$run")"
printf '%s\n' "$migration"
test "$(printf '%s\n' "$migration" | sed -n '/^OPERATING_MIGRATION_READY$/p')" = OPERATING_MIGRATION_READY
after="$(wp --path=. --user=1 eval-file "$work/check.php" after "$config_path" "$baseline" "$run")"
printf '%s\n' "$after"
receipt="$(printf '%s\n' "$after" | sed -n 's/^OPERATING_ACTIVATION_STORAGE_RESULT=//p')"
test -n "$receipt"
printf '%s\n' END_EDITORIAL_OPERATING_ACTIVATE_STORAGE_V1
