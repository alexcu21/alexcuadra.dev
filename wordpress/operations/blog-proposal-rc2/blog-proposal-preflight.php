<?php
/** Standalone diagnostic: only metadata/counts, no installation, activation or grants. */
namespace AlexCuadra\BlogProposalPreflight;
use AlexCuadra\BlogProposalAdmission as A;
if(!defined('WP_CLI')||!WP_CLI){throw new \RuntimeException('WP_CLI_REQUIRED');}
require_once __DIR__.'/blog-proposal-admission.php';
$input=$args??[];$phases=['before','after-install','after-enable','after-storage','after-owner','after-scope','withdrawn'];
if(count($input)!==2||$input[0]!=='2'||!in_array($input[1],$phases,true)){\WP_CLI::error('EXPECTED_USER_2_AND_PHASE');}
$phase=$input[1];$home=rtrim(home_url(),'/');$site=rtrim(site_url(),'/');
$local=A\laboratory_environment();
if(is_multisite()||(!$local&&($home!=='https://content.alexcuadra.dev'||$site!==$home))){\WP_CLI::error('WRONG_BLOG_PROPOSAL_DESTINATION');}
global $wpdb;
$profile=A\profile_report();
try{$schema=A\schema_report($wpdb);}catch(\DomainException $e){$schema=['present'=>-1,'checks'=>['metadataAccessible'=>false],'ready'=>false,'tables'=>[],'error'=>'schema_metadata_unavailable'];}
$plugin='alexcuadra-editorial-blog-proposals/alexcuadra-editorial-blog-proposals.php';$folder=WP_PLUGIN_DIR.'/alexcuadra-editorial-blog-proposals';
$hashes=json_decode('{"README.md":"606c7dff0d1e4e684342719800b4fe2a9bfa8b143d3cc936afa2607ec0079369","abilities.php":"46c4ef85ae4f336e7b62075ea34da9cfeba7d69e48eb366d19e1f364050009c2","admission.php":"7043443da1d1ea07208cd52dbafbd98f1458e425b19a00546d3219ac90805eb4","alexcuadra-editorial-blog-proposals.php":"62346abeeb919130faea84b8db88876140125cb837eaa06e0062a0fcf423c42a","connection-fence.php":"c836eb135f411cc2e59d54eb126bd5d2e4a91ba184ca47018c3728e4ae1b5be5","planner.php":"a47a8ce035a391fa4e0ba7de7d11a0db0b2b8d241aa7d3d9b5d063d55b44ae8f","wordpress-repository.php":"14756411443d31ad89409a8a0963b72d38492d2e544e4b8b523ca3592bbf7204","wordpress-source.php":"10a7efb3af20870fbd3cec53550d663b1253b397c8cffa8776a7cd39071a94bd"}',true,32,JSON_THROW_ON_ERROR);
$present=is_dir($folder);$exact=$present&&!is_link($folder);
if($exact){$actual=array_values(array_diff(scandir($folder),['.','..']));sort($actual);$expected=array_keys($hashes);sort($expected);$exact=$actual===$expected;
    foreach($hashes as$name=>$hash){$file=$folder.'/'.$name;$exact=$exact&&is_file($file)&&!is_link($file)&&hash_file('sha256',$file)===$hash;}}
$enabled=in_array($phase,['after-enable','after-storage','after-owner','after-scope'],true);
$storage=in_array($phase,['after-storage','after-owner','after-scope','withdrawn'],true);$enrolled=in_array($phase,['after-owner','after-scope','withdrawn'],true);$scoped=$phase==='after-scope';
$user=get_userdata(2);if(!$user){\WP_CLI::error('TARGET_USER_NOT_FOUND');}
$cap='alexcuadra_prepare_post_changes';$allowed=array_keys(array_filter($user->allcaps));sort($allowed);
$baseline=['alexcuadra_apply_project_changes','alexcuadra_prepare_project_changes','alexcuadra_read_public_posts','alexcuadra_read_public_projects','alexcuadra_recover_project_changes','level_0','read','subscriber'];
if($scoped){$baseline[]=$cap;}sort($baseline);
$roleWide=false;foreach(wp_roles()->roles as$role){$roleWide=$roleWide||!empty($role['capabilities'][$cap]);}
$scopeOwners=array_map('intval',get_users(['capability'=>$cap,'fields'=>'ID','number'=>3,'orderby'=>'ID','order'=>'ASC']));
$old=get_current_user_id();
try{wp_set_current_user(2);$denied=[];
    foreach(['manage_options','install_plugins','activate_plugins','update_plugins','edit_users','promote_users','delete_users','create_users','edit_posts','edit_pages','edit_others_posts','edit_others_pages','publish_posts','publish_pages','delete_posts','delete_pages']as$c){$denied[$c]=!current_user_can($c);}
    $scope=current_user_can($cap);$read=current_user_can('read');
}finally{wp_set_current_user($old);}
$registered=0;foreach(['inspect-post-context','create-post-proposal','update-post-proposal','get-post-proposal','list-post-proposals']as$name){if(function_exists('wp_has_ability')&&wp_has_ability('alexcuadra-blog/'.$name)){$registered++;}}
$owners=[];$empty=$schema['present']===0;
if($schema['present']===4&&$schema['ready']){
    $empty=true;
    $owners=array_map('intval',array_column(A\admission_rows($wpdb,"SELECT owner_id FROM {$wpdb->prefix}ac_blog_proposal_owners ORDER BY owner_id LIMIT 3"),'owner_id'));
    foreach(['proposals','revisions','requests']as$suffix){$rows=A\admission_rows($wpdb,"SELECT COUNT(*) AS n FROM {$wpdb->prefix}ac_blog_proposal_$suffix");$empty=$empty&&(int)$rows[0]['n']===0;}
}
$checks=['testedProfile'=>$profile['ready'],'subscriberOnly'=>$user->roles===['subscriber'],'read'=>$read,
    'noBroadPermissions'=>!in_array(false,$denied,true),'preservedExactCapabilities'=>$allowed===$baseline,
    'noRoleWideGrant'=>!$roleWide,'expectedScope'=>$scope===$scoped,'scopeOwners'=>$scopeOwners===($scoped?[2]:[]),
    'expectedFiles'=>$phase==='before'?!$present:$exact,'expectedActivation'=>is_plugin_active($plugin)===$enabled,
    'expectedGuard'=>A\site_admitted()===$enabled,'expectedRegistration'=>$registered===($enabled?5:0),
    'expectedSchema'=>$storage?$schema['ready']:$schema['present']===0,
    'expectedOwners'=>($schema['present']===0||$schema['ready'])&&$owners===($enrolled?[2]:[]),'initialPrivateStorageEmpty'=>$phase==='withdrawn'||$empty];
$report=['format'=>'blog-proposal-preflight-v1','phase'=>$phase,'userId'=>2,'packageSha256'=>'7e5bc2b3213a8497dc47426b4565bbf528400d69f4e4f3e57f40f6119d4d6def',
    'ready'=>!in_array(false,$checks,true),'checks'=>$checks,'profile'=>$profile,'schema'=>$schema,
    'effectiveCapabilityNames'=>$allowed,'broadPermissionsDenied'=>$denied,'sourceContentInspected'=>false,
    'applicationPasswordsRequested'=>false,'mutationsRequested'=>false,'initialAcceptanceOnly'=>$phase!=='withdrawn'];
\WP_CLI::line('BLOG_PROPOSAL_PREFLIGHT_RESULT='.wp_json_encode($report));if(!$report['ready']){\WP_CLI::halt(1);}
