<?php
use AlexCuadra\EditorialProposals as P;
if (getenv('AC_PROPOSALS_ACCEPTANCE') !== 'synthetic-v1' || !WP_CLI || wp_get_environment_type() !== 'local'
    || !in_array(DB_NAME, ['wordpress', 'wordpress_restore'], true)) { throw new RuntimeException('SYNTHETIC_ACCEPTANCE_REQUIRED'); }
$phase = $args[0] ?? ''; $cases = [];
$check = static function ($name, $ok) use (&$cases) { if (!$ok) { throw new RuntimeException('FAILED: ' . $name); } $cases[] = $name; };
global $wpdb;
$digest = static function () use ($wpdb) {
    $data = ['schemaVersion' => get_option('ac_proposals_storage_version')];
    foreach (['posts' => 'ID', 'postmeta' => 'meta_id', 'term_relationships' => 'object_id,term_taxonomy_id',
        'ac_proposal_owners' => 'owner_id', 'ac_proposal_requests' => 'owner_id,request_id'] as $suffix => $order) {
        $table = $wpdb->prefix . $suffix;
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY $order", ARRAY_A);
        if ($wpdb->last_error) { throw new RuntimeException('SNAPSHOT_QUERY_FAILED'); }
        $data[$suffix] = $rows;
    }
    foreach (['ac_proposal_owners', 'ac_proposal_requests'] as $suffix) {
        $table = $wpdb->prefix . $suffix;
        $schema = $wpdb->get_row("SHOW CREATE TABLE $table", ARRAY_A);
        if ($wpdb->last_error || !isset($schema['Create Table'])) { throw new RuntimeException('SCHEMA_SNAPSHOT_FAILED'); }
        $data['schema_' . $suffix] = $schema['Create Table'];
    }
    return hash('sha256', serialize($data));
};
if ($phase === 'before') {
    $check('source_database_only', DB_NAME === 'wordpress');
    $check('fresh_restore_fixture', get_option('proposals_acceptance_restore') === false);
    $id = wp_insert_post(['post_type' => 'project', 'post_status' => 'publish', 'post_title' => 'Synthetic archive source', 'post_content' => 'SOURCE_UNCHANGED'], true);
    $check('synthetic_source_created', !is_wp_error($id) && $id > 0);
    pll_set_post_language($id, 'en'); update_post_meta($id, 'project_tagline', 'ORIGINAL');
    $owner = wp_create_user('archive_owner', wp_generate_password(32));
    $check('synthetic_owner_created', !is_wp_error($owner));
    $user = new WP_User($owner); $user->set_role('subscriber');
    $user->add_cap('alexcuadra_read_public_projects'); $user->add_cap(P\CAPABILITY);
    wp_set_current_user($owner);
    $input = ['project_id' => $id, 'locale' => 'en', 'request_id' => bin2hex(random_bytes(16)),
        'patch' => ['body_html' => '<p>Archive café \\ "quotes"</p>', 'tagline' => 'PROPOSED']];
    $create = P\execute($input, 'create'); $check('create_for_archive', !is_wp_error($create));
    $updateInput = ['proposal_id' => $create['proposal_id'], 'locale' => 'en', 'request_id' => bin2hex(random_bytes(16)),
        'expected_version' => $create['version'], 'patch' => ['tagline' => 'UPDATED']];
    $update = P\execute($updateInput, 'update'); $check('update_for_archive', !is_wp_error($update));
    $state = ['owner' => $owner, 'project' => $id, 'input' => $input, 'create' => $create,
        'updateInput' => $updateInput, 'update' => $update, 'digest' => $digest()];
    update_option('proposals_acceptance_restore', $state, false);
    $check('source_not_changed_before_dump', get_post($id)->post_content === 'SOURCE_UNCHANGED' && get_post_meta($id, 'project_tagline', true) === 'ORIGINAL');
} elseif ($phase === 'after') {
    $check('restored_database_only', DB_NAME === 'wordpress_restore');
    $state = get_option('proposals_acceptance_restore'); $check('restore_state_present', is_array($state));
    $check('exact_rows_restored', $digest() === $state['digest']);
    wp_set_current_user($state['owner']);
    $usage = P\owner_usage($state['owner']);
    $check('create_receipt_replays_exactly', P\execute($state['input'], 'create') === $state['create']);
    $check('update_receipt_replays_exactly', P\execute($state['updateInput'], 'update') === $state['update']);
    $check('replay_does_not_consume_quota', P\owner_usage($state['owner']) === $usage);
    $check('replay_does_not_mutate_restored_rows', $digest() === $state['digest']);
    $head = P\execute(['proposal_id' => $state['create']['proposal_id'], 'locale' => 'en'], 'get');
    $check('head_is_updated_version', $head === $state['update']);
    $check('source_not_changed_after_restore', get_post($state['project'])->post_content === 'SOURCE_UNCHANGED'
        && get_post_meta($state['project'], 'project_tagline', true) === 'ORIGINAL');
    $user = new WP_User($state['owner']); $user->remove_cap(P\CAPABILITY); $user->remove_cap('alexcuadra_read_public_projects');
    wp_set_current_user(0); wp_set_current_user($state['owner']);
    $check('restored_owner_scope_revoked', is_wp_error(P\permission()));
} else { throw new RuntimeException('INVALID_PHASE'); }
WP_CLI::line('PROPOSALS_RESTORE_RESULT=' . wp_json_encode(['phase' => $phase, 'passed' => count($cases), 'cases' => $cases, 'syntheticOnly' => true]));
