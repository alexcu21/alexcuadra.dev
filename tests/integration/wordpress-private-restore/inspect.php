<?php
// Direct local SQL inspection only. Never prints source rows, names or credentials to the UI.
ini_set('display_errors', '0');
ini_set('zend.exception_ignore_args', '1');
set_exception_handler(static function () { fwrite(STDERR, "PRIVATE_RESTORE_INSPECTION_FAILED\n"); exit(1); });
$database = $argv[1] ?? '';
if (!in_array($database, ['restore_source', 'restore_roundtrip'], true)) { throw new RuntimeException('INVALID_TARGET'); }
$manifest = json_decode(file_get_contents('/control/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$db = new mysqli('localhost', 'restore_user', 'local-restore-only', $database, null, '/socket/mysql.sock');
$db->set_charset('utf8mb4');
$tables = [];
$result = $db->query('SHOW FULL TABLES');
while ($row = $result->fetch_row()) {
    if ($row[1] !== 'BASE TABLE') { throw new RuntimeException('UNEXPECTED_VIEW'); }
    $tables[] = $row[0];
}
sort($tables); $schemas = []; $checksums = []; $counts = [];
foreach ($tables as $table) {
    $quoted = '`' . str_replace('`', '``', $table) . '`';
    $schema = $db->query('SHOW CREATE TABLE ' . $quoted)->fetch_assoc();
    $schemas[$table] = hash('sha256', preg_replace('/ AUTO_INCREMENT=[0-9]+/', '', $schema['Create Table']));
    $checksum = $db->query('CHECKSUM TABLE ' . $quoted . ' EXTENDED')->fetch_assoc()['Checksum'];
    if ($checksum === null || !ctype_digit((string) $checksum)) { throw new RuntimeException('CHECKSUM_UNAVAILABLE'); }
    $checksums[$table] = (string) $checksum;
    $counts[$table] = (int) $db->query('SELECT COUNT(*) FROM ' . $quoted)->fetch_row()[0];
    $health = $db->query('CHECK TABLE ' . $quoted);
    $healthy = false;
    while ($status = $health->fetch_assoc()) {
        if ($status['Msg_type'] === 'error') { throw new RuntimeException('TABLE_CHECK_FAILED'); }
        if ($status['Msg_type'] === 'status' && $status['Msg_text'] === 'OK') { $healthy = true; }
    }
    if (!$healthy) { throw new RuntimeException('TABLE_HEALTH_UNVERIFIED'); }
}
$expected = $manifest['profile']['schemaHashes']; ksort($expected);
if ($schemas !== $expected || count($schemas) !== 37) { throw new RuntimeException('SOURCE_SCHEMA_MISMATCH'); }
echo json_encode(['tableCount' => count($tables), 'schemas' => $schemas, 'checksums' => $checksums, 'rows' => $counts,
    'tableHealthVerified' => true, 'databaseServer' => $db->server_info, 'php' => PHP_VERSION], JSON_THROW_ON_ERROR) . "\n";
