#!/bin/sh
# Read-only size inventory. Run on the hosting account, never source locally.
set -eu
case "${1:-}" in
    '') database_only=false ;;
    --database-only) database_only=true ;;
    *) printf '%s\n' 'Usage: sh script [--database-only]' >&2; exit 2 ;;
esac
cd "$HOME/domains/alexcuadra.dev/public_html/content"
test -f wp-load.php
test -d wp-content
printf '%s\n' 'EDITORIAL_STORAGE_PREFLIGHT_V1'
if test "$database_only" = false; then
printf '%s\n' 'SERVER_FILESYSTEM'
df -Pk .
printf '%s\n' 'SITE_ALLOCATED_KIB'
du -sk .
printf '%s\n' 'COMPONENT_ALLOCATED_KIB'
for component in plugins mu-plugins themes uploads; do
    if test -d "wp-content/$component"; then
        du -sk "wp-content/$component"
    fi
done
fi
printf '%s\n' 'DATABASE_APPROXIMATE_BYTES'
wp --path=. --skip-plugins --skip-themes eval-file - <<'PHP'
<?php
global $wpdb;
$previous = $wpdb->suppress_errors(true);
$row = $wpdb->get_row('SELECT COUNT(*) AS table_count, COALESCE(SUM(data_length),0) AS data_bytes, COALESCE(SUM(index_length),0) AS index_bytes, COALESCE(SUM(data_length+index_length),0) AS total_bytes FROM information_schema.tables WHERE table_schema=DATABASE()', ARRAY_A);
$failed = $wpdb->last_error !== '';
$wpdb->suppress_errors($previous);
if ($failed || !is_array($row) || (int) ($row['table_count'] ?? 0) < 1) {
    WP_CLI::error('DATABASE_SIZE_UNAVAILABLE');
}
foreach (['table_count', 'data_bytes', 'index_bytes', 'total_bytes'] as $key) {
    if (!isset($row[$key]) || !ctype_digit((string) $row[$key])) {
        WP_CLI::error('UNEXPECTED_DATABASE_SIZE_RESULT');
    }
}
WP_CLI::line('EDITORIAL_DATABASE_SIZE=' . wp_json_encode($row));
PHP
printf '%s\n' 'END_EDITORIAL_STORAGE_PREFLIGHT_V1'
