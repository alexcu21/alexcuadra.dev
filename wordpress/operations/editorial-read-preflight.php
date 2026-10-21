<?php
/**
 * Read-only operator diagnostic. Not a plugin or an MCP ability.
 * wp eval-file /private/path/editorial-read-preflight.php USER_ID before|after
 * Never grants capabilities, installs plugins, or reads application passwords.
 */
if (!defined('WP_CLI') || !WP_CLI || !function_exists('get_userdata')) {
    throw new RuntimeException('WP_CLI_WORDPRESS_REQUIRED');
}
$input = $args ?? [];
if (count($input) !== 2 || !preg_match('/^[1-9][0-9]*$/', $input[0])
    || !in_array($input[1], ['before', 'after'], true)) {
    WP_CLI::error('EXPECTED_USER_ID_AND_PHASE_BEFORE_OR_AFTER');
}
$site = rtrim(home_url(), '/');
if (!in_array($site, ['https://content.alexcuadra.dev', 'http://wordpress'], true) || is_multisite()) {
    WP_CLI::error('EXPECTED_SINGLE_SITE_OR_ISOLATED_LAB');
}
$target = get_userdata((int) $input[0]);
if (!$target) { WP_CLI::error('TARGET_USER_NOT_FOUND'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
global $wp_version;
$scope = 'alexcuadra_read_public_projects';
$extension = 'alexcuadra-editorial-read/alexcuadra-editorial-read.php';
$previousUser = get_current_user_id();
try {
    // Match current-user-dependent filters without changing stored roles/caps.
    wp_set_current_user($target->ID);
    $project = get_post_type_object('project');
    $capNames = ['read', $scope, 'manage_options', 'install_plugins', 'activate_plugins',
        'edit_users', 'promote_users', 'delete_users', 'edit_posts', 'edit_pages', 'publish_posts', 'publish_pages'];
    if ($project) {
        foreach (['edit_posts', 'edit_others_posts', 'publish_posts', 'delete_posts', 'create_posts'] as $key) {
            if (isset($project->cap->$key)) { $capNames[] = $project->cap->$key; }
        }
    }
    $effective = [];
    foreach (array_unique($capNames) as $name) { $effective[$name] = current_user_can($name); }
    $unexpected = array_keys(array_filter($target->allcaps));
    // Core subscriber includes the historical level_0 capability.
    $unexpected = array_values(array_diff($unexpected, ['subscriber', 'read', 'level_0', $scope]));
    sort($unexpected);
    $pluginVersions = [];
    foreach (get_plugins() as $path => $data) {
        foreach (['polylang/', 'mcp-adapter/', 'alexcuadra-editorial-read/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $pluginVersions[$prefix] = ['version' => $data['Version'], 'active' => is_plugin_active($path)];
            }
        }
    }
    $before = $input[1] === 'before';
    $polylangVersion = $pluginVersions['polylang/']['version'] ?? null;
    $testedRuntime = $wp_version === '7.1.3' && (
        ($polylangVersion === '3.8.11' && str_starts_with(PHP_VERSION, '8.5.'))
        || ($polylangVersion === '3.9.0' && str_starts_with(PHP_VERSION, '8.2.'))
    );
    $forbidden = $effective;
    unset($forbidden['read'], $forbidden[$scope]);
    $subscriberRole = get_role('subscriber');
    $checks = [
        'subscriber_only' => $target->roles === ['subscriber'],
        'read_allowed' => $effective['read'],
        'scope_matches_phase' => $effective[$scope] === !$before,
        'subscriber_role_has_no_editorial_scope' => $subscriberRole && !$subscriberRole->has_cap($scope),
        'no_checked_edit_or_admin_capabilities' => !in_array(true, $forbidden, true),
        'no_unexpected_stored_grants' => $unexpected === [],
        'supported_wordpress_php' => version_compare($wp_version, '6.9', '>=') && PHP_VERSION_ID >= 80000,
        'abilities_api_available' => function_exists('wp_get_ability') && function_exists('wp_register_ability'),
        'application_passwords_available' => wp_is_application_passwords_available_for_user($target),
        'project_is_public' => $project && $project->public && is_post_type_viewable($project),
        'project_is_translatable' => function_exists('pll_is_translated_post_type') && pll_is_translated_post_type('project'),
        'tested_polylang_active' => ($pluginVersions['polylang/']['active'] ?? false)
            && in_array($polylangVersion, ['3.8.11', '3.9.0'], true),
        'tested_runtime_combination' => $testedRuntime,
        'tested_adapter_active' => ($pluginVersions['mcp-adapter/']['active'] ?? false)
            && ($pluginVersions['mcp-adapter/']['version'] ?? null) === '0.7.1',
        'extension_activation_matches_phase' => is_plugin_active($extension) === !$before,
        'extension_version_if_installed' => !isset($pluginVersions['alexcuadra-editorial-read/'])
            || $pluginVersions['alexcuadra-editorial-read/']['version'] === '0.1.0',
    ];
    $report = ['site' => $site, 'phase' => $input[1], 'userId' => $target->ID, 'roles' => $target->roles,
        'wordpress' => $wp_version, 'php' => PHP_VERSION, 'capabilities' => $effective,
        'unexpectedStoredGrants' => $unexpected, 'dependencies' => $pluginVersions,
        'checks' => $checks, 'technicalChecksPassed' => !in_array(false, $checks, true),
        'manualAclReviewRequired' => true, 'installationAuthorized' => false];
} finally {
    wp_set_current_user($previousUser);
}
WP_CLI::line('EDITORIAL_PREFLIGHT_RESULT=' . wp_json_encode($report));
if (!$report['technicalChecksPassed']) { WP_CLI::halt(1); }
