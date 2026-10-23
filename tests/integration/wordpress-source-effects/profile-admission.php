<?php
function full_source_profile_admission(){
 if(getenv('AC_SOURCE_EFFECTS_FULL')!=='verified-backup-v1'){throw new RuntimeException('full_profile_required');}
 require_once ABSPATH.'wp-admin/includes/plugin.php';
 $expected=json_decode(file_get_contents('/recorded-profile.json'),true,512,JSON_THROW_ON_ERROR);
 $installed=get_plugins();$loaded=get_included_files();$active=[];
 foreach($expected['plugins'] as $p){
  $file=$p['file'];
  if(($installed[$file]['Version']??null)!==$p['version']||is_plugin_active($file)!==$p['active']||hash_file('sha256',WP_PLUGIN_DIR.'/'.$file)!==$expected['mainHashes'][$file]){throw new RuntimeException('source_plugin_profile_changed');}
  if($p['active']){$active[]=$file;if(!in_array(WP_PLUGIN_DIR.'/'.$file,$loaded,true)){throw new RuntimeException('source_plugin_not_loaded');}}
  elseif(in_array(WP_PLUGIN_DIR.'/'.$file,$loaded,true)){throw new RuntimeException('inactive_source_plugin_loaded');}
 }
 $active[]='alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php';sort($active);$actual=get_option('active_plugins');sort($actual);
 if($actual!==$active){throw new RuntimeException('active_profile_changed');}
 foreach($expected['candidateFiles'] as $path=>$sha){if(hash_file('sha256',WP_PLUGIN_DIR.'/'.$path)!==$sha){throw new RuntimeException('candidate_profile_changed');}}
 $mu=get_mu_plugins();unset($mu['source-effects-fixture.php']);$names=array_keys($mu);sort($names);$expectedNames=array_keys($expected['muHashes']);sort($expectedNames);
 if($names!==$expectedNames){throw new RuntimeException('mu_profile_changed');}
 foreach($expected['muHashes'] as $file=>$sha){if(!in_array(WPMU_PLUGIN_DIR.'/'.$file,$loaded,true)||hash_file('sha256',WPMU_PLUGIN_DIR.'/'.$file)!==$sha){throw new RuntimeException('mu_profile_changed');}}
 if(get_stylesheet()!=='twentytwentyfour'||get_template()!=='twentytwentyfour'||wp_get_theme()->get('Version')!=='1.6'||!in_array(get_theme_file_path('functions.php'),$loaded,true)){throw new RuntimeException('theme_profile_changed');}
 if(!defined('POLYLANG_VERSION')||POLYLANG_VERSION!=='3.9.1'){throw new RuntimeException('polylang_profile_changed');}
 return ['installedSourcePlugins'=>18,'activeSourcePlugins'=>13,'additionalPrivateProposals'=>1,'muPlugins'=>2,'theme'=>'twentytwentyfour','themeVersion'=>'1.6'];
}
