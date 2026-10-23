<?php
/** Plugin Name: Synthetic source/effect fixture */
if(wp_get_environment_type()!=='local'||!defined('AC_EDITORIAL_SOURCE_EXPERIMENT')||AC_EDITORIAL_SOURCE_EXPERIMENT!==true){throw new RuntimeException('experiment_required');}
add_filter('pre_http_request',static function(){return new WP_Error('experiment_offline','OUTBOUND_BLOCKED');},PHP_INT_MIN,3);
add_filter('pre_wp_mail',static function(){return false;},PHP_INT_MIN);
add_action('init',static function(){register_post_type('project',['public'=>true,'supports'=>['title','editor','custom-fields']]);},0);
add_action('acf/init',static function(){
 foreach(json_decode(file_get_contents('/fields.json'),true,512,JSON_THROW_ON_ERROR) as $group){
  if($group['key']==='group_676d40f7bb646'){acf_add_local_field_group($group);}
 }
});
