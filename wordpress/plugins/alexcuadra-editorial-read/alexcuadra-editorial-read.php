<?php
/**
 * Plugin Name: Alex Cuadra Editorial Read
 * Description: Scoped public project metadata abilities for the official MCP Adapter.
 * Version: 0.1.0
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 */

namespace AlexCuadra\EditorialRead;

if (!defined('ABSPATH')) { exit; }

const CAPABILITY = 'alexcuadra_read_public_projects';

function permission($input = null) {
    if (!is_user_logged_in() || !current_user_can('read') || !current_user_can(CAPABILITY)) {
        return new \WP_Error('alexcuadra_read_forbidden', 'Scoped project read permission is required.', ['status' => 403]);
    }
    return true;
}

function ready() {
    if (!post_type_exists('project') || !function_exists('pll_get_post_language') || !function_exists('pll_get_post_translations')
        || !function_exists('pll_is_translated_post_type') || !pll_is_translated_post_type('project')) {
        return new \WP_Error('alexcuadra_read_dependencies', 'Project language metadata is unavailable.', ['status' => 503]);
    }
    return true;
}

function validate_input($input, $list) {
    if (!is_array($input) || array_diff(array_keys($input), $list ? ['locale', 'page', 'per_page', 'search'] : ['locale', 'id'])
        || !isset($input['locale']) || !in_array($input['locale'], ['en', 'es'], true)) {
        return new \WP_Error('alexcuadra_read_input', 'Invalid project read parameters.', ['status' => 400]);
    }
    if ($list) {
        foreach (['page' => 100, 'per_page' => 20] as $key => $maximum) {
            if (array_key_exists($key, $input) && (!is_int($input[$key]) || $input[$key] < 1 || $input[$key] > $maximum)) {
                return new \WP_Error('alexcuadra_read_input', 'Invalid pagination bounds.', ['status' => 400]);
            }
        }
        if (array_key_exists('search', $input) && (!is_string($input['search']) || strlen($input['search']) > 400
            || preg_match_all('/./us', $input['search']) === false || preg_match_all('/./us', $input['search']) > 100
            || preg_match('/[\x00-\x1f\x7f]/', $input['search']))) {
            return new \WP_Error('alexcuadra_read_input', 'Invalid project search.', ['status' => 400]);
        }
    } elseif (!isset($input['id']) || !is_int($input['id']) || $input['id'] < 1) {
        return new \WP_Error('alexcuadra_read_input', 'Invalid project ID.', ['status' => 400]);
    }
    return true;
}

function visible($post, $locale) {
    return $post instanceof \WP_Post && $post->post_type === 'project' && $post->post_status === 'publish'
        && $post->post_password === '' && is_post_publicly_viewable($post) && current_user_can('read_post', $post->ID)
        && pll_get_post_language($post->ID, 'slug') === $locale;
}

function metadata($post, $locale) {
    $translations = [];
    foreach (pll_get_post_translations($post->ID) as $language => $id) {
        if (!in_array($language, ['en', 'es'], true) || $language === $locale || (int) $id === $post->ID) { continue; }
        $target = get_post((int) $id);
        if (!visible($target, $language)) { continue; }
        $reverse = pll_get_post_translations($target->ID);
        if ((int) ($reverse[$locale] ?? 0) !== $post->ID) { continue; }
        $translations[] = ['id' => $target->ID, 'slug' => $target->post_name, 'locale' => $language];
    }
    return ['id' => $post->ID, 'title' => $post->post_title, 'slug' => $post->post_name,
        'locale' => $locale, 'translations' => $translations];
}

function authorize_and_validate($input, $list) {
    $check = permission($input);
    if (is_wp_error($check)) { return $check; }
    $check = validate_input($input, $list);
    return is_wp_error($check) ? $check : ready();
}

