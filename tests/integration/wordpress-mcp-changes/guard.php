<?php
// Standalone activation-context check, without loading WordPress or any database.
$scenario = $argv[1] ?? '';
if (!in_array($scenario, ['production', 'other-origin'], true)) { exit(2); }
define('ABSPATH', '/synthetic/');
function wp_get_environment_type() { global $scenario; return $scenario === 'production' ? 'production' : 'local'; }
function home_url() { return 'https://content.alexcuadra.dev'; }
$registered = [];
function add_action(...$arguments) { global $registered; $registered[] = $arguments[0]; }
require '/changes-plugin/alexcuadra-editorial-changes.php';
echo 'EDITORIAL_CHANGES_GUARD_RESULT=' . json_encode(['scenario'=>$scenario, 'hooksRegistered'=>count($registered), 'passed'=>$registered===[]]) . PHP_EOL;
if ($registered) { exit(1); }
