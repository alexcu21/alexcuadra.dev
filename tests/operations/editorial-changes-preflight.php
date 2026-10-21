<?php
// Vanilla PHP fixtures only: no WordPress bootstrap, Docker, database or network.
define('WP_CLI', true); define('ABSPATH', '/synthetic-wordpress/'); define('ARRAY_A', 'ARRAY_A');
class DiagnosticHalt extends RuntimeException {}
class WP_CLI {
    public static $output = []; public static $status = 0;
    public static function line($s) { self::$output[] = $s; }
    public static function error($s) { self::$status = 1; throw new DiagnosticHalt($s); }
    public static function halt($n) { self::$status = $n; throw new DiagnosticHalt('HALTED'); }
}
$scenario = ''; $currentUser = 7;
function home_url() { global $scenario; return $scenario === 'wrong-site' ? 'https://unexpected.example.test' : 'http://wordpress'; }
function is_multisite() { return false; }
function get_userdata($id) { global $scenario; return $scenario === 'missing-user' ? false : (object) ['ID' => 2, 'roles' => ['subscriber']]; }
function get_current_user_id() { global $currentUser; return $currentUser; }
function wp_set_current_user($id) { global $currentUser; $currentUser = $id; }
function current_user_can($cap, $id = null) { global $scenario; return in_array($cap, ['read', 'read_post', 'alexcuadra_read_public_projects'], true) || ($scenario === 'write-scope' && $cap === 'alexcuadra_apply_project_changes'); }
function get_plugins() { return ['synthetic/synthetic.php' => ['Version' => '1.0']]; }
function get_mu_plugins() { return []; }
function is_plugin_active($file) { return true; }
function get_post_type_object($type) { return (object) ['public' => true]; }
function is_post_type_viewable($post) { return true; }
function get_all_post_type_supports($type) { return ['title' => true, 'editor' => true]; }
function acf_get_field($key) { global $scenario; return ['key' => $key, 'parent' => 99, 'name' => 'project_tagline', 'type' => $scenario === 'schema-drift' ? 'text' : 'textarea', 'graphql_field_name' => 'projectTagline']; }
function acf_get_field_group($id) { return ['key' => 'group_676d40f7bb646', 'active' => true, 'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'project']]]]; }
function get_post($id) { return (object) ['post_type' => 'project', 'post_status' => 'publish', 'post_password' => '']; }
function is_post_publicly_viewable($post) { return true; }
function pll_get_post_language($id, $field) { return $id === 146 ? 'en' : 'es'; }
function pll_get_post_translations($id) { return ['en' => 146, 'es' => 196]; }
function wp_normalize_path($path) { return $path; }
function wp_json_encode($value) { return json_encode($value); }
function fixture_save_callback() {}
class ReadOnlyDb {
    public $posts = 'wp_posts', $postmeta = 'wp_postmeta', $terms = 'wp_terms', $term_taxonomy = 'wp_term_taxonomy', $term_relationships = 'wp_term_relationships';
    public $last_error = '', $queries = [], $reads = [];
    public function prepare($sql, ...$args) {
        if (count($args) === 1 && is_array($args[0])) { $args = $args[0]; }
        return vsprintf(str_replace('%s', "'%s'", $sql), $args);
    }
    public function get_results($sql, $format) {
        global $scenario;
        if (!preg_match('/^SELECT\s/i', $sql)) { throw new RuntimeException('NON_READ_QUERY'); }
        $this->queries[] = $sql;
        if (str_contains($sql, 'information_schema.TABLES')) {
            return array_map(static function ($name) use ($scenario) { return ['TABLE_NAME' => $name, 'ENGINE' => $scenario === 'myisam' ? 'MyISAM' : 'InnoDB']; },
                ['wp_posts', 'wp_postmeta', 'wp_terms', 'wp_term_taxonomy', 'wp_term_relationships']);
        }
        if ($scenario === 'query-failure') { $this->last_error = 'SYNTHETIC_PRIVATE_DATABASE_ERROR'; return null; }
        if (str_contains($sql, 'FROM wp_posts')) {
            preg_match('/ID=(\d+)/', $sql, $m); $id = (int) $m[1]; $this->reads[$id] = ($this->reads[$id] ?? 0) + 1;
            return [['ID' => $id, 'post_content' => $scenario === 'source-drift' && $this->reads[$id] > 1 ? 'DRIFT_PRIVATE_BODY' : 'PRIVATE_SOURCE_BODY_MUST_NOT_LEAK']];
        }
        if (str_contains($sql, 'FROM wp_postmeta')) {
            $rows = [['meta_key' => 'project_tagline', 'meta_value' => 'PRIVATE_TAGLINE_MUST_NOT_LEAK'], ['meta_key' => '_project_tagline', 'meta_value' => 'field_676dcb2aec8d4']];
            if (!str_contains($sql, 'AND meta_key IN')) { $rows[] = ['meta_key' => 'private_credential_fixture', 'meta_value' => 'PRIVATE_CREDENTIAL_MUST_NOT_LEAK']; }
            return $rows;
        }
        return [['object_id' => 146, 'term_taxonomy_id' => 8]];
    }
}
$cases = [];
foreach (['baseline', 'wrong-site', 'missing-user', 'invalid-arguments', 'schema-drift', 'myisam', 'write-scope', 'source-drift', 'query-failure'] as $scenario) {
    $currentUser = 7; $wpdb = new ReadOnlyDb(); $wp_version = 'fixture';
    $wp_filter = ['save_post' => (object) ['callbacks' => [10 => [['function' => 'fixture_save_callback']]]]];
    $args = $scenario === 'invalid-arguments' ? ['2', '146', '146'] : ['2', '146', '196'];
    WP_CLI::$output = []; WP_CLI::$status = 0;
    try { include __DIR__ . '/../../wordpress/operations/editorial-changes-preflight.php'; } catch (DiagnosticHalt $e) {}
    $expected = $scenario === 'baseline' ? 0 : 1;
    if (WP_CLI::$status !== $expected || $currentUser !== 7) { throw new RuntimeException('SCENARIO_FAILED_' . $scenario); }
    $output = implode('\n', WP_CLI::$output);
    foreach (['PRIVATE_SOURCE_BODY', 'PRIVATE_TAGLINE', 'PRIVATE_CREDENTIAL', 'SYNTHETIC_PRIVATE_DATABASE_ERROR', 'DRIFT_PRIVATE_BODY'] as $secret) {
        if (str_contains($output, $secret)) { throw new RuntimeException('PRIVATE_OUTPUT_' . $scenario); }
    }
    if ($scenario === 'baseline') {
        $d = json_decode(substr(WP_CLI::$output[0], strlen('EDITORIAL_CHANGES_PREFLIGHT_RESULT=')), true);
        if (!$d['technicalSnapshotPassed'] || !$d['currentUserContextRestored'] || $d['installationEligible'] || $d['operatorWritesPerformed']
            || $d['sourceFingerprintsBefore'] !== $d['sourceFingerprintsAfter'] || count($d['selectedHooks']['save_post']) !== 1) { throw new RuntimeException('BASELINE_REPORT_FAILED'); }
    }
    $cases[] = ['scenario' => $scenario, 'expectedStatus' => $expected, 'observedStatus' => WP_CLI::$status,
        'queriesReadOnly' => true, 'contextRestored' => true, 'privateValuesAbsent' => true];
}
echo 'EDITORIAL_OPERATION_PREFLIGHT_TEST_RESULT=' . json_encode(['passed' => count($cases), 'failed' => 0, 'wordpressOrNetworkLoaded' => false, 'cases' => $cases]) . PHP_EOL;
