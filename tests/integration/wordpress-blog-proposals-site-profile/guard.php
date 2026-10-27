<?php
/** Plugin Name: Isolated site profile safety fixture */
if(wp_get_environment_type()!=='local'||DB_HOST!=='db'||getenv('BLOG_SITE_PROFILE_LAB')!=='verified-code-only'){throw new RuntimeException('SITE_PROFILE_LAB_REQUIRED');}
add_filter('pre_http_request',static function($pre,$args,$url){
    $p=parse_url($url);
    if(($p['scheme']??'')==='http'&&($p['host']??'')==='wordpress'&&($p['port']??80)===80&&!isset($p['user'])&&!isset($p['pass'])){return $pre;}
    return new WP_Error('site_profile_offline','OUTBOUND_BLOCKED');
},PHP_INT_MIN,3);
add_filter('pre_wp_mail',static fn()=>false,PHP_INT_MIN);
add_action('init',static function(){register_post_type('project',['public'=>true,'show_in_rest'=>true,'supports'=>['title','editor']]);},0);
