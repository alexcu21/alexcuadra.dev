<?php
/** Plugin Name: Isolated site profile safety fixture */
if(wp_get_environment_type()!=='local'||DB_HOST!=='db'||getenv('BLOG_SITE_PROFILE_LAB')!=='verified-code-only'){throw new RuntimeException('SITE_PROFILE_LAB_REQUIRED');}
add_filter('pre_http_request',static fn()=>new WP_Error('site_profile_offline','OUTBOUND_BLOCKED'),PHP_INT_MIN);
add_filter('pre_wp_mail',static fn()=>false,PHP_INT_MIN);
