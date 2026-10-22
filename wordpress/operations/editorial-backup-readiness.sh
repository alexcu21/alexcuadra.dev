#!/bin/sh
# Read-only prerequisites. Alex runs this via SSH; no archive or credential file is created.
set -eu
cd "$HOME/domains/alexcuadra.dev/public_html/content"
test -f wp-load.php
printf '%s\n' 'EDITORIAL_BACKUP_READINESS_V1' 'TOOLS'
for tool in bash mysqldump mariadb-dump mysql mariadb tar gzip sha256sum mktemp stat; do
    if command -v "$tool" >/dev/null 2>&1; then
        printf '%s=available\n' "$tool"
    else
        printf '%s=missing\n' "$tool"
    fi
done
printf '%s\n' 'DUMP_CLIENT_VERSION'
if command -v mysqldump >/dev/null 2>&1; then
    mysqldump --version
elif command -v mariadb-dump >/dev/null 2>&1; then
    mariadb-dump --version
else
    printf '%s\n' 'NO_SHELL_DUMP_CLIENT'
fi
printf '%s\n' 'BACKUP_DATABASE_AND_PLUGIN_PROFILE'
wp --path=. --skip-plugins --skip-themes eval-file - <<'PHP'
<?php
if (!defined('WP_CLI') || !WP_CLI || home_url() !== 'https://content.alexcuadra.dev' || is_multisite()) {
    WP_CLI::error('EXPECTED_SINGLE_SITE_REQUIRED');
}
global $wpdb;
$old = $wpdb->suppress_errors(true);
$rows = $wpdb->get_results('SELECT TABLE_TYPE AS table_type, ENGINE AS engine, COUNT(*) AS table_count FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() GROUP BY TABLE_TYPE,ENGINE ORDER BY TABLE_TYPE,ENGINE', ARRAY_A);
$failed = $wpdb->last_error !== '' || !is_array($rows) || !$rows;
$version = $wpdb->get_var('SELECT VERSION()');
$failed = $failed || $wpdb->last_error !== '' || !is_string($version);
$visible = [];
foreach (['triggers' => ['TRIGGERS', 'TRIGGER_SCHEMA'], 'routines' => ['ROUTINES', 'ROUTINE_SCHEMA'], 'events' => ['EVENTS', 'EVENT_SCHEMA']] as $name => [$table, $column]) {
    $value = $wpdb->get_var("SELECT COUNT(*) FROM information_schema.$table WHERE $column=DATABASE()");
    $visible[$name] = $wpdb->last_error === '' && $value !== null ? (int) $value : null;
}
$wpdb->suppress_errors($old);
if ($failed) { WP_CLI::error('BACKUP_PROFILE_QUERY_UNAVAILABLE'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$plugins = [];
foreach (get_plugins() as $file => $plugin) {
    $plugins[] = ['file' => $file, 'version' => $plugin['Version'], 'active' => is_plugin_active($file)];
}
$host = DB_HOST;
$shape = preg_match('/^[a-zA-Z0-9_.-]+$/D', $host) ? 'hostname'
    : (preg_match('/^[a-zA-Z0-9_.-]+:[0-9]+$/D', $host) ? 'hostname-port'
    : (str_contains($host, ':/') ? 'unix-socket' : 'other'));
WP_CLI::line('EDITORIAL_BACKUP_PROFILE=' . wp_json_encode([
    'format' => 'editorial-backup-readiness-v1', 'observedAtUtc' => gmdate('Y-m-d\TH:i:s\Z'),
    'wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION, 'databaseServer' => $version,
    'databaseHostShape' => $shape, 'tableGroups' => $rows, 'visibleDatabaseObjects' => $visible,
    'objectVisibilityCertifiesPrivileges' => false, 'plugins' => $plugins,
    'credentialsOrContentExported' => false, 'backupCreated' => false, 'restorationVerified' => false,
]));
PHP
printf '%s\n' 'END_EDITORIAL_BACKUP_READINESS_V1'
