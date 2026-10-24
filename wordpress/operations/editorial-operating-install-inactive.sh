#!/bin/sh
# Prepared for explicit approval of inactive installation only. Alex executes SSH.
set -eu
umask 077
cd "$HOME/domains/alexcuadra.dev/public_html/content"
archive="$HOME/.ac-editorial-deploy/operating-0.1.0-rc.3-20261024/candidate.zip"
test -f "$archive"
test ! -L "$archive"
actual="$(sha256sum "$archive")"
test "${actual%% *}" = d0b09a1612fa95606915b33fa8664ae29589008abed4ca77c3c35a5259b8ed30
test ! -e wp-content/plugins/alexcuadra-editorial-operating
test ! -L wp-content/plugins/alexcuadra-editorial-operating
available_kib="$(df -Pk wp-content/plugins | awk 'END {print $4}')"
case "$available_kib" in ''|*[!0-9]*) exit 1;; esac
test "$available_kib" -ge 16384
work="$(mktemp -d "$HOME/.ac-editorial-deploy/operating-install.XXXXXXXX")"
printf '%s\n' EDITORIAL_OPERATING_INSTALL_INACTIVE_V1
cat > "$work/check.php" <<'OPERATING_INSTALL_CHECK_PHP'
<?php
/** Closed destination admission. No activation, writes, grants or test bypasses. */
namespace AlexCuadra\EditorialSourceExperiment;
const OPERATING_SITE = 'https://content.alexcuadra.dev';
const OPERATING_VERSION = '0.1.0-rc.3';
function production_expected_profile(){return json_decode(<<<'PROFILE'
{"plugins":[{"file":"advanced-custom-fields/acf.php","version":"6.8.10","active":true,"sha256":"e3c7114e354ea19850463db3191c2d60ee244e57501d5008cd0617f7c0fb7e64"},{"file":"alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php","version":"0.1.0-rc.2","active":true,"sha256":"a9a1b498ab18472a30966cfcd160ef91d35aa62420911e31ca30e7ec3452949e"},{"file":"alexcuadra-editorial-read/alexcuadra-editorial-read.php","version":"0.1.0","active":true,"sha256":"9e4def28919b5dc896037fa13dddf62b4f63e4a725095fe12b3e0d8419f22619"},{"file":"all-in-one-wp-security-and-firewall/wp-security.php","version":"5.4.10","active":false,"sha256":"db9edf3779d7a45665bfe762ed079ae13e5b65780a136328ce6d37154dd458e2"},{"file":"projects-cpt-for-headless/projects-cpt.php","version":"1.0","active":false,"sha256":"5e7aee7f2681881f415f73bfc1815d26058ef030b5088cde5846032a65c87c32"},{"file":"hostinger-ai-assistant/hostinger-ai-assistant.php","version":"3.1.3","active":false,"sha256":"a8eb7d2f5d58e42034af9d0ecdd82b5531596106df485e790d0511b04e4fadb4"},{"file":"hostinger-easy-onboarding/hostinger-easy-onboarding.php","version":"3.0.5","active":true,"sha256":"5e2927308eaa142bb79149603aeb3e4b8b0ad33db6e6c59f4cfbd5b0761d0a19"},{"file":"hostinger-reach/hostinger-reach.php","version":"1.8.7","active":true,"sha256":"dffaaeea0c33214046f7f62b8d104d927ad17be0942c4af4a67d0e9ae734f48b"},{"file":"hostinger/hostinger.php","version":"3.0.80","active":true,"sha256":"5d1dd6e5d9677e6c53732765f343eb5fd573d13fb0314e67d30afeecf24d264e"},{"file":"litespeed-cache/litespeed-cache.php","version":"7.9.2","active":true,"sha256":"ad57729d8736659c99068f4727d299c6c6b29b55d019d67a5c417918c3370f2f"},{"file":"mcp-adapter/mcp-adapter.php","version":"0.7.1","active":true,"sha256":"ad0388cd18385c66a8efc7ec433047e6787e75cd2d0bbf2332333258717b4620"},{"file":"minimal-coming-soon-maintenance-mode/minimal-coming-soon-maintenance-mode.php","version":"2.45","active":false,"sha256":"c31156f4a2e3586a3edf696ce468e0683ed4f047fcfd732a09f34b0e50f2b902"},{"file":"polylang/polylang.php","version":"3.9.1","active":true,"sha256":"39cf938c68eb3c34078c8b1ea1dd900889458cb03b4977a9a9ff92f7b3e01379"},{"file":"wordpress-beta-tester/wp-beta-tester.php","version":"4.0.1","active":true,"sha256":"536e1073be4b7cefff0f5158aa14f6684f03d049e808db567aad963556641a40"},{"file":"wp-graphql/wp-graphql.php","version":"2.25.0","active":true,"sha256":"d1bfa232b509aebf130fc7f7f090618f5577d296df8d3d6bfef0af55618a4408"},{"file":"wpgraphql-acf/wpgraphql-acf.php","version":"3.0.0","active":true,"sha256":"6ef3736685ea63e4707e631886dea03dd8cee05c7e76b87cc1f6b0eae72db842"},{"file":"wpgraphql-ide/wpgraphql-ide.php","version":"5.7.0","active":true,"sha256":"b22c9f1d2474665577b7d2842214ad64835fa43cd7a91f8dd30d5561dd054236"},{"file":"wp-graphql-polylang-0.7.1/wp-graphql-polylang.php","version":"0.7.1","active":true,"sha256":"6e2933f965a072969e7d0dbf4a9e623e2a7e7e1ee1da6ea416bad7a18b7688a4"},{"file":"wp-migrate-db/wp-migrate-db.php","version":"2.7.12","active":false,"sha256":"fc084e7331a53fb699c6599512c9fb3f3fcc0c7ff5fe6d52601e61bd3b843020"}],"muPlugins":[{"file":"hostinger-preview-domain.php","version":"1.4.0","sha256":"ccedfe9be0bce8e941274a519bfea4ca686a7cc0a1f0cedccdbb39384d8764f4"},{"file":"hostinger-auto-updates.php","version":"1.0.8","sha256":"2492da0336ed34b4377b2a2de882603280e0fde70df712b08fcd2ddef588a5bf"}],"theme":{"stylesheet":"twentytwentyfour","template":"twentytwentyfour","version":"1.6"}}
PROFILE
,true,32,JSON_THROW_ON_ERROR);}
function validate_production_profile(array $p){
    $expected=production_expected_profile();
    foreach(['home','site','environment','wordpress','php','databaseVersion','wpdbClass','objectCacheClass','nativeCacheCode','nativeWpdbCode','loadedPlugins','loadedMu','themeLoaded','themeFunctionsHash','multisite','externalObjectCache','dropins','plugins','muPlugins','theme','engines','polylangSync','functions'] as $key){if(!array_key_exists($key,$p)){throw new \RuntimeException('operating_destination_changed');}}
    if($p['home']!==OPERATING_SITE||$p['site']!==OPERATING_SITE||$p['environment']!=='production'||$p['multisite']!==false
        ||$p['wordpress']!=='7.1.3'||$p['php']!=='8.5.11'||!preg_match('/^11\.8\.9-MariaDB(?:[-+].*)?$/D',$p['databaseVersion'])
        ||$p['wpdbClass']!=='wpdb'||$p['objectCacheClass']!=='WP_Object_Cache'||$p['nativeCacheCode']!==true||$p['externalObjectCache']!==false
        ||$p['nativeWpdbCode']!==true||$p['themeLoaded']!==true||$p['themeFunctionsHash']!=='6cec0f112f34ff3a043fe1e16c2d235ce905a735d46039a50879e8fd469f2bce'
        ||$p['polylangSync']!==[]||$p['theme']!==$expected['theme']){throw new \RuntimeException('operating_destination_changed');}
    if($p['dropins']!==array_fill_keys(['db.php','object-cache.php','advanced-cache.php','sunrise.php'],false)
        ||$p['engines']!==array_fill_keys(['posts','postmeta','options','terms','term_taxonomy','term_relationships','ac_proposal_owners','ac_proposal_requests'],'InnoDB')){throw new \RuntimeException('operating_destination_changed');}
    foreach(['mysqli_query','mysqli_report','mysqli_fetch_row','fopen','fsync','wp_register_ability'] as $fn){if(($p['functions'][$fn]??null)!==true){throw new \RuntimeException('operating_destination_changed');}}
    $map=static function($rows){$m=[];foreach($rows as $r){if(!is_array($r)||!isset($r['file'])||isset($m[$r['file']])){throw new \RuntimeException('operating_destination_changed');}$file=$r['file'];unset($r['file']);ksort($r);$m[$file]=$r;}ksort($m);return $m;};
    $loaded=[];foreach($expected['plugins'] as $r){$loaded[$r['file']]=$r['active'];}$loaded['alexcuadra-editorial-operating/alexcuadra-editorial-operating.php']=true;ksort($loaded);
    $muLoaded=[];foreach($expected['muPlugins'] as $r){$muLoaded[$r['file']]=true;}ksort($muLoaded);
    if($p['loadedPlugins']!==$loaded||$p['loadedMu']!==$muLoaded){throw new \RuntimeException('operating_destination_changed');}
    $plugins=$p['plugins'];$own=[];$others=[];
    foreach($plugins as $r){if(($r['file']??null)==='alexcuadra-editorial-operating/alexcuadra-editorial-operating.php'){$own[]=$r;}else{$others[]=$r;}}
    if(count($own)!==1||($own[0]['version']??null)!==OPERATING_VERSION||($own[0]['active']??null)!==true
        ||$map($others)!==$map($expected['plugins'])||$map($p['muPlugins'])!==$map($expected['muPlugins'])){throw new \RuntimeException('operating_destination_changed');}
}
function production_profile(){
    global $wpdb,$wp_object_cache;
    require_once ABSPATH.'wp-admin/includes/plugin.php';
    $plugins=[];foreach(get_plugins() as $file=>$p){$plugins[]=['file'=>$file,'version'=>$p['Version'],'active'=>is_plugin_active($file),'sha256'=>hash_file('sha256',WP_PLUGIN_DIR.'/'.$file)];}
    $mu=[];foreach(get_mu_plugins() as $file=>$p){$mu[]=['file'=>$file,'version'=>$p['Version'],'sha256'=>hash_file('sha256',WPMU_PLUGIN_DIR.'/'.$file)];}
    $dropins=[];foreach(['db.php','object-cache.php','advanced-cache.php','sunrise.php'] as $f){$dropins[$f]=file_exists(WP_CONTENT_DIR.'/'.$f);}
    $functions=[];foreach(['mysqli_query','mysqli_report','mysqli_fetch_row','fopen','fsync','wp_register_ability'] as $fn){$functions[$fn]=function_exists($fn);}
    $engines=[];foreach(['posts','postmeta','options','terms','term_taxonomy','term_relationships','ac_proposal_owners','ac_proposal_requests'] as $name){$table=$wpdb->prefix.$name;$engines[$name]=$wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));if($wpdb->last_error){throw new \RuntimeException('operating_destination_changed');}}
    $db=$wpdb->get_var('SELECT VERSION()');if($wpdb->last_error){throw new \RuntimeException('operating_destination_changed');}
    $theme=wp_get_theme();$included=array_map('realpath',get_included_files());$loaded=[];
    foreach($plugins as $r){$loaded[$r['file']]=in_array(realpath(WP_PLUGIN_DIR.'/'.$r['file']),$included,true);}ksort($loaded);
    $muLoaded=[];foreach($mu as $r){$muLoaded[$r['file']]=in_array(realpath(WPMU_PLUGIN_DIR.'/'.$r['file']),$included,true);}ksort($muLoaded);
    $themeFile=get_theme_file_path('functions.php');
    return ['home'=>home_url(),'site'=>site_url(),'environment'=>wp_get_environment_type(),'wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'databaseVersion'=>$db,'wpdbClass'=>get_class($wpdb),'objectCacheClass'=>is_object($wp_object_cache)?get_class($wp_object_cache):null,
        'nativeCacheCode'=>is_object($wp_object_cache)&&realpath((new \ReflectionClass($wp_object_cache))->getFileName())===realpath(ABSPATH.WPINC.'/class-wp-object-cache.php'),
        'nativeWpdbCode'=>realpath((new \ReflectionClass($wpdb))->getFileName())===realpath(ABSPATH.WPINC.'/class-wpdb.php'),'loadedPlugins'=>$loaded,'loadedMu'=>$muLoaded,'themeLoaded'=>in_array(realpath($themeFile),$included,true),'themeFunctionsHash'=>hash_file('sha256',$themeFile),
        'multisite'=>is_multisite(),'externalObjectCache'=>(bool)wp_using_ext_object_cache(),'dropins'=>$dropins,'plugins'=>$plugins,'muPlugins'=>$mu,'theme'=>['stylesheet'=>$theme->get_stylesheet(),'template'=>$theme->get_template(),'version'=>$theme->get('Version')],'engines'=>$engines,'polylangSync'=>function_exists('PLL')?PLL()->options->get('sync'):null,'functions'=>$functions];
}
function production_wp_guard(){
    if(!defined('AC_EDITORIAL_OPERATING_SITE')||AC_EDITORIAL_OPERATING_SITE!==OPERATING_SITE
        ||(defined('AC_EDITORIAL_OPERATING_LOCAL')&&AC_EDITORIAL_OPERATING_LOCAL!==false)
        ||(defined('AC_EDITORIAL_SOURCE_EXPERIMENT')&&AC_EDITORIAL_SOURCE_EXPERIMENT!==false)) {throw new \RuntimeException('operating_destination_optin_required');}
    if(realpath(__FILE__)!==realpath(WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/production-admission.php')){throw new \RuntimeException('operating_installed_package_required');}
    validate_production_profile(production_profile());
}

const DEPLOY_INSTALL_SHA256 = 'd0b09a1612fa95606915b33fa8664ae29589008abed4ca77c3c35a5259b8ed30';
const DEPLOY_INSTALL_FILES = <<<'PACKAGE_FILES'
{"README.md":{"bytes":1094,"sha256":"a0583e489f4562186fdc48de26e209134502baf84cb3a9a6ff69b673580fe241"},"alexcuadra-editorial-operating.php":{"bytes":2109,"sha256":"77cf118d953992a0d99e20ace6c5a31278ea637f0bfaa07ed1ee9970e0c4a3bf"},"files.json":{"bytes":2956,"sha256":"c28e67e953f935a71f7b6b4151e0a9ec3cdb2448c3cc62f0ed446d6ecd38beb1"},"includes/journal-records.php":{"bytes":2049,"sha256":"777661d86cfe9606442d9564e22640b10077e9440f34f598367e9bab2e9dbe76"},"includes/mariadb-operation.php":{"bytes":17587,"sha256":"099f3e686542a760dbee51de13cc52579da52b32ca0793e0aba9ec5ee5a15f8a"},"includes/operating-abilities.php":{"bytes":6077,"sha256":"b477451d092715d8728b6799603fb6d3b5010d1d9dcf2051599638b9549d0d37"},"includes/operating-audit.php":{"bytes":5992,"sha256":"174871a23be28c6c0668664dfb469d20fb925534a9c1dceb26927ede8e5d7a3f"},"includes/operating-contract.php":{"bytes":32766,"sha256":"7c91e7e2c76f6b6a2fb60ad48473628179a2c9632f65867eea421f4c9f74ed84"},"includes/operating-export.php":{"bytes":7297,"sha256":"e08307517ad64772685c78c13d005cd007eb28eeb1727bb4f8ce0197690f8dc7"},"includes/operating-migration.php":{"bytes":6510,"sha256":"b1883c3fce564210191dd1256c3cc99dd5bd6fa90121928010a2311ee5e8ed72"},"includes/operating-storage.php":{"bytes":9313,"sha256":"7b671b656e5c66fcf4d8a450af36430626e80e022969718fff0b224d7e49224f"},"includes/polylang-source.php":{"bytes":5926,"sha256":"12d35ab703d8ad911302be7764bd5b29ca99dcc472af4a944354e3421f8a3c61"},"includes/production-admission.php":{"bytes":10217,"sha256":"328015ef52f95261ef7d567386114c69e222829e5b76cade9b4a974f339afc16"},"includes/source-writer.php":{"bytes":5382,"sha256":"407cdd8b6126a0fdb25ce0b321a2900654db06cc4847919301af2ed25d873b49"},"includes/storage-barrier.php":{"bytes":1218,"sha256":"db14c339368631753b75d5ebeebb3f8b918cdd4c1ef4f0f17877d63f21e42338"}}
PACKAGE_FILES;
// Appended to the sealed admission module in a private deployment workspace.
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
OPERATING_INSTALL_CHECK_PHP
before="$(wp --path=. eval-file "$work/check.php" before)"
baseline="$(printf '%s\n' "$before" | sed -n 's/^OPERATING_INSTALL_BASELINE=//p')"
test -n "$baseline"
printf '%s\n' "$baseline" > "$work/baseline.base64"
printf '%s\n' OPERATING_INSTALL_BASELINE_CAPTURED
wp --path=. plugin install "$archive"
wp --path=. plugin get alexcuadra-editorial-operating --fields=name,version,status --format=json
after="$(wp --path=. eval-file "$work/check.php" after "$baseline")"
printf '%s\n' "$after"
receipt="$(printf '%s\n' "$after" | sed -n 's/^OPERATING_INSTALL_INACTIVE_RESULT=//p')"
test -n "$receipt"
printf '%s\n' END_EDITORIAL_OPERATING_INSTALL_INACTIVE_V1
