#!/bin/sh
# Alex executes SSH. Metadata only; no installation/config/grants/DDL/export.
set -eu
cd "$HOME/domains/alexcuadra.dev/public_html/content"
test -f wp-load.php
printf '%s\n' EDITORIAL_OPERATING_CANDIDATE_PREFLIGHT_V1
wp --path=. eval-file - <<'EDITORIAL_OPERATING_PREFLIGHT_PHP'
<?php
if(!defined('WP_CLI')||!WP_CLI){throw new RuntimeException('CLI_REQUIRED');}
require_once ABSPATH.'wp-admin/includes/plugin.php';
global $wpdb,$wp_object_cache;$plugins=[];
foreach(get_plugins() as $file=>$p){$plugins[]=['file'=>$file,'version'=>$p['Version'],'active'=>is_plugin_active($file),'sha256'=>hash_file('sha256',WP_PLUGIN_DIR.'/'.$file)];}
$mu=[];foreach(get_mu_plugins() as $file=>$p){$mu[]=['file'=>$file,'version'=>$p['Version'],'sha256'=>hash_file('sha256',WPMU_PLUGIN_DIR.'/'.$file)];}
$tables=[];foreach(['source_events','source_effects','operating_reviews','operating_approvals','operating_requests','operating_apps'] as $name){$table=$wpdb->prefix.'ac_exp_'.$name;$r=$wpdb->get_row($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table),ARRAY_A);if($wpdb->last_error){throw new RuntimeException('METADATA_QUERY_FAILED');}$tables[$name]=$r['ENGINE']??null;}
$core=[];foreach([$wpdb->posts,$wpdb->postmeta,$wpdb->options,$wpdb->terms,$wpdb->term_taxonomy,$wpdb->term_relationships,$wpdb->prefix.'ac_proposal_owners',$wpdb->prefix.'ac_proposal_requests'] as $table){$engine=$wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));if($wpdb->last_error){throw new RuntimeException('METADATA_QUERY_FAILED');}$core[$table]=$engine;}
$functions=[];foreach(['mysqli_query','mysqli_report','mysqli_fetch_row','fopen','fsync','link','wp_register_ability'] as $fn){$functions[$fn]=function_exists($fn);}
$u=get_userdata(2);$caps=[];foreach(['read','alexcuadra_read_public_projects','alexcuadra_prepare_project_changes','alexcuadra_apply_project_changes','alexcuadra_recover_project_changes','alexcuadra_approve_public_project_changes','edit_posts','manage_options'] as $cap){$caps[$cap]=$u?$u->has_cap($cap):false;}
$db=$wpdb->get_var('SELECT VERSION()');if($wpdb->last_error){throw new RuntimeException('METADATA_QUERY_FAILED');}
$marker=(int)$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->options WHERE option_name='ac_exp_operating_storage'");if($wpdb->last_error){throw new RuntimeException('METADATA_QUERY_FAILED');}
$dropins=[];foreach(['db.php','object-cache.php','advanced-cache.php','sunrise.php'] as $f){$dropins[$f]=file_exists(WP_CONTENT_DIR.'/'.$f);}
$theme=wp_get_theme();$result=['format'=>'editorial-operating-candidate-preflight-v1','observedAtUtc'=>gmdate('c'),'canonicalSite'=>home_url()==='https://content.alexcuadra.dev'&&site_url()===home_url(),'multisite'=>is_multisite(),'environment'=>wp_get_environment_type(),'wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'databaseVersion'=>$db,'wpdbClass'=>get_class($wpdb),'objectCacheClass'=>is_object($wp_object_cache)?get_class($wp_object_cache):null,'externalObjectCache'=>(bool)wp_using_ext_object_cache(),'dropins'=>$dropins,'plugins'=>$plugins,'muPlugins'=>$mu,'theme'=>['stylesheet'=>$theme->get_stylesheet(),'template'=>$theme->get_template(),'version'=>$theme->get('Version')],'functionsAvailable'=>$functions,'coreAndProposalEngines'=>$core,'operatingTables'=>$tables,'operatingMarkerPresent'=>$marker!==0,'target'=>['userId'=>2,'exists'=>(bool)$u,'subscriberOnly'=>$u&&$u->roles===['subscriber'],'caps'=>$caps],'polylangSync'=>function_exists('PLL')?PLL()->options->get('sync'):null,'localOptinDefined'=>defined('AC_EDITORIAL_OPERATING_LOCAL'),'installationAuthorized'=>false,'ddlExecuted'=>false,'credentialsOrContentExported'=>false,'productionAdmissionImplemented'=>false];
echo 'EDITORIAL_OPERATING_CANDIDATE_PREFLIGHT='.wp_json_encode($result).PHP_EOL;
EDITORIAL_OPERATING_PREFLIGHT_PHP
printf '%s\n' END_EDITORIAL_OPERATING_CANDIDATE_PREFLIGHT_V1
