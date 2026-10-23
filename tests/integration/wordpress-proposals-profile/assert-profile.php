<?php
if (getenv('AC_PROPOSALS_PROFILE') !== 'backup-2026-10-22' || wp_get_environment_type() !== 'local'
    || DB_NAME !== 'wordpress' || DB_HOST !== 'db') { WP_CLI::error('SYNTHETIC_PROFILE_REQUIRED'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$expected = json_decode(file_get_contents('/recorded-profile.json'), true, 512, JSON_THROW_ON_ERROR);
$plugins = get_plugins(); $loaded = get_included_files(); $active = [];
foreach ($expected['plugins'] as $entry) {
    $file = $entry['file'];
    if (($plugins[$file]['Version'] ?? null) !== $entry['version'] || is_plugin_active($file) !== $entry['active']) {
        WP_CLI::error('PROFILE_PLUGIN_VERSION_OR_STATE_MISMATCH');
    }
    if ($entry['active']) {
        if (!in_array(WP_PLUGIN_DIR . '/' . $file, $loaded, true)) { WP_CLI::error('ACTIVE_PLUGIN_NOT_INCLUDED'); }
        $active[] = $file;
    } elseif (in_array(WP_PLUGIN_DIR . '/' . $file, $loaded, true)) { WP_CLI::error('INACTIVE_PLUGIN_INCLUDED'); }
}
$expectedActive = array_merge($active, ['alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php']);
$actualActive = get_option('active_plugins'); sort($expectedActive); sort($actualActive);
if ($actualActive !== $expectedActive) { WP_CLI::error('UNEXPECTED_ACTIVE_PLUGIN'); }
if (get_bloginfo('version') !== $expected['wordpress'] || PHP_VERSION !== $expected['php']) { WP_CLI::error('CORE_RUNTIME_DRIFT'); }
$hosting = null;
if (getenv('AC_PROPOSALS_HOSTING_COMPONENTS') === 'verified-v1') {
    $hosting = json_decode(file_get_contents(ABSPATH . '.hosting-profile.json'), true, 512, JSON_THROW_ON_ERROR);
    if (get_stylesheet() !== $hosting['stylesheet'] || get_template() !== $hosting['template']
        || wp_get_theme()->get('Version') !== $hosting['stylesheetVersion']) { WP_CLI::error('SOURCE_THEME_DRIFT'); }
    $functions = get_stylesheet_directory() . '/functions.php';
    if (is_file($functions) && !in_array($functions, $loaded, true)) { WP_CLI::error('SOURCE_THEME_NOT_INCLUDED'); }
    foreach ($hosting['muPlugins'] as $mu) {
        $path = WPMU_PLUGIN_DIR . '/' . $mu['file'];
        if (!in_array($path, $loaded, true) || hash_file('sha256', $path) !== $mu['sha256']) { WP_CLI::error('SOURCE_MU_NOT_INCLUDED_OR_CHANGED'); }
    }
    if (home_url() !== 'https://content.alexcuadra.dev' || site_url() !== 'https://content.alexcuadra.dev') { WP_CLI::error('CANONICAL_GUARD_CONTEXT_CHANGED'); }
}
$http = wp_remote_get('https://example.invalid/');
if (!is_wp_error($http) || $http->get_error_code() !== 'acceptance_network_blocked'
    || wp_mail('nobody@example.invalid', 'synthetic-only', 'synthetic-only') !== false
    || !DISABLE_WP_CRON) { WP_CLI::error('OUTBOUND_GUARD_FAILED'); }
WP_CLI::line('PROPOSALS_PROFILE_RESULT=' . wp_json_encode(['installedSourcePlugins' => count($expected['plugins']),
    'activeSourcePlugins' => count($active), 'activeMainFilesIncluded' => true, 'inactiveMainFilesExcluded' => true,
    'wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION, 'httpBlocked' => true, 'mailBlocked' => true,
    'sourceThemeMuVerified' => $hosting !== null]));
