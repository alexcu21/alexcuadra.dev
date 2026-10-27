<?php
use AlexCuadra\ProjectClassificationLab as C;
if(!defined('AC_PROJECT_CLASSIFICATION_LAB')||AC_PROJECT_CLASSIFICATION_LAB!==true||wp_get_environment_type()!=='local'||DB_HOST!=='db'){throw new RuntimeException('LOCAL_PROJECT_LAB_REQUIRED');}
$cases=[];$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED:'.$name);}$cases[]=$name;};
$denied=static fn($result)=>is_wp_error($result);
foreach([['en','en_US','English'],['es','es_ES','Español']]as$l){if(!in_array($l[0],pll_languages_list(),true)){PLL()->model->add_language(['name'=>$l[2],'slug'=>$l[0],'locale'=>$l[1],'rtl'=>false,'term_group'=>$l[0]==='en'?0:1]);}}
PLL()->options->set('default_lang','en');PLL()->options->save_all();
$serial=0;$post=static function($label,$lang='en',$status='publish',$password='',$type='project')use(&$serial){
 $id=wp_insert_post(['post_type'=>$type,'post_status'=>$status,'post_title'=>$label.' '.++$serial,'post_content'=>'PRIVATE_BODY','post_password'=>$password,
 'post_date'=>$status==='future'?gmdate('Y-m-d H:i:s',time()+DAY_IN_SECONDS):current_time('mysql')],true);
 if(is_wp_error($id)){throw new RuntimeException('POST_FIXTURE_FAILED');}if($lang){pll_set_post_language($id,$lang);}else{wp_set_object_terms($id,[],'language');}
 update_post_meta($id,'private_note','PRIVATE_POST_META');return $id;
};
$term=static function($tax,$name,$lang)use(&$serial){$r=wp_insert_term($name.' '.++$serial,$tax,['description'=>'PRIVATE_DESCRIPTION']);if(is_wp_error($r)){throw new RuntimeException('TERM_FIXTURE_FAILED');}
 $id=$r['term_id'];pll_set_term_language($id,$lang);update_term_meta($id,'private_note','PRIVATE_TERM_META');return $id;};
