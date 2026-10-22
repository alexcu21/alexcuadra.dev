<?php
namespace AlexCuadra\EditorialProposals;
if (!defined('ABSPATH') || !defined('AC_EDITORIAL_PROPOSALS_SITE')
    || AC_EDITORIAL_PROPOSALS_SITE !== 'https://content.alexcuadra.dev'
    || home_url() !== AC_EDITORIAL_PROPOSALS_SITE || site_url() !== AC_EDITORIAL_PROPOSALS_SITE || is_multisite()) { return; }

const MAX_OWNER_PROPOSALS = 8;
const MAX_OWNER_REQUESTS = 128;
const MAX_OWNER_BYTES = 8388608;

function install_storage() {
    if (!defined('WP_CLI') || !WP_CLI || !current_user_can('manage_options')) { return error('forbidden', 403); }
    if (get_option('ac_proposals_storage_version') === '0.1') { return; }
    global $wpdb;
    $owners = $wpdb->prefix . 'ac_proposal_owners'; $requests = $wpdb->prefix . 'ac_proposal_requests';
    $a = $wpdb->query("CREATE TABLE IF NOT EXISTS $owners (owner_id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB");
    $b = $wpdb->query("CREATE TABLE IF NOT EXISTS $requests (owner_id BIGINT UNSIGNED NOT NULL, request_id VARBINARY(32) NOT NULL,
        request_hash VARBINARY(64) NOT NULL, proposal_id BIGINT UNSIGNED NOT NULL, response LONGTEXT NOT NULL,
        PRIMARY KEY(owner_id,request_id)) ENGINE=InnoDB");
    if ($a === false || $b === false) { return error('storage_unavailable', 503); }
    // Refuse pre-existing incompatible tables; CREATE IF NOT EXISTS does not validate their schema.
    foreach ([$owners => ['owner_id'], $requests => ['owner_id', 'request_id', 'request_hash', 'proposal_id', 'response']] as $table => $columns) {
        if ($wpdb->get_col("SHOW COLUMNS FROM $table") !== $columns || $wpdb->last_error) { return error('storage_unavailable', 503); }
    }
    $engines = $wpdb->get_results($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (%s,%s)', $owners, $requests), ARRAY_A);
    if (count($engines ?? []) !== 2 || array_filter($engines, static function ($r) { return $r['ENGINE'] !== 'InnoDB'; })) { return error('storage_unavailable', 503); }
    update_option('ac_proposals_storage_version', '0.1', false);
    return get_option('ac_proposals_storage_version') === '0.1' ? true : error('storage_unavailable', 503);
}
function canonical($value) {
    if (!is_array($value)) { return $value; }
    ksort($value);
    return array_map(__NAMESPACE__ . '\\canonical', $value);
}
function owner_usage($owner) {
    global $wpdb;
    $requests = $wpdb->prefix . 'ac_proposal_requests';
    $proposals = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type=%s AND post_author=%d", TYPE, $owner));
    if ($proposals === null || $wpdb->last_error) { return error('storage_unavailable', 503); }
    $bytes = $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(OCTET_LENGTH(m.meta_value)),0) FROM $wpdb->postmeta m
        INNER JOIN $wpdb->posts p ON p.ID=m.post_id WHERE p.post_type=%s AND p.post_author=%d AND m.meta_key=%s", TYPE, $owner, PAYLOAD));
    if ($bytes === null || $wpdb->last_error) { return error('storage_unavailable', 503); }
    $ledger = $wpdb->get_row($wpdb->prepare("SELECT COUNT(*) AS total,COALESCE(SUM(OCTET_LENGTH(response)),0) AS bytes FROM $requests WHERE owner_id=%d", $owner), ARRAY_A);
    if (!is_array($ledger) || $wpdb->last_error) { return error('storage_unavailable', 503); }
    return ['proposals' => (int) $proposals, 'requests' => (int) $ledger['total'],
        'bytes' => (int) $bytes + (int) $ledger['bytes']];
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
    $owner = get_current_user_id(); $owners = $wpdb->prefix . 'ac_proposal_owners'; $requests = $wpdb->prefix . 'ac_proposal_requests';
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
