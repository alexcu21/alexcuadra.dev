<?php
// Destructive fixtures are restricted to this fresh synthetic, networkless database.
global $table_prefix;
if (!defined('AC_SCHEMA_TEST') || AC_SCHEMA_TEST !== true || DB_NAME !== 'restore_source'
    || DB_HOST !== 'localhost:/socket/mysql.sock' || $table_prefix !== 'schema_') { WP_CLI::error('SYNTHETIC_SCHEMA_CONTEXT_REQUIRED'); }
require '/candidate/alexcuadra-editorial-proposals.php';
use AlexCuadra\EditorialProposals as P;
global $wpdb, $owners, $requests, $passed;
$owners = $wpdb->prefix . 'ac_proposal_owners'; $requests = $wpdb->prefix . 'ac_proposal_requests';
$passed = 0; $cases = [];
function verify_schema_test($ok, $label) {
    global $passed;
    if (!$ok) { WP_CLI::error('SCHEMA_ASSERTION_FAILED:' . $label); }
    $passed++;
}
function reset_schema_test() {
    global $wpdb, $owners, $requests;
    $wpdb->query("DROP TABLE IF EXISTS $requests"); $wpdb->query("DROP TABLE IF EXISTS $owners");
    delete_option('ac_proposals_storage_version');
    verify_schema_test(P\install_storage() === true, 'valid_install');
    $wpdb->query("INSERT INTO $owners VALUES (7)");
    $wpdb->query("INSERT INTO $requests VALUES (7,REPEAT('a',32),REPEAT('b',64),11,'synthetic-receipt')");
}
function schema_snapshot() {
    global $wpdb, $owners, $requests;
    $state = [];
    foreach ([$owners, $requests] as $table) {
        $state[] = $wpdb->get_results("SHOW CREATE TABLE $table", ARRAY_A);
        $state[] = $wpdb->get_results("SELECT * FROM $table ORDER BY owner_id", ARRAY_A);
    }
    return hash('sha256', serialize($state));
}
verify_schema_test(!P\storage_schema_valid(), 'empty_not_ready');
verify_schema_test(P\storage_schema_valid(true), 'empty_can_prepare');
reset_schema_test();
verify_schema_test(P\storage_schema_valid(), 'exact_valid_schema');
$before = schema_snapshot();
verify_schema_test(P\install_storage() === true, 'repeat_install');
verify_schema_test(schema_snapshot() === $before, 'repeat_preserves_schema_rows');
$changes = [
    'signed_owner' => "ALTER TABLE $owners MODIFY owner_id BIGINT NOT NULL",
    'narrow_owner' => "ALTER TABLE $owners MODIFY owner_id INT UNSIGNED NOT NULL",
    'auto_increment_owner' => "ALTER TABLE $owners MODIFY owner_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
    'extra_column' => "ALTER TABLE $owners ADD extra_value INT NULL",
    'column_comment' => "ALTER TABLE $owners MODIFY owner_id BIGINT UNSIGNED NOT NULL COMMENT 'unexpected'",
    'owner_default' => "ALTER TABLE $owners MODIFY owner_id BIGINT UNSIGNED NOT NULL DEFAULT 7",
    'short_request_id' => "ALTER TABLE $requests MODIFY request_id VARBINARY(31) NOT NULL",
    'text_request_id' => "ALTER TABLE $requests MODIFY request_id VARCHAR(32) NOT NULL",
    'short_hash' => "ALTER TABLE $requests MODIFY request_hash VARBINARY(63) NOT NULL",
    'nullable_hash' => "ALTER TABLE $requests MODIFY request_hash VARBINARY(64) NULL",
    'hash_default' => "ALTER TABLE $requests MODIFY request_hash VARBINARY(64) NOT NULL DEFAULT 'unexpected'",
    'signed_proposal' => "ALTER TABLE $requests MODIFY proposal_id BIGINT NOT NULL",
    'short_response' => "ALTER TABLE $requests MODIFY response TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL",
    'response_collation' => "ALTER TABLE $requests MODIFY response LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL",
    'missing_column' => "ALTER TABLE $requests DROP response",
    'column_order' => "ALTER TABLE $requests MODIFY response LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL FIRST",
    'reversed_primary' => "ALTER TABLE $requests DROP PRIMARY KEY, ADD PRIMARY KEY(request_id,owner_id)",
    'incomplete_primary' => "ALTER TABLE $requests DROP PRIMARY KEY, ADD PRIMARY KEY(request_id)",
    'prefix_primary' => "ALTER TABLE $requests DROP PRIMARY KEY, ADD PRIMARY KEY(owner_id,request_id(16))",
    'missing_primary' => "ALTER TABLE $owners DROP PRIMARY KEY",
    'extra_index' => "ALTER TABLE $requests ADD INDEX unexpected(proposal_id)",
    'extra_unique' => "ALTER TABLE $requests ADD UNIQUE unexpected(proposal_id)",
    'extra_check' => "ALTER TABLE $requests ADD CONSTRAINT unexpected CHECK (proposal_id > 0)",
    'foreign_key' => "ALTER TABLE $requests ADD CONSTRAINT unexpected FOREIGN KEY(owner_id) REFERENCES $owners(owner_id)",
    'owner_myisam' => "ALTER TABLE $owners ENGINE=MyISAM",
    'requests_myisam' => "ALTER TABLE $requests ENGINE=MyISAM",
    'partitioned' => "ALTER TABLE $owners PARTITION BY HASH(owner_id) PARTITIONS 2",
    'trigger' => "CREATE TRIGGER unexpected BEFORE INSERT ON $requests FOR EACH ROW SET NEW.response=NEW.response",
];
// Mutations must execute, not merely be attempted: otherwise a rejection could be a false positive.
foreach ($changes as $name => $sql) {
    reset_schema_test();
    // Narrowing binary columns requires short fixture values first.
    if (in_array($name, ['short_request_id', 'short_hash'], true)) {
        $wpdb->query("UPDATE $requests SET request_id='a',request_hash='b'");
    }
    verify_schema_test($wpdb->query($sql) !== false, $name . '_fixture_applied');
    $before = schema_snapshot();
    verify_schema_test(!P\storage_schema_valid(), $name . '_schema_rejected');
    $result = P\install_storage();
    verify_schema_test(is_wp_error($result) && $result->get_error_code() === 'ac_change_storage_unavailable', $name . '_migration_denied');
    verify_schema_test(get_option('ac_proposals_storage_version') === false, $name . '_stale_marker_removed');
    verify_schema_test(schema_snapshot() === $before, $name . '_rows_schema_preserved');
    $cases[] = $name;
}
reset_schema_test();
$wpdb->query("DROP TABLE $requests");
$wpdb->query("ALTER TABLE $owners MODIFY owner_id INT UNSIGNED NOT NULL");
verify_schema_test(is_wp_error(P\install_storage()), 'partial_incompatible_denied');
verify_schema_test($wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $requests)) === '0', 'no_new_table_when_existing_incompatible');
reset_schema_test();
$before = schema_snapshot(); $wpdb->query('START TRANSACTION');
$result = P\install_storage();
verify_schema_test(is_wp_error($result) && $result->get_error_code() === 'ac_change_transaction_busy', 'outer_transaction_denied');
verify_schema_test($wpdb->get_var('SELECT @@in_transaction') === '1', 'outer_transaction_still_open');
$wpdb->query('ROLLBACK');
verify_schema_test(schema_snapshot() === $before, 'outer_transaction_no_ddl');

