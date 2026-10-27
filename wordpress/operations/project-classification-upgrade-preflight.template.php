<?php
/** Read-only coordinated upgrade checks for existing non-empty private proposal history. */
namespace AlexCuadra\ProjectClassificationUpgrade;
use AlexCuadra\EditorialBlogProposals as B;
if(!defined('WP_CLI')||!WP_CLI){throw new \RuntimeException('WP_CLI_REQUIRED');}
ini_set('zend.exception_ignore_args','1');
$policy=json_decode('__BUNDLE_POLICY_JSON__',true,64,JSON_THROW_ON_ERROR);
$input=$args??[];$phase=$input[0]??'';$expectedHistory=$input[1]??null;
$phases=['before','proposal-upgraded','classifier-installed','classifier-enabled','after-scope','rolled-back'];
if(!in_array($phase,$phases,true)||count($input)!==($phase==='before'?1:2)||($phase!=='before'&&(!is_string($expectedHistory)||!preg_match('/^[a-f0-9]{64}$/D',$expectedHistory)))){\WP_CLI::error('EXPECTED_UPGRADE_PHASE_AND_ORIGINAL_HISTORY_SHA');}
require_once ABSPATH.'wp-admin/includes/plugin.php';
$exact=static function($component){$dir=WP_PLUGIN_DIR.'/'.$component['slug'];if(!is_dir($dir)||is_link($dir)){return false;}
 $files=[];foreach($component['files']as$e){$files[substr($e['path'],strlen($component['slug'])+1)]=$e['sha256'];}
 $actual=array_values(array_diff(scandir($dir),['.','..']));sort($actual);$wanted=array_keys($files);sort($wanted);if($actual!==$wanted){return false;}
 foreach($files as$n=>$sha){$p=$dir.'/'.$n;if(!is_file($p)||is_link($p)||hash_file('sha256',$p)!==$sha){return false;}}return true;
};
$old=in_array($phase,['before','rolled-back'],true);$modulePresent=!in_array($phase,['before','proposal-upgraded'],true);$moduleActive=in_array($phase,['classifier-enabled','after-scope'],true);
$scoped=in_array($phase,['before','after-scope','rolled-back'],true);$blog=$policy['components'][$old?'oldProposals':'newProposals'];$reader=$policy['components']['classification'];
$bp=$blog['slug'].'/'.$blog['slug'].'.php';$cp=$reader['slug'].'/'.$reader['slug'].'.php';
$canReport=function_exists('AlexCuadra\\EditorialBlogProposals\\profile_report');
$profile=$canReport?B\profile_report():['ready'=>false];
global $wpdb;$schema=$canReport?B\schema_report($wpdb):['ready'=>false,'present'=>-1];$history=[];$historyShape=false;$pilotHash=null;
if($schema['ready']){
 $columns=['owners'=>'owner_id','proposals'=>'id,owner_id,version','revisions'=>'proposal_id,owner_id,version,document_hash','requests'=>'owner_id,request_id,request_hash,proposal_id,version'];
 $orders=['owners'=>'owner_id','proposals'=>'id','revisions'=>'proposal_id,version','requests'=>'owner_id,request_id'];
 foreach($columns as$s=>$cols){$history[$s]=B\admission_rows($wpdb,"SELECT $cols FROM {$wpdb->prefix}ac_blog_proposal_$s ORDER BY {$orders[$s]} LIMIT 129");}
 $historyShape=count($history['owners'])===1&&$history['owners'][0]['owner_id']==='2'
  &&count($history['proposals'])===1&&$history['proposals'][0]===['id'=>'1','owner_id'=>'2','version'=>'1']
  &&count($history['revisions'])===1&&$history['revisions'][0]['proposal_id']==='1'&&$history['revisions'][0]['owner_id']==='2'&&$history['revisions'][0]['version']==='1'
  &&count($history['requests'])===1&&$history['requests'][0]['owner_id']==='2'&&$history['requests'][0]['proposal_id']==='1'&&$history['requests'][0]['version']==='1';
 $pilotHash=$history['revisions'][0]['document_hash']??null;
}
$historySha=hash('sha256',json_encode($history,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES));
$u=get_userdata(2);$caps=$u?array_keys(array_filter($u->allcaps)):[];sort($caps);
$baseline=['alexcuadra_apply_project_changes','alexcuadra_prepare_project_changes','alexcuadra_read_public_posts','alexcuadra_read_public_projects','alexcuadra_recover_project_changes','level_0','read','subscriber'];
if($scoped){$baseline[]='alexcuadra_prepare_post_changes';}sort($baseline);
$oldActor=get_current_user_id();try{wp_set_current_user(2);$broadDenied=[];foreach(['manage_options','install_plugins','activate_plugins','update_plugins','edit_users','promote_users','delete_users','create_users','edit_posts','edit_pages','edit_others_posts','edit_others_pages','publish_posts','publish_pages','delete_posts','delete_pages']as$c){$broadDenied[$c]=!current_user_can($c);}$scope=current_user_can('alexcuadra_prepare_post_changes');}finally{wp_set_current_user($oldActor);}
$scopeOwners=array_map('intval',get_users(['capability'=>'alexcuadra_prepare_post_changes','fields'=>'ID','number'=>3,'orderby'=>'ID','order'=>'ASC']));
$roleWide=false;foreach(wp_roles()->roles as$r){$roleWide=$roleWide||!empty($r['capabilities']['alexcuadra_prepare_post_changes']);}
$tools=0;foreach(['inspect-post-context','create-post-proposal','update-post-proposal','get-post-proposal','list-post-proposals']as$n){$tools+=(int)wp_has_ability('alexcuadra-blog/'.$n);}
$classTools=0;foreach(['get-public-project-classification','list-public-project-terms']as$n){$classTools+=(int)wp_has_ability('alexcuadra/'.$n);}
$taxonomiesReady=post_type_exists('project')&&function_exists('pll_is_translated_post_type')&&pll_is_translated_post_type('project');
foreach(['project_category','project_technology']as$t){$taxonomy=get_taxonomy($t);$taxonomiesReady=$taxonomiesReady&&$taxonomy&&$taxonomy->object_type===['project']&&is_taxonomy_viewable($taxonomy)&&function_exists('pll_is_translated_taxonomy')&&pll_is_translated_taxonomy($t);}
$checks=['singleTarget'=>!is_multisite()&&home_url()==='https://content.alexcuadra.dev'&&site_url()===home_url(),
 'exactProposalPackage'=>$exact($blog),'proposalActive'=>is_plugin_active($bp),'proposalGuard'=>$canReport&&B\site_admitted(),'profileCompatible'=>$profile['ready'],
 'exactClassificationFiles'=>$modulePresent?$exact($reader):!file_exists(WP_PLUGIN_DIR.'/'.$reader['slug']),
 'expectedClassificationActivation'=>is_plugin_active($cp)===$moduleActive,'privateToolsRegistered'=>$tools===5,'classificationToolsRegistered'=>$classTools===($moduleActive?2:0),
 'translatedProjectOnlyTaxonomies'=>$taxonomiesReady,'schemaReady'=>$schema['ready'],'expectedPilotShape'=>$historyShape,
 'originalHistoryPreserved'=>$phase==='before'||hash_equals($expectedHistory,$historySha),'subscriberOnly'=>$u&&$u->roles===['subscriber'],
 'exactCapabilities'=>$caps===$baseline,'expectedScope'=>$scope===$scoped,'scopeOwners'=>$scopeOwners===($scoped?[2]:[]),'noRoleWideGrant'=>!$roleWide,'noBroadPermissions'=>!in_array(false,$broadDenied,true)];
$result=['format'=>'project-classification-upgrade-preflight-v1','phase'=>$phase,'ready'=>!in_array(false,$checks,true),'checks'=>$checks,
 'proposalPackageSha256'=>$blog['sha256'],'classificationPackageSha256'=>$reader['sha256'],'historySha256'=>$historySha,'pilotDocumentSha256'=>$pilotHash,
 'counts'=>array_map('count',$history),'effectiveCapabilityNames'=>$caps,'broadPermissionsDenied'=>$broadDenied,
 'sourceBodiesRead'=>false,'privateDocumentBodiesRead'=>false,'mutationsRequested'=>false,'capturesOnlyNewModuleMetadataNotLegacySourceBaseline'=>true];
\WP_CLI::line('PROJECT_CLASSIFICATION_UPGRADE_RESULT='.json_encode($result,JSON_THROW_ON_ERROR));if(!$result['ready']){\WP_CLI::halt(1);}
