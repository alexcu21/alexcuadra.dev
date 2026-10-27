<?php
/** Plugin Name: Isolated project classification fixture */
if(wp_get_environment_type()!=='local'||DB_HOST!=='db'||home_url()!=='http://wordpress'){throw new RuntimeException('LOCAL_PROJECT_LAB_REQUIRED');}
add_filter('pre_wp_mail',static fn()=>false);
add_filter('pre_http_request',static function($pre,$args,$url){$u=parse_url($url);return ($u['scheme']??'')==='http'&&($u['host']??'')==='wordpress'&&($u['port']??80)===80&&!isset($u['user'])&&!isset($u['pass'])?$pre:new WP_Error('offline','OUTBOUND_BLOCKED');},PHP_INT_MIN,3);
add_filter('pll_get_post_types',static function($types){$types['project']='project';return $types;});
add_filter('pll_get_taxonomies',static function($types){foreach(['project_category','project_technology']as$t){$types[$t]=$t;}return $types;});
add_action('init',static function(){
 register_post_type('project',['public'=>true,'show_in_rest'=>true,'supports'=>['title','editor']]);
 register_taxonomy('project_category',['project'],['public'=>true,'hierarchical'=>true,'show_in_rest'=>true]);
 register_taxonomy('project_technology',['project'],['public'=>true,'hierarchical'=>false,'show_in_rest'=>true]);
},0);
require_once '/project-classification/reader.php';
