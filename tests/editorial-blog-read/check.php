<?php
// Synthetic API contract doubles. No WordPress bootstrap, database or network.
use AlexCuadra\EditorialBlogRead as B;
define('ABSPATH', __DIR__);
$mode = $argv[1] ?? 'local';
if ($mode !== 'no-opt-in') { define('AC_EDITORIAL_BLOG_READ_LAB', true); }
$hooks = $abilities = $posts = $languages = $relations = $terms = $termLanguages = $termRelations = $assignments = [];
$caps = []; $user = 0; $queryOverride = null; $termFailure = false; $publicTaxonomies = true; $translated = true; $checks = [];
class WP_Error { public function __construct(public $code, public $message = '', public $data = []) {} }
class WP_Post { public function __construct(public $ID, public $post_type = 'post', public $post_status = 'publish',
    public $post_password = '', public $post_title = 'Synthetic public title', public $post_name = 'synthetic-post',
    public $public = true, public $post_content = 'PRIVATE_BODY', public $private = 'PRIVATE_META') {} }
class WP_Term { public function __construct(public $term_id, public $taxonomy, public $name = 'Public term', public $slug = 'public-term') {} }
function wp_get_environment_type() { global $mode; return $mode === 'production' ? 'production' : 'local'; }
function home_url() { return 'http://wordpress'; }
function site_url() { return 'http://wordpress'; }
function is_multisite() { return false; }
function is_wp_error($value) { return $value instanceof WP_Error; }
function is_user_logged_in() { global $user; return $user > 0; }
function current_user_can($cap, ...$args) { global $caps; return $caps[$cap] ?? false; }
function post_type_exists($type) { return $type === 'post'; }
function pll_is_translated_post_type($type) { global $translated; return $translated && $type === 'post'; }
function pll_is_translated_taxonomy($taxonomy) { return in_array($taxonomy, ['category','post_tag'], true); }
function is_taxonomy_viewable($taxonomy) { global $publicTaxonomies; return $publicTaxonomies && in_array($taxonomy, ['category','post_tag'], true); }
function pll_get_post_language($id, $field) { global $languages; return $languages[$id] ?? false; }
function pll_get_post_translations($id) { global $relations; return $relations[$id] ?? []; }
function pll_get_term_language($id, $field) { global $termLanguages; return $termLanguages[$id] ?? false; }
function pll_get_term_translations($id) { global $termRelations; return $termRelations[$id] ?? []; }
function is_post_publicly_viewable($post) { return $post->public; }
function get_post($id) { global $posts; return $posts[$id] ?? null; }
function get_term($id, $taxonomy) { global $terms; return isset($terms[$id]) && $terms[$id]->taxonomy === $taxonomy ? $terms[$id] : null; }
function wp_get_object_terms($id, $taxonomy, $options) {
    global $assignments, $termFailure;
    if ($termFailure) { return new WP_Error('PRIVATE_DATABASE_ERROR'); }
    return array_slice($assignments[$id][$taxonomy] ?? [], 0, $options['number']);
}
function add_action($name, $callback) { global $hooks; $hooks[$name][] = $callback; }
function wp_register_ability_category($name, $config) {}
function wp_register_ability($name, $config) { global $abilities; $abilities[$name] = $config; }
class WP_Query {
    public $posts;
    public function __construct($args) {
        global $posts, $languages, $queryOverride;
        verify($args['post_type'] === 'post' && $args['post_status'] === 'publish' && $args['has_password'] === false, 'bounded_public_native_query');
        verify($args['search_columns'] === ['post_title'] && $args['orderby'] === 'ID' && $args['order'] === 'ASC', 'title_search_stable_order');
        $selected = array_values(array_filter($posts, fn($p) => $p->post_type === 'post' && $p->post_status === 'publish'
            && $p->post_password === '' && ($languages[$p->ID] ?? null) === $args['lang']
            && ($args['s'] === '' || str_contains($p->post_title, $args['s']))));
        usort($selected, fn($a,$b) => $a->ID <=> $b->ID);
        $this->posts = $queryOverride ?? array_slice($selected, $args['offset'], $args['posts_per_page']);
    }
}
function verify($condition, $name) { global $checks; if (!$condition) { throw new RuntimeException('FAILED:' . $name); } $checks[] = $name; }
function error_is($result, $code, $status) { return is_wp_error($result) && $result->code === 'ac_blog_read_' . $code && $result->data['status'] === $status; }
require $argv[2] ?? __DIR__ . '/../../wordpress/experimental/editorial-blog-read/alexcuadra-editorial-blog-read.php';
if ($mode !== 'local') {
    verify($hooks === [], 'closed_outside_local_opt_in');
    echo json_encode(['mode' => $mode, 'checks' => count($checks), 'passed' => true]) . "\n"; exit;
}
foreach ($hooks as $callbacks) { foreach ($callbacks as $callback) { $callback(); } }
verify(array_keys($abilities) === ['alexcuadra/list-public-posts','alexcuadra/get-public-post'], 'two_exact_read_tools');
foreach ($abilities as $ability) {
    verify(is_callable($ability['permission_callback']) && is_callable($ability['execute_callback']), 'callbacks_are_callable');
    verify($ability['meta']['annotations'] === ['readonly'=>true,'destructive'=>false,'idempotent'=>true], 'read_only_annotations');
    verify($ability['meta']['public'] === false && $ability['meta']['show_in_rest'] === false, 'not_anonymous_rest');
    verify($ability['input_schema']['additionalProperties'] === false, 'closed_input_schema');
}
verify(error_is(B\list_posts(['locale'=>'en']), 'forbidden', 403), 'anonymous_denied');
$user = 2; $caps = ['read'=>true,'read_post'=>true,'alexcuadra_read_public_projects'=>true];
verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']), 'forbidden', 403), 'project_scope_does_not_expand');
$caps = ['read'=>true,'read_post'=>true,'manage_options'=>true];
verify(error_is(B\list_posts(['locale'=>'en']), 'forbidden', 403), 'admin_without_scope_denied');
$caps = ['read'=>true,'read_post'=>true,B\CAPABILITY=>true];
verify(!current_user_can('edit_posts') && !current_user_can('manage_options'), 'scope_has_no_edit_admin');
foreach ([1=>'en',2=>'es',3=>'en',4=>'en'] as $id=>$locale) { $posts[$id] = new WP_Post($id); $languages[$id] = $locale; }
$posts[2]->post_name = 'entrada'; $relations[1] = ['en'=>1,'es'=>2]; $relations[2] = $relations[1];
$terms[10] = new WP_Term(10,'category','Testing','testing'); $termLanguages[10]='en';
$terms[11] = new WP_Term(11,'category','Pruebas','pruebas'); $termLanguages[11]='es';
$termRelations[10] = $termRelations[11] = ['en'=>10,'es'=>11];
$terms[20] = new WP_Term(20,'post_tag','WordPress','wordpress'); $termLanguages[20]='en';
$assignments[1] = ['category'=>[$terms[10]],'post_tag'=>[$terms[20]]];
$assignments[2] = ['category'=>[$terms[11]],'post_tag'=>[]];
$before = serialize([$posts,$terms,$assignments,$caps,$relations,$termRelations]);
$result = B\get_post_metadata(['id'=>1,'locale'=>'en']);
verify(array_keys($result) === ['id','title','slug','locale','translations','categories','tags'], 'exact_public_dto');
verify($result['translations'] === [['id'=>2,'slug'=>'entrada','locale'=>'es']], 'reciprocal_post_translation');
verify($result['categories'][0]['translations'] === [['id'=>11,'name'=>'Pruebas','slug'=>'pruebas','locale'=>'es']], 'reciprocal_term_translation');
verify($result['tags'][0]['taxonomy'] === 'post_tag', 'native_taxonomies_separate');
verify(!str_contains(json_encode($result),'PRIVATE'), 'body_meta_and_database_details_absent');
verify(serialize([$posts,$terms,$assignments,$caps,$relations,$termRelations]) === $before, 'reads_preserve_source_and_permissions');
verify(B\list_posts(['locale'=>'en','per_page'=>2])['next_page'] === 2, 'pagination_lookahead');
verify(array_column(B\list_posts(['locale'=>'en','per_page'=>2,'page'=>2])['items'],'id') === [4], 'pagination_final_page');
verify(array_column(B\list_posts(['locale'=>'es'])['items'],'id') === [2], 'exact_spanish_inventory');
verify(B\list_posts(['locale'=>'en','search'=>'PRIVATE_BODY']) === ['items'=>[],'next_page'=>null], 'search_does_not_read_body');
foreach ([['locale'=>'fr'],['locale'=>'en','page'=>0],['locale'=>'en','page'=>'1'],['locale'=>'en','per_page'=>21],
    ['locale'=>'en','page'=>101],['locale'=>'en','search'=>str_repeat('a',101)],['locale'=>'en','search'=>"x\x85"],
    ['locale'=>'en','search'=>"x\n"],['locale'=>'en','fields'=>['post_content']]] as $input) {
    verify(error_is(B\list_posts($input),'input',400), 'invalid_list_parameters');
}
foreach ([['locale'=>'en','id'=>'1'],['locale'=>'en','id'=>0],['locale'=>'en','id'=>1,'post_type'=>'project']] as $input) {
    verify(error_is(B\get_post_metadata($input),'input',400), 'invalid_get_parameters');
}
verify(!is_wp_error(B\list_posts(['locale'=>'en','search'=>str_repeat('á',80)])), 'unicode_title_search');
verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'es']),'not_found',404), 'no_locale_fallback');
foreach (['draft','private','future','trash'] as $status) {
    $posts[1]->post_status=$status;
    verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']),'not_found',404), 'nonpublic_not_found');
}
$posts[1]->post_status='publish';
$posts[1]->public=false;
verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']),'not_found',404), 'nonviewable_post_not_found');
$posts[1]->public=true; $languages[1]=false;
verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']),'not_found',404), 'unknown_post_language_not_found');
$languages[1]='en';
foreach (['project','page','articles','lab_project'] as $type) {
    $posts[1]->post_type=$type;
    verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']),'not_found',404), 'other_types_not_found');
}
$posts[1]->post_type='post'; $posts[1]->post_password='PRIVATE';
verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']),'not_found',404), 'password_not_found');
$posts[1]->post_password=''; $caps['read_post']=false;
verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']),'not_found',404), 'object_read_denied');
$caps['read_post']=true; $posts[2]->post_status='trash';
verify(B\get_post_metadata(['id'=>1,'locale'=>'en'])['translations'] === [], 'trashed_translation_hidden');
$posts[2]->post_status='publish'; $relations[2]=[]; $termRelations[11]=[];
$result=B\get_post_metadata(['id'=>1,'locale'=>'en']);
verify($result['translations'] === [] && $result['categories'][0]['translations'] === [], 'nonreciprocal_relations_hidden');
$termLanguages[10]='es';
verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']),'classification',503), 'contradictory_term_language_fails_closed');
$termLanguages[10]='en'; $termFailure=true;
verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']),'classification',503), 'term_error_sanitized');
$termFailure=false; $assignments[1]['category']=array_fill(0,101,$terms[10]);
verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']),'classification',503), 'term_limit_not_silently_truncated');
$assignments[1]['category']=[$terms[10]]; $posts[3]->post_status='draft'; $queryOverride=[$posts[3]];
verify(error_is(B\list_posts(['locale'=>'en']),'visibility',503), 'contradictory_query_not_partial');
$queryOverride=array_fill(0,2,$posts[1]);
verify(error_is(B\list_posts(['locale'=>'en','page'=>100,'per_page'=>1]),'page_limit',422), 'terminal_page_limit_explicit');
$queryOverride=null; $publicTaxonomies=false;
verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']),'dependencies',503), 'private_taxonomy_dependency_denied');
$publicTaxonomies=true; $translated=false;
verify(error_is(B\list_posts(['locale'=>'en']),'dependencies',503), 'missing_translation_support_denied');
$translated=true; $relations[1]=false;
verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']),'relationships',503), 'invalid_source_relationship_contract');
$relations[1]=[]; $termRelations[10]=false;
verify(error_is(B\get_post_metadata(['id'=>1,'locale'=>'en']),'relationships',503), 'invalid_term_relationship_contract');
$caps[B\CAPABILITY]=false;
verify(error_is(B\list_posts(['locale'=>'en']),'forbidden',403), 'revoked_scope_denies_next_call');
echo json_encode(['mode'=>'local','checks'=>count($checks),'passed'=>true,'database'=>false,'network'=>false,'cases'=>$checks],JSON_PRETTY_PRINT) . "\n";
