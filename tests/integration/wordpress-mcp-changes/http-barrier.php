<?php
/** Plugin Name: Synthetic change-proposal HTTP race barrier */
// Test instrumentation only; never mounted/copied to the shared CMS.
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress'
    || !isset($_SERVER['HTTP_X_AC_CHANGES_RACE'])
    || !preg_match('/^[a-f0-9]{32}$/D', $_SERVER['HTTP_X_AC_CHANGES_RACE'])) { return; }
add_filter('query', static function ($query) {
    static $entered = false;
    global $wpdb;
    if ($entered || !str_starts_with(ltrim($query), 'SELECT owner_id FROM ' . $wpdb->prefix . 'ac_change_owners')
        || !str_contains($query, 'FOR UPDATE')) { return $query; }
    $entered = true; // Prevent recursion for the instrumentation's own SQL.
    $table = $wpdb->prefix . 'ac_change_http_races';
    $probe = new \wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
    // Separate autocommit connection: arrivals must be visible while the proposal transaction is open.
    $key = $_SERVER['HTTP_X_AC_CHANGES_RACE']; $worker = getmypid(); $arrival = microtime(true);
    $added = $probe->query($probe->prepare("INSERT INTO $table (race_key,worker_id,arrived,released) VALUES (%s,%d,%f,0)", $key,$worker,$arrival));
    if ($added !== 1) { throw new RuntimeException('HTTP_RACE_BARRIER_INSERT_FAILED'); }
    $deadline = microtime(true) + 10;
    do {
        $count = (int) $probe->get_var($probe->prepare("SELECT COUNT(*) FROM $table WHERE race_key=%s",$key));
        if ($count === 2) {
            $probe->query($probe->prepare("UPDATE $table SET released=%f WHERE race_key=%s AND worker_id=%d",microtime(true),$key,$worker));
            $probe->close();
            return $query; // Both workers reached admission; updates already loaded their proposal versions.
        }
        usleep(25000);
    } while (microtime(true) < $deadline);
    $probe->close();
    throw new RuntimeException('HTTP_RACE_BARRIER_TIMEOUT');
});
