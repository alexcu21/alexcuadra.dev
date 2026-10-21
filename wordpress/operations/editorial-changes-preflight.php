<?php
/**
 * Read-only readiness inventory, NOT a plugin/ability or an installation approval.
 * wp eval-file editorial-changes-preflight.php USER_ID EN_PROJECT_ID ES_PROJECT_ID
 * Never reads credentials, option values, source bodies or arbitrary meta into its output.
 */
if (!defined('WP_CLI') || !WP_CLI || !function_exists('get_userdata')) {
    throw new RuntimeException('WORDPRESS_CLI_REQUIRED');
}
$input = $args ?? [];
if (count($input) !== 3 || array_filter($input, static function ($v) { return !is_string($v) || !preg_match('/^[1-9][0-9]*$/D', $v); })) {
    WP_CLI::error('EXPECTED_USER_AND_TWO_PUBLIC_PROJECT_IDS');
}
$site = rtrim(home_url(), '/');
if (!in_array($site, ['https://content.alexcuadra.dev', 'http://wordpress'], true) || is_multisite()) {
    WP_CLI::error('EXPECTED_SINGLE_SITE_OR_ISOLATED_FIXTURE');
}
$actor = get_userdata((int) $input[0]);
if (!$actor) { WP_CLI::error('TARGET_USER_NOT_FOUND'); }
if (!function_exists('get_plugins')) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }
global $wpdb, $wp_version, $wp_filter;
$read = static function ($sql) use ($wpdb) {
    $rows = $wpdb->get_results($sql, ARRAY_A);
    if (!is_array($rows) || $wpdb->last_error) { WP_CLI::error('READ_ONLY_QUERY_FAILED'); }
    return $rows;
};
$fingerprint = static function ($id) use ($wpdb, $read) {
    // Values are hashed in memory only; the report never contains post content/meta values.
    return hash('sha256', serialize([
        $read($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d ORDER BY ID", $id)),
        $read($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id=%d ORDER BY meta_id", $id)),
        $read($wpdb->prepare("SELECT * FROM $wpdb->term_relationships WHERE object_id=%d ORDER BY term_taxonomy_id", $id)),
    ]));
};
$ids = [(int) $input[1] => 'en', (int) $input[2] => 'es'];
if (count($ids) !== 2) { WP_CLI::error('TWO_DISTINCT_PROJECTS_REQUIRED'); }
$previous = get_current_user_id(); $before = []; $after = [];
try {
    wp_set_current_user($actor->ID);
    foreach ($ids as $id => $locale) { $before[$id] = $fingerprint($id); }
    $caps = [];
    foreach (['read', 'alexcuadra_read_public_projects', 'alexcuadra_prepare_project_changes', 'alexcuadra_apply_project_changes',
        'alexcuadra_recover_project_changes', 'alexcuadra_approve_public_project_changes', 'edit_posts', 'edit_pages',
        'publish_posts', 'publish_pages', 'manage_options', 'install_plugins', 'edit_users'] as $cap) { $caps[$cap] = current_user_can($cap); }
    $plugins = [];
    foreach (get_plugins() as $file => $data) {
        $plugins[] = ['file' => $file, 'version' => $data['Version'], 'active' => is_plugin_active($file)];
    }
    $mu = [];
    foreach (get_mu_plugins() as $file => $data) { $mu[] = ['file' => $file, 'version' => $data['Version']]; }
    $project = get_post_type_object('project');
    $field = function_exists('acf_get_field') ? acf_get_field('field_676dcb2aec8d4') : false;
    $group = $field && function_exists('acf_get_field_group') ? acf_get_field_group($field['parent']) : false;
    $mapping = $field && $group ? ['fieldKey' => $field['key'], 'name' => $field['name'], 'type' => $field['type'],
        'graphqlField' => $field['graphql_field_name'] ?? null, 'groupKey' => $group['key'], 'active' => (bool) $group['active'],
        'location' => $group['location'], 'definitionHash' => hash('sha256', wp_json_encode([$field, $group]))] : null;
    $samples = [];
    foreach ($ids as $id => $locale) {
        $post = get_post($id);
        $meta = $read($wpdb->prepare("SELECT meta_key,meta_value FROM $wpdb->postmeta WHERE post_id=%d
            AND meta_key IN ('project_tagline','_project_tagline') ORDER BY meta_id", $id));
        $values = array_values(array_filter($meta, static function ($r) { return $r['meta_key'] === 'project_tagline'; }));
        $refs = array_values(array_filter($meta, static function ($r) { return $r['meta_key'] === '_project_tagline'; }));
        $translations = function_exists('pll_get_post_translations') ? pll_get_post_translations($id) : [];
        $otherLocale = $locale === 'en' ? 'es' : 'en'; $expectedOther = array_search($otherLocale, $ids, true);
        $samples[] = ['id' => $id, 'locale' => $locale, 'eligible' => $post && $post->post_type === 'project'
            && $post->post_status === 'publish' && $post->post_password === '' && is_post_publicly_viewable($post)
            && current_user_can('read_post', $id) && function_exists('pll_get_post_language') && pll_get_post_language($id, 'slug') === $locale,
            'expectedTranslationLinked' => ($translations[$otherLocale] ?? null) === $expectedOther,
            'taglineRows' => count($values), 'referenceRows' => count($refs),
            'referenceMatches' => count($refs) === 1 && $refs[0]['meta_value'] === 'field_676dcb2aec8d4'];
    }
    $tables = [$wpdb->posts, $wpdb->postmeta, $wpdb->terms, $wpdb->term_taxonomy, $wpdb->term_relationships];
    $engines = $read($wpdb->prepare('SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('
        . implode(',', array_fill(0, count($tables), '%s')) . ') ORDER BY TABLE_NAME', $tables));
    $hooks = [];
    $selected = ['wp_insert_post_data', 'wp_insert_post_empty_content', 'pre_post_update', 'post_updated', 'transition_post_status',
        'publish_project', 'wp_insert_post', 'wp_after_insert_post', 'save_post', 'save_post_project', 'content_save_pre',
        'update_post_metadata', 'add_post_metadata', 'delete_post_metadata', 'updated_post_meta', 'added_post_meta', 'deleted_post_meta',
        'clean_post_cache', 'clean_object_term_cache', 'set_object_terms', 'pre_wp_mail', 'wp_mail', 'pre_http_request',
        'pre_schedule_event', 'schedule_event', 'user_has_cap', 'map_meta_cap'];
    foreach (array_keys($wp_filter) as $name) {
        if (preg_match('~^acf/(pre_update_value|update_value|update_metadata|delete_value|load_field)(/|$)|^pll_save_post~', $name)) { $selected[] = $name; }
    }
    $selected = array_values(array_unique($selected)); sort($selected);
    foreach ($selected as $name) {
        $hooks[$name] = [];
        foreach (($wp_filter[$name]->callbacks ?? []) as $priority => $callbacks) {
            foreach ($callbacks as $entry) {
                $cb = $entry['function'];
                try {
                    if (is_array($cb)) { $r = new ReflectionMethod($cb[0], $cb[1]); }
                    elseif (is_object($cb) && !$cb instanceof Closure) { $r = new ReflectionMethod($cb, '__invoke'); }
                    elseif (is_string($cb) && str_contains($cb, '::')) { $r = new ReflectionMethod($cb); }
                    else { $r = new ReflectionFunction($cb); }
                    $file = $r->getFileName(); $root = wp_normalize_path(ABSPATH);
                    $file = $file ? wp_normalize_path($file) : '';
                    $source = str_starts_with($file, $root) ? substr($file, strlen($root)) : 'outside-wordpress-or-internal';
                    $canHash = preg_match('~^(wp-includes/|wp-admin/|wp-content/(plugins|mu-plugins|themes)/)~', $source) && is_file($file);
                    $class = $r instanceof ReflectionMethod ? ($r->getDeclaringClass()->isAnonymous() ? 'anonymous-class' : $r->getDeclaringClass()->getName()) . '::' : '';
                    $symbol = $cb instanceof Closure ? '{closure}' : $class . $r->getName();
                    $hooks[$name][] = ['priority' => (int) $priority, 'symbol' => $symbol,
                        'source' => $source, 'line' => $r->getStartLine(), 'fileHash' => $canHash ? hash_file('sha256', $file) : null];
                } catch (ReflectionException $e) { $hooks[$name][] = ['priority' => (int) $priority, 'source' => 'unresolved']; }
            }
        }
    }
    foreach ($ids as $id => $locale) { $after[$id] = $fingerprint($id); }
    $checks = [
        'existing_read_account_preserved' => $actor->roles === ['subscriber'] && $caps['read'] && $caps['alexcuadra_read_public_projects'],
        'no_write_or_approval_scope_enabled' => !array_filter(array_intersect_key($caps, array_flip(['alexcuadra_prepare_project_changes', 'alexcuadra_apply_project_changes', 'alexcuadra_recover_project_changes', 'alexcuadra_approve_public_project_changes']))),
        'no_checked_global_edit_or_admin_caps' => !array_filter(array_intersect_key($caps, array_flip(['edit_posts', 'edit_pages', 'publish_posts', 'publish_pages', 'manage_options', 'install_plugins', 'edit_users']))),
        'public_project_type' => $project && $project->public && is_post_type_viewable($project),
        'expected_acf_mapping' => $mapping && $mapping['fieldKey'] === 'field_676dcb2aec8d4' && $mapping['name'] === 'project_tagline'
            && $mapping['type'] === 'textarea' && $mapping['groupKey'] === 'group_676d40f7bb646' && $mapping['active']
            && $mapping['location'] === [[['param' => 'post_type', 'operator' => '==', 'value' => 'project']]],
        'two_public_localized_samples' => count($samples) === 2 && !array_filter($samples, static function ($s) { return !$s['eligible'] || !$s['expectedTranslationLinked'] || $s['taglineRows'] !== 1 || !$s['referenceMatches']; }),
        'source_tables_innodb' => count($engines) === 5 && !array_filter($engines, static function ($e) { return $e['ENGINE'] !== 'InnoDB'; }),
        'sample_fingerprints_unchanged' => $before === $after,
    ];
    $report = ['format' => 'editorial-changes-preflight-v1', 'site' => $site, 'observedAtUtc' => gmdate('Y-m-d\TH:i:s\Z'),
        'context' => 'wp-cli-selected-hooks-only', 'actor' => ['id' => $actor->ID, 'roles' => $actor->roles, 'capabilities' => $caps],
        'wordpress' => $wp_version, 'php' => PHP_VERSION, 'plugins' => $plugins, 'muPlugins' => $mu,
        'projectSupports' => array_keys(get_all_post_type_supports('project')), 'fieldMapping' => $mapping, 'samples' => $samples,
        'sourceTableEngines' => $engines, 'selectedHooks' => $hooks, 'sourceFingerprintsBefore' => $before, 'sourceFingerprintsAfter' => $after,
        'checks' => $checks, 'technicalSnapshotPassed' => !in_array(false, $checks, true),
        'backupAndRestoreVerified' => false, 'allRequestContextsAudited' => false, 'installationEligible' => false,
        'permissionsGranted' => false, 'operatorWritesPerformed' => false];
} finally { wp_set_current_user($previous); }
$report['currentUserContextRestored'] = get_current_user_id() === $previous;
WP_CLI::line('EDITORIAL_CHANGES_PREFLIGHT_RESULT=' . wp_json_encode($report));
if (!$report['technicalSnapshotPassed'] || !$report['currentUserContextRestored']) { WP_CLI::halt(1); }
