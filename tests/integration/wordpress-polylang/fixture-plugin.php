<?php
/** Plugin Name: Portfolio security lab fixtures (local only) */
if (wp_get_environment_type() !== 'local') { return; }
add_filter('wp_is_application_passwords_available', '__return_true');
add_action('after_setup_theme', function () { add_theme_support('post-thumbnails'); });
add_action('init', function () {
    foreach (['project' => ['project', 'projects'], 'articles' => ['article', 'articles']] as $key => $names) {
        register_post_type($key, [
            'label' => 'Synthetic ' . $names[1], 'public' => true, 'show_in_rest' => true,
            'show_in_graphql' => true, 'graphql_single_name' => $names[0],
            'capability_type' => $key === 'project' ? 'page' : 'post',
            'graphql_plural_name' => $names[1], 'supports' => ['title', 'editor', 'excerpt', 'custom-fields', 'thumbnail'],
            'taxonomies' => ['category', 'post_tag'],
        ]);
    }
}, 0);
add_action('acf/init', function () {
    // Field definitions only: no production database, media or credentials are mounted.
    foreach (json_decode(file_get_contents('/acf-fields.json'), true, 512, JSON_THROW_ON_ERROR) as $group) {
        if (in_array($group['title'], ['Home_page', 'About_Page'], true)) {
            // Synthetic pages have different IDs; the real groups stay unchanged in the CMS.
            $group['location'] = [[['param' => 'post_type', 'operator' => '==', 'value' => 'page']]];
        }
        acf_add_local_field_group($group);
    }
    acf_add_local_field_group([
        'key' => 'group_security_lab', 'title' => 'Synthetic security fixture',
        'show_in_graphql' => true, 'graphql_field_name' => 'securityFixture',
        'map_graphql_types_from_location_rules' => true,
        'location' => array_map(function ($type) {
            return [['param' => 'post_type', 'operator' => '==', 'value' => $type]];
        }, ['post', 'page', 'project', 'articles']),
        'fields' => [[ 'key' => 'field_security_lab_note', 'name' => 'note', 'label' => 'Note',
            'type' => 'text', 'show_in_graphql' => true, 'graphql_field_name' => 'note' ]],
    ]);
});