foreach (['information_schema.TABLES', 'SHOW FULL COLUMNS', 'SHOW INDEX', 'information_schema.TABLE_CONSTRAINTS', 'information_schema.TRIGGERS', 'information_schema.PARTITIONS'] as $target) {
    reset_schema_test(); $before = schema_snapshot(); $prior = $wpdb->suppress_errors(true);
    $filter = static function ($query) use ($target) { return str_contains($query, $target) ? 'SELECT FROM synthetic_invalid_sql' : $query; };
    add_filter('query', $filter);
    $result = P\install_storage();
    remove_filter('query', $filter); $wpdb->suppress_errors($prior);
    verify_schema_test(is_wp_error($result), 'metadata_sql_failure_denied');
    verify_schema_test(get_option('ac_proposals_storage_version') === false, 'metadata_failure_marker_removed');
    verify_schema_test(schema_snapshot() === $before, 'metadata_failure_preserves_tables');
}
reset_schema_test(); $before = schema_snapshot(); wp_set_current_user(0);
verify_schema_test(is_wp_error(P\install_storage()), 'anonymous_operator_denied');
verify_schema_test(schema_snapshot() === $before, 'anonymous_no_changes');
WP_CLI::line('PROPOSALS_SCHEMA_RESULT=' . wp_json_encode(['passed' => $passed, 'failed' => 0,
    'incompatibleSchemasRejected' => $cases, 'metadataSqlFailuresRejected' => 6,
    'wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION, 'databaseServer' => $wpdb->db_server_info()]));
