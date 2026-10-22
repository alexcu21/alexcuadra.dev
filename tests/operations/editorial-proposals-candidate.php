<?php
// Standalone boundary checks, NOT a WordPress/database acceptance suite.
$scenario = $argv[1] ?? 'enabled';
$known = ['enabled', 'no-optin', 'wrong-optin', 'wrong-home', 'wrong-site', 'multisite', 'lab-active', 'no-storage', 'no-cli'];
if (!in_array($scenario, $known, true)) { exit(2); }
define('ABSPATH', '/synthetic/'); define('ARRAY_A', 'ARRAY_A');
define('WP_CLI', $scenario !== 'no-cli');
if ($scenario !== 'no-optin') { define('AC_EDITORIAL_PROPOSALS_SITE', $scenario === 'wrong-optin' ? 'https://other.test' : 'https://content.alexcuadra.dev'); }
$hooks = []; $abilities = []; $types = []; $checks = 0; $caps = []; $logged = false;
function home_url() { global $scenario; return $scenario === 'wrong-home' ? 'https://other.test' : 'https://content.alexcuadra.dev'; }
function site_url() { global $scenario; return $scenario === 'wrong-site' ? 'https://other.test' : 'https://content.alexcuadra.dev'; }
function is_multisite() { global $scenario; return $scenario === 'multisite'; }
function get_option($key, $default = false) {
    global $scenario;
    if ($key === 'active_plugins') { return $scenario === 'lab-active' ? ['alexcuadra-editorial-changes/alexcuadra-editorial-changes.php'] : []; }
    return $key === 'ac_proposals_storage_version' && $scenario !== 'no-storage' ? '0.1' : $default;
}
function add_action($name, $callback, $priority = 10) { global $hooks; $hooks[$name][] = $callback; }
function register_post_type($name, $args) { global $types; $types[$name] = $args; }
function wp_register_ability_category($name, $args) {}
function wp_register_ability($name, $args) { global $abilities; $abilities[$name] = $args; }
function is_user_logged_in() { global $logged; return $logged; }
function current_user_can($cap, ...$args) { global $caps; return in_array($cap, $caps, true); }
class WP_Error { public function __construct(public $code, public $message, public $data) {} }
function is_wp_error($value) { return $value instanceof WP_Error; }
function check($ok, $name) { global $checks; if (!$ok) { throw new RuntimeException($name); } $checks++; }
require __DIR__ . '/../../wordpress/plugins/alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php';
use AlexCuadra\EditorialProposals as P;
$closed = !in_array($scenario, ['enabled', 'no-storage', 'no-cli'], true);
if ($closed) {
    check($hooks === [], 'closed_guard_no_hooks');
} else {
    // No database object exists: loading and registration must not create or query tables.
    foreach (['init', 'wp_abilities_api_categories_init', 'wp_abilities_api_init'] as $hook) {
        foreach ($hooks[$hook] ?? [] as $callback) { $callback(); }
    }
    $expected = ['create-project-change', 'get-project-change', 'update-project-change', 'list-project-change-revisions', 'get-project-change-revision', 'restore-project-change-revision'];
    check(array_keys($abilities) === array_map(fn($s) => 'alexcuadra/' . $s, $expected), 'exact_six_abilities');
    foreach (['public', 'publicly_queryable', 'show_ui', 'show_in_rest', 'show_in_graphql'] as $flag) {
        check($types[P\TYPE][$flag] === false, 'private_cpt_' . $flag);
    }
    foreach ($abilities as $ability) {
        check($ability['meta']['public'] === false && $ability['meta']['show_in_rest'] === false, 'private_ability');
        check(is_wp_error(($ability['execute_callback'])([])), 'anonymous_executor_denied');
    }
    $logged = true; $caps = ['read', 'manage_options'];
    check(is_wp_error(P\permission()), 'admin_without_scopes_denied');
    $caps = ['read', 'alexcuadra_read_public_projects'];
    check(is_wp_error(P\permission()), 'read_only_scope_denied');
    $caps[] = 'alexcuadra_prepare_project_changes';
    check((P\permission() === true) === ($scenario !== 'no-storage'), 'storage_gate');
    if ($scenario !== 'no-storage') {
        check(P\execute([], 'apply')->code === 'ac_change_invalid_input', 'apply_not_implemented');
        check(P\execute([], 'recover')->code === 'ac_change_invalid_input', 'recover_not_implemented');
    }
    $input = ['project_id' => 146, 'locale' => 'en', 'patch' => ['tagline' => 'Proposed'], 'request_id' => str_repeat('a', 32)];
    check(P\validate($input, 'create') === true, 'valid_proposal');
    $input['patch']['post_status'] = 'publish';
    check(is_wp_error(P\validate($input, 'create')), 'source_status_patch_denied');
    unset($input['patch']['post_status']); $input['locale'] = 'fr';
    check(is_wp_error(P\validate($input, 'create')), 'unsupported_locale_denied');
    $dto = P\dto(1, ['project_id' => 146, 'locale' => 'en', 'version' => str_repeat('a', 64), 'base_version' => str_repeat('b', 64), 'patch' => ['tagline' => 'Proposed']]);
    check($dto['state'] === 'draft' && $dto['applied'] === false, 'draft_only_dto');
    $caps = [];
    check(is_wp_error(P\install_storage()), 'migration_denied_without_operator');
    if ($scenario === 'no-cli') {
        $caps = ['manage_options']; check(is_wp_error(P\install_storage()), 'migration_cli_only');
    }
    check(!function_exists('AlexCuadra\\EditorialProposals\\application_execute'), 'no_source_executor');
    check(!function_exists('AlexCuadra\\EditorialProposals\\review_approve'), 'no_approver');
}
echo json_encode(['scenario' => $scenario, 'checks' => $checks, 'passed' => true]) . PHP_EOL;
