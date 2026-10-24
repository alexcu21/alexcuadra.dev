#!/bin/sh
# CMS reads only; retains a small private helper outside the public directory.
set -eu
umask 077
test "$#" -eq 0
cd "$HOME/domains/alexcuadra.dev/public_html/content"
test -d "$HOME/.ac-editorial-deploy"
test ! -L "$HOME/.ac-editorial-deploy"
work="$(mktemp -d "$HOME/.ac-editorial-deploy/operating-operator.XXXXXXXX")"
printf '%s\n' EDITORIAL_OPERATING_OPERATOR_CHECK_V1
cat > "$work/check.php" <<'OPERATING_OPERATOR_PHP'
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

const DEPLOY_INSTALL_SHA256 = 'd0b09a1612fa95606915b33fa8664ae29589008abed4ca77c3c35a5259b8ed30';
const DEPLOY_INSTALL_FILES = <<<'PACKAGE_FILES'
{"README.md":{"bytes":1094,"sha256":"a0583e489f4562186fdc48de26e209134502baf84cb3a9a6ff69b673580fe241"},"alexcuadra-editorial-operating.php":{"bytes":2109,"sha256":"77cf118d953992a0d99e20ace6c5a31278ea637f0bfaa07ed1ee9970e0c4a3bf"},"files.json":{"bytes":2956,"sha256":"c28e67e953f935a71f7b6b4151e0a9ec3cdb2448c3cc62f0ed446d6ecd38beb1"},"includes/journal-records.php":{"bytes":2049,"sha256":"777661d86cfe9606442d9564e22640b10077e9440f34f598367e9bab2e9dbe76"},"includes/mariadb-operation.php":{"bytes":17587,"sha256":"099f3e686542a760dbee51de13cc52579da52b32ca0793e0aba9ec5ee5a15f8a"},"includes/operating-abilities.php":{"bytes":6077,"sha256":"b477451d092715d8728b6799603fb6d3b5010d1d9dcf2051599638b9549d0d37"},"includes/operating-audit.php":{"bytes":5992,"sha256":"174871a23be28c6c0668664dfb469d20fb925534a9c1dceb26927ede8e5d7a3f"},"includes/operating-contract.php":{"bytes":32766,"sha256":"7c91e7e2c76f6b6a2fb60ad48473628179a2c9632f65867eea421f4c9f74ed84"},"includes/operating-export.php":{"bytes":7297,"sha256":"e08307517ad64772685c78c13d005cd007eb28eeb1727bb4f8ce0197690f8dc7"},"includes/operating-migration.php":{"bytes":6510,"sha256":"b1883c3fce564210191dd1256c3cc99dd5bd6fa90121928010a2311ee5e8ed72"},"includes/operating-storage.php":{"bytes":9313,"sha256":"7b671b656e5c66fcf4d8a450af36430626e80e022969718fff0b224d7e49224f"},"includes/polylang-source.php":{"bytes":5926,"sha256":"12d35ab703d8ad911302be7764bd5b29ca99dcc472af4a944354e3421f8a3c61"},"includes/production-admission.php":{"bytes":10217,"sha256":"328015ef52f95261ef7d567386114c69e222829e5b76cade9b4a974f339afc16"},"includes/source-writer.php":{"bytes":5382,"sha256":"407cdd8b6126a0fdb25ce0b321a2900654db06cc4847919301af2ed25d873b49"},"includes/storage-barrier.php":{"bytes":1218,"sha256":"db14c339368631753b75d5ebeebb3f8b918cdd4c1ef4f0f17877d63f21e42338"}}
PACKAGE_FILES;
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

foreach(['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities'] as $module){require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/'.$module.'.php';}
echo 'OPERATING_OPERATOR_CHECK_RESULT='.wp_json_encode(deploy_operator_check($args[0]??'')).PHP_EOL;
OPERATING_OPERATOR_PHP
config_path="$(wp --path=. config path)"
result="$(wp --path=. --user=1 eval-file "$work/check.php" "$config_path")"
printf '%s\n' "$result"
test -n "$(printf '%s\n' "$result" | sed -n 's/^OPERATING_OPERATOR_CHECK_RESULT=//p')"
printf '%s\n' END_EDITORIAL_OPERATING_OPERATOR_CHECK_V1
