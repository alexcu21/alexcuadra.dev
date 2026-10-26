<?php
if(wp_get_environment_type()!=='local'||home_url()!=='http://wordpress'||DB_HOST!=='db'){throw new RuntimeException('ISOLATED_RELEASE_LAB_REQUIRED');}
$mode=($args??[])[0]??'';
if($mode==='setup'){
    foreach([['en','en_US','English'],['es','es_ES','Español']]as$lang){
        if(!in_array($lang[0],pll_languages_list(),true)){
            $r=PLL()->model->add_language(['name'=>$lang[2],'slug'=>$lang[0],'locale'=>$lang[1],'rtl'=>false,'term_group'=>$lang[0]==='en'?0:1]);
            if(is_wp_error($r)){throw new RuntimeException('LANGUAGE_SETUP_FAILED');}
        }
    }
    PLL()->options->set('default_lang','en');PLL()->options->save_all();
    if(get_user_by('login','package_reader')){throw new RuntimeException('FRESH_PACKAGE_LAB_REQUIRED');}
    $id=wp_create_user('package_reader',wp_generate_password(32),'package-reader@example.test');
    if(is_wp_error($id)||$id!==2){throw new RuntimeException('EXPECTED_SYNTHETIC_USER_TWO');}
    $user=new WP_User($id);$user->set_role('subscriber');
}elseif(in_array($mode,['grant','cleanup'],true)){
    $user=get_user_by('login','package_reader');
    if(!$user||$user->ID!==2){throw new RuntimeException('SYNTHETIC_READER_REQUIRED');}
    if($mode==='grant'){$user->add_cap('alexcuadra_read_public_posts');}else{$user->remove_cap('alexcuadra_read_public_posts');}
}else{throw new RuntimeException('INVALID_FIXTURE_PHASE');}
WP_CLI::line('PACKAGE_FIXTURE_'.$mode.'_OK');
