<?php
// Synthetic-only harness; the operator diagnostic itself never writes data.
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') {
    throw new RuntimeException('ISOLATED_PREFLIGHT_LAB_REQUIRED');
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$scenario = $args[0] ?? '';
if (!in_array($scenario, ['before', 'after', 'extra-cap', 'wrong-phase', 'missing-user'], true)) {
    throw new RuntimeException('UNKNOWN_PREFLIGHT_SCENARIO');
}
PLL()->options->set('post_types', ['project']); PLL()->options->save_all();
$fixtureId = wp_create_user('preflight' . bin2hex(random_bytes(6)), wp_generate_password(32));
if (is_wp_error($fixtureId)) { throw new RuntimeException('PREFLIGHT_FIXTURE_FAILED'); }
$fixture = new WP_User($fixtureId); $fixture->set_role('subscriber');
$readPlugin = 'alexcuadra-editorial-read/alexcuadra-editorial-read.php';
if ($scenario === 'before') { deactivate_plugins($readPlugin, true); }
else { activate_plugin($readPlugin, '', false, true); }
if (in_array($scenario, ['after', 'extra-cap'], true)) { $fixture->add_cap('alexcuadra_read_public_projects'); }
if ($scenario === 'extra-cap') { $fixture->add_cap('manage_options'); }
$phase = $scenario === 'before' ? 'before' : 'after';
$args = [(string) ($scenario === 'missing-user' ? 2147483647 : $fixtureId), $phase];
// Check all database rows before/after the diagnostic, including user/option state.
$stateHash = static function () {
    global $wpdb;
    $tables = [$wpdb->users => 'ID', $wpdb->usermeta => 'umeta_id', $wpdb->posts => 'ID',
        $wpdb->postmeta => 'meta_id', $wpdb->options => 'option_id', $wpdb->term_relationships => 'object_id,term_taxonomy_id'];
    $rows = [];
    foreach ($tables as $table => $order) { $rows[] = $wpdb->get_results("SELECT * FROM $table ORDER BY $order", ARRAY_A); }
    return hash('sha256', serialize($rows));
};
$initialState = $stateHash();
$initialUser = get_current_user_id();
// halt() ends negative cases; register a shutdown check for both success/failure.
register_shutdown_function(static function () use ($stateHash, $initialState, $initialUser, $fixtureId) {
    $readOnly = $stateHash() === $initialState;
    $restored = get_current_user_id() === $initialUser;
    WP_CLI::line('PREFLIGHT_INVARIANTS=' . wp_json_encode(['databaseUnchanged' => $readOnly, 'currentUserRestored' => $restored]));
    // Cleanup only the synthetic user's extra grants and restore the lab plugin.
    $cleanup = new WP_User($fixtureId);
    $cleanup->remove_cap('alexcuadra_read_public_projects'); $cleanup->remove_cap('manage_options');
    activate_plugin('alexcuadra-editorial-read/alexcuadra-editorial-read.php', '', false, true);
    if (!$readOnly || !$restored) { exit(3); }
});
require '/read-operations/editorial-read-preflight.php';
