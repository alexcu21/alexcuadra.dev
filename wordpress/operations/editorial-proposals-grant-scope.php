<?php
// Approved phase B2: fingerprints/read-only postflight, never grants capabilities.
if (!defined('WP_CLI') || !WP_CLI || home_url() !== 'https://content.alexcuadra.dev'
    || site_url() !== home_url() || is_multisite()) { throw new RuntimeException('EXPECTED_CLI_SITE'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
function ac_proposals_activation_snapshot() {
    global $wpdb;
    $ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type='project' ORDER BY ID");
    if ($wpdb->last_error || !is_array($ids) || !in_array('146', array_map('strval', $ids), true) || !in_array('196', array_map('strval', $ids), true)) { throw new RuntimeException('SOURCE_IDS_UNAVAILABLE'); }
    $list = implode(',', array_map('intval', $ids)); $source = [];
    foreach (["SELECT * FROM $wpdb->posts WHERE ID IN ($list) ORDER BY ID",
        "SELECT * FROM $wpdb->postmeta WHERE post_id IN ($list) ORDER BY meta_id",
        "SELECT * FROM $wpdb->term_relationships WHERE object_id IN ($list) ORDER BY object_id,term_taxonomy_id"] as $query) {
        $rows = $wpdb->get_results($query, ARRAY_A);
        if ($wpdb->last_error || !is_array($rows)) { throw new RuntimeException('SOURCE_SNAPSHOT_FAILED'); }
        $source[] = $rows;
    }
    $user = get_userdata(2); $role = get_role('subscriber');
    if (!$user || !$role || $user->roles !== ['subscriber']) { throw new RuntimeException('TARGET_ACCOUNT_CHANGED'); }
    $caps = $user->caps; ksort($caps); $roleCaps = $role->capabilities; ksort($roleCaps);
    $plugins = array_values(array_diff(get_option('active_plugins', []), ['alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php'])); sort($plugins);
    $hash = static function ($value) { return hash('sha256', serialize($value)); };
    return ['projectCount' => count($ids), 'sourceHash' => $hash($source), 'user2CapsHash' => $hash($caps),
        'subscriberRoleHash' => $hash($roleCaps), 'otherActivePluginsHash' => $hash($plugins),
        'stylesheet' => get_stylesheet(), 'template' => get_template(),
        'readPluginSha256' => hash_file('sha256', WP_PLUGIN_DIR . '/alexcuadra-editorial-read/alexcuadra-editorial-read.php')];
}

$scope = 'alexcuadra_prepare_project_changes';
$operator = get_current_user_id(); $granted = false; $target = null;
try {
    if ($operator !== 1 || !current_user_can('manage_options')) { throw new RuntimeException('EXPECTED_OPERATOR_ONE'); }
    if (!defined('AC_EDITORIAL_PROPOSALS_SITE') || AC_EDITORIAL_PROPOSALS_SITE !== 'https://content.alexcuadra.dev'
        || !is_plugin_active('alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php')
        || get_option('ac_proposals_storage_version') !== '0.1' || !AlexCuadra\EditorialProposals\storage_schema_valid()) { throw new RuntimeException('STORAGE_NOT_READY'); }
    $before = ac_proposals_activation_snapshot();
    if ($before['projectCount'] !== 14 || $before['sourceHash'] !== 'c38acd1690f173488f075443d03a80c96b6b483a5bd407040cbbd40a7ff462af') { throw new RuntimeException('SOURCE_BASELINE_CHANGED'); }
    $target = get_userdata(2); $expectedCaps = $target->caps;
    wp_set_current_user(2);
    if (!empty($expectedCaps[$scope]) || get_role('subscriber')->has_cap($scope) || get_role('subscriber')->has_cap('alexcuadra_read_public_projects') || current_user_can($scope) || !current_user_can('read') || !current_user_can('alexcuadra_read_public_projects')) { throw new RuntimeException('TARGET_SCOPE_STATE_CHANGED'); }
    wp_set_current_user($operator);
    $expectedCaps[$scope] = true; ksort($expectedCaps);
    $granted = true; // Also roll back if an update hook throws after persisting the grant.
    $target->add_cap($scope, true);
    clean_user_cache(2);
    $after = ac_proposals_activation_snapshot();
    if ($after['user2CapsHash'] !== hash('sha256', serialize($expectedCaps))) { throw new RuntimeException('UNEXPECTED_DIRECT_CAPABILITY_CHANGE'); }
    unset($before['user2CapsHash'], $after['user2CapsHash']);
    if ($before !== $after) { throw new RuntimeException('SOURCE_ROLE_OR_DEPENDENCY_CHANGED'); }
    wp_set_current_user(2);
    if (AlexCuadra\EditorialProposals\permission() !== true || get_userdata(2)->roles !== ['subscriber']) { throw new RuntimeException('PREPARE_PERMISSION_NOT_READY'); }
    foreach (['manage_options','install_plugins','activate_plugins','edit_users','promote_users','edit_posts','edit_pages','publish_posts','publish_pages'] as $cap) {
        if (current_user_can($cap)) { throw new RuntimeException('UNEXPECTED_EFFECTIVE_CAPABILITY'); }
    }
    foreach ([146 => 'en', 196 => 'es'] as $id => $locale) {
        if (current_user_can('edit_post', $id) || current_user_can('delete_post', $id)
            || is_wp_error(AlexCuadra\EditorialRead\get_project(['id' => $id, 'locale' => $locale]))) { throw new RuntimeException('PUBLIC_READ_OR_SOURCE_PERMISSION_CHANGED'); }
    }
    wp_set_current_user($operator);
    WP_CLI::line('PROPOSALS_SCOPE_RESULT=' . wp_json_encode(['userId' => 2, 'role' => 'subscriber', 'scope' => $scope,
        'prepareScopeGranted' => true, 'onlyExpectedDirectCapabilityAdded' => true, 'subscriberRoleUnchanged' => true,
        'otherActivePluginsUnchanged' => true, 'sourcesUnchanged' => true, 'projectCount' => $after['projectCount'],
        'sourceHash' => $after['sourceHash'], 'publicReadVerifiedEnEs' => true, 'sourceEditAdminDenied' => true,
        'proposalCreated' => false]));
} catch (Throwable $error) {
    wp_set_current_user($operator);
    if ($granted && $target) {
        $target->remove_cap($scope); clean_user_cache(2);
        WP_CLI::line('PROPOSALS_SCOPE_ROLLBACK=' . wp_json_encode(['removedNewScope' => empty(get_userdata(2)->caps[$scope]) && !user_can(2, $scope)]));
    }
    $reason = preg_match('/^[A-Z_]+$/D', $error->getMessage()) ? $error->getMessage() : 'SCOPE_PHASE_FAILED';
    WP_CLI::error($reason);
}
