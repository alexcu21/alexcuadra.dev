<?php
if (getenv('AC_PROPOSALS_HOSTING_COMPONENTS') !== 'verified-v1' || wp_get_environment_type() !== 'local'
    || DB_NAME !== 'wordpress' || DB_HOST !== 'db') { WP_CLI::error('SYNTHETIC_HOSTING_REQUIRED'); }
global $wpdb;
$cases = [];
$check = static function ($name, $ok) use (&$cases) { if (!$ok) { WP_CLI::error('HOSTING_FAILED:' . $name); } $cases[] = $name; };
$check('source_auto_update_mu_loaded', function_exists('hostinger_use_proxy_services'));
$check('source_preview_mu_loaded', class_exists('Hostinger_Temporary_Domain_Handler', false));
$check('auto_update_mu_proxy_hook_registered', has_filter('pre_http_request', 'hostinger_use_proxy_services') === 10);
$check('core_auto_updates_disabled', apply_filters('automatic_updater_disabled', false) === true);
$check('plugin_auto_updates_disabled', apply_filters('auto_update_plugin', true, (object) ['plugin' => 'synthetic/plugin.php']) === false);
$check('theme_auto_updates_disabled', apply_filters('auto_update_theme', true, (object) ['theme' => 'synthetic']) === false);
foreach (['https://api.wordpress.org/core/version-check/1.7/', 'https://downloads.wordpress.org/plugin/synthetic.zip', 'http://127.0.0.1:7777/hsub_downloads/synthetic.zip'] as $url) {
    $response = wp_remote_get($url, ['timeout' => 2, 'redirection' => 0]);
    $check('mu_proxy_path_remains_offline_' . count($cases), is_wp_error($response) && $response->get_error_code() === 'acceptance_network_blocked');
}
$snapshot = static function () use ($wpdb) {
    $state = [];
    foreach ([$wpdb->posts, $wpdb->postmeta, $wpdb->prefix . 'ac_proposal_owners', $wpdb->prefix . 'ac_proposal_requests'] as $table) {
        $rows = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
        if ($wpdb->last_error) { WP_CLI::error('SNAPSHOT_FAILED'); }
        $state[] = $rows;
    }
    return hash('sha256', serialize($state));
};
$context = static function ($preview = false) {
    $headers = $preview ? ['X-Preview-Indicator' => '1'] : [];
    $result = wp_remote_get('http://wordpress/?rest_route=/proposals-acceptance/v1/context', ['headers' => $headers, 'redirection' => 0, 'timeout' => 10]);
    if (is_wp_error($result)) { WP_CLI::error('INTERNAL_CONTEXT_HTTP_FAILED'); }
    return json_decode(wp_remote_retrieve_body($result), true);
};
$normal = $context();
$check('normal_http_guard_context_preserved', ($normal['home'] ?? null) === 'https://content.alexcuadra.dev'
    && ($normal['site'] ?? null) === 'https://content.alexcuadra.dev' && ($normal['proposalRegistered'] ?? false));
$before = $snapshot(); $preview = $context(true);
$check('preview_header_rewrites_context_in_fixture', ($preview['home'] ?? null) === 'https://wordpress' && ($preview['site'] ?? null) === 'https://wordpress');
$check('preview_noncanonical_context_closes_candidate', ($preview['proposalRegistered'] ?? null) === false);
$check('preview_request_does_not_mutate_editorial_rows', $snapshot() === $before);
$normalAgain = $context();
$check('normal_context_recovers_after_preview_request', $normalAgain === $normal);
$check('source_dropins_absent', !is_file(WP_CONTENT_DIR . '/object-cache.php') && !is_file(WP_CONTENT_DIR . '/db.php') && !is_file(WP_CONTENT_DIR . '/advanced-cache.php'));
WP_CLI::line('PROPOSALS_HOSTING_RESULT=' . wp_json_encode(['passed' => count($cases), 'failed' => 0, 'cases' => $cases,
    'sourceTheme' => get_stylesheet(), 'sourceThemeVersion' => wp_get_theme()->get('Version'),
    'previewGuardClosed' => true, 'sourceDropinsPresent' => false]));
