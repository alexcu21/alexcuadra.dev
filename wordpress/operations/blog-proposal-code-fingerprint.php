<?php
/** Read-only code metadata. No installation, grants, source content or credentials. */
namespace AlexCuadra\BlogProposalCodeFingerprint;
if(!defined('WP_CLI')||!WP_CLI){throw new \RuntimeException('WP_CLI_REQUIRED');}
if(($args??[])!==[]){\WP_CLI::error('EXPECTED_NO_ARGUMENTS');}
if(is_multisite()||rtrim(home_url(),'/')!=='https://content.alexcuadra.dev'||rtrim(site_url(),'/')!=='https://content.alexcuadra.dev'){
    \WP_CLI::error('WRONG_CODE_FINGERPRINT_DESTINATION');
}
require_once ABSPATH.'wp-admin/includes/plugin.php';
$expected=json_decode('{"plugins":{"advanced-custom-fields/acf.php":{"version":"6.8.10","mainSha256":"e3c7114e354ea19850463db3191c2d60ee244e57501d5008cd0617f7c0fb7e64"},"alexcuadra-editorial-blog-read/alexcuadra-editorial-blog-read.php":{"version":"0.1.0-rc.1","mainSha256":"7a50acba34429ec6cedcf467fbdcfae8e2702abf6420f170235f019664024f73"},"alexcuadra-editorial-operating/alexcuadra-editorial-operating.php":{"version":"0.1.0-rc.3","mainSha256":"77cf118d953992a0d99e20ace6c5a31278ea637f0bfaa07ed1ee9970e0c4a3bf"},"alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php":{"version":"0.1.0-rc.2","mainSha256":"a9a1b498ab18472a30966cfcd160ef91d35aa62420911e31ca30e7ec3452949e"},"alexcuadra-editorial-read/alexcuadra-editorial-read.php":{"version":"0.1.0","mainSha256":"9e4def28919b5dc896037fa13dddf62b4f63e4a725095fe12b3e0d8419f22619"},"hostinger-easy-onboarding/hostinger-easy-onboarding.php":{"version":"3.0.5","mainSha256":"5e2927308eaa142bb79149603aeb3e4b8b0ad33db6e6c59f4cfbd5b0761d0a19"},"hostinger-reach/hostinger-reach.php":{"version":"1.8.7","mainSha256":"dffaaeea0c33214046f7f62b8d104d927ad17be0942c4af4a67d0e9ae734f48b"},"hostinger/hostinger.php":{"version":"3.0.80","mainSha256":"5d1dd6e5d9677e6c53732765f343eb5fd573d13fb0314e67d30afeecf24d264e"},"litespeed-cache/litespeed-cache.php":{"version":"7.9.2","mainSha256":"ad57729d8736659c99068f4727d299c6c6b29b55d019d67a5c417918c3370f2f"},"mcp-adapter/mcp-adapter.php":{"version":"0.7.1","mainSha256":"ad0388cd18385c66a8efc7ec433047e6787e75cd2d0bbf2332333258717b4620"},"polylang/polylang.php":{"version":"3.9.1","mainSha256":"39cf938c68eb3c34078c8b1ea1dd900889458cb03b4977a9a9ff92f7b3e01379"},"wordpress-beta-tester/wp-beta-tester.php":{"version":"4.0.1","mainSha256":"536e1073be4b7cefff0f5158aa14f6684f03d049e808db567aad963556641a40"},"wp-graphql-polylang-0.7.1/wp-graphql-polylang.php":{"version":"0.7.1","mainSha256":"6e2933f965a072969e7d0dbf4a9e623e2a7e7e1ee1da6ea416bad7a18b7688a4"},"wp-graphql/wp-graphql.php":{"version":"2.25.0","mainSha256":"d1bfa232b509aebf130fc7f7f090618f5577d296df8d3d6bfef0af55618a4408"},"wpgraphql-acf/wpgraphql-acf.php":{"version":"3.0.0","mainSha256":"6ef3736685ea63e4707e631886dea03dd8cee05c7e76b87cc1f6b0eae72db842"},"wpgraphql-ide/wpgraphql-ide.php":{"version":"5.7.0","mainSha256":"b22c9f1d2474665577b7d2842214ad64835fa43cd7a91f8dd30d5561dd054236"}},"muPluginSha256":{"hostinger-auto-updates.php":"2492da0336ed34b4377b2a2de882603280e0fde70df712b08fcd2ddef588a5bf","hostinger-preview-domain.php":"ccedfe9be0bce8e941274a519bfea4ca686a7cc0a1f0cedccdbb39384d8764f4"},"theme":{"stylesheet":"twentytwentyfour","template":"twentytwentyfour","version":"1.6","styleSha256":"fd3c8a4e86382cbd69046dd4670112c7b17817bdff3671bf29d3e49a874c1fe2","functionsSha256":"6cec0f112f34ff3a043fe1e16c2d235ce905a735d46039a50879e8fd469f2bce"},"wpdbSourceSha256":"e15403e90032dd0508811301505e491d4212de5152581a98f51b744a1a3903bd","fixtureMuSha256":"285beda78da96fa89a30ae9f1c7b9c24f62cbe40f8c79759241698e13bd2f7d5"}',true,32,JSON_THROW_ON_ERROR);
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
