#!/bin/sh
# CMS reads only. Saves hashes in a new private observation, never replacing the grant baseline.
set -eu
umask 077
run="${1-}"
test "$#" -eq 1
case "$run" in operating-scopes.*) ;; *) exit 1;; esac
case "$run" in *[!A-Za-z0-9.-]*) exit 1;; esac
test "${#run}" -le 80
cd "$HOME/domains/alexcuadra.dev/public_html/content"
original="$HOME/.ac-editorial-deploy/$run"
test -d "$original"
test ! -L "$original"
test -f "$original/baseline.base64"
test ! -L "$original/baseline.base64"
work="$(mktemp -d "$HOME/.ac-editorial-deploy/operating-postmeta.XXXXXXXX")"
printf '%s\n' EDITORIAL_OPERATING_POSTMETA_DIAGNOSIS_V1
cat > "$work/check.php" <<'OPERATING_POSTMETA_PHP'
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
OPERATING_POSTMETA_PHP
config_path="$(wp --path=. config path)"
for mode in capture compare; do
    result="$(wp --path=. --user=1 eval-file "$work/check.php" "$config_path" "$original/baseline.base64" "$run" "$mode" "$work/observation.json")"
    printf '%s\n' "$result"
    test -n "$(printf '%s\n' "$result" | sed -n 's/^OPERATING_POSTMETA_DIAGNOSIS=//p')"
done
printf '%s\n' END_EDITORIAL_OPERATING_POSTMETA_DIAGNOSIS_V1