$en=$post('Public EN');$es=$post('Público ES','es');$other=$post('Another public');pll_save_post_translations(['en'=>$en,'es'=>$es]);
$hidden=$post('Hidden','es','private');$blog=$post('Blog','en','publish','','post');
$excluded=[$post('Draft','en','draft'),$hidden,$post('Protected','en','publish','synthetic-only'),$post('Future','en','future'),$post('Trash','en','trash'),$blog,$post('Page','en','publish','','page'),$post('No language',null),PHP_INT_MAX];
$cat=$term('project_category','Category','en');$catEs=$term('project_category','Categoría','es');$second=$term('project_category','Other','en');
$tech=$term('project_technology','React','en');$techEs=$term('project_technology','React ES','es');
pll_save_term_translations(['en'=>$cat,'es'=>$catEs]);pll_save_term_translations(['en'=>$tech,'es'=>$techEs]);
wp_set_object_terms($en,[$cat],'project_category');wp_set_object_terms($es,[$catEs],'project_category');wp_set_object_terms($other,[$second],'project_category');
wp_set_object_terms($en,[$tech],'project_technology');wp_set_object_terms($es,[$techEs],'project_technology');
$publicUnpaired=$term('project_technology','Public technology','en');$privateTranslation=$term('project_technology','PRIVATE_TRANSLATION','es');
pll_save_term_translations(['en'=>$publicUnpaired,'es'=>$privateTranslation]);wp_set_object_terms($en,[$tech,$publicUnpaired],'project_technology');wp_set_object_terms($hidden,[$privateTranslation],'project_technology');
$unused=$term('project_category','PRIVATE_UNUSED','en');$draftOnly=$term('project_category','PRIVATE_DRAFT_ONLY','en');$blogOnly=$term('project_category','PRIVATE_BLOG_ONLY','en');
wp_set_object_terms($excluded[0],[$draftOnly],'project_category');wp_set_object_terms($blog,[$blogOnly],'project_category');
$legacy=$term('category','PRIVATE_LEGACY','en');wp_set_object_terms($en,[$legacy],'category');
$readerId=wp_create_user('classification_reader',wp_generate_password(32),'classification@example.test');if(is_wp_error($readerId)){throw new RuntimeException('USER_FIXTURE_FAILED');}
$reader=new WP_User($readerId);$reader->set_role('subscriber');
$get=wp_get_ability('alexcuadra/get-public-project-classification');$list=wp_get_ability('alexcuadra/list-public-project-terms');
$check('two_native_abilities_registered',$get!==null&&$list!==null);
$check('fixture_registration_does_not_grant_scope',!$reader->has_cap(C\CAPABILITY)&&!get_role('subscriber')->has_cap(C\CAPABILITY));
wp_set_current_user(0);$check('anonymous_denied',$denied($list->execute(['locale'=>'en','taxonomy'=>'project_category'])));
wp_set_current_user(1);$check('admin_without_scope_denied',$denied($get->execute(['id'=>$en,'locale'=>'en'])));
wp_set_current_user($readerId);foreach(['alexcuadra_read_public_posts','alexcuadra_prepare_post_changes']as$cap){$reader->add_cap($cap);$check('unrelated_scope_denied_'.$cap,$denied($get->execute(['id'=>$en,'locale'=>'en'])));$reader->remove_cap($cap);}
$reader->add_cap(C\CAPABILITY);wp_set_current_user(0);wp_set_current_user($readerId);
$check('reader_keeps_subscriber_without_editing',!current_user_can('manage_options')&&!current_user_can('edit_posts'));
$snapshot=static function(){global $wpdb;$all=[];foreach(['posts'=>'ID','postmeta'=>'meta_id','terms'=>'term_id','term_taxonomy'=>'term_taxonomy_id','term_relationships'=>'object_id,term_taxonomy_id','termmeta'=>'meta_id']as$t=>$o){$all[$t]=$wpdb->get_results("SELECT * FROM {$wpdb->$t} ORDER BY $o",ARRAY_A);}return hash('sha256',serialize($all));};
$before=$snapshot();$result=$get->execute(['id'=>$en,'locale'=>'en']);
if(is_wp_error($result)){WP_CLI::line('CLASSIFICATION_DIAGNOSTIC='.wp_json_encode(['code'=>$result->get_error_code(),'details'=>$result->get_error_data(),'ready'=>is_wp_error(C\ready())?C\ready()->get_error_code():true,'inventory'=>is_wp_error(C\inventory())?C\inventory()->get_error_code():true]));}
$check('classification_has_closed_project_fields',!is_wp_error($result)&&array_keys($result)===['project_id','locale','categories','technologies']);
$check('categories_are_project_owned',array_column($result['categories'],'id')===[$cat]&&$result['categories'][0]['taxonomy']==='project_category');
$check('technologies_are_project_owned',array_column($result['technologies'],'id')===[$tech,$publicUnpaired]);
$check('reciprocal_category_translation',$result['categories'][0]['translations'][0]['id']===$catEs);
$check('reciprocal_technology_translation',$result['technologies'][0]['translations'][0]['id']===$techEs);
$check('private_only_translation_not_exposed',$result['technologies'][1]['translations']===[]);
$check('no_body_meta_description_or_private_names',!str_contains(wp_json_encode($result),'PRIVATE'));
$esResult=$get->execute(['id'=>$es,'locale'=>'es']);$check('spanish_uses_exact_spanish_terms',!is_wp_error($esResult)&&$esResult['categories'][0]['id']===$catEs&&$esResult['technologies'][0]['id']===$techEs);
$check('empty_assignments_are_empty_arrays',$get->execute(['id'=>$other,'locale'=>'en'])['technologies']===[]);
$first=$list->execute(['locale'=>'en','taxonomy'=>'project_category','limit'=>1]);$last=$list->execute(['locale'=>'en','taxonomy'=>'project_category','after'=>$cat,'limit'=>1]);
$check('bounded_cursor_first_page',array_column($first['items'],'id')===[$cat]&&$first['next_after']===$cat);
$check('bounded_cursor_terminal_page',array_column($last['items'],'id')===[$second]&&$last['next_after']===null);
$all=$list->execute(['locale'=>'en','taxonomy'=>'project_category']);
$check('unused_draft_and_blog_only_terms_excluded',array_column($all['items'],'id')===[$cat,$second]);
$check('catalog_has_no_counts_parents_or_descriptions',array_keys($all['items'][0])===['id','name','slug','taxonomy','locale','translations']);
$check('catalog_spanish_is_exact',array_column($list->execute(['locale'=>'es','taxonomy'=>'project_category'])['items'],'id')===[$catEs]);
foreach($excluded as$i=>$id){$e=$get->execute(['id'=>$id,'locale'=>'en']);$check('excluded_source_'.$i,is_wp_error($e)&&($e->get_error_data()['status']??null)===404);}
$check('wrong_language_does_not_fallback',$denied($get->execute(['id'=>$en,'locale'=>'es'])));
foreach([['locale'=>'en','taxonomy'=>'category'],['locale'=>'en','taxonomy'=>'post_tag'],['locale'=>'en','taxonomy'=>'project_category','limit'=>21],['locale'=>'en','taxonomy'=>'project_category','limit'=>0],['locale'=>'en','taxonomy'=>'project_category','after'=>-1],['locale'=>'en','taxonomy'=>'project_category','after'=>'1'],['locale'=>'en','taxonomy'=>'project_category','after'=>null],['locale'=>'fr','taxonomy'=>'project_category'],['locale'=>'en','taxonomy'=>'project_category','fields'=>['description']]]as$i=>$in){$check('closed_catalog_input_'.$i,$denied($list->execute($in)));}
$check('closed_classification_input',$denied($get->execute(['id'=>$en,'locale'=>'en','include'=>'postmeta'])));
$adapter=wp_get_ability('mcp-adapter/execute-ability')->execute(['ability_name'=>'alexcuadra/get-public-project-classification','parameters'=>['id'=>$en,'locale'=>'en']]);
$check('native_adapter_executes_classification',!is_wp_error($adapter)&&$adapter['success']&&$adapter['data']===$result);
$discovery=wp_get_ability('mcp-adapter/discover-abilities')->execute();$check('adapter_discovers_both_tools',count(array_intersect(['alexcuadra/get-public-project-classification','alexcuadra/list-public-project-terms'],array_column($discovery['abilities'],'name')))===2);
foreach(C\contracts()as$n=>$c){$closed=static function($s)use(&$closed){if(!is_array($s)){return true;}if(($s['type']??null)==='object'&&($s['additionalProperties']??null)!==false){return false;}foreach($s as$v){if(is_array($v)&&!$closed($v)){return false;}}return true;};$check('recursive_closed_contract_'.$n,$closed($c['input'])&&$closed($c['output']));}
$check('read_operations_preserve_editorial_tables',$snapshot()===$before);
wp_set_object_terms($en,[$catEs],'project_category');$check('mixed_language_assignment_fails_closed',$denied($get->execute(['id'=>$en,'locale'=>'en'])));wp_set_object_terms($en,[$cat],'project_category');
wp_update_post(['ID'=>$hidden,'post_status'=>'publish']);$shown=$get->execute(['id'=>$en,'locale'=>'en']);$check('new_public_translation_witness_is_visible',$shown['technologies'][1]['translations'][0]['id']===$privateTranslation);
wp_update_post(['ID'=>$hidden,'post_status'=>'private']);$check('withdrawn_translation_witness_disappears',$get->execute(['id'=>$en,'locale'=>'en'])['technologies'][1]['translations']===[]);
$tax=get_taxonomy('project_category');$types=$tax->object_type;$tax->object_type[]='post';$check('taxonomy_shared_with_posts_rejected',$denied($list->execute(['locale'=>'en','taxonomy'=>'project_category'])));$tax->object_type=$types;
unregister_taxonomy('project_technology');$check('missing_taxonomy_fails_closed',$denied($get->execute(['id'=>$en,'locale'=>'en'])));register_taxonomy('project_technology',['project'],['public'=>true,'hierarchical'=>false,'show_in_rest'=>true]);
$many=[];for($i=0;$i<201;$i++){$many[]=$term('project_category','Bound '.$i,'en');}
wp_set_object_terms($en,array_slice($many,0,101),'project_category');$e=$get->execute(['id'=>$en,'locale'=>'en']);$check('more_than_100_assigned_terms_rejected',is_wp_error($e)&&$e->get_error_data()['status']===422);wp_set_object_terms($en,[$cat],'project_category');
$third=$post('Term bound witness');foreach([[$en,array_slice($many,0,70)],[$other,array_slice($many,70,70)],[$third,array_slice($many,140)]]as[$p,$terms]){wp_set_object_terms($p,$terms,'project_category');}
$e=$list->execute(['locale'=>'en','taxonomy'=>'project_category']);$check('catalog_over_200_public_terms_rejected',is_wp_error($e)&&$e->get_error_data()['status']===422);
wp_set_object_terms($en,[$cat],'project_category');wp_set_object_terms($other,[$second],'project_category');wp_set_object_terms($third,[],'project_category');wp_update_post(['ID'=>$third,'post_status'=>'draft']);
$extra=[];for($i=0;$i<199;$i++){$extra[]=$post('Project bound');}
$e=$list->execute(['locale'=>'en','taxonomy'=>'project_category']);$check('inventory_over_200_public_projects_rejected',is_wp_error($e)&&$e->get_error_data()['status']===422);foreach($extra as$id){wp_update_post(['ID'=>$id,'post_status'=>'draft']);}
$check('bounds_do_not_leave_a_partial_catalog',array_column($list->execute(['locale'=>'en','taxonomy'=>'project_category'])['items'],'id')===[$cat,$second]);
$reader->remove_cap(C\CAPABILITY);wp_set_current_user(0);wp_set_current_user($readerId);$check('scope_revocation_denies_next_read',$denied($get->execute(['id'=>$en,'locale'=>'en'])));
$check('no_role_wide_grant',!get_role('subscriber')->has_cap(C\CAPABILITY));
update_option('project_classification_test_fixture',['en'=>$en,'es'=>$es,'private'=>$hidden,'category'=>$cat,'categoryEs'=>$catEs,'technology'=>$tech]);
WP_CLI::line('PROJECT_CLASSIFICATION_RESULT='.wp_json_encode(['passed'=>count($cases),'failed'=>0,'cases'=>$cases,'syntheticScopeRemoved'=>true,'sourceFingerprintsPreservedDuringReads'=>true,'wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'polylang'=>POLYLANG_VERSION]));
