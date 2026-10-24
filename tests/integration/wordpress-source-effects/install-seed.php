<?php
// Synthetic state before the exact inactive deployment script; no real database import.
use AlexCuadra\EditorialProposals as P;
$id=wp_insert_user(['user_login'=>'inactive_install_owner','user_pass'=>wp_generate_password(),'role'=>'subscriber','user_email'=>'inactive-install@example.invalid']);
if(is_wp_error($id)||$id!==2){throw new RuntimeException('INSTALL_SEED_USER');}
$u=get_userdata($id);foreach(['alexcuadra_read_public_projects','alexcuadra_prepare_project_changes'] as $cap){$u->add_cap($cap);}
wp_set_current_user(1);$pair=[];
foreach(['en','es'] as $lang){$p=wp_insert_post(['post_type'=>'project','post_status'=>'publish','post_title'=>'Inactive deployment '.$lang,'post_content'=>'Retain synthetic source '.$lang],true);if(is_wp_error($p)){throw new RuntimeException('INSTALL_SEED_PROJECT');}pll_set_post_language($p,$lang);update_field('field_676dcb2aec8d4','Retain source tagline',$p);$pair[$lang]=$p;}
pll_save_post_translations($pair);wp_set_current_user(2);
$r=P\execute(['project_id'=>$pair['en'],'locale'=>'en','patch'=>['body_html'=>'Private unapplied candidate'],'request_id'=>bin2hex(random_bytes(16))],'create');
if(is_wp_error($r)){throw new RuntimeException('INSTALL_SEED_PROPOSAL');}
wp_set_current_user(0);echo "INSTALL_SEED_READY\n";
