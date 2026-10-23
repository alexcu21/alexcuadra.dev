<?php
/** Read-only operator inspection. No grants, DDL, plugin activation or config edits. */
if (!defined('WP_CLI') || !WP_CLI || !defined('ABSPATH')) { throw new RuntimeException('WORDPRESS_CLI_REQUIRED'); }
$expected = json_decode(base64_decode('@@EXPECTED_PROFILE@@', true), true, 512, JSON_THROW_ON_ERROR);
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
