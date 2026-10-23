<?php
// Safe core bootstrap: source plugins/themes/MU/drop-ins are absent from this runtime tree.
if (!defined('AC_PRIVATE_RESTORE') || AC_PRIVATE_RESTORE !== true || DB_HOST !== 'localhost:/socket/mysql.sock'
    || DB_NAME !== 'restore_source' || wp_get_environment_type() !== 'local') { WP_CLI::error('SAFE_RESTORE_CONTEXT_REQUIRED'); }
if (wp_get_mu_plugins() !== [WPMU_PLUGIN_DIR . '/private-restore-guard.php']) { WP_CLI::error('UNEXPECTED_MU_PLUGIN'); }
if (get_included_files()) {
    foreach (get_included_files() as $file) {
        if (str_starts_with($file, WP_PLUGIN_DIR . '/') || str_starts_with($file, WP_CONTENT_DIR . '/themes/')) { WP_CLI::error('SOURCE_EXTENSION_LOADED'); }
    }
}
$http = wp_remote_get('https://example.invalid/');
if (!is_wp_error($http) || $http->get_error_code() !== 'private_restore_offline') { WP_CLI::error('HTTP_NOT_BLOCKED'); }
if (wp_mail('nobody@example.invalid', 'offline-test', 'synthetic-only') !== false) { WP_CLI::error('MAIL_NOT_BLOCKED'); }
foreach (['object-cache.php', 'db.php', 'advanced-cache.php', 'sunrise.php'] as $dropin) {
    if (file_exists(WP_CONTENT_DIR . '/' . $dropin)) { WP_CLI::error('SOURCE_DROPIN_PRESENT'); }
}
if (get_bloginfo('version') !== '7.1.3') { WP_CLI::error('CORE_VERSION_MISMATCH'); }
WP_CLI::line('PRIVATE_CORE_BOOTSTRAP=' . wp_json_encode(['wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION,
    'sourceConfigurationExecuted' => false, 'sourcePluginsExecuted' => false, 'sourceThemesExecuted' => false,
    'sourceMuExecuted' => false, 'sourceDropinsExecuted' => false, 'httpBlocked' => true, 'mailBlocked' => true]));
