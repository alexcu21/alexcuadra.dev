<?php
if(getenv('AC_SOURCE_EFFECTS_FULL')!=='verified-backup-v1'){throw new RuntimeException('full_profile_required');}
PLL()->options->set('post_types',['project']);PLL()->options->set('sync',[]);PLL()->options->save_all();
foreach([['en','en_US','English'],['es','es_ES','Español']] as [$slug,$locale,$name]){
 if(!in_array($slug,pll_languages_list(),true)){$r=PLL()->model->add_language(['name'=>$name,'slug'=>$slug,'locale'=>$locale,'rtl'=>false,'term_group'=>$slug==='en'?0:1]);if(is_wp_error($r)){throw new RuntimeException('language_setup_failed');}}
}
PLL()->options->set('default_lang','en');PLL()->options->save_all();
echo "SOURCE_FULL_SETUP_READY\n";
