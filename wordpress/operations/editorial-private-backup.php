<?php
/** Private export helper; no content/credentials on stdout. Not a plugin or ability. */
ini_set('zend.exception_ignore_args', '1');
$cliContext = defined('WP_CLI') && WP_CLI;
$phase = $cliContext ? ($args[0] ?? '') : ($argv[1] ?? '');
$workArg = $cliContext ? ($args[1] ?? '') : ($argv[2] ?? '');
function ac_backup_fail($message) { throw new RuntimeException($message); }
function ac_backup_write($path, $value) {
    $handle = fopen($path, 'xb');
    if (!$handle) { ac_backup_fail('PRIVATE_FILE_CREATE_FAILED'); }
    chmod($path, 0600);
    $offset = 0;
    while ($offset < strlen($value)) {
        $written = fwrite($handle, substr($value, $offset));
        if (!$written) { fclose($handle); ac_backup_fail('PRIVATE_FILE_WRITE_FAILED'); }
        $offset += $written;
    }
    fclose($handle);
}
function ac_backup_option($value) {
    if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $value)) { ac_backup_fail('UNSUPPORTED_CLIENT_OPTION'); }
    return '"' . strtr($value, ['\\' => '\\\\', '"' => '\\"', "\n" => '\\n', "\r" => '\\r', "\t" => '\\t']) . '"';
}
function ac_backup_inventory($root) {
    $selected = [];
    $add = static function ($relative) use ($root, &$selected) {
        $path = $root . '/' . $relative;
        if (is_link($path)) { ac_backup_fail('SYMLINK_REQUIRES_MANUAL_REVIEW'); }
        if (is_dir($path)) { $selected[$relative] = ['type' => 'directory', 'mode' => fileperms($path) & 0777]; }
        elseif (is_file($path)) {
            $selected[$relative] = ['type' => 'file', 'mode' => fileperms($path) & 0777,
                'bytes' => filesize($path), 'sha256' => hash_file('sha256', $path)];
        } else { ac_backup_fail('UNSUPPORTED_FILE_TYPE'); }
    };
    foreach (scandir($root) as $name) {
        if ($name === '.' || $name === '..') { continue; }
        if (is_link($root . '/' . $name)) { ac_backup_fail('ROOT_SYMLINK_REQUIRES_REVIEW'); }
        if (is_file($root . '/' . $name)) { $add($name); }
    }
    $directories = ['wp-admin', 'wp-includes', 'wp-content/plugins', 'wp-content/mu-plugins', 'wp-content/themes', 'wp-content/uploads', 'wp-content/languages'];
    foreach ($directories as $directory) {
        if (!file_exists($root . '/' . $directory)) { continue; }
        $add($directory);
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $file) {
            $add(substr($file->getPathname(), strlen($root) + 1));
        }
    }
    foreach (scandir($root . '/wp-content') as $name) {
        if ($name === '.' || $name === '..' || $name === 'debug.log') { continue; }
        if (is_link($root . '/wp-content/' . $name)) { ac_backup_fail('CONTENT_SYMLINK_REQUIRES_REVIEW'); }
        if (is_file($root . '/wp-content/' . $name)) { $add('wp-content/' . $name); }
    }
    ksort($selected);
    $bytes = 0;
    foreach ($selected as $path => $entry) {
        if ($path === 'backup' || str_starts_with($path, 'backup/')) { ac_backup_fail('RESERVED_ARCHIVE_PATH'); }
        $bytes += $entry['bytes'] ?? 0;
    }
    if ($bytes > 2147483648 || !isset($selected['wp-config.php'])) { ac_backup_fail('FILES_TOO_LARGE_OR_CONFIG_OUTSIDE_ROOT'); }
    return $selected;
}
function ac_backup_profile() {
    global $wpdb;
    if (!defined('WP_CLI') || !WP_CLI || home_url() !== 'https://content.alexcuadra.dev' || is_multisite()) { ac_backup_fail('EXPECTED_SINGLE_SITE_REQUIRED'); }
    $groups = $wpdb->get_results('SELECT TABLE_TYPE AS table_type, ENGINE AS engine, COUNT(*) AS table_count FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() GROUP BY TABLE_TYPE,ENGINE ORDER BY TABLE_TYPE,ENGINE', ARRAY_A);
    if ($wpdb->last_error || count($groups ?? []) !== 1 || $groups[0]['table_type'] !== 'BASE TABLE'
        || $groups[0]['engine'] !== 'InnoDB' || (int) $groups[0]['table_count'] !== 37) { ac_backup_fail('DATABASE_ENGINE_PROFILE_CHANGED'); }
    $version = $wpdb->get_var('SELECT VERSION()');
    if ($wpdb->last_error || !is_string($version)) { ac_backup_fail('DATABASE_VERSION_UNAVAILABLE'); }
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $plugins = [];
    foreach (get_plugins() as $file => $plugin) { $plugins[$file] = ['version' => $plugin['Version'], 'active' => is_plugin_active($file)]; }
    ksort($plugins);
    $schemas = [];
    $tables = $wpdb->get_col('SHOW TABLES');
    if ($wpdb->last_error || count($tables ?? []) !== 37) { ac_backup_fail('DATABASE_TABLE_SET_CHANGED'); }
    sort($tables);
    foreach ($tables as $table) {
        $quoted = '`' . str_replace('`', '``', $table) . '`';
        $row = $wpdb->get_row('SHOW CREATE TABLE ' . $quoted, ARRAY_A);
        if ($wpdb->last_error || !isset($row['Create Table'])) { ac_backup_fail('DATABASE_SCHEMA_UNAVAILABLE'); }
        $schemas[$table] = hash('sha256', preg_replace('/ AUTO_INCREMENT=[0-9]+/', '', $row['Create Table']));
    }
    return ['wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION, 'databaseServer' => $version,
        'plugins' => $plugins, 'schemaHashes' => $schemas];
}
$work = realpath($workArg);
$backupHome = realpath(getenv('HOME') ?: '');
if (!$work || !$backupHome || dirname($work) !== $backupHome || !str_starts_with(basename($work), '.ac-editorial-backup.')
    || is_link($workArg) || (fileperms($work) & 0777) !== 0700) {
    ac_backup_fail('PRIVATE_WORK_DIRECTORY_REQUIRED');
}
if ($phase === 'prepare') {
    $expected = json_decode(base64_decode('@@EXPECTED_PROFILE@@'), true, 512, JSON_THROW_ON_ERROR);
    $profile = ac_backup_profile();
    foreach (['wordpress', 'php', 'databaseServer', 'plugins'] as $key) {
        if ($profile[$key] !== $expected[$key]) { ac_backup_fail('RECORDED_PROFILE_CHANGED'); }
    }
    if (!preg_match('/^[a-zA-Z0-9_.-]+$/D', DB_HOST) || !preg_match('/^[a-zA-Z0-9_]+$/D', DB_NAME)) { ac_backup_fail('UNSUPPORTED_DB_HOST_OR_NAME'); }
    $root = rtrim(realpath(ABSPATH), '/');
    $inventory = ac_backup_inventory($root);
    $config = "[client]\nuser=" . ac_backup_option(DB_USER) . "\npassword=" . ac_backup_option(DB_PASSWORD)
        . "\nhost=" . ac_backup_option(DB_HOST) . "\ndefault-character-set=utf8mb4\n";
    $socket = ini_get('mysqli.default_socket');
    if (DB_HOST === 'localhost' && is_string($socket) && $socket !== '') { $config .= 'socket=' . ac_backup_option($socket) . "\n"; }
    ac_backup_write($work . '/client.cnf', $config);
    ac_backup_write($work . '/database-name', DB_NAME . "\n");
    $manifest = ['format' => 'ac-private-wordpress-backup-v1', 'startedAtUtc' => gmdate('Y-m-d\TH:i:s\Z'),
        'profile' => $profile, 'files' => $inventory, 'excluded' => ['other top-level directories', 'wp-content cache/backup/upgrade directories', 'wp-content/debug.log'],
        'containsPrivateDataAndConfig' => true, 'restorationVerified' => false,
        'objectVisibilityCertifiesPrivileges' => false, 'retention' => 'keep-until-install-and-restore-validated-then-decide'];
    ac_backup_write($work . '/backup/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
    ac_backup_write($work . '/files.list', implode("\0", array_map(static fn($p) => './' . $p, array_keys($inventory))) . "\0");
} elseif ($phase === 'collect-sql') {
    $handle = fopen($work . '/backup/database.sql', 'xb');
    if (!$handle) { ac_backup_fail('SQL_CREATE_FAILED'); }
    chmod($work . '/backup/database.sql', 0600); $bytes = 0;
    while (!feof(STDIN)) {
        $chunk = fread(STDIN, 65536);
        if ($chunk === false) { ac_backup_fail('SQL_READ_FAILED'); }
        $bytes += strlen($chunk);
        if ($bytes > 536870912) { ac_backup_fail('SQL_BUDGET_EXCEEDED'); }
        if (fwrite($handle, $chunk) !== strlen($chunk)) { ac_backup_fail('SQL_WRITE_FAILED'); }
    }
    fclose($handle);
    if ($bytes < 1) { ac_backup_fail('EMPTY_SQL'); }
} elseif (in_array($phase, ['verify-schema', 'seal', 'check-files'], true)) {
    $manifest = json_decode(file_get_contents($work . '/backup/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    if ($phase === 'verify-schema') {
        if (ac_backup_profile() !== $manifest['profile']) { ac_backup_fail('DATABASE_SCHEMA_OR_PLUGIN_PROFILE_CHANGED'); }
    } else {
        if (ac_backup_inventory(getcwd()) !== $manifest['files']) { ac_backup_fail('FILES_CHANGED_DURING_BACKUP'); }
        if ($phase === 'seal') {
            $sql = $work . '/backup/database.sql';
            $manifest['database'] = ['bytes' => filesize($sql), 'sha256' => hash_file('sha256', $sql)];
            $manifest['sealedAtUtc'] = gmdate('Y-m-d\TH:i:s\Z');
            $value = json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
            if (file_put_contents($work . '/backup/manifest.json', $value) !== strlen($value)) { ac_backup_fail('MANIFEST_SEAL_FAILED'); }
        }
    }
} else { ac_backup_fail('UNKNOWN_BACKUP_PHASE'); }
