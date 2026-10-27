<?php
if(wp_get_environment_type()!=='local'||DB_HOST!=='db'||getenv('BLOG_SITE_PROFILE_LAB')!=='verified-code-only'){throw new RuntimeException('SITE_PROFILE_LAB_REQUIRED');}
$profile=json_decode(file_get_contents(ABSPATH.'.lab-profile.json'),true,32,JSON_THROW_ON_ERROR);
$user=wp_create_user('site_profile_reader',wp_generate_password(32),'site-profile@example.test');
if(is_wp_error($user)||$user!==2){throw new RuntimeException('FRESH_SYNTHETIC_DATABASE_REQUIRED');}
$u=new WP_User(2);$u->set_role('subscriber');
foreach(['alexcuadra_apply_project_changes','alexcuadra_prepare_project_changes','alexcuadra_read_public_posts','alexcuadra_read_public_projects','alexcuadra_recover_project_changes']as$cap){$u->add_cap($cap);}
update_option('stylesheet',$profile['theme']['stylesheet']);update_option('template',$profile['theme']['template']);
update_option('active_plugins',array_keys($profile['activePlugins']));
WP_CLI::line('SITE_PROFILE_SYNTHETIC_SETUP_OK');
