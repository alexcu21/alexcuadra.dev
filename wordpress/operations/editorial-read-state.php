<?php
// Read-only fingerprints for the approved plugin installation. Never emits content/meta values.
if (!defined('WP_CLI') || !WP_CLI || rtrim(home_url(), '/') !== 'https://content.alexcuadra.dev') {
    throw new RuntimeException('EXPECTED_WORDPRESS_CLI_SITE');
}
global $wpdb;
$ids = array_map('intval', $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'project' ORDER BY ID"));
$editorial = [];
if ($ids) {
    $idList = implode(',', $ids);
    $editorial = [
        $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID IN ($idList) ORDER BY ID", ARRAY_A),
        $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id IN ($idList) ORDER BY meta_id", ARRAY_A),
        $wpdb->get_results("SELECT * FROM $wpdb->term_relationships WHERE object_id IN ($idList) ORDER BY object_id,term_taxonomy_id", ARRAY_A),
    ];
}
$scope = 'alexcuadra_read_public_projects';
$user = get_userdata(2); $subscriber = get_role('subscriber');
if (!$user || !$subscriber) { WP_CLI::error('EXPECTED_ACCOUNT_AND_ROLE'); }
$otherCaps = $user->caps; unset($otherCaps[$scope]); ksort($otherCaps);
$roleCaps = $subscriber->capabilities; ksort($roleCaps);
$others = array_values(array_diff(get_option('active_plugins', []), ['alexcuadra-editorial-read/alexcuadra-editorial-read.php']));
sort($others);
$directory = WP_PLUGIN_DIR . '/alexcuadra-editorial-read/';
$hash = static function ($value) { return hash('sha256', serialize($value)); };
WP_CLI::line('EDITORIAL_INSTALL_STATE=' . wp_json_encode([
    'projectCount' => count($ids), 'editorialHash' => $hash($editorial),
    'otherUserCapabilitiesHash' => $hash($otherCaps), 'subscriberRoleHash' => $hash($roleCaps),
    'otherActivePluginsHash' => $hash($others), 'scopeDirectlyGranted' => !empty($user->caps[$scope]),
    'pluginPhpSha256' => is_file($directory . 'alexcuadra-editorial-read.php') ? hash_file('sha256', $directory . 'alexcuadra-editorial-read.php') : null,
    'pluginReadmeSha256' => is_file($directory . 'README.md') ? hash_file('sha256', $directory . 'README.md') : null,
]));
