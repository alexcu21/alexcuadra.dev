<?php
namespace AlexCuadra\EditorialProposals;
if (!defined('ABSPATH') || !defined('AC_EDITORIAL_PROPOSALS_SITE')
    || AC_EDITORIAL_PROPOSALS_SITE !== 'https://content.alexcuadra.dev'
    || home_url() !== AC_EDITORIAL_PROPOSALS_SITE || site_url() !== AC_EDITORIAL_PROPOSALS_SITE || is_multisite()) { return; }

const MAX_OWNER_PROPOSALS = 8;
const MAX_OWNER_REQUESTS = 128;
const MAX_OWNER_BYTES = 8388608;

// Compare semantic schema, not server-specific SHOW CREATE formatting.
function storage_schema_valid($allow_missing = false) {
    global $wpdb;
    $contracts = [
        $wpdb->prefix . 'ac_proposal_owners' => ['owner_id' => ['bigint unsigned', null]],
        $wpdb->prefix . 'ac_proposal_requests' => [
            'owner_id' => ['bigint unsigned', null], 'request_id' => ['varbinary(32)', null],
            'request_hash' => ['varbinary(64)', null], 'proposal_id' => ['bigint unsigned', null],
            'response' => ['longtext', 'utf8mb4_bin']],
    ];
    foreach ($contracts as $table => $columns) {
        $status = $wpdb->get_row($wpdb->prepare('SELECT ENGINE,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $table), ARRAY_A);
        if ($wpdb->last_error) { return false; }
        if (!$status) { if ($allow_missing) { continue; } return false; }
        if ($status['ENGINE'] !== 'InnoDB' || $status['TABLE_TYPE'] !== 'BASE TABLE') { return false; }
        $quoted = '`' . str_replace('`', '``', $table) . '`';
        $actual = $wpdb->get_results("SHOW FULL COLUMNS FROM $quoted", ARRAY_A);
        if ($wpdb->last_error || !is_array($actual) || array_column($actual, 'Field') !== array_keys($columns)) { return false; }
        foreach ($actual as $column) {
            [$type, $collation] = $columns[$column['Field']];
            $observed = preg_replace('/^bigint\\([0-9]+\\)/', 'bigint', strtolower($column['Type']));
            if ($observed !== $type || $column['Collation'] !== $collation || $column['Null'] !== 'NO'
                || $column['Default'] !== null || $column['Extra'] !== '' || $column['Comment'] !== '') { return false; }
        }
        $keys = array_key_exists('request_id', $columns) ? ['owner_id', 'request_id'] : ['owner_id'];
        $indexes = $wpdb->get_results("SHOW INDEX FROM $quoted", ARRAY_A);
        if ($wpdb->last_error || !is_array($indexes) || count($indexes) !== count($keys)) { return false; }
        usort($indexes, static function ($a, $b) { return (int) $a['Seq_in_index'] <=> (int) $b['Seq_in_index']; });
        foreach ($indexes as $i => $index) {
            if ($index['Key_name'] !== 'PRIMARY' || (int) $index['Non_unique'] !== 0
                || (int) $index['Seq_in_index'] !== $i + 1 || $index['Column_name'] !== $keys[$i]
                || $index['Sub_part'] !== null || $index['Index_type'] !== 'BTREE' || $index['Collation'] !== 'A'
                || ($index['Ignored'] ?? 'NO') !== 'NO' || ($index['Visible'] ?? 'YES') !== 'YES') { return false; }
        }
        $constraints = $wpdb->get_results($wpdb->prepare('SELECT CONSTRAINT_NAME,CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $table), ARRAY_A);
        if ($wpdb->last_error || $constraints !== [['CONSTRAINT_NAME' => 'PRIMARY', 'CONSTRAINT_TYPE' => 'PRIMARY KEY']]) { return false; }
        $triggers = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE=%s', $table));
        if ($wpdb->last_error || $triggers === null || (int) $triggers !== 0) { return false; }
        $partitions = $wpdb->get_results($wpdb->prepare('SELECT PARTITION_NAME FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $table), ARRAY_A);
        if ($wpdb->last_error || $partitions !== [['PARTITION_NAME' => null]]) { return false; }
    }
    return true;
}
function install_storage() {
    if (!defined('WP_CLI') || !WP_CLI || !current_user_can('manage_options')) { return error('forbidden', 403); }
    global $wpdb;
    $transaction = $wpdb->get_var('SELECT @@in_transaction');
    if ($transaction === null || $wpdb->last_error || (int) $transaction !== 0) { return error('transaction_busy', 503); }
    $fail = static function () {
        // A stale marker must not admit operations after a failed explicit revalidation.
        delete_option('ac_proposals_storage_version');
        return error('storage_unavailable', 503);
    };
    // Inspect existing objects before DDL; never ALTER or repair incompatible user data.
    if (!storage_schema_valid(true)) { return $fail(); }
    $owners = $wpdb->prefix . 'ac_proposal_owners'; $requests = $wpdb->prefix . 'ac_proposal_requests';
    $a = $wpdb->query("CREATE TABLE IF NOT EXISTS $owners (owner_id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB");
    $b = $wpdb->query("CREATE TABLE IF NOT EXISTS $requests (owner_id BIGINT UNSIGNED NOT NULL, request_id VARBINARY(32) NOT NULL,
        request_hash VARBINARY(64) NOT NULL, proposal_id BIGINT UNSIGNED NOT NULL,
        response LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
        PRIMARY KEY(owner_id,request_id)) ENGINE=InnoDB");
    if ($a === false || $b === false || !storage_schema_valid()) { return $fail(); }
    update_option('ac_proposals_storage_version', '0.1', false);
    return get_option('ac_proposals_storage_version') === '0.1' ? true : $fail();
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
