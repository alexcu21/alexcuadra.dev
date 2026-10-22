<?php
/** Plugin Name: Synthetic proposals acceptance fixture */
if (getenv('AC_PROPOSALS_ACCEPTANCE') !== 'synthetic-v1' || wp_get_environment_type() !== 'local') { throw new RuntimeException('SYNTHETIC_ACCEPTANCE_REQUIRED'); }
// Guard hostname is an option/constant inside an isolated network, never a connection target here.
add_filter('pre_http_request', static function () { return new WP_Error('acceptance_network_blocked', 'OUTBOUND_HTTP_BLOCKED'); }, PHP_INT_MIN);
add_filter('pre_wp_mail', static function () { return false; }, PHP_INT_MIN);
add_action('init', static function () {
    register_post_type('project', ['public' => true, 'show_in_rest' => true, 'show_in_graphql' => true,
        'graphql_single_name' => 'project', 'graphql_plural_name' => 'projects',
        'capability_type' => 'page', 'map_meta_cap' => true, 'supports' => ['title', 'editor', 'custom-fields']]);
}, 0);
add_action('acf/init', static function () {
    $groups = json_decode(file_get_contents('/project-field-export.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($groups as $group) { if ($group['key'] === 'group_676d40f7bb646') { acf_add_local_field_group($group); } }
});
