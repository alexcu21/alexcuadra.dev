<?php
/** Standalone WP-CLI diagnostic. Never installs, activates, grants or saves state. */
if (!defined('WP_CLI') || !WP_CLI) { throw new RuntimeException('WP_CLI_REQUIRED'); }
$input = $args ?? [];
$phases = ['before','after-install','after-enable','after-scope'];
if (count($input)!==2 || !preg_match('/^[1-9][0-9]*$/D',$input[0]) || !in_array($input[1],$phases,true)) {
    WP_CLI::error('EXPECTED_USER_AND_PHASE');
}
$site=rtrim(home_url(),'/');$url=rtrim(site_url(),'/');
$lab=$site==='http://wordpress'&&$url===$site&&wp_get_environment_type()==='local';
if (is_multisite() || (!$lab && ($site!=='https://content.alexcuadra.dev'||$url!==$site||$input[0]!=='2'))) { WP_CLI::error('WRONG_BLOG_READ_DESTINATION'); }
require_once ABSPATH.'wp-admin/includes/plugin.php';
$target=get_userdata((int)$input[0]);if(!$target){WP_CLI::error('TARGET_USER_NOT_FOUND');}
$phase=$input[1];$scope='alexcuadra_read_public_posts';$plugin='alexcuadra-editorial-blog-read/alexcuadra-editorial-blog-read.php';
$folder=WP_PLUGIN_DIR.'/alexcuadra-editorial-blog-read';
$expected=[
    'alexcuadra-editorial-blog-read.php' => '7a50acba34429ec6cedcf467fbdcfae8e2702abf6420f170235f019664024f73',
    'reader.php' => 'debba438e59f368a060de45bf5fc1e98f3cc02a85e783e6ed426e6782b352b46',
    'README.md' => '858668be3aa77093d163a2fb8134c4638f3275a03e3aee051993ae8f0c74fe9b',
];
$present=is_dir($folder);$exact=$present&&!is_link($folder);
if($exact){
    $names=array_values(array_diff(scandir($folder),['.','..']));sort($names);$wanted=array_keys($expected);sort($wanted);$exact=$names===$wanted;
    foreach($expected as$name=>$hash){$path=$folder.'/'.$name;$exact=$exact&&is_file($path)&&!is_link($path)&&hash_file('sha256',$path)===$hash;}
}
$active=is_plugin_active($plugin);
$guard=$lab ? (defined('AC_EDITORIAL_BLOG_READ_LAB')&&AC_EDITORIAL_BLOG_READ_LAB===true)
    : (defined('AC_EDITORIAL_BLOG_READ_SITE')&&AC_EDITORIAL_BLOG_READ_SITE==='https://content.alexcuadra.dev');
$enabled=in_array($phase,['after-enable','after-scope'],true);$scoped=$phase==='after-scope';
$old=get_current_user_id();
try{
    wp_set_current_user($target->ID);
    $broad=['manage_options','install_plugins','activate_plugins','update_plugins','edit_users','promote_users','delete_users',
        'create_users','edit_posts','edit_pages','edit_others_posts','edit_others_pages','publish_posts','publish_pages','delete_posts','delete_pages'];
    $denied=[];foreach($broad as$cap){$denied[$cap]=!current_user_can($cap);}
    $allowed=array_keys(array_filter($target->allcaps));sort($allowed);
    $roleScoped=false;foreach(wp_roles()->roles as$role){if(!empty($role['capabilities'][$scope])){$roleScoped=true;}}
    $granted=get_users(['capability'=>$scope,'fields'=>'ID','number'=>3,'orderby'=>'ID','order'=>'ASC']);$granted=array_map('intval',$granted);
    $dependencyVersions=[];
    foreach(['polylang/polylang.php'=>'3.9.1','mcp-adapter/mcp-adapter.php'=>'0.7.1']as$path=>$version){
        $file=WP_PLUGIN_DIR.'/'.$path;$data=is_file($file)?get_plugin_data($file,false,false):[];
        $dependencyVersions[$path]=['version'=>$data['Version']??null,'active'=>is_plugin_active($path),'matches'=>($data['Version']??null)===$version&&is_plugin_active($path)];
    }
    $translated=true;foreach(['pll_is_translated_post_type','pll_is_translated_taxonomy','pll_languages_list']as$fn){$translated=$translated&&function_exists($fn);}
    $translated=$translated&&pll_is_translated_post_type('post')&&pll_is_translated_taxonomy('category')&&pll_is_translated_taxonomy('post_tag');
    $languages=$translated?pll_languages_list():[];
    $registered=0;foreach(['alexcuadra/list-public-posts','alexcuadra/get-public-post']as$name){if(function_exists('wp_get_ability')&&wp_get_ability($name)){$registered++;}}
    global $wp_version;
    $checks=[
        'testedRuntime'=>$wp_version==='7.1.3'&&PHP_MAJOR_VERSION===8&&PHP_MINOR_VERSION===5,
        'testedDependencies'=>!in_array(false,array_column($dependencyVersions,'matches'),true),
        'publicTranslatedTaxonomies'=>$translated&&is_taxonomy_viewable('category')&&is_taxonomy_viewable('post_tag'),
        'languages'=>in_array('en',$languages,true)&&in_array('es',$languages,true),
        'subscriberOnly'=>$target->roles===['subscriber'], 'read'=>current_user_can('read'),
        'noBroadPermissions'=>!in_array(false,$denied,true), 'noRoleWideGrant'=>!$roleScoped,
        'expectedScope'=>current_user_can($scope)===$scoped,
        'scopeOwners'=>$granted===($scoped?[$target->ID]:[]),
        'expectedFiles'=>$phase==='before'?!$present:$exact,
        'expectedActivation'=>$active===$enabled, 'expectedGuard'=>$guard===$enabled,
        'expectedRegistration'=>$registered===($enabled?2:0),
    ];
    $report=['format'=>'editorial-blog-read-preflight-v1','phase'=>$phase,'userId'=>$target->ID,
        'packageSha256'=>'d8f8fcb1dcc83962036a70c3fb0e3a57e44dbeb96b5556e8abe1dcb7fbd205e6','readOnly'=>true,'wordpress'=>$wp_version,'php'=>PHP_VERSION,
        'dependencies'=>$dependencyVersions,'checks'=>$checks,'ready'=>!in_array(false,$checks,true),
        'effectiveCapabilityNames'=>$allowed,'broadPermissionsDenied'=>$denied,
        'sourceContentInspected'=>false,'applicationPasswordsRequested'=>false,'mutationsRequested'=>false];
}finally{wp_set_current_user($old);}
WP_CLI::line('EDITORIAL_BLOG_PREFLIGHT_RESULT='.wp_json_encode($report));
if(!$report['ready']){WP_CLI::halt(1);}
