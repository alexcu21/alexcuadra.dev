#!/bin/sh
# Alex executes SSH. Prospective metadata only: no install/config/DDL/grants/export.
set -eu
cd "$HOME/domains/alexcuadra.dev/public_html/content"
test -f wp-load.php
printf '%s\n' EDITORIAL_OPERATING_ADMISSION_PREFLIGHT_V1
wp --path=. eval-file - <<'EDITORIAL_OPERATING_ADMISSION_PHP'
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

// This evaluates a prospective post-install profile; it never enables the guard.
if(!defined('WP_CLI')||!WP_CLI){throw new \RuntimeException('CLI_REQUIRED');}
$observed=production_profile();$checks=[];
$checks['candidateAbsent']=!isset(get_plugins()['alexcuadra-editorial-operating/alexcuadra-editorial-operating.php']);
$checks['operatingOptinsAbsent']=!defined('AC_EDITORIAL_OPERATING_SITE')&&!defined('AC_EDITORIAL_OPERATING_LOCAL')&&!defined('AC_EDITORIAL_SOURCE_EXPERIMENT');
$prospective=$observed;
$prospective['plugins'][]=['file'=>'alexcuadra-editorial-operating/alexcuadra-editorial-operating.php','version'=>OPERATING_VERSION,'active'=>true,'sha256'=>'77cf118d953992a0d99e20ace6c5a31278ea637f0bfaa07ed1ee9970e0c4a3bf'];
$prospective['loadedPlugins']['alexcuadra-editorial-operating/alexcuadra-editorial-operating.php']=true;ksort($prospective['loadedPlugins']);
try{validate_production_profile($prospective);$checks['prospectiveRuntimeProfile']=true;}catch(\Throwable $e){$checks['prospectiveRuntimeProfile']=false;}
$type=get_post_type_object('project');$checks['projectPublicAndTranslatable']=$type&&$type->public&&function_exists('pll_is_translated_post_type')&&pll_is_translated_post_type('project');
$field=function_exists('acf_get_field')?acf_get_field('field_676dcb2aec8d4'):null;
$checks['taglineSchemaAvailable']=is_array($field)&&($field['name']??null)==='project_tagline'&&($field['type']??null)==='textarea';
$checks['polylangFiltersAbsent']=true;foreach(['pll_copy_post_metas','is_protected_meta','pll_pre_current_user_can_synchronize_post'] as $hook){if(has_filter($hook)!==false){$checks['polylangFiltersAbsent']=false;}}
global $wpdb,$wp_filter;$tables=[];
foreach(['source_events','source_effects','operating_reviews','operating_approvals','operating_requests','operating_apps'] as $name){$n=$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$wpdb->prefix.'ac_exp_'.$name));if($wpdb->last_error||$n===null){throw new \RuntimeException('METADATA_QUERY_FAILED');}$tables[$name]=(int)$n;}
$checks['operatingTablesAbsent']=array_sum($tables)===0;
$n=$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->options WHERE option_name='ac_exp_operating_storage'");if($wpdb->last_error||$n===null){throw new \RuntimeException('METADATA_QUERY_FAILED');}$checks['operatingMarkerAbsent']=(int)$n===0;
$u=get_userdata(2);$checks['targetBaseline']=$u&&$u->roles===['subscriber'];
foreach(['read','alexcuadra_read_public_projects','alexcuadra_prepare_project_changes'] as $cap){if(!$u||!$u->has_cap($cap)){$checks['targetBaseline']=false;}}
foreach(['alexcuadra_apply_project_changes','alexcuadra_recover_project_changes','alexcuadra_approve_public_project_changes','edit_posts','manage_options'] as $cap){if(!$u||$u->has_cap($cap)){$checks['targetBaseline']=false;}}
$operator=get_userdata(1);$checks['operatorAvailable']=$operator&&$operator->has_cap('manage_options');
$found=[];foreach(($wp_filter['transition_post_status']->callbacks??[]) as $priority=>$entries){foreach($entries as $entry){$fn=$entry['function'];if(is_array($fn)&&is_object($fn[0])&&get_class($fn[0])==='LiteSpeed\\Purge'&&$fn[1]==='purge_publish'){$found[]=[$priority,$entry];}}}
$checks['nativePurgeCallback']=count($found)===1&&$found[0][0]===10&&$found[0][1]['accepted_args']===3&&hash_file('sha256',(new \ReflectionMethod($found[0][1]['function'][0],'purge_publish'))->getFileName())==='b9a53d0221687eafa935245b592138f989c143036b56bce00e633ff8fe5bc5a6';
echo 'EDITORIAL_OPERATING_ADMISSION_PREFLIGHT='.wp_json_encode(['format'=>'editorial-operating-admission-preflight-v1','observedAtUtc'=>gmdate('c'),'candidateVersion'=>OPERATING_VERSION,'candidateSha256'=>'d0b09a1612fa95606915b33fa8664ae29589008abed4ca77c3c35a5259b8ed30','checks'=>$checks,'technicalChecksPassed'=>!in_array(false,array_map('boolval',$checks),true),'observedProfile'=>$observed,'prospectiveProfileOnly'=>true,'installationAuthorized'=>false,'optinChanged'=>false,'ddlExecuted'=>false,'capabilitiesChanged'=>false,'editorialDataWritten'=>false,'credentialsOrContentExported'=>false]).PHP_EOL;
EDITORIAL_OPERATING_ADMISSION_PHP
printf '%s\n' END_EDITORIAL_OPERATING_ADMISSION_PREFLIGHT_V1
