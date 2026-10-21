<?php
namespace AlexCuadra\EditorialChanges;
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { return; }

const MAX_OWNER_PROPOSALS = 8;
const MAX_OWNER_REQUESTS = 128;
const MAX_OWNER_BYTES = 8388608;

function install_storage() {
    if (get_option('ac_changes_storage_version') === '0.5') { return; }
    global $wpdb;
    $owners = $wpdb->prefix . 'ac_change_owners'; $requests = $wpdb->prefix . 'ac_change_requests';
    $a = $wpdb->query("CREATE TABLE IF NOT EXISTS $owners (owner_id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB");
    $b = $wpdb->query("CREATE TABLE IF NOT EXISTS $requests (owner_id BIGINT UNSIGNED NOT NULL, request_id VARBINARY(32) NOT NULL,
        request_hash VARBINARY(64) NOT NULL, proposal_id BIGINT UNSIGNED NOT NULL, response LONGTEXT NOT NULL,
        PRIMARY KEY(owner_id,request_id)) ENGINE=InnoDB");
    // Additive migration: old receipt bytes, request hashes and proposal payloads stay untouched.
    $reviews = $wpdb->prefix . 'ac_change_reviews'; $approvals = $wpdb->prefix . 'ac_change_approvals';
    $c = $wpdb->query("CREATE TABLE IF NOT EXISTS $reviews (review_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        owner_id BIGINT UNSIGNED NOT NULL, proposal_id BIGINT UNSIGNED NOT NULL, payload LONGTEXT NOT NULL,
        KEY owner_id (owner_id)) ENGINE=InnoDB");
    $d = $wpdb->query("CREATE TABLE IF NOT EXISTS $approvals (approval_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        owner_id BIGINT UNSIGNED NOT NULL, review_id BIGINT UNSIGNED NOT NULL, payload LONGTEXT NOT NULL,
        UNIQUE KEY review_id (review_id), KEY owner_id (owner_id)) ENGINE=InnoDB");
    $apps = $wpdb->prefix . 'ac_change_applications';
    $e = $wpdb->query("CREATE TABLE IF NOT EXISTS $apps (application_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        owner_id BIGINT UNSIGNED NOT NULL, proposal_id BIGINT UNSIGNED NOT NULL, payload LONGTEXT NOT NULL,
        reserved_bytes INT UNSIGNED NOT NULL, reserved_requests INT UNSIGNED NOT NULL, reserved_reviews INT UNSIGNED NOT NULL,
        UNIQUE KEY proposal_id (proposal_id), KEY owner_id (owner_id)) ENGINE=InnoDB");
    if ($a !== false && $b !== false && $c !== false && $d !== false && $e !== false) { update_option('ac_changes_storage_version', '0.5', false); }
}
function canonical($value) {
    if (!is_array($value)) { return $value; }
    ksort($value);
    return array_map(__NAMESPACE__ . '\\canonical', $value);
}
function owner_usage($owner) {
    global $wpdb;
    $requests = $wpdb->prefix . 'ac_change_requests';
    $proposals = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type=%s AND post_author=%d", TYPE, $owner));
    if ($proposals === null || $wpdb->last_error) { return error('storage_unavailable', 503); }
    $bytes = $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(OCTET_LENGTH(m.meta_value)),0) FROM $wpdb->postmeta m
        INNER JOIN $wpdb->posts p ON p.ID=m.post_id WHERE p.post_type=%s AND p.post_author=%d AND m.meta_key=%s", TYPE, $owner, PAYLOAD));
    if ($bytes === null || $wpdb->last_error) { return error('storage_unavailable', 503); }
    $ledger = $wpdb->get_row($wpdb->prepare("SELECT COUNT(*) AS total,COALESCE(SUM(OCTET_LENGTH(response)),0) AS bytes FROM $requests WHERE owner_id=%d", $owner), ARRAY_A);
    if (!is_array($ledger) || $wpdb->last_error) { return error('storage_unavailable', 503); }
    $extra = 0;
    foreach (['ac_change_reviews', 'ac_change_approvals'] as $suffix) {
        $table = $wpdb->prefix . $suffix;
        $size = $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(OCTET_LENGTH(payload)),0) FROM $table WHERE owner_id=%d", $owner));
        if ($size === null || $wpdb->last_error) { return error('storage_unavailable', 503); }
        $extra += (int) $size;
    }
    $apps = $wpdb->prefix . 'ac_change_applications';
    $reserved = $wpdb->get_row($wpdb->prepare("SELECT COALESCE(SUM(OCTET_LENGTH(payload)+reserved_bytes),0) AS bytes,
        COALESCE(SUM(reserved_requests),0) AS requests FROM $apps WHERE owner_id=%d", $owner), ARRAY_A);
    if (!$reserved || $wpdb->last_error) { return error('storage_unavailable', 503); }
    return ['proposals' => (int) $proposals, 'requests' => (int) $ledger['total'] + (int) $reserved['requests'],
        'bytes' => (int) $bytes + (int) $ledger['bytes'] + $extra + (int) $reserved['bytes']];
}
function replay($row, $input, $hash) {
    if (!hash_equals($row['request_hash'], $hash)) { return error('request_reused', 409); }
    $loaded = load_owned((int) $row['proposal_id'], $input['locale']);
    if (is_wp_error($loaded)) { return $loaded; }
    $response = json_decode($row['response'], true); $payload = $loaded['payload'];
    if (!is_array($response) || array_keys($response) !== ['proposal_id', 'project_id', 'locale', 'version', 'base_version', 'patch', 'state', 'applied']
        || $response['proposal_id'] !== (int) $row['proposal_id'] || $response['project_id'] !== $payload['project_id']
        || $response['locale'] !== $payload['locale'] || $response['base_version'] !== $payload['base_version']
        || $response['state'] !== 'draft' || $response['applied'] !== false) { return error('storage_unavailable', 503); }
    foreach ($payload['history'] as $entry) {
        if ($entry['version'] === $response['version'] && $entry['patch'] === $response['patch']) { return $response; }
    }
    return error('storage_unavailable', 503);
}
function transact($input, $kind, $prepared) {
    global $wpdb;
    $owner = get_current_user_id(); $owners = $wpdb->prefix . 'ac_change_owners'; $requests = $wpdb->prefix . 'ac_change_requests';
    // Never nest: a WordPress hook must not commit or roll back an outer caller's transaction.
    $transaction = $wpdb->get_var('SELECT @@in_transaction');
    if ($transaction === null || $wpdb->last_error) { return error('storage_unavailable', 503); }
    if ((int) $transaction !== 0) { return error('transaction_busy', 503); }
    $engines = $wpdb->get_results($wpdb->prepare("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()
        AND TABLE_NAME IN (%s,%s,%s,%s)", $owners, $requests, $wpdb->posts, $wpdb->postmeta), ARRAY_A);
    if (count($engines ?? []) !== 4 || array_filter($engines, static function ($r) { return $r['ENGINE'] !== 'InnoDB'; })) { return error('storage_unavailable', 503); }
    // An empty lock row is harmless if a later request fails; it contains no payload or counter.
    if ($wpdb->query($wpdb->prepare("INSERT IGNORE INTO $owners (owner_id) VALUES (%d)", $owner)) === false
        || $wpdb->query('START TRANSACTION') === false) { return error('storage_unavailable', 503); }
    $committed = false; $touched = $input['proposal_id'] ?? null;
    try {
        $lock = $wpdb->get_var($wpdb->prepare("SELECT owner_id FROM $owners WHERE owner_id=%d FOR UPDATE", $owner));
        if ((int) $lock !== $owner || $wpdb->last_error) { return error('storage_unavailable', 503); }
        $allowed = permission(); if (is_wp_error($allowed)) { return $allowed; }
        $hash = hash('sha256', wp_json_encode([$kind, canonical($input)]));
        $existing = $wpdb->get_row($wpdb->prepare("SELECT request_hash,proposal_id,response FROM $requests WHERE owner_id=%d AND request_id=%s FOR UPDATE", $owner, $input['request_id']), ARRAY_A);
        if ($wpdb->last_error) { return error('storage_unavailable', 503); }
        if ($existing) { return replay($existing, $input, $hash); }
        $usage = owner_usage($owner); if (is_wp_error($usage)) { return $usage; }
        if ($usage['requests'] >= MAX_OWNER_REQUESTS || ($kind === 'create' && $usage['proposals'] >= MAX_OWNER_PROPOSALS)) { return error('owner_quota', 429); }
        $result = execute_prepared($input, $kind, $prepared, $touched);
        if (is_wp_error($result)) { return $result; }
        $touched = $result['proposal_id']; $response = wp_json_encode($result);
        if ($wpdb->query($wpdb->prepare("INSERT INTO $requests (owner_id,request_id,request_hash,proposal_id,response) VALUES (%d,%s,%s,%d,%s)",
            $owner, $input['request_id'], $hash, $touched, $response)) !== 1) { return error('storage_unavailable', 503); }
        $usage = owner_usage($owner); if (is_wp_error($usage)) { return $usage; }
        if ($usage['bytes'] > MAX_OWNER_BYTES) { return error('owner_quota', 429); }
        if ($wpdb->query('COMMIT') === false) { return error('storage_unavailable', 503); }
        $committed = true;
        return $result;
    } finally {
        if (!$committed) { $wpdb->query('ROLLBACK'); }
        if ($touched) { wp_cache_delete($touched, 'post_meta'); clean_post_cache($touched); }
    }
}
