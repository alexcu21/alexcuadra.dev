<?php
if(wp_get_environment_type()!=='local'||DB_HOST!=='db'||getenv('BLOG_SITE_PROFILE_LAB')!=='verified-code-only'){throw new RuntimeException('SITE_PROFILE_LAB_REQUIRED');}
require_once ABSPATH.'wp-admin/includes/plugin.php';
require_once '/proposal-operations/blog-proposal-admission.php';
use AlexCuadra\BlogProposalAdmission as A;
global $wpdb;
$expected=json_decode(file_get_contents(ABSPATH.'.lab-profile.json'),true,32,JSON_THROW_ON_ERROR);
$inventory=json_decode(file_get_contents(ABSPATH.'.lab-inventory.json'),true,32,JSON_THROW_ON_ERROR);
$cases=[];$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED:'.$name);}$cases[]=$name;};
$active=get_option('active_plugins');sort($active);$wanted=array_keys($expected['activePlugins']);sort($wanted);
$check('sixteen_active_plugins_match_receipt',$active===$wanted&&count($active)===16);
$included=array_map('realpath',get_included_files());
foreach($expected['activePlugins']as$file=>$version){
    $path=WP_PLUGIN_DIR.'/'.$file;
    $check('plugin_loaded_'.str_replace(['/','.','-'],'_',$file),in_array(realpath($path),$included,true));
    $check('plugin_code_'.str_replace(['/','.','-'],'_',$file),get_plugin_data($path,false,false)['Version']===$version&&hash_file('sha256',$path)===$inventory['plugins'][$file]['mainSha256']);
}
foreach($expected['muPluginSha256']as$file=>$hash){$path=WPMU_PLUGIN_DIR.'/'.$file;
    $check('mu_loaded_'.str_replace(['.','-'],'_',$file),in_array(realpath($path),$included,true)&&hash_file('sha256',$path)===$hash);}
$theme=wp_get_theme();$dir=$theme->get_stylesheet_directory();
$check('twentytwentyfour_16_matches_live_entries',$theme->get_stylesheet()==='twentytwentyfour'&&$theme->get('Version')==='1.6'&&hash_file('sha256',$dir.'/style.css')===$expected['theme']['styleSha256']&&hash_file('sha256',$dir.'/functions.php')===$expected['theme']['functionsSha256']);
$check('theme_functions_loaded',in_array(realpath($dir.'/functions.php'),$included,true));
$profile=A\profile_report();
$failed=array_keys(array_filter($profile['checks'],static fn($v)=>$v===false));sort($failed);
$check('minimal_candidate_still_rejects_only_profile_differences',$failed===['testedActivePlugins','testedMuPlugins','testedTheme']&&!$profile['ready']);
$check('core_and_php_branch_match',$profile['checks']['testedWordPress']&&$profile['checks']['testedPhp']&&$profile['checks']['coreWpdb']);
$check('no_dropins_or_persistent_object_cache',$profile['checks']['noDropins']&&$profile['checks']['standardObjectCache']);
$schema=A\schema_report($wpdb);$check('new_private_tables_remain_absent',$schema['present']===0);
$check('new_scope_not_granted',!(new WP_User(2))->has_cap('alexcuadra_prepare_post_changes'));
$check('candidate_not_installed',!is_dir(WP_PLUGIN_DIR.'/alexcuadra-editorial-blog-proposals'));
$check('outbound_http_blocked',is_wp_error(wp_remote_get('https://example.invalid/')));
$check('mail_guard_installed',has_filter('pre_wp_mail')!==false);
$check('source_sql_and_config_not_imported',!file_exists('/verified-profile/backup/database.sql')&&!file_exists('/verified-profile/wp-config.php'));
WP_CLI::line('BLOG_SITE_PROFILE_BOOT_RESULT='.wp_json_encode(['environment'=>'isolated-synthetic-site-profile-bootstrap','passed'=>count($cases),'failed'=>0,
    'cases'=>$cases,'profile'=>$profile,'installedCandidate'=>false,'newScopeGranted'=>false,'proposalWritesTested'=>false,'hostingDatabaseImported'=>false]));
