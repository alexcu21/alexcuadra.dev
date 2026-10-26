<?php
use AlexCuadra\EditorialBlogRead as B;
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress' || DB_HOST !== 'db'
    || !defined('AC_EDITORIAL_BLOG_READ_LAB') || AC_EDITORIAL_BLOG_READ_LAB !== true) { throw new RuntimeException('ISOLATED_BLOG_LAB_REQUIRED'); }
$cases=[];
$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED:'.$name);} $cases[]=$name;};
foreach ([['en','en_US','English'],['es','es_ES','Español']] as $language) {
    if(!in_array($language[0],pll_languages_list(),true)){
        $added=PLL()->model->add_language(['name'=>$language[2],'slug'=>$language[0],'locale'=>$language[1],'rtl'=>false,'term_group'=>$language[0]==='en'?0:1]);
        if(is_wp_error($added)){throw new RuntimeException('LANGUAGE_SETUP_FAILED');}
    }
}
PLL()->options->set('default_lang','en');PLL()->options->save_all();
$marker='blogread'.bin2hex(random_bytes(4));
$create=static function($label,$locale='en',$status='publish',$password='',$type='post')use($marker){
    $id=wp_insert_post(['post_type'=>$type,'post_status'=>$status,'post_title'=>"$marker $label",
        'post_content'=>'PRIVATE_BODY [secret value="PRIVATE_TOKEN"]','post_password'=>$password,
        'post_date'=>$status==='future'?gmdate('Y-m-d H:i:s',time()+DAY_IN_SECONDS):current_time('mysql')],true);
    if(is_wp_error($id)){throw new RuntimeException('POST_SETUP_FAILED');}
    if($locale){pll_set_post_language($id,$locale);}else{wp_set_object_terms($id,[],'language');}
    wp_set_object_terms($id,[],'category');wp_set_object_terms($id,[],'post_tag');
    update_post_meta($id,'private_contact','PRIVATE_RECIPIENT');return $id;
};
$en=$create('English');$es=$create('Español','es');$second=$create('Second');$third=$create('Third');
pll_save_post_translations(['en'=>$en,'es'=>$es]);
$excluded=[$create('Draft','en','draft'),$create('Private','en','private'),$create('Protected','en','publish','synthetic-only'),
    $create('Future','en','future'),$create('Trash','en','trash'),$create('Page','en','publish','','page'),
    $create('Project','en','publish','','project'),$create('No language',null),2147483647];
$term=static function($taxonomy,$label,$locale)use($marker){$r=wp_insert_term("$marker $label",$taxonomy);if(is_wp_error($r)){throw new RuntimeException('TERM_SETUP_FAILED');}
    $id=$r['term_id'];pll_set_term_language($id,$locale);update_term_meta($id,'private_note','PRIVATE_TERM_META');return $id;};
$category=$term('category','Category','en');$categoryEs=$term('category','Categoría','es');
$tag=$term('post_tag','Tag','en');$tagEs=$term('post_tag','Etiqueta','es');
pll_save_term_translations(['en'=>$category,'es'=>$categoryEs]);pll_save_term_translations(['en'=>$tag,'es'=>$tagEs]);
foreach([[$en,$category,$tag],[$es,$categoryEs,$tagEs]]as[$id,$cat,$label]){wp_set_object_terms($id,[$cat],'category');wp_set_object_terms($id,[$label],'post_tag');}
$readerId=wp_create_user($marker.'reader',wp_generate_password(32),$marker.'@example.test');
if(is_wp_error($readerId)){throw new RuntimeException('USER_SETUP_FAILED');}
$reader=new WP_User($readerId);$reader->set_role('subscriber');
$list=wp_get_ability('alexcuadra/list-public-posts');$get=wp_get_ability('alexcuadra/get-public-post');
$check('two_abilities_registered',$list!==null&&$get!==null);
$check('activation_does_not_grant_capability',!$reader->has_cap(B\CAPABILITY)&&!get_role('subscriber')->has_cap(B\CAPABILITY));
wp_set_current_user(0);$check('anonymous_denied',is_wp_error($list->execute(['locale'=>'en'])));
wp_set_current_user($readerId);$reader->add_cap('alexcuadra_read_public_projects');
$check('project_scope_cannot_read_posts',is_wp_error($get->execute(['locale'=>'en','id'=>$en])));
$reader->remove_cap('alexcuadra_read_public_projects');
wp_set_current_user(1);$check('admin_without_scope_denied',is_wp_error($list->execute(['locale'=>'en'])));
$reader->add_cap(B\CAPABILITY);wp_set_current_user($readerId);
$check('reader_has_no_edit_or_admin',!current_user_can('edit_posts')&&!current_user_can('manage_options'));
$snapshot=static function(){global $wpdb;$state=[];foreach(['posts'=>'ID','postmeta'=>'meta_id','terms'=>'term_id','term_taxonomy'=>'term_taxonomy_id','term_relationships'=>'object_id,term_taxonomy_id','termmeta'=>'meta_id']as$table=>$order){$name=$wpdb->$table;$state[]=$wpdb->get_results("SELECT * FROM $name ORDER BY $order",ARRAY_A);}return hash('sha256',serialize($state));};
$before=$snapshot();
$first=$list->execute(['locale'=>'en','search'=>$marker,'per_page'=>2]);
$last=$list->execute(['locale'=>'en','search'=>$marker,'per_page'=>2,'page'=>2]);
$check('pagination_returns_complete_public_inventory',!is_wp_error($first)&&!is_wp_error($last)
    &&array_column($first['items'],'id')===[$en,$second]&&$first['next_page']===2&&array_column($last['items'],'id')===[$third]&&$last['next_page']===null);
