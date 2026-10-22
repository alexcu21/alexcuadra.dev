<?php
/** Plugin Name: Synthetic proposals acceptance fixture */
if (getenv('AC_PROPOSALS_ACCEPTANCE') !== 'synthetic-v1' || wp_get_environment_type() !== 'local') { throw new RuntimeException('SYNTHETIC_ACCEPTANCE_REQUIRED'); }
// Simulate a TLS-terminating frontend for internal HTTP only; this is NOT a TLS transport test.
if (getenv('AC_PROPOSALS_TRANSPORT') === 'synthetic-http-v1' && ($_SERVER['HTTP_HOST'] ?? '') === 'wordpress') {
    $_SERVER['HTTPS'] = 'on'; $_SERVER['SERVER_PORT'] = 443;
}
add_action('rest_api_init', static function () {
    if (getenv('AC_PROPOSALS_TRANSPORT') !== 'synthetic-http-v1') { return; }
    register_rest_route('proposals-acceptance/v1', '/context', ['methods' => 'GET', 'permission_callback' => '__return_true',
        'callback' => static function () { return ['home' => home_url(), 'site' => site_url(), 'sslContext' => is_ssl(),
            'proposalRegistered' => wp_get_ability('alexcuadra/create-project-change') !== null]; }]);
});
// Guard hostname is an option/constant inside an isolated network, never a connection target here.
if (getenv('AC_PROPOSALS_TRANSPORT') === 'synthetic-http-v1' && !defined('WP_ACCESSIBLE_HOSTS')) { define('WP_ACCESSIBLE_HOSTS', 'wordpress'); }
add_filter('pre_http_request', static function ($pre, $args, $url) {
    $parts = parse_url($url);
    if (getenv('AC_PROPOSALS_TRANSPORT') === 'synthetic-http-v1' && ($parts['scheme'] ?? '') === 'http'
        && ($parts['host'] ?? '') === 'wordpress' && ($parts['port'] ?? 80) === 80
        && !isset($parts['user']) && !isset($parts['pass'])) { return $pre; }
    return new WP_Error('acceptance_network_blocked', 'OUTBOUND_HTTP_BLOCKED');
}, PHP_INT_MIN, 3);
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
