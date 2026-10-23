<?php
/** Plugin Name: Synthetic source/effect fixture */
if(wp_get_environment_type()!=='local'||!defined('AC_EDITORIAL_SOURCE_EXPERIMENT')||AC_EDITORIAL_SOURCE_EXPERIMENT!==true){throw new RuntimeException('experiment_required');}
if(getenv('AC_SOURCE_EFFECTS_FULL')==='verified-backup-v1'&&($_SERVER['HTTP_HOST']??'')==='wordpress'){$_SERVER['HTTPS']='on';$_SERVER['SERVER_PORT']=443;}
add_filter('pre_http_request',static function($pre,$args,$url){
 $p=parse_url($url);
 if(($p['scheme']??'')==='http'&&($p['host']??'')==='wordpress'&&($p['port']??80)===80&&!isset($p['user'])&&!isset($p['pass'])){return $pre;}
 return new WP_Error('experiment_offline','OUTBOUND_BLOCKED');
},PHP_INT_MIN,3);
add_filter('pre_wp_mail',static function(){return false;},PHP_INT_MIN);
add_action('init',static function(){register_post_type('project',['public'=>true,'supports'=>['title','editor','custom-fields'],'show_in_graphql'=>true,'graphql_single_name'=>'project','graphql_plural_name'=>'projects']);},0);
add_action('acf/init',static function(){
 foreach(json_decode(file_get_contents('/fields.json'),true,512,JSON_THROW_ON_ERROR) as $group){
  if($group['key']==='group_676d40f7bb646'){acf_add_local_field_group($group);}
 }
});

if(getenv('AC_SOURCE_EFFECTS_HTTP')==='synthetic-v1'){require_once '/integration/http-fixture.php';}

if(getenv('AC_SOURCE_EFFECTS_FULL')==='verified-backup-v1'){add_action('save_post_project',static function($id){if(function_exists('pll_get_post_language')&&!pll_get_post_language($id)&&in_array('en',pll_languages_list(),true)){pll_set_post_language($id,'en');}},100);}

// Local-only operating MCP surface; the source modules register nothing on load.
if(getenv('AC_SOURCE_EFFECTS_FULL')==='verified-backup-v1'){
 require_once '/experiment/operating-contract.php';require_once '/experiment/operating-abilities.php';
 add_action('wp_abilities_api_categories_init',static function(){wp_register_ability_category('alexcuadra-operating',['label'=>'Owned editorial operations','description'=>'Local operating transport acceptance.']);});
 add_action('wp_abilities_api_init',static function(){AlexCuadra\EditorialSourceExperiment\register_operating_abilities(static function(){global $wpdb;if(($_SERVER['HTTP_X_OPERATING_TEST_FAILURE']??'')==='1'){throw new RuntimeException('SYNTHETIC_PRIVATE_FAILURE SQL /private/path');}return new AlexCuadra\EditorialSourceExperiment\OperatingContract($wpdb,source_operation($wpdb),'snapshot','field_schema','cache_clear');});});
}
