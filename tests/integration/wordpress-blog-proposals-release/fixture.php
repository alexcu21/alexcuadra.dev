<?php
use AlexCuadra\EditorialBlogProposals as B;
if(wp_get_environment_type()!=='local'||home_url()!=='http://wordpress'||DB_HOST!=='db'){throw new RuntimeException('RELEASE_LAB_ONLY');}
global $wpdb;
$mode=($args??[])[0]??'';wp_set_current_user(1);
if($mode==='metadata'){
    require_once ABSPATH.'wp-admin/includes/plugin.php';$theme=wp_get_theme();$dir=$theme->get_stylesheet_directory();
    WP_CLI::line('RELEASE_PROFILE_METADATA='.wp_json_encode(['theme'=>$theme->get_stylesheet(),'version'=>$theme->get('Version'),'parent'=>$theme->get_template(),
        'styleSha256'=>hash_file('sha256',$dir.'/style.css'),'functionsSha256'=>is_file($dir.'/functions.php')?hash_file('sha256',$dir.'/functions.php'):null,
        'dropins'=>array_keys(get_dropins()),'abilityFunctions'=>['wp_get_abilities'=>function_exists('wp_get_abilities'),'wp_has_ability'=>function_exists('wp_has_ability')],
        'registryMethods'=>get_class_methods('WP_Abilities_Registry')]));
}elseif($mode==='setup'){
    foreach([['en','en_US','English'],['es','es_ES','Español']]as$lang){if(!in_array($lang[0],pll_languages_list(),true)){$r=PLL()->model->add_language(['name'=>$lang[2],'slug'=>$lang[0],'locale'=>$lang[1],'rtl'=>false,'term_group'=>$lang[0]==='en'?0:1]);if(is_wp_error($r)){throw new RuntimeException('LANGUAGE_SETUP_FAILED');}}}
    PLL()->options->set('default_lang','en');PLL()->options->save_all();
    $id=wp_create_user('release_reader',wp_generate_password(32),'release@example.test');if(is_wp_error($id)||$id!==2){throw new RuntimeException('FRESH_RELEASE_LAB_REQUIRED');}
    $user=new WP_User(2);$user->set_role('subscriber');
    foreach(['alexcuadra_apply_project_changes','alexcuadra_prepare_project_changes','alexcuadra_read_public_posts','alexcuadra_read_public_projects','alexcuadra_recover_project_changes']as$cap){$user->add_cap($cap);}
}elseif($mode==='assert-retained'){
    $counts=[];foreach(['proposals','revisions','requests']as$suffix){$counts[]=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ac_blog_proposal_$suffix");}
    if($counts!==[1,1,1]||(new WP_User(2))->has_cap('alexcuadra_prepare_post_changes')||!(new WP_User(2))->has_cap('alexcuadra_read_public_posts')){throw new RuntimeException('RETENTION_OR_SCOPE_FAILED');}
}elseif(in_array($mode,['install-storage','enroll','grant','withdraw','poison','repair','assert-poison','seed-private'],true)){
    $repo=new B\WordPressRepository($wpdb);
    if($mode==='install-storage'){$repo->install();}
    if($mode==='enroll'){$repo->enroll(2);}
    if($mode==='grant'){(new WP_User(2))->add_cap(B\PREPARE_SCOPE);}
    if($mode==='withdraw'){(new WP_User(2))->remove_cap(B\PREPARE_SCOPE);}
    if($mode==='poison'){$wpdb->query("ALTER TABLE {$wpdb->prefix}ac_blog_proposal_proposals ADD INDEX unreviewed(version)");}
    if($mode==='repair'){$wpdb->query("ALTER TABLE {$wpdb->prefix}ac_blog_proposal_proposals DROP INDEX unreviewed");}
    if($mode==='assert-poison'){
        wp_set_current_user(2);try{$repo->listing();throw new RuntimeException('DRIFT_NOT_BLOCKED');}catch(DomainException $e){if($e->getMessage()!=='blog_proposal_storage_not_ready'){throw $e;}}
    }
    if($mode==='seed-private'){
        $id=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'Retention fixture','post_content'=>'Synthetic source'],true);if(is_wp_error($id)){throw new RuntimeException('SEED_SOURCE_FAILED');}
        pll_set_post_language($id,'en');wp_set_object_terms($id,[],'category');wp_set_object_terms($id,[],'post_tag');wp_set_current_user(2);$p=B\wordpress_projection($id);
        $repo->create(['request_id'=>str_repeat('a',32),'source_id'=>$id,'locale'=>'en','base_hash'=>$p['base_hash'],'operation'=>'update-post','fields'=>['title'=>'Private retained intention']]);
    }
}else{throw new RuntimeException('UNKNOWN_LAB_MODE');}
WP_CLI::line('RELEASE_FIXTURE_'.$mode.'_OK');