$result=$get->execute(['locale'=>'en','id'=>$en]);
$check('dto_has_only_public_metadata',!is_wp_error($result)&&array_keys($result)===['id','title','slug','locale','translations','categories','tags']);
$check('post_translation_is_reciprocal',($result['translations'][0]['id']??null)===$es);
$check('category_assignment_and_translation',($result['categories'][0]['id']??null)===$category&&($result['categories'][0]['translations'][0]['id']??null)===$categoryEs);
$check('tag_assignment_and_translation',($result['tags'][0]['id']??null)===$tag&&($result['tags'][0]['translations'][0]['id']??null)===$tagEs);
$check('body_meta_term_meta_not_exposed',!str_contains(wp_json_encode($result),'PRIVATE'));
$spanish=$get->execute(['locale'=>'es','id'=>$es]);
$check('spanish_terms_are_spanish',!is_wp_error($spanish)&&$spanish['categories'][0]['locale']==='es'&&$spanish['tags'][0]['locale']==='es');
foreach($excluded as$id){$error=$get->execute(['locale'=>'en','id'=>$id]);$check('excluded_source_404_'.count($cases),is_wp_error($error)&&$error->get_error_code()==='ac_blog_read_not_found');}
$check('wrong_language_never_falls_back',is_wp_error($get->execute(['locale'=>'es','id'=>$en])));
foreach([['locale'=>'en','per_page'=>21],['locale'=>'en','page'=>101],['locale'=>'en','page'=>'1'],['locale'=>'fr'],['locale'=>'en','fields'=>['post_content']]]as$input){$check('invalid_schema_'.count($cases),is_wp_error($list->execute($input)));}
$check('title_search_does_not_read_body',$list->execute(['locale'=>'en','search'=>'PRIVATE_BODY'])===['items'=>[],'next_page'=>null]);
$discover=wp_get_ability('mcp-adapter/discover-abilities')->execute();
$check('adapter_discovers_new_abilities',!is_wp_error($discover)&&count(array_intersect(['alexcuadra/list-public-posts','alexcuadra/get-public-post'],array_column($discover['abilities'],'name')))===2);
$executed=wp_get_ability('mcp-adapter/execute-ability')->execute(['ability_name'=>'alexcuadra/get-public-post','parameters'=>['locale'=>'en','id'=>$en]]);
$check('adapter_executes_authorized_read',!is_wp_error($executed)&&($executed['success']??false)&&$executed['data']['id']===$en);
$check('reads_preserve_editorial_rows',$snapshot()===$before);
$reader->remove_cap(B\CAPABILITY);wp_set_current_user(0);wp_set_current_user($readerId);
$check('revocation_denies_next_read',is_wp_error($get->execute(['locale'=>'en','id'=>$en])));
$check('role_unchanged',!get_role('subscriber')->has_cap(B\CAPABILITY));
$report=['environment'=>'isolated-wordpress','wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'polylang'=>POLYLANG_VERSION,
    'passed'=>count($cases),'failed'=>0,'cases'=>$cases,'realCmsModified'=>false,'realPermissionsChanged'=>false,'emailSent'=>false];
WP_CLI::line('EDITORIAL_BLOG_READ_RESULT='.wp_json_encode($report));
