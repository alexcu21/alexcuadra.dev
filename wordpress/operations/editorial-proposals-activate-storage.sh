#!/bin/sh
# Approved phase B2. Alex runs SSH. No user grants or source writes.
set -eu
umask 077
cd "$HOME/domains/alexcuadra.dev/public_html/content"
test -d "$HOME/.ac-editorial-deploy/proposals-0.1.0-rc.2-20261023"
work="$(mktemp -d "$HOME/.ac-editorial-deploy/activation.XXXXXXXX")"
printf '%s\n' EDITORIAL_PROPOSALS_ACTIVATE_STORAGE_V1
cat > "$work/check.php" <<'AC_ACTIVATION_CHECK_PHP'
<?php
// Approved phase B2: fingerprints/read-only postflight, never grants capabilities.
if (!defined('WP_CLI') || !WP_CLI || home_url() !== 'https://content.alexcuadra.dev'
    || site_url() !== home_url() || is_multisite()) { throw new RuntimeException('EXPECTED_CLI_SITE'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
function ac_proposals_activation_snapshot() {
    global $wpdb;
    $ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type='project' ORDER BY ID");
    if ($wpdb->last_error || !is_array($ids) || !in_array('146', array_map('strval', $ids), true) || !in_array('196', array_map('strval', $ids), true)) { WP_CLI::error('SOURCE_IDS_UNAVAILABLE'); }
    $list = implode(',', array_map('intval', $ids)); $source = [];
    foreach (["SELECT * FROM $wpdb->posts WHERE ID IN ($list) ORDER BY ID",
        "SELECT * FROM $wpdb->postmeta WHERE post_id IN ($list) ORDER BY meta_id",
        "SELECT * FROM $wpdb->term_relationships WHERE object_id IN ($list) ORDER BY object_id,term_taxonomy_id"] as $query) {
        $rows = $wpdb->get_results($query, ARRAY_A);
        if ($wpdb->last_error || !is_array($rows)) { WP_CLI::error('SOURCE_SNAPSHOT_FAILED'); }
        $source[] = $rows;
    }
    $user = get_userdata(2); $role = get_role('subscriber');
    if (!$user || !$role || $user->roles !== ['subscriber']) { WP_CLI::error('TARGET_ACCOUNT_CHANGED'); }
    $caps = $user->caps; ksort($caps); $roleCaps = $role->capabilities; ksort($roleCaps);
    $plugins = array_values(array_diff(get_option('active_plugins', []), ['alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php'])); sort($plugins);
    $hash = static function ($value) { return hash('sha256', serialize($value)); };
    return ['projectCount' => count($ids), 'sourceHash' => $hash($source), 'user2CapsHash' => $hash($caps),
        'subscriberRoleHash' => $hash($roleCaps), 'otherActivePluginsHash' => $hash($plugins),
        'stylesheet' => get_stylesheet(), 'template' => get_template(),
        'readPluginSha256' => hash_file('sha256', WP_PLUGIN_DIR . '/alexcuadra-editorial-read/alexcuadra-editorial-read.php')];
}
$phase = $args[0] ?? '';
$file = 'alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php';
if ($phase === 'before') {
    if (defined('AC_EDITORIAL_PROPOSALS_SITE') || is_plugin_active($file) || get_option('ac_proposals_storage_version', false) !== false) { WP_CLI::error('ACTIVATION_ALREADY_STARTED'); }
    global $wpdb;
    $tables = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (%s,%s)', $wpdb->prefix . 'ac_proposal_owners', $wpdb->prefix . 'ac_proposal_requests'));
    if ($wpdb->last_error || $tables === null || (int) $tables !== 0) { WP_CLI::error('STORAGE_COLLISION'); }
    $snapshot = ac_proposals_activation_snapshot();
    WP_CLI::line('PROPOSALS_ACTIVATION_BASELINE=' . base64_encode(wp_json_encode($snapshot)));
} elseif ($phase === 'after' && count($args) === 2) {
    $before = json_decode(base64_decode($args[1], true), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($before) || ac_proposals_activation_snapshot() !== $before) { WP_CLI::error('SOURCE_PERMISSIONS_OR_DEPENDENCIES_CHANGED'); }
    if (!defined('AC_EDITORIAL_PROPOSALS_SITE') || AC_EDITORIAL_PROPOSALS_SITE !== 'https://content.alexcuadra.dev'
        || !is_plugin_active($file) || get_option('ac_proposals_storage_version') !== '0.1'
        || !function_exists('AlexCuadra\\EditorialProposals\\storage_schema_valid') || !AlexCuadra\EditorialProposals\storage_schema_valid()) { WP_CLI::error('ACTIVATED_STORAGE_INVALID'); }
    global $wpdb;
    foreach (['ac_proposal_owners', 'ac_proposal_requests'] as $suffix) {
        $count = $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . $suffix);
        if ($wpdb->last_error || $count === null || (int) $count !== 0) { WP_CLI::error('NEW_STORAGE_NOT_EMPTY'); }
    }
    $previous = get_current_user_id();
    try {
        wp_set_current_user(2);
        if (!current_user_can('read') || !current_user_can('alexcuadra_read_public_projects') || current_user_can('alexcuadra_prepare_project_changes')
            || current_user_can('manage_options') || current_user_can('edit_posts') || current_user_can('edit_pages')) { WP_CLI::error('TARGET_CAPABILITIES_CHANGED'); }
        foreach ([146 => 'en', 196 => 'es'] as $id => $locale) {
            if (current_user_can('edit_post', $id) || current_user_can('delete_post', $id)
                || is_wp_error(AlexCuadra\EditorialRead\get_project(['id' => $id, 'locale' => $locale]))) { WP_CLI::error('SOURCE_READ_OR_WRITE_PERMISSION_CHANGED'); }
        }
        $denied = AlexCuadra\EditorialProposals\execute(['proposal_id' => 1, 'locale' => 'en'], 'get');
        if (!is_wp_error($denied) || $denied->get_error_code() !== 'ac_change_forbidden') { WP_CLI::error('TARGET_MUST_REMAIN_DENIED'); }
    } finally { wp_set_current_user($previous); }
    WP_CLI::line('PROPOSALS_ACTIVATION_RESULT=' . wp_json_encode(['version' => '0.1.0-rc.2', 'active' => true,
        'storageSchemaVerified' => true, 'newStorageEmpty' => true, 'projectCount' => $before['projectCount'],
        'sourceHash' => $before['sourceHash'], 'sourcesUnchanged' => true, 'targetCapabilitiesUnchanged' => true,
        'subscriberRoleUnchanged' => true, 'otherActivePluginsUnchanged' => true, 'readVerifiedEnEs' => true,
        'prepareScopeGranted' => false, 'targetPrepareDenied' => true]));
} else { WP_CLI::error('EXPECTED_ACTIVATION_PHASE'); }

AC_ACTIVATION_CHECK_PHP
before="$(wp --path=. eval-file "$work/check.php" before)"
baseline="$(printf '%s\n' "$before" | sed -n 's/^PROPOSALS_ACTIVATION_BASELINE=//p')"
test -n "$baseline"
printf '%s\n' "$baseline" > "$work/baseline.base64"
printf '%s\n' PROPOSALS_BASELINE_CAPTURED
wp --path=. config set AC_EDITORIAL_PROPOSALS_SITE 'https://content.alexcuadra.dev' --type=constant
wp --path=. plugin activate alexcuadra-editorial-proposals
wp --path=. --user=1 eval '$r=AlexCuadra\EditorialProposals\install_storage(); if ($r !== true || !AlexCuadra\EditorialProposals\storage_schema_valid()) { WP_CLI::error("PROPOSALS_STORAGE_NOT_READY"); } WP_CLI::line("PROPOSALS_STORAGE_READY");'
wp --path=. eval-file "$work/check.php" after "$baseline"
printf '%s\n' END_EDITORIAL_PROPOSALS_ACTIVATE_STORAGE_V1
