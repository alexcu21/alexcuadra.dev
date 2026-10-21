<?php
/** Plugin Name: Synthetic editorial ACF GraphQL stack */
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { return; }
add_action('init', static function () {
    // Replace the minimal reader fixture, preserving the same page capabilities.
    register_post_type('project', ['label' => 'Synthetic projects', 'public' => true, 'show_in_rest' => true,
        'show_in_graphql' => true, 'graphql_single_name' => 'project', 'graphql_plural_name' => 'projects',
        'capability_type' => 'page', 'map_meta_cap' => true, 'supports' => ['title', 'editor', 'custom-fields']]);
}, 0);
add_action('acf/init', static function () {
    $groups = json_decode(file_get_contents('/project-field-export.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($groups as $group) {
        if ($group['key'] === 'group_676d40f7bb646') { acf_add_local_field_group($group); }
    }
});
