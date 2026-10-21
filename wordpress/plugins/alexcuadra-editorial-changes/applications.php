<?php
namespace AlexCuadra\EditorialChanges;
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { return; }

// One complete recovery review, approval, two receipts and event overhead, charged before apply.
const RECOVERY_BYTES = MAX_PAYLOAD_BYTES + 16384;

function application_load_proposal($proposal) {
    global $wpdb;
    $table = $wpdb->prefix . 'ac_change_applications';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE proposal_id=%d AND owner_id=%d", $proposal, get_current_user_id()), ARRAY_A);
    if ($wpdb->last_error) { return error('storage_unavailable', 503); }
    return $row ? application_decode($row) : null;
}
function application_decode($row) {
    $p = json_decode($row['payload'], true);
    $keys = ['format', 'application_id', 'owner_id', 'proposal_id', 'project_id', 'locale', 'review_id', 'proposal_version', 'before_version', 'after_version', 'schema_version', 'state', 'recovery_review_id', 'events'];
    if (!is_array($p) || array_keys($p) !== $keys || $p['format'] !== 'source-application-v1'
        || $p['application_id'] !== (int) $row['application_id'] || $p['owner_id'] !== (int) $row['owner_id'] || $p['proposal_id'] !== (int) $row['proposal_id']
        || !in_array($p['state'], ['applied', 'recovered'], true) || !is_array($p['events'])
        || count($p['events']) !== ($p['state'] === 'applied' ? 1 : 2)) { return error('application_unavailable', 503); }
    foreach (['application_id', 'owner_id', 'proposal_id', 'project_id', 'review_id'] as $key) {
        if (!is_int($p[$key]) || $p[$key] < 1) { return error('application_unavailable', 503); }
    }
    foreach (['proposal_version', 'before_version', 'after_version', 'schema_version'] as $key) {
        if (!is_string($p[$key]) || !preg_match('/^[a-f0-9]{64}$/D', $p[$key])) { return error('application_unavailable', 503); }
    }
    if (!in_array($p['locale'], ['en', 'es'], true) || ($p['recovery_review_id'] !== null && (!is_int($p['recovery_review_id']) || $p['recovery_review_id'] < 1))) { return error('application_unavailable', 503); }
    foreach ($p['events'] as $i => $event) {
        if (!is_array($event) || array_keys($event) !== ['version', 'operation', 'review_id', 'approval_id', 'source_version', 'recorded_at']
            || $event['operation'] !== ($i === 0 ? 'apply' : 'recover') || !preg_match('/^[a-f0-9]{64}$/D', $event['version'] ?? '')
            || !preg_match('/^[a-f0-9]{64}$/D', $event['source_version'] ?? '') || !is_int($event['review_id']) || $event['review_id'] < 1
            || !is_int($event['approval_id']) || $event['approval_id'] < 1 || !is_string($event['recorded_at'])
            || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $event['recorded_at'])) { return error('application_unavailable', 503); }
    }
    return ['payload' => $p, 'row' => $row];
}
function application_state($proposal) {
    $a = application_load_proposal($proposal);
    return is_wp_error($a) ? $a : ($a ? $a['payload']['state'] : 'draft');
}
function application_read($id, $locale, $locking = false) {
    global $wpdb;
    $table = $wpdb->prefix . 'ac_change_applications';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE application_id=%d AND owner_id=%d" . ($locking ? ' FOR UPDATE' : ''), $id, get_current_user_id()), ARRAY_A);
    if ($wpdb->last_error) { return error('storage_unavailable', 503); }
    if (!$row) { return error('not_found', 404); }
    $a = application_decode($row); if (is_wp_error($a)) { return $a; }
    if ($a['payload']['locale'] !== $locale) { return error('not_found', 404); }
    $owned = load_owned($a['payload']['proposal_id'], $locale, false); if (is_wp_error($owned)) { return $owned; }
    return $a;
}
function application_review_count($owner) {
    global $wpdb;
    $reviews = $wpdb->prefix . 'ac_change_reviews'; $apps = $wpdb->prefix . 'ac_change_applications';
    return $wpdb->get_var($wpdb->prepare("SELECT (SELECT COUNT(*) FROM $reviews WHERE owner_id=%d)
        + (SELECT COALESCE(SUM(reserved_reviews),0) FROM $apps WHERE owner_id=%d)", $owner, $owner));
}
function application_permission($operation) {
    $allowed = permission(); if (is_wp_error($allowed)) { return $allowed; }
    return current_user_can($operation === 'apply' ? APPLY_CAPABILITY : RECOVER_CAPABILITY) ? true : error('forbidden', 403);
}
function approval_read($stored, $locking = false) {
    global $wpdb;
    $d = $stored['document']; $table = $wpdb->prefix . 'ac_change_approvals';
    $row = $wpdb->get_row($wpdb->prepare("SELECT approval_id,payload FROM $table WHERE review_id=%d AND owner_id=%d" . ($locking ? ' FOR UPDATE' : ''), $d['review_id'], $d['owner_id']), ARRAY_A);
    if ($wpdb->last_error) { return error('storage_unavailable', 503); }
    if (!$row) { return null; }
    $a = json_decode($row['payload'], true);
    $expected = ['format' => 'source-approval-v1', 'review_id' => $d['review_id'], 'review_hash' => $stored['review_hash'], 'executor_id' => $d['owner_id'],
        'project_id' => $d['project_id'], 'locale' => $d['locale'], 'operation' => $d['operation'], 'source_version' => $d['source_version'],
        'schema_version' => $d['schema_version'], 'actor_id' => $a['actor_id'] ?? null, 'approved_at' => $a['approved_at'] ?? null,
        'expires_at' => $d['expires_at'], 'consumed' => $a['consumed'] ?? null];
    if ($a !== $expected || !is_bool($a['consumed']) || !is_int($a['actor_id']) || $a['actor_id'] < 1 || $a['actor_id'] === $d['owner_id']
        || !is_int($a['approved_at']) || $a['approved_at'] < $d['created_at'] || $a['approved_at'] >= $d['expires_at']) { return error('review_unavailable', 503); }
    return ['approval_id' => (int) $row['approval_id'], 'payload' => $a];
}
function application_lock_proposal($id) {
    global $wpdb;
    $row = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE ID=%d AND post_author=%d AND post_type=%s FOR UPDATE", $id, get_current_user_id(), TYPE));
    if ($wpdb->last_error) { return error('storage_unavailable', 503); }
    if (!$row) { return error('not_found', 404); }
    $wpdb->get_results($wpdb->prepare("SELECT meta_id FROM $wpdb->postmeta WHERE post_id=%d AND meta_key=%s FOR UPDATE", $id, PAYLOAD));
    if ($wpdb->last_error) { return error('storage_unavailable', 503); }
    review_clear_cache($id); return true;
}
function recovery_current($stored, $locking = false) {
    $d = $stored['document'];
    $app = application_read($d['application_id'], $d['locale'], $locking); if (is_wp_error($app)) { return $app; }
    $p = $app['payload'];
    if ($p['state'] !== 'applied' || $p['recovery_review_id'] !== $d['review_id'] || $p['project_id'] !== $d['project_id']
        || $p['proposal_id'] !== $d['proposal_id'] || $p['proposal_version'] !== $d['proposal_version']) { return error('application_changed', 409); }
    $original = review_read($p['review_id'], get_current_user_id(), $d['locale']); if (is_wp_error($original)) { return $original; }
    $schema = review_schema(); if (is_wp_error($schema)) { return $schema; }
    if ($schema !== $d['schema_version']) { return error('schema_changed', 409); }
    $patch = array_fill_keys(array_keys($d['before']), '');
    $source = review_source($d['project_id'], $d['locale'], $patch, $locking); if (is_wp_error($source)) { return $source; }
    if ($source['version'] !== $p['after_version'] || $source['version'] !== $d['source_version']
        || $source['before'] !== $d['before'] || $d['after'] !== $original['document']['before']) { return error('source_changed', 409); }
    return true;
}
function recovery_spend($id, $bytes, $requests = 0, $reviews = 0, $owner = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'ac_change_applications';
    $changed = $wpdb->query($wpdb->prepare("UPDATE $table SET reserved_bytes=reserved_bytes-%d,reserved_requests=reserved_requests-%d,
        reserved_reviews=reserved_reviews-%d WHERE application_id=%d AND owner_id=%d AND reserved_bytes>=%d AND reserved_requests>=%d AND reserved_reviews>=%d",
        $bytes, $requests, $reviews, $id, $owner ?? get_current_user_id(), $bytes, $requests, $reviews));
    return $changed === 1 ? true : error($changed === false ? 'storage_unavailable' : 'recovery_capacity', $changed === false ? 503 : 409);
}
function application_write($app, $reserve = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'ac_change_applications'; $p = $app['payload'];
    $row = $app['row']; $reserve = $reserve ?? [(int) $row['reserved_bytes'], (int) $row['reserved_requests'], (int) $row['reserved_reviews']];
    $n = $wpdb->query($wpdb->prepare("UPDATE $table SET payload=%s,reserved_bytes=%d,reserved_requests=%d,reserved_reviews=%d
        WHERE application_id=%d AND owner_id=%d AND BINARY payload=BINARY %s", wp_json_encode($p), $reserve[0], $reserve[1], $reserve[2],
        $p['application_id'], $p['owner_id'], $row['payload']));
    return $n === 1 ? true : error($n === false ? 'storage_unavailable' : 'application_changed', $n === false ? 503 : 409);
}
function application_receipt($input, $kind) {
    global $wpdb;
    $table = $wpdb->prefix . 'ac_change_requests';
    $hash = hash('sha256', wp_json_encode([$kind, canonical($input)]));
    $row = $wpdb->get_row($wpdb->prepare("SELECT request_hash,response FROM $table WHERE owner_id=%d AND request_id=%s FOR UPDATE", get_current_user_id(), $input['request_id']), ARRAY_A);
    if ($wpdb->last_error) { return error('storage_unavailable', 503); }
    if (!$row) { return null; }
    if (!hash_equals($row['request_hash'], $hash)) { return error('request_reused', 409); }
    $r = json_decode($row['response'], true);
    if (!is_array($r) || array_keys($r) !== ['receipt_type', 'result'] || $r['receipt_type'] !== 'source-event-v1' || !is_array($r['result'])) { return error('application_unavailable', 503); }
    $dto = $r['result'];
    $app = application_read($dto['application_id'], $input['locale']); if (is_wp_error($app)) { return $app; }
    if ($kind === 'prepare-project-recovery') {
        $stored = review_read($dto['review_id'], get_current_user_id(), $input['locale']); if (is_wp_error($stored)) { return $stored; }
        if ($stored['document']['application_id'] !== $dto['application_id'] || $stored['review_hash'] !== $dto['review_hash']) { return error('application_unavailable', 503); }
    } else {
        $found = false;
        foreach ($app['payload']['events'] as $event) { if (application_event_dto($app['payload'], $event) === $dto) { $found = true; } }
        if (!$found) { return error('application_unavailable', 503); }
    }
    return $dto;
}
function application_save_receipt($input, $kind, $proposal, $result) {
    global $wpdb;
    $table = $wpdb->prefix . 'ac_change_requests';
    $response = wp_json_encode(['receipt_type' => 'source-event-v1', 'result' => $result]);
    $n = $wpdb->query($wpdb->prepare("INSERT INTO $table (owner_id,request_id,request_hash,proposal_id,response) VALUES (%d,%s,%s,%d,%s)",
        get_current_user_id(), $input['request_id'], hash('sha256', wp_json_encode([$kind, canonical($input)])), $proposal, $response));
    return $n === 1 ? strlen($response) : error('storage_unavailable', 503);
}
function application_event_dto($p, $event) {
    return ['application_id' => $p['application_id'], 'proposal_id' => $p['proposal_id'], 'project_id' => $p['project_id'], 'locale' => $p['locale'],
        'review_id' => $event['review_id'], 'version' => $event['version'], 'source_version' => $event['source_version'],
        'state' => $event['operation'] === 'apply' ? 'applied' : 'recovered', 'applied' => true];
}
function application_validate($input, $kind) {
    $keys = $kind === 'prepare-project-recovery' ? ['application_id', 'locale', 'expected_source_version', 'request_id']
        : ['review_id', 'locale', 'expected_review_hash', 'approval_id', 'request_id'];
    if (!is_array($input) || array_diff(array_keys($input), $keys) || array_diff($keys, array_keys($input))
        || !in_array($input['locale'], ['en', 'es'], true)) { return error('invalid_input', 400); }
    foreach ($keys as $key) {
        if (str_ends_with($key, '_id') && $key !== 'request_id' && (!is_int($input[$key]) || $input[$key] < 1)) { return error('invalid_input', 400); }
        if (in_array($key, ['request_id', 'expected_review_hash', 'expected_source_version'], true)
            && (!is_string($input[$key]) || !preg_match('/^[a-f0-9]{' . ($key === 'request_id' ? 32 : 64) . '}$/D', $input[$key]))) { return error('invalid_input', 400); }
    }
    return true;
}
function recovery_prepare($input) {
    global $wpdb;
    $allowed = application_permission('recover'); if (is_wp_error($allowed)) { return $allowed; }
    $valid = application_validate($input, 'prepare-project-recovery'); if (is_wp_error($valid)) { return $valid; }
    return review_transaction(get_current_user_id(), static function () use ($wpdb, $input) {
        $allowed = application_permission('recover'); if (is_wp_error($allowed)) { return $allowed; }
        $receipt = application_receipt($input, 'prepare-project-recovery'); if ($receipt !== null) { return $receipt; }
        $app = application_read($input['application_id'], $input['locale']); if (is_wp_error($app)) { return $app; }
        $p = $app['payload'];
        $locked = application_lock_proposal($p['proposal_id']); if (is_wp_error($locked)) { return $locked; }
        $app = application_read($input['application_id'], $input['locale'], true); if (is_wp_error($app)) { return $app; }
        $p = $app['payload'];
        if ($p['state'] !== 'applied') { return error('application_changed', 409); }
        if ($p['recovery_review_id'] !== null) {
            $prior = review_read($p['recovery_review_id'], get_current_user_id(), $input['locale']); if (is_wp_error($prior)) { return $prior; }
            if (time() < $prior['document']['expires_at']) { return error('application_changed', 409); }
            // The first attempt is reserved by apply. Replacing an expired retained review
            // needs fresh capacity; old snapshots/approvals are never overwritten or pruned.
            $usage = owner_usage(get_current_user_id()); if (is_wp_error($usage)) { return $usage; }
            $count = application_review_count(get_current_user_id());
            if ($count === null || $wpdb->last_error) { return error('storage_unavailable', 503); }
            if ((int) $count + 1 > MAX_OWNER_REVIEWS || $usage['requests'] + 2 - (int) $app['row']['reserved_requests'] > MAX_OWNER_REQUESTS
                || $usage['bytes'] + RECOVERY_BYTES - (int) $app['row']['reserved_bytes'] > MAX_OWNER_BYTES) { return error('owner_quota', 429); }
            $refilled = application_write($app, [RECOVERY_BYTES, 2, 1]); if (is_wp_error($refilled)) { return $refilled; }
            $app['row']['reserved_bytes'] = RECOVERY_BYTES; $app['row']['reserved_requests'] = 2; $app['row']['reserved_reviews'] = 1;
        }
        if ($input['expected_source_version'] !== $p['after_version']) { return error('source_changed', 409); }
        $original = review_read($p['review_id'], get_current_user_id(), $input['locale']); if (is_wp_error($original)) { return $original; }
        $d = $original['document']; $schema = review_schema(); if (is_wp_error($schema)) { return $schema; }
        if ($schema !== $p['schema_version']) { return error('schema_changed', 409); }
        $source = review_source($p['project_id'], $input['locale'], array_fill_keys(array_keys($d['after']), ''), true); if (is_wp_error($source)) { return $source; }
        if ($source['version'] !== $p['after_version'] || $source['before'] !== $d['after']) { return error('source_changed', 409); }
        $table = $wpdb->prefix . 'ac_change_reviews';
        if ($wpdb->query($wpdb->prepare("INSERT INTO $table (owner_id,proposal_id,payload) VALUES (%d,%d,'')", get_current_user_id(), $p['proposal_id'])) !== 1) { return error('storage_unavailable', 503); }
        $id = (int) $wpdb->insert_id; $now = time();
        $d['review_id'] = $id; $d['operation'] = 'recover'; $d['source_version'] = $source['version'];
        $d['before'] = $source['before']; $d['after'] = $original['document']['before']; $d['created_at'] = $now; $d['expires_at'] = $now + REVIEW_LIFETIME; $d['application_id'] = $p['application_id'];
        $stored = ['document' => $d, 'review_hash' => hash('sha256', wp_json_encode(canonical($d)))]; $encoded = wp_json_encode($stored);
        if (!is_string($encoded) || strlen($encoded) > MAX_PAYLOAD_BYTES) { return error('review_full', 409); }
        if ($wpdb->query($wpdb->prepare("UPDATE $table SET payload=%s WHERE review_id=%d", $encoded, $id)) !== 1) { return error('storage_unavailable', 503); }
        $app['payload']['recovery_review_id'] = $id;
        $written = application_write($app); if (is_wp_error($written)) { return $written; }
        $dto = review_prepared_dto($stored); $dto['application_id'] = $p['application_id'];
        $bytes = application_save_receipt($input, 'prepare-project-recovery', $p['proposal_id'], $dto); if (is_wp_error($bytes)) { return $bytes; }
        $spent = recovery_spend($p['application_id'], strlen($encoded) + $bytes + 32, 1, 1); if (is_wp_error($spent)) { return $spent; }
        return $dto;
    });
}
function application_source_write($stored) {
    $d = $stored['document']; $id = $d['project_id']; $fields = array_fill_keys(array_keys($d['after']), '');
    $before = review_source($id, $d['locale'], $fields, true); if (is_wp_error($before)) { return $before; }
    if ($before['version'] !== $d['source_version'] || $before['before'] !== $d['before']) { return error('source_changed', 409); }
    $post = ['ID' => $id]; if (array_key_exists('body_html', $d['after'])) { $post['post_content'] = $d['after']['body_html']; }
    $changed = wp_update_post(wp_slash($post), true);
    if (is_wp_error($changed) || !$changed) { return error('write_failed', 503); }
    if (isset($d['after']['tagline'])) {
        $meta = $d['after']['tagline'];
        if ($meta['project_tagline']['present']) { update_field(TAGLINE_KEY, wp_slash($meta['project_tagline']['value']), $id); }
        else { delete_field(TAGLINE_KEY, $id); }
        if ($meta['_project_tagline']['present']) { update_post_meta($id, '_project_tagline', wp_slash($meta['_project_tagline']['value'])); }
        else { delete_post_meta($id, '_project_tagline'); }
    }
    review_clear_cache($id);
    $after = review_source($id, $d['locale'], $fields, true); if (is_wp_error($after)) { return $after; }
    if ($after['before'] !== $d['after']) { return error('postcondition_failed', 409); }
    $a = $before['rows']; $b = $after['rows'];
    foreach (['post_modified', 'post_modified_gmt'] as $key) { unset($a['post'][$key], $b['post'][$key]); }
    if (isset($fields['body_html'])) { unset($a['post']['post_content'], $b['post']['post_content']); }
    if (isset($fields['tagline'])) {
        $unrelated = static function ($rows) { return array_values(array_filter($rows, static function ($r) { return !in_array($r['meta_key'], ['project_tagline', '_project_tagline'], true); })); };
        $a['meta'] = $unrelated($a['meta']); $b['meta'] = $unrelated($b['meta']);
    }
    if ($a !== $b) { return error('postcondition_failed', 409); }
    $schema = review_schema(); if (is_wp_error($schema)) { return $schema; }
    if ($schema !== $d['schema_version']) { return error('schema_changed', 409); }
    return $after;
}
function application_commit($input, $operation) {
    global $wpdb;
    $allowed = application_permission($operation); if (is_wp_error($allowed)) { return $allowed; }
    $kind = $operation === 'apply' ? 'apply-project-change' : 'recover-project-change';
    $valid = application_validate($input, $kind); if (is_wp_error($valid)) { return $valid; }
    $touched = [];
    try { return review_transaction(get_current_user_id(), static function () use ($wpdb, $input, $operation, $kind, &$touched) {
        $allowed = application_permission($operation); if (is_wp_error($allowed)) { return $allowed; }
        $receipt = application_receipt($input, $kind); if ($receipt !== null) { return $receipt; }
        $stored = review_read($input['review_id'], get_current_user_id(), $input['locale']); if (is_wp_error($stored)) { return $stored; }
        $locked = application_lock_proposal($stored['document']['proposal_id']); if (is_wp_error($locked)) { return $locked; }
        $stored = review_read($input['review_id'], get_current_user_id(), $input['locale'], true); if (is_wp_error($stored)) { return $stored; }
        $d = $stored['document'];
        if ($d['operation'] !== $operation || !hash_equals($stored['review_hash'], $input['expected_review_hash'])) { return error('review_changed', 409); }
        $approval = approval_read($stored, true); if (is_wp_error($approval)) { return $approval; }
        if (!$approval || $approval['approval_id'] !== $input['approval_id']) { return error('approval_required', 403); }
        if ($approval['payload']['consumed']) { return error('approval_consumed', 409); }
        if (time() >= $d['expires_at']) { return error('review_expired', 409); }
        $actor = $approval['payload']['actor_id'];
        if ($actor === get_current_user_id() || !user_can($actor, 'read') || !user_can($actor, APPROVE_CAPABILITY)) { return error('forbidden', 403); }
        $current = review_current($stored, true); if (is_wp_error($current)) { return $current; }
        if (time() >= $d['expires_at']) { return error('review_expired', 409); }
        $usage = owner_usage(get_current_user_id()); if (is_wp_error($usage)) { return $usage; }
        if ($operation === 'apply') {
            $count = application_review_count(get_current_user_id());
            if ($count === null || $wpdb->last_error) { return error('storage_unavailable', 503); }
            if ((int) $count + 1 > MAX_OWNER_REVIEWS || $usage['requests'] + 3 > MAX_OWNER_REQUESTS
                || $usage['bytes'] + RECOVERY_BYTES + 8192 > MAX_OWNER_BYTES) { return error('owner_quota', 429); }
            if (strlen(wp_json_encode($stored)) + 1024 > MAX_PAYLOAD_BYTES) { return error('recovery_capacity', 409); }
        }
        $touched = [$d['project_id'], $d['proposal_id']];
        $source = application_source_write($stored); if (is_wp_error($source)) { return $source; }
        review_clear_cache($d['proposal_id']);
        $proposal = load_owned($d['proposal_id'], $d['locale'], false); if (is_wp_error($proposal)) { return $proposal; }
        if ($proposal['payload']['version'] !== $d['proposal_version']) { return error('proposal_changed', 409); }
        // Reauthorize after WP/ACF hooks as well; no grants or role changes are performed here.
        $allowed = application_permission($operation); if (is_wp_error($allowed)) { return $allowed; }
        if (time() >= $d['expires_at']) { return error('review_expired', 409); }
        if (!user_can($actor, APPROVE_CAPABILITY)) { return error('forbidden', 403); }
        $event = ['version' => bin2hex(random_bytes(32)), 'operation' => $operation, 'review_id' => $d['review_id'],
            'approval_id' => $approval['approval_id'], 'source_version' => $source['version'], 'recorded_at' => gmdate('Y-m-d\TH:i:s\Z')];
        $apps = $wpdb->prefix . 'ac_change_applications';
        if ($operation === 'apply') {
            if ($wpdb->query($wpdb->prepare("INSERT INTO $apps (owner_id,proposal_id,payload,reserved_bytes,reserved_requests,reserved_reviews) VALUES (%d,%d,'',%d,2,1)", get_current_user_id(), $d['proposal_id'], RECOVERY_BYTES)) !== 1) { return error('storage_unavailable', 503); }
            $id = (int) $wpdb->insert_id;
            $p = ['format' => 'source-application-v1', 'application_id' => $id, 'owner_id' => get_current_user_id(), 'proposal_id' => $d['proposal_id'],
                'project_id' => $d['project_id'], 'locale' => $d['locale'], 'review_id' => $d['review_id'], 'proposal_version' => $d['proposal_version'],
                'before_version' => $d['source_version'], 'after_version' => $source['version'], 'schema_version' => $d['schema_version'], 'state' => 'applied', 'recovery_review_id' => null, 'events' => [$event]];
            $row = ['payload' => '', 'reserved_bytes' => RECOVERY_BYTES, 'reserved_requests' => 2, 'reserved_reviews' => 1];
            $app = ['payload' => $p, 'row' => $row];
            $saved = application_write($app); if (is_wp_error($saved)) { return $saved; }
        } else {
            $app = application_read($d['application_id'], $d['locale'], true); if (is_wp_error($app)) { return $app; }
            $app['payload']['state'] = 'recovered'; $app['payload']['events'][] = $event;
            $p = $app['payload'];
            $saved = application_write($app, [0, 0, 0]); if (is_wp_error($saved)) { return $saved; }
        }
        $approvals = $wpdb->prefix . 'ac_change_approvals'; $a = $approval['payload']; $a['consumed'] = true;
        if ($wpdb->query($wpdb->prepare("UPDATE $approvals SET payload=%s WHERE approval_id=%d AND owner_id=%d AND BINARY payload=BINARY %s",
            wp_json_encode($a), $approval['approval_id'], get_current_user_id(), wp_json_encode($approval['payload']))) !== 1) { return error('storage_unavailable', 503); }
        $dto = application_event_dto($p, $event);
        $receipt = application_save_receipt($input, $kind, $p['proposal_id'], $dto); if (is_wp_error($receipt)) { return $receipt; }
        return $dto;
    }); } finally { foreach ($touched as $id) { review_clear_cache($id); } }
}

add_action('wp_abilities_api_init', static function () {
    $id = ['type' => 'integer', 'minimum' => 1]; $locale = ['type' => 'string', 'enum' => ['en', 'es']]; $token = ['type' => 'string', 'pattern' => '^[a-f0-9]{64}$'];
    $event = ['application_id' => $id, 'proposal_id' => $id, 'project_id' => $id, 'locale' => $locale, 'review_id' => $id,
        'version' => $token, 'source_version' => $token, 'state' => ['type' => 'string', 'enum' => ['applied', 'recovered']], 'applied' => ['type' => 'boolean', 'enum' => [true]]];
    foreach (['apply-project-change' => 'apply', 'prepare-project-recovery' => 'recover', 'recover-project-change' => 'recover'] as $name => $operation) {
        $prepare = $name === 'prepare-project-recovery';
        $input = $prepare ? ['application_id' => $id, 'locale' => $locale, 'expected_source_version' => $token]
            : ['review_id' => $id, 'locale' => $locale, 'expected_review_hash' => $token, 'approval_id' => $id];
        $input['request_id'] = ['type' => 'string', 'pattern' => '^[a-f0-9]{32}$'];
        $output = $prepare ? wp_get_ability('alexcuadra/prepare-project-application')->get_output_schema() : ['type' => 'object', 'additionalProperties' => false, 'required' => array_keys($event), 'properties' => $event];
        if ($prepare) { $output['properties']['application_id'] = $id; $output['required'][] = 'application_id'; }
        wp_register_ability('alexcuadra/' . $name, ['label' => $name, 'description' => 'Local laboratory only. Apply or recover exactly an independently approved public project diff, with conditional source checks.',
            'category' => 'alexcuadra-change-lab', 'permission_callback' => static function () use ($operation) { return application_permission($operation); },
            'input_schema' => ['type' => 'object', 'additionalProperties' => false, 'properties' => $input, 'required' => array_keys($input)], 'output_schema' => $output,
            'execute_callback' => $prepare ? __NAMESPACE__ . '\\recovery_prepare' : static function ($input) use ($operation) { return application_commit($input, $operation); },
            'meta' => ['public' => false, 'show_in_rest' => false, 'mcp' => ['public' => true, 'type' => 'tool'],
                'annotations' => ['readonly' => false, 'destructive' => !$prepare, 'idempotent' => true]]]);
    }
}, 20);
