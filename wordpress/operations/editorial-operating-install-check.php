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
$phase=$args[0]??'';$slug='alexcuadra-editorial-operating/alexcuadra-editorial-operating.php';
deploy_install_context();
if($phase==='before'){
    deploy_install_require(!file_exists(WP_PLUGIN_DIR.'/alexcuadra-editorial-operating')&&!is_link(WP_PLUGIN_DIR.'/alexcuadra-editorial-operating'),'INSTALL_CANDIDATE_EXISTS');
    echo 'OPERATING_INSTALL_BASELINE='.base64_encode(wp_json_encode(deploy_install_snapshot())).PHP_EOL;
}elseif($phase==='after'&&count($args)===2){
    $before=json_decode(base64_decode($args[1],true),true,32,JSON_THROW_ON_ERROR);
    deploy_install_require(deploy_install_snapshot()===$before,'INSTALL_CONTENT_PERMISSIONS_OR_ACTIVE_PLUGINS_CHANGED');
    $plugins=get_plugins();deploy_install_require(($plugins[$slug]['Version']??null)===OPERATING_VERSION&&!is_plugin_active($slug),'INSTALL_MUST_REMAIN_INACTIVE');deploy_install_verify_files();
    echo 'OPERATING_INSTALL_INACTIVE_RESULT='.wp_json_encode(['version'=>OPERATING_VERSION,'candidateSha256'=>DEPLOY_INSTALL_SHA256,'packageFilesVerified'=>true,'active'=>false,'optinAbsent'=>true,'operatingTablesAbsent'=>true,'operatingAbilitiesAbsent'=>true,'projectCount'=>$before['projectCount'],'sourcesAndPrivateProposalsUnchanged'=>true,'userMetadataAndRolesUnchanged'=>true,'activePluginsUnchanged'=>true,'capabilitiesAdded'=>false,'editorialDataWritten'=>false]).PHP_EOL;
}else{throw new \RuntimeException('INSTALL_EXPECTED_PHASE');}
