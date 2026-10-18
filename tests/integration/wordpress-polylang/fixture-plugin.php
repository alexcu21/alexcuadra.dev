<?php
/** Plugin Name: Portfolio security lab fixtures (local only) */
if (wp_get_environment_type() !== 'local') { return; }
add_filter('wp_is_application_passwords_available', '__return_true');
add_action('init', function () {
    foreach (['project' => ['project', 'projects'], 'articles' => ['article', 'articles']] as $key => $names) {
        register_post_type($key, [
            'label' => 'Synthetic ' . $names[1], 'public' => true, 'show_in_rest' => true,
            'show_in_graphql' => true, 'graphql_single_name' => $names[0],
            'capability_type' => $key === 'project' ? 'page' : 'post',
            'graphql_plural_name' => $names[1], 'supports' => ['title', 'editor', 'excerpt', 'custom-fields'],
        ]);
    }
}, 0);
add_action('acf/init', function () {
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
