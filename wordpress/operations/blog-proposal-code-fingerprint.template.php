<?php
/** Read-only code metadata. No installation, grants, source content or credentials. */
namespace AlexCuadra\BlogProposalCodeFingerprint;
if(!defined('WP_CLI')||!WP_CLI){throw new \RuntimeException('WP_CLI_REQUIRED');}
if(($args??[])!==[]){\WP_CLI::error('EXPECTED_NO_ARGUMENTS');}
if(is_multisite()||rtrim(home_url(),'/')!=='https://content.alexcuadra.dev'||rtrim(site_url(),'/')!=='https://content.alexcuadra.dev'){
    \WP_CLI::error('WRONG_CODE_FINGERPRINT_DESTINATION');
}
require_once ABSPATH.'wp-admin/includes/plugin.php';
$expected=json_decode('__EXPECTED_PROFILE_JSON__',true,32,JSON_THROW_ON_ERROR);
$active=get_option('active_plugins',[]);sort($active);$wanted=array_keys($expected['plugins']);sort($wanted);
$plugins=[];$versions=true;$hashes=true;$root=realpath(WP_PLUGIN_DIR);
foreach($active as$name){
    $file=is_string($name)?realpath(WP_PLUGIN_DIR.'/'.$name):false;
    $inside=$file&&str_starts_with($file,$root.DIRECTORY_SEPARATOR)&&is_file($file);
    $version=$inside?get_plugin_data($file,false,false)['Version']:null;$hash=$inside?hash_file('sha256',$file):null;
    $known=$expected['plugins'][$name]??null;
    $plugins[$name]=['version'=>$version,'mainSha256'=>$hash];
    $versions=$versions&&$known!==null&&$known['version']===$version;
    $hashes=$hashes&&$known!==null&&$known['mainSha256']===$hash;
}
$mu=[];foreach(array_keys(get_mu_plugins())as$name){$mu[$name]=hash_file('sha256',WPMU_PLUGIN_DIR.'/'.$name);}
$lab=defined('DB_HOST')&&DB_HOST==='db'&&wp_get_environment_type()==='local'&&getenv('BLOG_SITE_PROFILE_LAB')==='verified-code-only';
$ignored=false;
if($lab&&($mu['000-blog-site-guard.php']??null)===$expected['fixtureMuSha256']){unset($mu['000-blog-site-guard.php']);$ignored=true;}
ksort($mu);$wantedMu=$expected['muPluginSha256'];ksort($wantedMu);
$theme=wp_get_theme();$dir=$theme->get_stylesheet_directory();$actualTheme=['stylesheet'=>$theme->get_stylesheet(),'template'=>$theme->get_template(),'version'=>$theme->get('Version'),
    'styleSha256'=>is_file($dir.'/style.css')?hash_file('sha256',$dir.'/style.css'):null,
    'functionsSha256'=>is_file($dir.'/functions.php')?hash_file('sha256',$dir.'/functions.php'):null];
$dropins=array_keys(get_dropins());sort($dropins);
global $wpdb,$wp_version;
$coreFile=is_object($wpdb)?(new \ReflectionClass($wpdb))->getFileName():null;
$coreHash=$coreFile&&is_file($coreFile)?hash_file('sha256',$coreFile):null;
$user=get_userdata(2);$caps=$user?array_keys(array_filter($user->allcaps)):[];sort($caps);
$checks=['activePluginNames'=>$active===$wanted,'pluginVersions'=>$versions,'pluginMainHashes'=>$hashes,'muNamesAndHashes'=>$mu===$wantedMu,
    'themeEntryHashes'=>$actualTheme===$expected['theme'],'noDropins'=>$dropins===[],'standardObjectCache'=>!wp_using_ext_object_cache(),
    'coreWpdbHash'=>is_object($wpdb)&&get_class($wpdb)==='wpdb'&&$coreHash===$expected['wpdbSourceSha256'],
    'wordpressVersion'=>$wp_version==='7.1.3','phpBranch'=>PHP_MAJOR_VERSION===8&&PHP_MINOR_VERSION===5,
    'newScopeAbsent'=>!in_array('alexcuadra_prepare_post_changes',$caps,true)];
\WP_CLI::line('BLOG_PROPOSAL_CODE_FINGERPRINT_RESULT='.wp_json_encode(['format'=>'blog-proposal-code-fingerprint-v1',
    'checks'=>$checks,'codeMatchesLocalFixture'=>!in_array(false,$checks,true),'installationApproved'=>false,
    'wordpress'=>$wp_version,'php'=>PHP_VERSION,'plugins'=>$plugins,'muPluginSha256'=>$mu,'theme'=>$actualTheme,'dropinNames'=>$dropins,
    'wpdbSourceSha256'=>$coreHash,'effectiveCapabilityNames'=>$caps,'laboratoryFixtureIgnored'=>$ignored,
    'wholePluginTreesVerified'=>false,'sourceContentInspected'=>false,'applicationPasswordsRequested'=>false,'mutationsRequested'=>false]));
