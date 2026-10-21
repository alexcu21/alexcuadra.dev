<?php
use AlexCuadra\EditorialChanges as Changes;
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { throw new RuntimeException('ISOLATED_HISTORY_LAB_REQUIRED'); }
global $wpdb;
$cases = []; $users = [];
$report = ['environment' => 'isolated-proposal-history', 'sharedCmsModified' => false, 'emailSent' => false];
$check = static function ($name, $ok) use (&$cases) { if (!$ok) { throw new RuntimeException('FAILED: ' . $name); } $cases[] = $name; };
$status = static function ($r, $code) { return is_wp_error($r) && (($r->get_error_data()['status'] ?? null) === $code
    || (['ability_invalid_input' => 400, 'ability_invalid_permissions' => 403][$r->get_error_code()] ?? null) === $code); };
$names = ['create' => 'create-project-change', 'get' => 'get-project-change', 'update' => 'update-project-change',
    'history' => 'list-project-change-revisions', 'revision' => 'get-project-change-revision', 'restore' => 'restore-project-change-revision'];
$call = static function ($op, $input) use ($names) { if (in_array($op, ['create','update','restore'], true)) { $input['request_id'] = $input['request_id'] ?? bin2hex(random_bytes(16)); } return wp_get_ability('alexcuadra/' . $names[$op])->execute($input); };
$switch = static function ($user) { wp_set_current_user(0); wp_set_current_user($user); };
$raw = static function ($id) use ($wpdb) { return $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE post_id=%d AND meta_key=%s", $id, Changes\PAYLOAD)); };
try {
    $en = wp_insert_post(['post_type' => 'project', 'post_status' => 'publish', 'post_author' => 1, 'post_title' => 'History source', 'post_content' => 'PRIVATE_SOURCE_HISTORY']);
    pll_set_post_language($en, 'en'); update_post_meta($en, 'project_tagline', 'Original source');
    $snapshot = static function () use ($wpdb, $en) { return hash('sha256', serialize([
        $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d", $en), ARRAY_A),
        $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id=%d ORDER BY meta_id", $en), ARRAY_A),
        $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->term_relationships WHERE object_id=%d ORDER BY term_taxonomy_id", $en), ARRAY_A)])); };
    $before = $snapshot();
    foreach (['owner', 'other'] as $name) {
        $id = wp_create_user('history' . bin2hex(random_bytes(6)), wp_generate_password(32));
        if (is_wp_error($id)) { throw new RuntimeException('USER_SETUP_FAILED'); }
        $u = new WP_User($id); $u->set_role('subscriber'); $u->add_cap('alexcuadra_read_public_projects'); $u->add_cap(Changes\CAPABILITY); $users[$name] = $u;
    }
    $switch($users['owner']->ID);
    $create = static function ($patch) use ($call, $en) { return $call('create', ['project_id' => $en, 'locale' => 'en', 'patch' => $patch]); };
    $first = $create(['tagline' => 'First café "quoted" \\ text']); $pid = $first['proposal_id'];
    $base = ['proposal_id' => $pid, 'locale' => 'en'];
    $list = $base + ['expected_version' => $first['version'], 'page' => 1, 'per_page' => 1];
    $h = $call('history', $list);
    $check('creation_records_first_revision', !is_wp_error($h) && $h['total'] === 1 && $h['next_page'] === null && $h['revisions'][0]['operation'] === 'create');
    $check('history_metadata_never_returns_patch_or_source', !str_contains(wp_json_encode($h), 'First café') && !str_contains(wp_json_encode($h), 'PRIVATE_SOURCE'));
    $r = $call('revision', $base + ['revision_version' => $first['version']]);
    $check('revision_returns_exact_normalized_snapshot', !is_wp_error($r) && $r['revision']['patch'] === $first['patch'] && !$r['applied']);
    $second = $call('update', $base + ['expected_version' => $first['version'], 'patch' => ['body_html' => '<h2 onclick="bad()">Safe</h2><script>bad()</script>', 'tagline' => 'Second']]);
    $check('update_appends_normalized_snapshot', !is_wp_error($second) && !str_contains($second['patch']['body_html'], '<script'));
    $check('history_pagination_rejects_changed_head', $status($call('history', $list), 409));
    $list['expected_version'] = $second['version']; $h = $call('history', $list);
    $check('history_first_page_has_stable_parent_and_next', $h['total'] === 2 && $h['next_page'] === 2 && $h['revisions'][0]['parent_version'] === null);
    $list['page'] = 2; $h = $call('history', $list);
    $check('history_second_page_links_to_first_version', $h['next_page'] === null && $h['revisions'][0]['parent_version'] === $first['version']);
    $list['page'] = 3; $h = $call('history', $list);
    $check('history_page_after_end_is_empty', $h['revisions'] === [] && $h['next_page'] === null);
    $restore = $base + ['expected_version' => $second['version'], 'revision_version' => $first['version']];
    $restored = $call('restore', $restore);
    $check('restore_creates_fresh_head_without_reusing_old_token', !is_wp_error($restored) && !in_array($restored['version'], [$first['version'], $second['version']], true));
    $check('restore_replaces_full_snapshot_removing_later_fields', $restored['patch'] === $first['patch'] && !isset($restored['patch']['body_html']));
    $list = $base + ['expected_version' => $restored['version'], 'page' => 1, 'per_page' => 10]; $h = $call('history', $list);
    $check('restore_preserves_old_versions_and_records_provenance', $h['total'] === 3 && $h['revisions'][2]['operation'] === 'restore'
        && $h['revisions'][2]['restored_from'] === $first['version'] && $h['revisions'][2]['parent_version'] === $second['version']);
    $r = $call('revision', $base + ['revision_version' => $second['version']]);
    $check('old_snapshot_survives_restore', $r['revision']['patch'] === $second['patch']);
    $saved = $raw($pid);
    $check('stale_restore_is_conflict', $status($call('restore', $restore), 409));
    $check('stale_restore_does_not_append_or_mutate', $raw($pid) === $saved);
    $restore['expected_version'] = $restored['version']; $restore['revision_version'] = str_repeat('0', 64);
    $check('unknown_revision_is_not_found', $status($call('restore', $restore), 404));
    $foreign = $create(['tagline' => 'Foreign proposal snapshot']);
    $check('revision_from_other_proposal_cannot_be_restored', $status($call('restore', $base + ['expected_version' => $restored['version'], 'revision_version' => $foreign['version']]), 404));
    foreach (['history', 'revision', 'restore'] as $op) {
        $input = $op === 'history' ? $list : $base + ['revision_version' => $first['version']];
        if ($op === 'restore') { $input['expected_version'] = $restored['version']; }
        $switch($users['other']->ID); $check('other_user_denied_' . $op, $status($call($op, $input), 404));
        $switch($users['owner']->ID); $wrong = $input; $wrong['locale'] = 'es';
        $check('wrong_language_denied_' . $op, $status($call($op, $wrong), 404));
        $bad = $input; $bad['post_status'] = 'publish';
        $check('extra_field_denied_' . $op, $status($call($op, $bad), 400) && $status(Changes\execute($bad, $op), 400));
        $users['owner']->remove_cap(Changes\CAPABILITY); $switch($users['owner']->ID);
        $check('revoked_scope_denied_' . $op, $status($call($op, $input), 403));
        $users['owner']->add_cap(Changes\CAPABILITY); $switch($users['owner']->ID);
    }
    foreach ([['per_page' => 11], ['page' => 0], ['page' => '1']] as $bad) {
        $check('invalid_pagination_' . count($cases), $status($call('history', array_merge($list, $bad)), 400));
    }
    // Fail the real CAS and prove that a failed write cannot leave a detached history event.
    $fail = static function ($query) { return str_contains($query, 'SET m.meta_value=') ? 'UPDATE nonexistent_history_failure SET x=1' : $query; };
    $suppress = $wpdb->suppress_errors(true); add_filter('query', $fail);
    try { $failed = $call('restore', $base + ['expected_version' => $restored['version'], 'revision_version' => $first['version']]); }
    finally { remove_filter('query', $fail); $wpdb->suppress_errors($suppress); }
    $check('database_failure_does_not_split_head_and_history', $status($failed, 503) && $raw($pid) === $saved);
    // A 0.1 record has no recoverable past: expose its existing head as an explicit undated baseline.
    $legacy = $create(['tagline' => 'Surviving legacy version']); $legacyId = $legacy['proposal_id'];
    $legacyPayload = json_decode($raw($legacyId), true); unset($legacyPayload['history']);
    update_post_meta($legacyId, Changes\PAYLOAD, wp_slash(wp_json_encode($legacyPayload))); $legacyRaw = $raw($legacyId);
    $lh = $call('history', ['proposal_id' => $legacyId, 'locale' => 'en', 'expected_version' => $legacy['version'], 'page' => 1, 'per_page' => 10]);
    $check('legacy_read_is_explicit_baseline_without_fabricated_date', $lh['total'] === 1 && $lh['revisions'][0]['operation'] === 'baseline' && $lh['revisions'][0]['recorded_at'] === null);
    $check('legacy_read_does_not_write_migration', $raw($legacyId) === $legacyRaw);
    $legacyUpdated = $call('update', ['proposal_id' => $legacyId, 'locale' => 'en', 'expected_version' => $legacy['version'], 'patch' => ['tagline' => 'New legacy version']]);
    $lp = json_decode($raw($legacyId), true);
    $check('legacy_upgrade_commits_baseline_and_update_together', !is_wp_error($legacyUpdated) && count($lp['history']) === 2 && $lp['history'][0]['version'] === $legacy['version']);
    // Corrupted journal/head consistency is rejected, never used as a recovery source.
    $broken = json_decode($saved, true); $broken['history'][1]['parent_version'] = str_repeat('0', 64);
    update_post_meta($pid, Changes\PAYLOAD, wp_slash(wp_json_encode($broken)));
    $check('broken_revision_chain_fails_closed', $status($call('history', $list), 503));
    $broken = json_decode($saved, true); $broken['patch']['tagline'] = 'Unjournaled head';
    update_post_meta($pid, Changes\PAYLOAD, wp_slash(wp_json_encode($broken)));
    $check('head_without_matching_revision_fails_closed', $status($call('get', $base), 503));
    update_post_meta($pid, Changes\PAYLOAD, wp_slash($saved));
    $bounded = $create(['tagline' => '0']); $boundedId = $bounded['proposal_id'];
    for ($i = 1; $i < Changes\MAX_REVISIONS; $i++) {
        $bounded = $call('update', ['proposal_id' => $boundedId, 'locale' => 'en', 'expected_version' => $bounded['version'], 'patch' => ['tagline' => (string) $i]]);
        if (is_wp_error($bounded)) { throw new RuntimeException('HISTORY_FILL_FAILED'); }
    }
    $full = $raw($boundedId);
    $tooMany = $call('update', ['proposal_id' => $boundedId, 'locale' => 'en', 'expected_version' => $bounded['version'], 'patch' => ['tagline' => 'Overflow']]);
    $check('revision_bound_rejects_write_without_discarding_history', $status($tooMany, 409) && $tooMany->get_error_message() === 'history_full' && $raw($boundedId) === $full);
    // Large valid snapshots hit the encoded-byte bound before the revision count bound.
    $large = $create(['body_html' => str_repeat('界', 40000)]); $largeId = $large['proposal_id']; $byteBound = false;
    for ($i = 1; $i < Changes\MAX_REVISIONS; $i++) {
        $old = $raw($largeId);
        $next = $call('update', ['proposal_id' => $largeId, 'locale' => 'en', 'expected_version' => $large['version'], 'patch' => ['tagline' => (string) $i]]);
        if (is_wp_error($next)) { $byteBound = $next->get_error_message() === 'history_full' && $raw($largeId) === $old && $i < Changes\MAX_REVISIONS - 1; break; }
        $large = $next;
    }
    $check('encoded_byte_bound_rejects_without_partial_commit', $byteBound);
    $check('history_and_recovery_preserve_all_source_records', $snapshot() === $before);
    update_post_meta($en, 'project_tagline', 'External drift');
    $check('source_drift_blocks_recovery', $status($call('restore', $base + ['expected_version' => $restored['version'], 'revision_version' => $first['version']]), 409));
    update_post_meta($en, 'project_tagline', 'Original source');
    wp_update_post(['ID' => $en, 'post_status' => 'draft']);
    $check('withdrawn_source_blocks_history', $status($call('history', $list), 404));
    wp_update_post(['ID' => $en, 'post_status' => 'publish']);
    // Exact source hash was checked before the harness's intentional drift/status mutations.
    $check('source_body_and_tagline_never_replaced', get_post($en)->post_content === 'PRIVATE_SOURCE_HISTORY' && get_post_meta($en, 'project_tagline', true) === 'Original source');
    $report['sourceHashBeforeFixtureDrift'] = $before;
} catch (Throwable $e) {
    $report['failure'] = preg_match('/^(FAILED: [a-z0-9_]+|[A-Z_]+)$/', $e->getMessage()) ? $e->getMessage() : 'HISTORY_LAB_FAILED';
    $report['diagnostic'] = ['class' => get_class($e), 'line' => $e->getLine()];
} finally {
    foreach ($users as $user) { $user->remove_cap(Changes\CAPABILITY); $user->remove_cap('alexcuadra_read_public_projects'); }
    $switch(0); $clean = true;
    foreach ($users as $user) { $u = new WP_User($user->ID); $clean = $clean && !$u->has_cap(Changes\CAPABILITY) && !$u->has_cap('alexcuadra_read_public_projects'); }
    $check('history_synthetic_scopes_removed', $clean);
}
$report['passed'] = count($cases); $report['failed'] = isset($report['failure']) ? 1 : 0; $report['cases'] = $cases;
WP_CLI::line('EDITORIAL_CHANGES_HISTORY_RESULT=' . wp_json_encode($report));
if ($report['failed']) { WP_CLI::halt(1); }
