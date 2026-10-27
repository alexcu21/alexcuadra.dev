<?php
/** Native WordPress/Polylang projection. Explicit isolated laboratory only. */
namespace AlexCuadra\EditorialBlogProposals;
require_once __DIR__.'/planner.php';

function wordpress_guard(): void {
    if(!site_admitted()||!profile_report()['ready']){reject('destination_or_profile');}
}
function wordpress_actor(): array {
    wordpress_guard();
    $actor=['id'=>get_current_user_id(),'authenticated'=>is_user_logged_in(),
        'capabilities'=>[PREPARE_SCOPE=>current_user_can(PREPARE_SCOPE) && current_user_can('read')]];
    authorize_actor($actor); return $actor;
}
/** No MCP exposure. All source bodies and relationships remain in this private scope. */
function wordpress_projection(int $id): array {
    wordpress_actor();
    if (!positive($id)) { reject('input'); }
    foreach (['pll_get_post_language','pll_get_post_translations','pll_get_term_language','pll_is_translated_post_type','pll_is_translated_taxonomy'] as $fn) {
        if (!function_exists($fn)) { reject('dependencies'); }
    }
    if (!pll_is_translated_post_type('post') || !pll_is_translated_taxonomy('category') || !pll_is_translated_taxonomy('post_tag')) { reject('dependencies'); }
    global $wpdb;
    // Read actual source columns, avoiding a stale WP_Post object-cache body.
    $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID=%d",$id));
    if ($wpdb->last_error) { reject('storage_failure'); }
    $post=$row?new \WP_Post($row):null;
    if (!$post || $post->post_type!=='post' || $post->post_status!=='publish' || $post->post_password!==''
        || !is_post_publicly_viewable($post) || !current_user_can('read_post',$id)) { reject('source'); }
    $language=pll_get_post_language($id,'slug'); locale($language);
    $group=pll_get_post_translations($id);
    if (!is_array($group) || ($group[$language]??null)!==$id || array_diff(array_keys($group),['en','es'])) { reject('relationships'); }
    foreach ($group as $lang=>$member) {
        if (!positive($member)) { reject('relationships'); }
        $target=$wpdb->get_row($wpdb->prepare("SELECT ID,post_type FROM {$wpdb->posts} WHERE ID=%d",$member));
        // Include linked drafts/private targets in the group; never read their bodies.
        if (!$target || $target->post_type!=='post' || pll_get_post_language($member,'slug')!==$lang
            || canonical(pll_get_post_translations($member))!==canonical($group)) { reject('relationships'); }
    }
    $rows=get_terms(['taxonomy'=>['category','post_tag'],'hide_empty'=>false,'number'=>1001,'orderby'=>'term_id','order'=>'ASC','lang'=>'']);
    if (is_wp_error($rows) || count($rows)>1000) { reject('catalog'); }
    $terms=[];
    foreach ($rows as $term) {
        $lang=pll_get_term_language($term->term_id,'slug');
        if (!in_array($lang,['en','es'],true)) { continue; }
        $terms[]=['id'=>$term->term_id,'taxonomy'=>$term->taxonomy,'locale'=>$lang,'public'=>is_taxonomy_viewable($term->taxonomy)];
    }
    $source=['id'=>$id,'type'=>$post->post_type,'status'=>$post->post_status,'password'=>$post->post_password,
        'locale'=>$language,'title'=>$post->post_title,'content'=>$post->post_content,'excerpt'=>$post->post_excerpt,
        'translations'=>$group];
    foreach (['categories'=>'category','tags'=>'post_tag'] as $field=>$taxonomy) {
        $assigned=wp_get_object_terms($id,$taxonomy,['fields'=>'ids','number'=>101]);
        if (is_wp_error($assigned)) { reject('classification'); }
        $source[$field]=$assigned;
    }
    $source=snapshot($source,catalog($terms));
    return ['source'=>$source,'terms'=>$terms,'base_hash'=>base_hash($source,$terms)];
}
