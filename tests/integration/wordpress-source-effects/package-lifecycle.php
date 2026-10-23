<?php
require_once ABSPATH.'wp-admin/includes/plugin.php';
require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/operating-contract.php';
require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/operating-abilities.php';
global $wpdb;$phase=getenv('AC_PACKAGE_LIFECYCLE_PHASE');$GLOBALS['lifecycle_checks']=0;$slug='alexcuadra-editorial-operating/alexcuadra-editorial-operating.php';
function lifecycle_check($ok,$label){$GLOBALS['lifecycle_checks']++;if(!$ok){throw new RuntimeException('LIFECYCLE: '.$label);}}
function lifecycle_snapshot(){global $wpdb;
 $hash=hash_init('sha256');
 foreach(['posts','postmeta','term_relationships','term_taxonomy','terms','usermeta'] as $key){$rows=$wpdb->get_results("SELECT * FROM {$wpdb->$key} ORDER BY ".(['posts'=>'ID','postmeta'=>'meta_id','term_relationships'=>'object_id,term_taxonomy_id','term_taxonomy'=>'term_taxonomy_id','terms'=>'term_id','usermeta'=>'umeta_id'][$key]),ARRAY_A);if($wpdb->last_error){throw new RuntimeException('snapshot_sql');}hash_update($hash,serialize($rows));}
 foreach(['source_events','source_effects','operating_reviews','operating_requests','operating_apps','operating_approvals'] as $name){$rows=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}ac_exp_$name",ARRAY_A);if($wpdb->last_error){throw new RuntimeException('snapshot_sql');}$encoded=array_map('serialize',$rows);sort($encoded);hash_update($hash,serialize($encoded));}
 hash_update($hash,serialize(get_option($wpdb->prefix.'user_roles')));hash_update($hash,serialize(get_option('ac_exp_operating_storage')));
 $active=array_values(array_diff(get_option('active_plugins',[]),['alexcuadra-editorial-operating/alexcuadra-editorial-operating.php']));return ['data'=>hash_final($hash),'otherActive'=>$active];
}
if($phase==='baseline'){update_option('ac_package_lifecycle_baseline',lifecycle_snapshot(),false);return;}
$specs=AlexCuadra\EditorialSourceExperiment\operating_ability_specs();
if($phase==='enabled'){
 lifecycle_check(is_plugin_active($slug),'active');
 foreach($specs as $name=>$spec){lifecycle_check(wp_get_ability('alexcuadra/'.$name) instanceof WP_Ability,'normal plugin ability');}
 $main=WP_PLUGIN_DIR.'/'.$slug;lifecycle_check((new ReflectionFunction('AlexCuadra\\EditorialOperatingPackage\\controller'))->getFileName()===$main,'installed controller path');
 $owner=get_user_by('login','mcp_operating_owner')->ID;wp_set_current_user($owner);$proposal=(int)$wpdb->get_var($wpdb->prepare("SELECT proposal_id FROM {$wpdb->prefix}ac_exp_operating_apps WHERE owner_id=%d",$owner));
 $result=wp_get_ability('mcp-adapter/execute-ability')->execute(['ability_name'=>'alexcuadra/get-project-effect-state','parameters'=>['proposal_id'=>$proposal,'locale'=>'en']]);lifecycle_check(!is_wp_error($result)&&($result['success']??false)&&$result['data']['effect_state']==='pending','official adapter uses normal plugin');wp_set_current_user(0);
}else{
 lifecycle_check(is_plugin_active($slug)===($phase==='disabled'),'expected activation');
 foreach($specs as $name=>$spec){lifecycle_check(wp_get_ability('alexcuadra/'.$name)===null,'ability absent');}
}
lifecycle_check(lifecycle_snapshot()===get_option('ac_package_lifecycle_baseline'),'data caps sources and other plugins unchanged');
echo 'SOURCE_PACKAGE_LIFECYCLE_RESULT='.wp_json_encode(['phase'=>$phase,'checks'=>$GLOBALS['lifecycle_checks'],'passed'=>true]).PHP_EOL;
