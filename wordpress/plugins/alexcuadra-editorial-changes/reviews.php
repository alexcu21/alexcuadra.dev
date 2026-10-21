<?php
namespace AlexCuadra\EditorialChanges;
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { return; }

// Reviews remain immutable; consumption and application events live in separate private records.
const APPLY_CAPABILITY = 'alexcuadra_apply_project_changes';
const APPROVE_CAPABILITY = 'alexcuadra_approve_public_project_changes';
const RECOVER_CAPABILITY = 'alexcuadra_recover_project_changes';
const MAX_OWNER_REVIEWS = 8;
const REVIEW_LIFETIME = 600;

function review_permission($input = null) {
    $allowed = permission();
    return is_wp_error($allowed) ? $allowed : ((current_user_can(APPLY_CAPABILITY) || current_user_can(RECOVER_CAPABILITY)) ? true : error('forbidden', 403));
}
function review_validate($input, $kind) {
    $keys = $kind === 'prepare' ? ['proposal_id', 'locale', 'expected_proposal_version', 'expected_base_version', 'request_id'] : ['review_id', 'locale'];
    if (!is_array($input) || array_diff(array_keys($input), $keys) || array_diff($keys, array_keys($input))
        || !in_array($input['locale'], ['en', 'es'], true)) { return error('invalid_input', 400); }
    $id = $input[$kind === 'prepare' ? 'proposal_id' : 'review_id'];
    if (!is_int($id) || $id < 1) { return error('invalid_input', 400); }
    if ($kind === 'prepare') {
        foreach (['expected_proposal_version' => 64, 'expected_base_version' => 64, 'request_id' => 32] as $key => $length) {
            if (!is_string($input[$key]) || !preg_match('/^[a-f0-9]{' . $length . '}$/D', $input[$key])) { return error('invalid_input', 400); }
        }
    }
    return true;
}
function review_schema() {
    if (!function_exists('acf_get_field') || !function_exists('acf_get_field_group')) { return error('dependencies', 503); }
    $field = acf_get_field(TAGLINE_KEY);
    $group = $field ? acf_get_field_group($field['parent']) : false;
    if (!$field || !$group || $field['key'] !== TAGLINE_KEY || $field['name'] !== 'project_tagline'
        || $field['type'] !== 'textarea' || $group['key'] !== 'group_676d40f7bb646'
        || empty($group['active']) || $group['location'] !== [[['param' => 'post_type', 'operator' => '==', 'value' => 'project']]]) { return error('schema_changed', 409); }
    return hash('sha256', wp_json_encode(canonical(['field' => $field, 'group' => $group])));
}
function review_clear_cache($id) {
    clean_post_cache($id); wp_cache_delete($id, 'post_meta');
    if (function_exists('acf_flush_value_cache')) { acf_flush_value_cache($id, 'project_tagline'); }
}
function review_source($id, $locale, $patch, $locking = false) {
    global $wpdb;
    $suffix = $locking ? ' FOR UPDATE' : '';
    // Hash all source rows, but persist only the whitelisted before/after values.
    $posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d" . $suffix, $id), ARRAY_A);
    if ($wpdb->last_error) { return error('storage_unavailable', 503); }
    $meta = $wpdb->get_results($wpdb->prepare("SELECT meta_key,meta_value FROM $wpdb->postmeta WHERE post_id=%d ORDER BY meta_id" . $suffix, $id), ARRAY_A);
    if ($wpdb->last_error) { return error('storage_unavailable', 503); }
    $terms = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->term_relationships WHERE object_id=%d ORDER BY term_taxonomy_id" . $suffix, $id), ARRAY_A);
    if ($wpdb->last_error) { return error('storage_unavailable', 503); }
    if (count($posts ?? []) !== 1) { return error('not_found', 404); }
    review_clear_cache($id);
    $base = source($id, $locale); if (is_wp_error($base)) { return $base; }
    $before = []; $after = [];
    if (array_key_exists('body_html', $patch)) { $before['body_html'] = $posts[0]['post_content']; $after['body_html'] = $patch['body_html']; }
    if (array_key_exists('tagline', $patch)) {
        foreach (['project_tagline', '_project_tagline'] as $key) {
            $rows = array_values(array_filter($meta, static function ($r) use ($key) { return $r['meta_key'] === $key; }));
            if (count($rows) > 1) { return error('ambiguous_meta', 409); }
            // Raw strings: missing row and an explicitly empty string are different snapshots.
            $before['tagline'][$key] = ['present' => count($rows) === 1, 'value' => count($rows) ? $rows[0]['meta_value'] : null];
        }
        if ($before['tagline']['_project_tagline']['present'] && $before['tagline']['_project_tagline']['value'] !== TAGLINE_KEY) { return error('schema_changed', 409); }
        $after['tagline'] = ['project_tagline' => ['present' => true, 'value' => $patch['tagline']],
            '_project_tagline' => ['present' => true, 'value' => TAGLINE_KEY]];
    }
    return ['version' => hash('sha256', serialize([$posts, $meta, $terms])), 'base' => $base, 'before' => $before, 'after' => $after,
        'rows' => ['post' => $posts[0], 'meta' => $meta, 'terms' => $terms]];
}
function review_read($id, $owner, $locale, $locking = false) {
    global $wpdb;
    $table = $wpdb->prefix . 'ac_change_reviews';
    $row = $wpdb->get_row($wpdb->prepare("SELECT payload FROM $table WHERE review_id=%d AND owner_id=%d" . ($locking ? ' FOR UPDATE' : ''), $id, $owner), ARRAY_A);
    if ($wpdb->last_error) { return error('storage_unavailable', 503); }
    if (!$row) { return error('not_found', 404); }
    $stored = json_decode($row['payload'], true); $d = $stored['document'] ?? null;
    $keys = ['format', 'review_id', 'operation', 'owner_id', 'proposal_id', 'project_id', 'locale', 'proposal_version', 'base_version', 'source_version', 'schema_version', 'before', 'after', 'created_at', 'expires_at'];
    if (($d['operation'] ?? '') === 'recover') { $keys[] = 'application_id'; }
    if (!is_array($stored) || array_keys($stored) !== ['document', 'review_hash'] || !is_array($d) || array_keys($d) !== $keys
        || $d['format'] !== 'source-review-v1' || $d['review_id'] !== $id || $d['owner_id'] !== $owner || !in_array($d['operation'], ['apply', 'recover'], true)
        || !is_int($d['proposal_id']) || $d['proposal_id'] < 1 || !is_int($d['project_id']) || $d['project_id'] < 1
        || !is_int($d['created_at']) || !is_int($d['expires_at']) || $d['expires_at'] !== $d['created_at'] + REVIEW_LIFETIME
        || !is_string($stored['review_hash']) || !hash_equals(hash('sha256', wp_json_encode(canonical($d))), $stored['review_hash'])) { return error('review_unavailable', 503); }
    if ($d['locale'] !== $locale) { return error('not_found', 404); }
    return $stored;
}
function review_current($stored, $locking = false) {
    $d = $stored['document'];
    if ($d['operation'] === 'recover') { return recovery_current($stored, $locking); }
    review_clear_cache($d['proposal_id']);
    $loaded = load_owned($d['proposal_id'], $d['locale']);
    if (is_wp_error($loaded)) { return $loaded; }
    if (application_state($d['proposal_id']) !== 'draft') { return error('proposal_frozen', 409); }
    if ($loaded['payload']['version'] !== $d['proposal_version']) { return error('proposal_changed', 409); }
    $schema = review_schema(); if (is_wp_error($schema)) { return $schema; }
    if ($schema !== $d['schema_version']) { return error('schema_changed', 409); }
    $source = review_source($d['project_id'], $d['locale'], $loaded['payload']['patch'], $locking);
    if (is_wp_error($source)) { return $source; }
    if ($source['version'] !== $d['source_version'] || $source['before'] !== $d['before'] || $source['after'] !== $d['after']) { return error('source_changed', 409); }
    return true;
}
function review_dto($stored) {
    global $wpdb;
    $d = $stored['document'];
    // Public/locale gate remains mandatory even for expired snapshots and confirmed receipts.
    $visible = source($d['project_id'], $d['locale']); if (is_wp_error($visible)) { return $visible; }
    $current = review_current($stored);
    if (is_wp_error($current) && ($current->get_error_data()['status'] ?? 503) !== 409) { return $current; }
    $approved = approval_read($stored);
    if (is_wp_error($approved)) { return $approved; }
    $consumed = $approved && $approved['payload']['consumed'];
    return ['review_id' => $d['review_id'], 'proposal_id' => $d['proposal_id'], 'project_id' => $d['project_id'], 'locale' => $d['locale'],
        'review_hash' => $stored['review_hash'], 'proposal_version' => $d['proposal_version'], 'source_version' => $d['source_version'],
        'schema_version' => $d['schema_version'], 'before' => $d['before'], 'after' => $d['after'],
        'created_at' => $d['created_at'], 'expires_at' => $d['expires_at'],
        'state' => $consumed ? 'consumed' : (time() >= $d['expires_at'] ? 'expired' : ($approved ? 'approved' : 'prepared')),
        'current' => !$consumed && $current === true, 'public_effect' => 'would_modify_published_project', 'applied' => (bool) $consumed];
}
// Shared owner lock, same request namespace/byte budget as the 0.3 proposal ledger.
function review_transaction($owner, $callback) {
    global $wpdb;
    $active = $wpdb->get_var('SELECT @@in_transaction');
    if ($active === null || $wpdb->last_error) { return error('storage_unavailable', 503); }
    if ((int) $active !== 0) { return error('transaction_busy', 503); }
    $names = [$wpdb->posts, $wpdb->postmeta, $wpdb->term_relationships];
    foreach (['owners', 'requests', 'reviews', 'approvals', 'applications'] as $suffix) { $names[] = $wpdb->prefix . 'ac_change_' . $suffix; }
    $sql = implode(',', array_fill(0, count($names), '%s'));
    $engines = $wpdb->get_results($wpdb->prepare("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ($sql)", $names), ARRAY_A);
    if ($wpdb->last_error || count($engines ?? []) !== count($names)
        || array_filter($engines, static function ($r) { return $r['ENGINE'] !== 'InnoDB'; })) { return error('storage_unavailable', 503); }
    $owners = $wpdb->prefix . 'ac_change_owners';
    if ($wpdb->query($wpdb->prepare("INSERT IGNORE INTO $owners (owner_id) VALUES (%d)", $owner)) === false
        || $wpdb->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED') === false
        || $wpdb->query('START TRANSACTION') === false) { return error('storage_unavailable', 503); }
    $committed = false;
    try {
        $locked = $wpdb->get_var($wpdb->prepare("SELECT owner_id FROM $owners WHERE owner_id=%d FOR UPDATE", $owner));
        if ($wpdb->last_error || (int) $locked !== $owner) { return error('storage_unavailable', 503); }
        $result = $callback(); if (is_wp_error($result)) { return $result; }
        $usage = owner_usage($owner); if (is_wp_error($usage)) { return $usage; }
        if ($usage['bytes'] > MAX_OWNER_BYTES || $usage['requests'] > MAX_OWNER_REQUESTS) { return error('owner_quota', 429); }
        if ($wpdb->query('COMMIT') === false) { return error('storage_unavailable', 503); }
        $committed = true; return $result;
    } catch (\Throwable $e) { return error('write_failed', 503); }
    finally { if (!$committed) { $wpdb->query('ROLLBACK'); } }
}
function review_prepare($input) {
    global $wpdb;
    $allowed = review_permission(); if (is_wp_error($allowed)) { return $allowed; }
    if (!current_user_can(APPLY_CAPABILITY)) { return error('forbidden', 403); }
    $valid = review_validate($input, 'prepare'); if (is_wp_error($valid)) { return $valid; }
    $owner = get_current_user_id();
    return review_transaction($owner, static function () use ($input, $owner, $wpdb) {
        $allowed = review_permission(); if (is_wp_error($allowed)) { return $allowed; }
        if (!current_user_can(APPLY_CAPABILITY)) { return error('forbidden', 403); }
        $requests = $wpdb->prefix . 'ac_change_requests'; $reviews = $wpdb->prefix . 'ac_change_reviews';
        $hash = hash('sha256', wp_json_encode(['prepare-project-application', canonical($input)]));
        $receipt = $wpdb->get_row($wpdb->prepare("SELECT request_hash,proposal_id,response FROM $requests WHERE owner_id=%d AND request_id=%s FOR UPDATE", $owner, $input['request_id']), ARRAY_A);
        if ($wpdb->last_error) { return error('storage_unavailable', 503); }
        if ($receipt) {
            if (!hash_equals($receipt['request_hash'], $hash)) { return error('request_reused', 409); }
            $event = json_decode($receipt['response'], true);
            if (!is_array($event) || array_keys($event) !== ['receipt_type', 'review_id', 'review_hash'] || $event['receipt_type'] !== 'source-review-v1'
                || !is_int($event['review_id'])) { return error('storage_unavailable', 503); }
            $stored = review_read($event['review_id'], $owner, $input['locale'], true);
            if (is_wp_error($stored)) { return $stored; }
            if ($event['review_hash'] !== $stored['review_hash'] || $stored['document']['proposal_id'] !== (int) $receipt['proposal_id']) { return error('storage_unavailable', 503); }
            $d = $stored['document'];
            $owned = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE ID=%d AND post_author=%d AND post_type=%s
                AND post_status='draft' AND post_password=''", $d['proposal_id'], $owner, TYPE));
            if ($wpdb->last_error) { return error('storage_unavailable', 503); }
            if (!$owned) { return error('not_found', 404); }
            // Replay the immutable prepared DTO; GET provides the current approval/expiry state.
            review_clear_cache($stored['document']['project_id']);
            $visible = source($stored['document']['project_id'], $input['locale']); if (is_wp_error($visible)) { return $visible; }
            return review_prepared_dto($stored);
        }
        $usage = owner_usage($owner); if (is_wp_error($usage)) { return $usage; }
        $count = application_review_count($owner);
        if ($count === null || $wpdb->last_error) { return error('storage_unavailable', 503); }
        if ((int) $count >= MAX_OWNER_REVIEWS || $usage['requests'] >= MAX_OWNER_REQUESTS) { return error('owner_quota', 429); }
        $post = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE ID=%d AND post_author=%d AND post_type=%s FOR UPDATE", $input['proposal_id'], $owner, TYPE));
        if ($wpdb->last_error) { return error('storage_unavailable', 503); }
        if (!$post) { return error('not_found', 404); }
        $wpdb->get_results($wpdb->prepare("SELECT meta_id FROM $wpdb->postmeta WHERE post_id=%d AND meta_key=%s FOR UPDATE", $input['proposal_id'], PAYLOAD));
        if ($wpdb->last_error) { return error('storage_unavailable', 503); }
        review_clear_cache($input['proposal_id']);
        $loaded = load_owned($input['proposal_id'], $input['locale']); if (is_wp_error($loaded)) { return $loaded; }
        $p = $loaded['payload'];
        if (application_state($input['proposal_id']) !== 'draft') { return error('proposal_frozen', 409); }
        if ($p['version'] !== $input['expected_proposal_version']) { return error('proposal_changed', 409); }
        if ($p['base_version'] !== $input['expected_base_version']) { return error('source_changed', 409); }
        $schema = review_schema(); if (is_wp_error($schema)) { return $schema; }
        $source = review_source($p['project_id'], $input['locale'], $p['patch'], true); if (is_wp_error($source)) { return $source; }
        if ($source['base'] !== $p['base_version']) { return error('source_changed', 409); }
        if ($wpdb->query($wpdb->prepare("INSERT INTO $reviews (owner_id,proposal_id,payload) VALUES (%d,%d,'')", $owner, $input['proposal_id'])) !== 1) { return error('storage_unavailable', 503); }
        $id = (int) $wpdb->insert_id; $now = time();
        $d = ['format' => 'source-review-v1', 'review_id' => $id, 'operation' => 'apply', 'owner_id' => $owner,
            'proposal_id' => $input['proposal_id'], 'project_id' => $p['project_id'], 'locale' => $p['locale'],
            'proposal_version' => $p['version'], 'base_version' => $p['base_version'], 'source_version' => $source['version'],
            'schema_version' => $schema, 'before' => $source['before'], 'after' => $source['after'], 'created_at' => $now, 'expires_at' => $now + REVIEW_LIFETIME];
        $stored = ['document' => $d, 'review_hash' => hash('sha256', wp_json_encode(canonical($d)))];
        $encoded = wp_json_encode($stored);
        if (!is_string($encoded) || strlen($encoded) > MAX_PAYLOAD_BYTES) { return error('review_full', 409); }
        if ($wpdb->query($wpdb->prepare("UPDATE $reviews SET payload=%s WHERE review_id=%d AND owner_id=%d", $encoded, $id, $owner)) !== 1) { return error('storage_unavailable', 503); }
        $receipt = wp_json_encode(['receipt_type' => 'source-review-v1', 'review_id' => $id, 'review_hash' => $stored['review_hash']]);
        if ($wpdb->query($wpdb->prepare("INSERT INTO $requests (owner_id,request_id,request_hash,proposal_id,response) VALUES (%d,%s,%s,%d,%s)",
            $owner, $input['request_id'], $hash, $input['proposal_id'], $receipt)) !== 1) { return error('storage_unavailable', 503); }
        return review_prepared_dto($stored);
    });
}
function review_prepared_dto($stored) {
    $d = $stored['document'];
    // Historical result of prepare, intentionally distinct from review_dto's current state.
    return ['review_id' => $d['review_id'], 'proposal_id' => $d['proposal_id'], 'project_id' => $d['project_id'], 'locale' => $d['locale'],
        'review_hash' => $stored['review_hash'], 'proposal_version' => $d['proposal_version'], 'source_version' => $d['source_version'],
        'schema_version' => $d['schema_version'], 'before' => $d['before'], 'after' => $d['after'],
        'created_at' => $d['created_at'], 'expires_at' => $d['expires_at'], 'state' => 'prepared', 'current' => true,
        'public_effect' => 'would_modify_published_project', 'applied' => false];
}
function review_get($input) {
    $allowed = review_permission(); if (is_wp_error($allowed)) { return $allowed; }
    $valid = review_validate($input, 'get'); if (is_wp_error($valid)) { return $valid; }
    $stored = review_read($input['review_id'], get_current_user_id(), $input['locale']);
    if (!is_wp_error($stored) && !current_user_can($stored['document']['operation'] === 'apply' ? APPLY_CAPABILITY : RECOVER_CAPABILITY)) { return error('forbidden', 403); }
    return is_wp_error($stored) ? $stored : review_dto($stored);
}
// Operator-only CLI entry point, never registered as an ability/REST/GraphQL route.
// The caller must review the exact diff and public effect before invoking this operation.
function review_approve($review_id, $locale, $expected_hash) {
    if (!defined('WP_CLI') || !WP_CLI || !is_user_logged_in() || !current_user_can('read') || !current_user_can(APPROVE_CAPABILITY)) { return error('forbidden', 403); }
    if (!is_int($review_id) || $review_id < 1 || !in_array($locale, ['en', 'es'], true)
        || !is_string($expected_hash) || !preg_match('/^[a-f0-9]{64}$/D', $expected_hash)) { return error('invalid_input', 400); }
    global $wpdb;
    $reviews = $wpdb->prefix . 'ac_change_reviews';
    $owner = (int) $wpdb->get_var($wpdb->prepare("SELECT owner_id FROM $reviews WHERE review_id=%d", $review_id));
    if ($wpdb->last_error) { return error('storage_unavailable', 503); }
    if (!$owner) { return error('not_found', 404); }
    if ($owner === get_current_user_id()) { return error('independent_approval_required', 403); }
    $actor = get_current_user_id();
    return review_transaction($owner, static function () use ($wpdb, $review_id, $locale, $expected_hash, $owner, $actor) {
        if (!current_user_can(APPROVE_CAPABILITY) || $actor === $owner) { return error('forbidden', 403); }
        foreach (['read', 'alexcuadra_read_public_projects', CAPABILITY] as $cap) {
            if (!user_can($owner, $cap)) { return error('forbidden', 403); }
        }
        $reviews = $wpdb->prefix . 'ac_change_reviews';
        $proposal = $wpdb->get_var($wpdb->prepare("SELECT proposal_id FROM $reviews WHERE review_id=%d AND owner_id=%d", $review_id, $owner));
        if ($wpdb->last_error) { return error('storage_unavailable', 503); }
        if (!$proposal) { return error('not_found', 404); }
        $wpdb->get_results($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE ID=%d FOR UPDATE", (int) $proposal));
        if ($wpdb->last_error) { return error('storage_unavailable', 503); }
        $wpdb->get_results($wpdb->prepare("SELECT meta_id FROM $wpdb->postmeta WHERE post_id=%d AND meta_key=%s FOR UPDATE", (int) $proposal, PAYLOAD));
        if ($wpdb->last_error) { return error('storage_unavailable', 503); }
        $stored = review_read($review_id, $owner, $locale, true); if (is_wp_error($stored)) { return $stored; }
        if (!hash_equals($stored['review_hash'], $expected_hash)) { return error('review_changed', 409); }
        $d = $stored['document'];
        if (!user_can($owner, $d['operation'] === 'apply' ? APPLY_CAPABILITY : RECOVER_CAPABILITY)) { return error('forbidden', 403); }
        if (time() >= $d['expires_at']) { return error('review_expired', 409); }
        $table = $wpdb->prefix . 'ac_change_approvals';
        $existing = $wpdb->get_row($wpdb->prepare("SELECT approval_id,payload FROM $table WHERE review_id=%d FOR UPDATE", $review_id), ARRAY_A);
        if ($wpdb->last_error) { return error('storage_unavailable', 503); }
        // Ownership gate uses the executor's identity. Always restore the operator, including failures.
        wp_set_current_user(0); wp_set_current_user($owner);
        try { $current = review_current($stored, true); }
        finally { wp_set_current_user(0); wp_set_current_user($actor); }
        if (is_wp_error($current)) { return $current; }
        if (time() >= $d['expires_at']) { return error('review_expired', 409); }
        $payload = ['format' => 'source-approval-v1', 'review_id' => $review_id, 'review_hash' => $expected_hash, 'executor_id' => $owner,
            'project_id' => $d['project_id'], 'locale' => $locale, 'operation' => $d['operation'], 'source_version' => $d['source_version'],
            'schema_version' => $d['schema_version'], 'actor_id' => $actor, 'approved_at' => time(), 'expires_at' => $d['expires_at'], 'consumed' => false];
        if ($existing) {
            $saved = json_decode($existing['payload'], true);
            if (!is_array($saved) || array_keys($saved) !== array_keys($payload)) { return error('review_unavailable', 503); }
            $payload['approved_at'] = $saved['approved_at'];
            if ($payload !== $saved) { return error('approval_conflict', 409); }
            return ['approval_id' => (int) $existing['approval_id'], 'review_id' => $review_id, 'review_hash' => $expected_hash, 'expires_at' => $d['expires_at']];
        }
        if ($wpdb->query($wpdb->prepare("INSERT INTO $table (owner_id,review_id,payload) VALUES (%d,%d,%s)", $owner, $review_id, wp_json_encode($payload))) !== 1) { return error('storage_unavailable', 503); }
        $id = (int) $wpdb->insert_id;
        if ($d['operation'] === 'recover') {
            $reserved = recovery_spend($d['application_id'], strlen(wp_json_encode($payload)), 0, 0, $owner);
            if (is_wp_error($reserved)) { return $reserved; }
        }
        return ['approval_id' => $id, 'review_id' => $review_id, 'review_hash' => $expected_hash, 'expires_at' => $d['expires_at']];
    });
}

add_action('wp_abilities_api_init', static function () {
    $id = ['type' => 'integer', 'minimum' => 1]; $locale = ['type' => 'string', 'enum' => ['en', 'es']];
    $token = ['type' => 'string', 'pattern' => '^[a-f0-9]{64}$'];
    $meta = ['type' => 'object', 'additionalProperties' => false, 'required' => ['present', 'value'],
        'properties' => ['present' => ['type' => 'boolean'], 'value' => ['type' => ['string', 'null']]]];
    $snapshot = ['type' => 'object', 'additionalProperties' => false, 'minProperties' => 1, 'properties' => [
        'body_html' => ['type' => 'string'], 'tagline' => ['type' => 'object', 'additionalProperties' => false,
            'required' => ['project_tagline', '_project_tagline'], 'properties' => ['project_tagline' => $meta, '_project_tagline' => $meta]]]];
    $properties = ['review_id' => $id, 'proposal_id' => $id, 'project_id' => $id, 'locale' => $locale, 'review_hash' => $token,
        'proposal_version' => $token, 'source_version' => $token, 'schema_version' => $token, 'before' => $snapshot, 'after' => $snapshot,
        'created_at' => ['type' => 'integer'], 'expires_at' => ['type' => 'integer'], 'state' => ['type' => 'string', 'enum' => ['prepared', 'approved', 'expired', 'consumed']],
        'current' => ['type' => 'boolean'], 'public_effect' => ['type' => 'string', 'enum' => ['would_modify_published_project']],
        'applied' => ['type' => 'boolean']];
    $output = ['type' => 'object', 'additionalProperties' => false, 'properties' => $properties, 'required' => array_keys($properties)];
    foreach (['prepare' => 'prepare-project-application', 'get' => 'get-project-application'] as $kind => $name) {
        $input = $kind === 'prepare' ? ['proposal_id' => $id, 'locale' => $locale, 'expected_proposal_version' => $token,
            'expected_base_version' => $token, 'request_id' => ['type' => 'string', 'pattern' => '^[a-f0-9]{32}$']] : ['review_id' => $id, 'locale' => $locale];
        wp_register_ability('alexcuadra/' . $name, ['label' => $name,
            'description' => 'Freeze or inspect an owned private review. Approval is a separate operator action. Never writes source content.',
            'category' => 'alexcuadra-change-lab', 'permission_callback' => __NAMESPACE__ . '\\review_permission',
            'input_schema' => ['type' => 'object', 'additionalProperties' => false, 'properties' => $input, 'required' => array_keys($input)],
            'output_schema' => $output, 'execute_callback' => $kind === 'prepare' ? __NAMESPACE__ . '\\review_prepare' : __NAMESPACE__ . '\\review_get',
            'meta' => ['public' => false, 'show_in_rest' => false, 'mcp' => ['public' => true, 'type' => 'tool'],
                'annotations' => ['readonly' => $kind === 'get', 'destructive' => false, 'idempotent' => true]]]);
    }
});
