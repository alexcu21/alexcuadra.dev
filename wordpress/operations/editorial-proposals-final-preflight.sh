#!/bin/sh
# Read-only operator check; Alex runs SSH. No scratch/export/grants/DDL.
set -eu
cd "$HOME/domains/alexcuadra.dev/public_html/content"
test -f wp-load.php
printf "%s\n" EDITORIAL_PROPOSALS_FINAL_PREFLIGHT_V1
wp --path=. eval-file - <<'AC_PROPOSALS_PREFLIGHT_PHP'
<?php
/** Read-only operator inspection. No grants, DDL, plugin activation or config edits. */
if (!defined('WP_CLI') || !WP_CLI || !defined('ABSPATH')) { throw new RuntimeException('WORDPRESS_CLI_REQUIRED'); }
$expected = json_decode(base64_decode('eyJ3b3JkcHJlc3MiOiI3LjEuMyIsInBocCI6IjguNS4xMSIsImRhdGFiYXNlU2VydmVyIjoiMTEuOC45LU1hcmlhREItbG9nIiwicGx1Z2lucyI6eyJhZHZhbmNlZC1jdXN0b20tZmllbGRzL2FjZi5waHAiOnsidmVyc2lvbiI6IjYuOC4xMCIsImFjdGl2ZSI6dHJ1ZX0sImFsZXhjdWFkcmEtZWRpdG9yaWFsLXJlYWQvYWxleGN1YWRyYS1lZGl0b3JpYWwtcmVhZC5waHAiOnsidmVyc2lvbiI6IjAuMS4wIiwiYWN0aXZlIjp0cnVlfSwiYWxsLWluLW9uZS13cC1zZWN1cml0eS1hbmQtZmlyZXdhbGwvd3Atc2VjdXJpdHkucGhwIjp7InZlcnNpb24iOiI1LjQuMTAiLCJhY3RpdmUiOmZhbHNlfSwicHJvamVjdHMtY3B0LWZvci1oZWFkbGVzcy9wcm9qZWN0cy1jcHQucGhwIjp7InZlcnNpb24iOiIxLjAiLCJhY3RpdmUiOmZhbHNlfSwiaG9zdGluZ2VyLWFpLWFzc2lzdGFudC9ob3N0aW5nZXItYWktYXNzaXN0YW50LnBocCI6eyJ2ZXJzaW9uIjoiMy4xLjMiLCJhY3RpdmUiOmZhbHNlfSwiaG9zdGluZ2VyLWVhc3ktb25ib2FyZGluZy9ob3N0aW5nZXItZWFzeS1vbmJvYXJkaW5nLnBocCI6eyJ2ZXJzaW9uIjoiMy4wLjUiLCJhY3RpdmUiOnRydWV9LCJob3N0aW5nZXItcmVhY2gvaG9zdGluZ2VyLXJlYWNoLnBocCI6eyJ2ZXJzaW9uIjoiMS44LjciLCJhY3RpdmUiOnRydWV9LCJob3N0aW5nZXIvaG9zdGluZ2VyLnBocCI6eyJ2ZXJzaW9uIjoiMy4wLjgwIiwiYWN0aXZlIjp0cnVlfSwibGl0ZXNwZWVkLWNhY2hlL2xpdGVzcGVlZC1jYWNoZS5waHAiOnsidmVyc2lvbiI6IjcuOS4yIiwiYWN0aXZlIjp0cnVlfSwibWNwLWFkYXB0ZXIvbWNwLWFkYXB0ZXIucGhwIjp7InZlcnNpb24iOiIwLjcuMSIsImFjdGl2ZSI6dHJ1ZX0sIm1pbmltYWwtY29taW5nLXNvb24tbWFpbnRlbmFuY2UtbW9kZS9taW5pbWFsLWNvbWluZy1zb29uLW1haW50ZW5hbmNlLW1vZGUucGhwIjp7InZlcnNpb24iOiIyLjQ1IiwiYWN0aXZlIjpmYWxzZX0sInBvbHlsYW5nL3BvbHlsYW5nLnBocCI6eyJ2ZXJzaW9uIjoiMy45LjEiLCJhY3RpdmUiOnRydWV9LCJ3b3JkcHJlc3MtYmV0YS10ZXN0ZXIvd3AtYmV0YS10ZXN0ZXIucGhwIjp7InZlcnNpb24iOiI0LjAuMSIsImFjdGl2ZSI6dHJ1ZX0sIndwLWdyYXBocWwvd3AtZ3JhcGhxbC5waHAiOnsidmVyc2lvbiI6IjIuMjUuMCIsImFjdGl2ZSI6dHJ1ZX0sIndwZ3JhcGhxbC1hY2Yvd3BncmFwaHFsLWFjZi5waHAiOnsidmVyc2lvbiI6IjMuMC4wIiwiYWN0aXZlIjp0cnVlfSwid3BncmFwaHFsLWlkZS93cGdyYXBocWwtaWRlLnBocCI6eyJ2ZXJzaW9uIjoiNS43LjAiLCJhY3RpdmUiOnRydWV9LCJ3cC1ncmFwaHFsLXBvbHlsYW5nLTAuNy4xL3dwLWdyYXBocWwtcG9seWxhbmcucGhwIjp7InZlcnNpb24iOiIwLjcuMSIsImFjdGl2ZSI6dHJ1ZX0sIndwLW1pZ3JhdGUtZGIvd3AtbWlncmF0ZS1kYi5waHAiOnsidmVyc2lvbiI6IjIuNy4xMiIsImFjdGl2ZSI6ZmFsc2V9fSwic3R5bGVzaGVldCI6InR3ZW50eXR3ZW50eWZvdXIiLCJ0ZW1wbGF0ZSI6InR3ZW50eXR3ZW50eWZvdXIiLCJ0aGVtZVZlcnNpb24iOiIxLjYiLCJ0cmVlcyI6eyJwbHVnaW5zIjp7ImZpbGVzIjo5NTkwLCJieXRlcyI6MTYwMjg3MTE5LCJzaGEyNTYiOiI3MDU5ZWQ2ZDJlZGYxMzU1MzFkOWFlZTE5NGRkOTFhYjI4OWMwZTU3ZGVmZDhmNzIzM2JlNWM5NWVhYmI2ZmM4In0sIm11LXBsdWdpbnMiOnsiZmlsZXMiOjIsImJ5dGVzIjo0MTA0NCwic2hhMjU2IjoiNGVlZTYxM2ZiYzJmMjk4YWU3MDkyNGI2NTlhYmJlZmUwYjU5ZGEwZDNkMTUxMWM3MzZkODk0YzUzYjQ5OGYzNCJ9LCJ0aGVtZXMiOnsiZmlsZXMiOjQwNDQsImJ5dGVzIjo0Njg2NTQ2Niwic2hhMjU2IjoiZTY1Y2FkMWQwY2I5ZjY5YTliOGI4MGFkYzcyOGM0NWRlYWI1M2ExNTI5YmVjOTRlMmFjODY2NWNiMGJhNDg3ZiJ9LCJjb250ZW50LXJvb3QiOnsiZmlsZXMiOjEsImJ5dGVzIjoyOCwic2hhMjU2IjoiNTY2YmE5MWI2NjQyODA5OTVjY2QyMzI4NWNkOGYyZjU2MDhmNDQ5ZDA5MDJmZDAzYzAyZDEwZTZmZTA3MzliNSJ9fX0=', true), true, 512, JSON_THROW_ON_ERROR);
require_once ABSPATH . 'wp-admin/includes/plugin.php';
function ac_proposals_final_tree($directory, $rootOnly = false) {
    if (!is_dir($directory) || is_link($directory)) { return null; }
    $files = []; $bytes = 0;
    $iterator = $rootOnly ? new DirectoryIterator($directory) : new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (in_array($file->getFilename(), ['.', '..'], true) || ($rootOnly && $file->getFilename() === 'debug.log')) { continue; }
        if ($file->isLink() || (!$file->isFile() && !$file->isDir())) { return null; }
        if (!$file->isFile()) { continue; }
        $name = substr($file->getPathname(), strlen($directory) + 1);
        $digest = hash_file('sha256', $file->getPathname());
        if ($digest === false) { return null; }
        $files[$name] = $digest; $bytes += $file->getSize();
        if (count($files) > 100000 || $bytes > 512 * 1024 * 1024) { return null; }
    }
    ksort($files, SORT_STRING); $hash = hash_init('sha256');
    foreach ($files as $name => $digest) { hash_update($hash, $name . "\0" . $digest . "\n"); }
    return ['files' => count($files), 'bytes' => $bytes, 'sha256' => hash_final($hash)];
}
$site = 'https://content.alexcuadra.dev';
$plugins = [];
foreach (get_plugins() as $file => $data) { $plugins[$file] = ['version' => $data['Version'], 'active' => is_plugin_active($file)]; }
ksort($plugins); ksort($expected['plugins']);
$trees = [];
foreach (['plugins', 'mu-plugins', 'themes'] as $directory) { $trees[$directory] = ac_proposals_final_tree(WP_CONTENT_DIR . '/' . $directory); }
$trees['content-root'] = ac_proposals_final_tree(WP_CONTENT_DIR, true);
$treeChecks = [];
foreach ($expected['trees'] as $name => $tree) { $treeChecks[$name] = isset($trees[$name]) && $trees[$name] === $tree; }
global $wpdb, $wp_version;
$oldErrors = $wpdb->suppress_errors(true);
$tables = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (%s,%s)', $wpdb->prefix . 'ac_proposal_owners', $wpdb->prefix . 'ac_proposal_requests'));
$tablesOk = $tables !== null && !$wpdb->last_error && (int) $tables === 0;
$engines = $wpdb->get_results($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (%s,%s)', $wpdb->posts, $wpdb->postmeta), ARRAY_A);
$enginesOk = !$wpdb->last_error && is_array($engines) && count($engines) === 2 && !array_filter($engines, static function ($r) { return $r['ENGINE'] !== 'InnoDB'; });
$dbVersion = $wpdb->get_var('SELECT VERSION()');
$dbOk = !$wpdb->last_error && $dbVersion === $expected['databaseServer'];
$wpdb->suppress_errors($oldErrors);
$previous = get_current_user_id(); $target = get_userdata(2); $effective = []; $unexpected = []; $roleOk = false; $passwordsOk = false; $samplesOk = false;
try {
    if ($target) {
        wp_set_current_user(2);
        $project = get_post_type_object('project');
        $names = ['read', 'alexcuadra_read_public_projects', 'alexcuadra_prepare_project_changes', 'manage_options', 'install_plugins', 'activate_plugins',
            'edit_users', 'promote_users', 'delete_users', 'edit_posts', 'edit_pages', 'publish_posts', 'publish_pages'];
        foreach (['edit_posts', 'edit_others_posts', 'publish_posts', 'delete_posts', 'create_posts'] as $key) {
            if ($project && isset($project->cap->$key)) { $names[] = $project->cap->$key; }
        }
        foreach (array_unique($names) as $name) { $effective[$name] = current_user_can($name); }
        $unexpected = array_diff(array_keys(array_filter($target->allcaps)), ['subscriber', 'read', 'level_0', 'alexcuadra_read_public_projects']);
        $role = get_role('subscriber');
        $roleOk = $target->roles === ['subscriber'] && $role && !$role->has_cap('alexcuadra_read_public_projects') && !$role->has_cap('alexcuadra_prepare_project_changes');
        $passwordsOk = wp_is_application_passwords_available_for_user($target);
        $samplesOk = true;
        foreach ([146 => 'en', 196 => 'es'] as $id => $locale) {
            $post = get_post($id);
            $samplesOk = $samplesOk && $post && $post->post_type === 'project' && $post->post_status === 'publish' && $post->post_password === ''
                && function_exists('pll_get_post_language') && pll_get_post_language($id, 'slug') === $locale
                && current_user_can('read_post', $id) && !current_user_can('edit_post', $id) && !current_user_can('delete_post', $id);
        }
    }
} finally { wp_set_current_user($previous); }
$readOk = ($effective['read'] ?? false) && ($effective['alexcuadra_read_public_projects'] ?? false);
unset($effective['read'], $effective['alexcuadra_read_public_projects']);
$migrationOperators = array_values(array_filter(array_map('intval', get_users(['role' => 'administrator', 'fields' => 'ID'])), static function ($id) { return user_can($id, 'manage_options'); }));
sort($migrationOperators);
$checks = [
    'canonicalSingleSite' => home_url() === $site && site_url() === $site && !is_multisite(),
    'wordpressVersion' => $wp_version === $expected['wordpress'], 'phpVersion' => PHP_VERSION === $expected['php'],
    'databaseVersion' => $dbOk, 'pluginVersionsAndActivation' => $plugins === $expected['plugins'],
    'filesMatchBackup' => !in_array(false, $treeChecks, true),
    'activeTheme' => get_stylesheet() === $expected['stylesheet'] && get_template() === $expected['template'] && wp_get_theme()->get('Version') === $expected['themeVersion'],
    'candidateNotInstalled' => !isset($plugins['alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php']),
    'candidateOptinAbsent' => !defined('AC_EDITORIAL_PROPOSALS_SITE'),
    'candidateTablesAbsent' => $tablesOk, 'storageMarkerAbsent' => get_option('ac_proposals_storage_version', false) === false,
    'coreTransactionalTables' => $enginesOk,
    'legacyLabInactive' => !is_plugin_active('alexcuadra-editorial-changes/alexcuadra-editorial-changes.php'),
    'targetSubscriberOnlyNoGlobalScopes' => $roleOk, 'targetPublicReadScopePresent' => $readOk,
    'targetNoCheckedEditAdminPrepare' => $target && !in_array(true, $effective, true), 'targetNoUnexpectedStoredGrants' => $target && !$unexpected,
    'applicationPasswordsAvailable' => $passwordsOk,
    'publicProjectSamplesReadableNotEditable' => $samplesOk,
    'projectPublicAndTranslatable' => post_type_exists('project') && is_post_type_viewable('project') && function_exists('pll_is_translated_post_type') && pll_is_translated_post_type('project'),
    'abilitiesApiAvailable' => function_exists('wp_get_ability') && function_exists('wp_register_ability'),
    'migrationOperatorAvailable' => $migrationOperators !== [],
];
WP_CLI::line('EDITORIAL_PROPOSALS_FINAL_PREFLIGHT=' . wp_json_encode([
    'format' => 'editorial-proposals-final-preflight-v1', 'observedAtUtc' => gmdate('Y-m-d\TH:i:s\Z'),
    'userId' => 2, 'migrationOperatorIds' => $migrationOperators, 'checks' => $checks, 'fileChecks' => $treeChecks,
    'technicalChecksPassed' => !in_array(false, $checks, true), 'installationAuthorized' => false,
    'credentialsOrContentExported' => false, 'operatorWroteEditorialData' => false, 'ddlPrivilegesExercised' => false,
    'candidateVersion' => '0.1.0-rc.2', 'candidateSha256' => 'c31e6814864be0692035de7b46322fc2fc0159d2104e0b4d25404c3b15859f07',
]));

AC_PROPOSALS_PREFLIGHT_PHP
printf "%s\n" END_EDITORIAL_PROPOSALS_FINAL_PREFLIGHT_V1
