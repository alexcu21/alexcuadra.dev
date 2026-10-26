<?php
namespace AlexCuadra\EditorialBlogRead;
if (!defined('ABSPATH')) { exit; }
const CAPABILITY = 'alexcuadra_read_public_posts';
function failure($code, $status) { return new \WP_Error('ac_blog_read_' . $code, 'Public blog read unavailable.', ['status' => $status]); }
function permission($input = null) {
    return is_user_logged_in() && current_user_can('read') && current_user_can(CAPABILITY) ? true : failure('forbidden', 403);
}
function ready() {
    foreach (['pll_get_post_language', 'pll_get_post_translations', 'pll_is_translated_post_type',
        'pll_get_term_language', 'pll_get_term_translations', 'pll_is_translated_taxonomy'] as $function) {
        if (!function_exists($function)) { return failure('dependencies', 503); }
    }
    if (!post_type_exists('post') || !pll_is_translated_post_type('post')) { return failure('dependencies', 503); }
    foreach (['category', 'post_tag'] as $taxonomy) {
        if (!is_taxonomy_viewable($taxonomy) || !pll_is_translated_taxonomy($taxonomy)) { return failure('dependencies', 503); }
    }
    return true;
}
function validate($input, $list) {
    if (!is_array($input) || array_diff(array_keys($input), $list ? ['locale', 'page', 'per_page', 'search'] : ['locale', 'id'])
        || !isset($input['locale']) || !in_array($input['locale'], ['en', 'es'], true)) { return failure('input', 400); }
    if (!$list) { return isset($input['id']) && is_int($input['id']) && $input['id'] > 0 ? true : failure('input', 400); }
    foreach (['page' => 100, 'per_page' => 20] as $key => $maximum) {
        if (array_key_exists($key, $input) && (!is_int($input[$key]) || $input[$key] < 1 || $input[$key] > $maximum)) { return failure('input', 400); }
    }
    if (array_key_exists('search', $input)) {
        $search = $input['search'];
        if (!is_string($search) || strlen($search) > 400 || preg_match_all('/./us', $search) === false
            || preg_match_all('/./us', $search) > 100 || preg_match('/[\p{Cc}]/u', $search)) { return failure('input', 400); }
    }
    return true;
}
function authorize($input, $list) {
    $check = permission($input); if (is_wp_error($check)) { return $check; }
    $check = validate($input, $list); if (is_wp_error($check)) { return $check; }
    return ready();
}
function visible($post, $locale) {
    return $post instanceof \WP_Post && $post->post_type === 'post' && $post->post_status === 'publish'
        && $post->post_password === '' && is_post_publicly_viewable($post) && current_user_can('read_post', $post->ID)
        && pll_get_post_language($post->ID, 'slug') === $locale;
}
function term_visible($term, $taxonomy, $locale) {
    return $term instanceof \WP_Term && $term->taxonomy === $taxonomy && pll_get_term_language($term->term_id, 'slug') === $locale;
}
function term_metadata($term, $locale) {
    $translations = [];
    $relations = pll_get_term_translations($term->term_id);
    if (!is_array($relations)) { return failure('relationships', 503); }
    foreach ($relations as $language => $id) {
        if (!in_array($language, ['en', 'es'], true) || $language === $locale || (int) $id === $term->term_id) { continue; }
        $target = get_term((int) $id, $term->taxonomy);
        if (!term_visible($target, $term->taxonomy, $language)) { continue; }
        $reverse = pll_get_term_translations($target->term_id);
        if ((int) ($reverse[$locale] ?? 0) !== $term->term_id) { continue; }
        $translations[] = ['id' => $target->term_id, 'name' => $target->name, 'slug' => $target->slug, 'locale' => $language];
    }
    return ['id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug,
        'taxonomy' => $term->taxonomy, 'locale' => $locale, 'translations' => $translations];
}
function metadata($post, $locale) {
    $translations = [];
    $relations = pll_get_post_translations($post->ID);
    if (!is_array($relations)) { return failure('relationships', 503); }
    foreach ($relations as $language => $id) {
        if (!in_array($language, ['en', 'es'], true) || $language === $locale || (int) $id === $post->ID) { continue; }
        $target = get_post((int) $id);
        if (!visible($target, $language)) { continue; }
        $reverse = pll_get_post_translations($target->ID);
        if ((int) ($reverse[$locale] ?? 0) !== $post->ID) { continue; }
        $translations[] = ['id' => $target->ID, 'slug' => $target->post_name, 'locale' => $language];
    }
    $classification = [];
    foreach (['category', 'post_tag'] as $taxonomy) {
        $terms = wp_get_object_terms($post->ID, $taxonomy, ['orderby' => 'term_id', 'order' => 'ASC', 'number' => 101]);
        if (is_wp_error($terms) || !is_array($terms) || count($terms) > 100) { return failure('classification', 503); }
        $classification[$taxonomy] = [];
        foreach ($terms as $term) {
            if (!term_visible($term, $taxonomy, $locale)) { return failure('classification', 503); }
            $item = term_metadata($term, $locale);
            if (is_wp_error($item)) { return $item; }
            $classification[$taxonomy][] = $item;
        }
    }
    return ['id' => $post->ID, 'title' => $post->post_title, 'slug' => $post->post_name, 'locale' => $locale,
        'translations' => $translations, 'categories' => $classification['category'], 'tags' => $classification['post_tag']];
}
function list_posts($input) {
    $check = authorize($input, true); if (is_wp_error($check)) { return $check; }
    $page = $input['page'] ?? 1; $size = $input['per_page'] ?? 10;
    $query = new \WP_Query(['post_type' => 'post', 'post_status' => 'publish', 'has_password' => false,
        'lang' => $input['locale'], 'posts_per_page' => $size + 1, 'offset' => ($page - 1) * $size,
        'orderby' => 'ID', 'order' => 'ASC', 'ignore_sticky_posts' => true, 'no_found_rows' => true,
        'suppress_filters' => false, 's' => $input['search'] ?? '', 'search_columns' => ['post_title'],
        'update_post_meta_cache' => false, 'update_post_term_cache' => false]);
    $items = [];
    foreach ($query->posts as $post) { if (!visible($post, $input['locale'])) { return failure('visibility', 503); } }
    foreach (array_slice($query->posts, 0, $size) as $post) {
        $item = metadata($post, $input['locale']); if (is_wp_error($item)) { return $item; } $items[] = $item;
    }
    $more = count($query->posts) > $size;
    if ($more && $page === 100) { return failure('page_limit', 422); }
    return ['items' => $items, 'next_page' => $more ? $page + 1 : null];
}
function get_post_metadata($input) {
    $check = authorize($input, false); if (is_wp_error($check)) { return $check; }
    $post = get_post($input['id']);
    return visible($post, $input['locale']) ? metadata($post, $input['locale']) : failure('not_found', 404);
}
add_action('wp_abilities_api_categories_init', static function () {
    wp_register_ability_category('alexcuadra-blog-editorial', ['label' => 'Public blog editorial metadata', 'description' => 'Read-only local candidate.']);
});
add_action('wp_abilities_api_init', static function () {
    $locale = ['type' => 'string', 'enum' => ['en', 'es']];
    $object = static function ($properties) { return ['type' => 'object', 'additionalProperties' => false,
        'properties' => $properties, 'required' => array_keys($properties)]; };
    $relation = $object(['id' => ['type' => 'integer'], 'slug' => ['type' => 'string'], 'locale' => $locale]);
    $termRelation = $object(['id' => ['type' => 'integer'], 'name' => ['type' => 'string'], 'slug' => ['type' => 'string'], 'locale' => $locale]);
    $term = $object(['id' => ['type' => 'integer'], 'name' => ['type' => 'string'], 'slug' => ['type' => 'string'],
        'taxonomy' => ['type' => 'string', 'enum' => ['category', 'post_tag']], 'locale' => $locale,
        'translations' => ['type' => 'array', 'items' => $termRelation]]);
    $post = $object(['id' => ['type' => 'integer'], 'title' => ['type' => 'string'], 'slug' => ['type' => 'string'],
        'locale' => $locale, 'translations' => ['type' => 'array', 'items' => $relation],
        'categories' => ['type' => 'array', 'items' => $term], 'tags' => ['type' => 'array', 'items' => $term]]);
    $common = ['category' => 'alexcuadra-blog-editorial', 'permission_callback' => __NAMESPACE__ . '\\permission',
        'meta' => ['public' => false, 'show_in_rest' => false, 'mcp' => ['public' => true, 'type' => 'tool'],
            'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true]]];
    wp_register_ability('alexcuadra/list-public-posts', array_merge($common, [
        'label' => 'List public blog posts', 'description' => 'Read native post metadata, reciprocal translations and assigned categories/tags. No bodies or writes.',
        'input_schema' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['locale'], 'properties' => [
            'locale' => $locale, 'page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
            'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20], 'search' => ['type' => 'string', 'maxLength' => 100]]],
        'output_schema' => $object(['items' => ['type' => 'array', 'items' => $post], 'next_page' => ['type' => ['integer', 'null']]]),
        'execute_callback' => __NAMESPACE__ . '\\list_posts',
    ]));
    wp_register_ability('alexcuadra/get-public-post', array_merge($common, [
        'label' => 'Read public blog post metadata', 'description' => 'Read one native published post in the exact language; no body, ACF, private data or writes.',
        'input_schema' => $object(['locale' => $locale, 'id' => ['type' => 'integer', 'minimum' => 1]]),
        'output_schema' => $post, 'execute_callback' => __NAMESPACE__ . '\\get_post_metadata',
    ]));
});