function list_projects($input) {
    $check = authorize_and_validate($input, true);
    if (is_wp_error($check)) { return $check; }
    $page = $input['page'] ?? 1;
    $size = $input['per_page'] ?? 10;
    $query = new \WP_Query(['post_type' => 'project', 'post_status' => 'publish', 'has_password' => false,
        'lang' => $input['locale'], 'posts_per_page' => $size + 1, 'offset' => ($page - 1) * $size,
        'orderby' => 'ID', 'order' => 'ASC', 'ignore_sticky_posts' => true, 'no_found_rows' => true,
        'suppress_filters' => false, 's' => $input['search'] ?? '', 'search_columns' => ['post_title'],
        'update_post_meta_cache' => false, 'update_post_term_cache' => false]);
    $items = [];
    // A contradictory query result must not become a partial public inventory.
    foreach ($query->posts as $post) {
        if (!visible($post, $input['locale'])) {
            return new \WP_Error('alexcuadra_read_visibility', 'Public inventory validation failed.', ['status' => 503]);
        }
    }
    foreach (array_slice($query->posts, 0, $size) as $post) { $items[] = metadata($post, $input['locale']); }
    $more = count($query->posts) > $size;
    if ($more && $page === 100) {
        return new \WP_Error('alexcuadra_read_page_limit', 'Inventory exceeds the pagination bound.', ['status' => 422]);
    }
    return ['items' => $items, 'next_page' => $more ? $page + 1 : null];
}

function get_project($input) {
    $check = authorize_and_validate($input, false);
    if (is_wp_error($check)) { return $check; }
    $post = get_post($input['id']);
    if (!visible($post, $input['locale'])) {
        return new \WP_Error('alexcuadra_project_not_found', 'Public project not found in this language.', ['status' => 404]);
    }
    return metadata($post, $input['locale']);
}

add_action('wp_abilities_api_categories_init', static function () {
    wp_register_ability_category('alexcuadra-editorial', ['label' => 'Portfolio editorial',
        'description' => 'Scoped public portfolio metadata.']);
});

add_action('wp_abilities_api_init', static function () {
    $locale = ['type' => 'string', 'enum' => ['en', 'es']];
    $translation = ['type' => 'object', 'additionalProperties' => false,
        'properties' => ['id' => ['type' => 'integer'], 'slug' => ['type' => 'string'], 'locale' => $locale],
        'required' => ['id', 'slug', 'locale']];
    $project = ['type' => 'object', 'additionalProperties' => false,
        'properties' => ['id' => ['type' => 'integer'], 'title' => ['type' => 'string'], 'slug' => ['type' => 'string'],
            'locale' => $locale, 'translations' => ['type' => 'array', 'items' => $translation]],
        'required' => ['id', 'title', 'slug', 'locale', 'translations']];
    $common = ['category' => 'alexcuadra-editorial', 'permission_callback' => __NAMESPACE__ . '\\permission',
        'meta' => ['public' => false, 'show_in_rest' => false, 'mcp' => ['public' => true, 'type' => 'tool'],
            'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true]]];
    wp_register_ability('alexcuadra/list-public-projects', array_merge($common, [
        'label' => 'List public portfolio projects',
        'description' => 'List public project IDs, titles, slugs, language and reciprocal public translations. Search titles and paginate in ID order.',
        'input_schema' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['locale'],
            'properties' => ['locale' => $locale, 'page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
                'search' => ['type' => 'string', 'maxLength' => 100]]],
        'output_schema' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['items', 'next_page'],
            'properties' => ['items' => ['type' => 'array', 'items' => $project], 'next_page' => ['type' => ['integer', 'null']]]],
        'execute_callback' => __NAMESPACE__ . '\\list_projects',
    ]));
    wp_register_ability('alexcuadra/get-public-project', array_merge($common, [
        'label' => 'Get public portfolio project',
        'description' => 'Read one public project by ID and exact language, including reciprocal public translation metadata.',
        'input_schema' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['locale', 'id'],
            'properties' => ['locale' => $locale, 'id' => ['type' => 'integer', 'minimum' => 1]]],
        'output_schema' => $project, 'execute_callback' => __NAMESPACE__ . '\\get_project',
    ]));
});
