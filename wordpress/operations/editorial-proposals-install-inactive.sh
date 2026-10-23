#!/bin/sh
# Approved deployment phase B1. Alex executes SSH; stop before opt-in/activation/scopes.
set -eu
umask 077
cd "$HOME/domains/alexcuadra.dev/public_html/content"
archive="$HOME/.ac-editorial-deploy/proposals-0.1.0-rc.2-20261023/candidate.zip"
test -f "$archive"
test ! -L "$archive"
actual="$(sha256sum "$archive")"
test "${actual%% *}" = c31e6814864be0692035de7b46322fc2fc0159d2104e0b4d25404c3b15859f07
test ! -e wp-content/plugins/alexcuadra-editorial-proposals
test ! -L wp-content/plugins/alexcuadra-editorial-proposals
printf '%s\n' EDITORIAL_PROPOSALS_INSTALL_INACTIVE_V1
wp --path=. eval 'if (home_url() !== "https://content.alexcuadra.dev" || site_url() !== home_url() || is_multisite() || defined("AC_EDITORIAL_PROPOSALS_SITE") || get_option("ac_proposals_storage_version", false) !== false) { WP_CLI::error("INSTALL_CONTEXT_CHANGED"); }'
wp --path=. plugin install "$archive"
wp --path=. plugin get alexcuadra-editorial-proposals --fields=name,version,status --format=json
wp --path=. eval-file - <<'AC_PACKAGE_VERIFY_PHP'
<?php
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$file = 'alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php';
$plugins = get_plugins();
if (($plugins[$file]['Version'] ?? null) !== '0.1.0-rc.2' || is_plugin_active($file) || defined('AC_EDITORIAL_PROPOSALS_SITE')) {
    WP_CLI::error('PACKAGE_MUST_REMAIN_INACTIVE');
}
$directory = WP_PLUGIN_DIR . '/alexcuadra-editorial-proposals';
$expected = [
    'alexcuadra-editorial-proposals.php' => 'a9a1b498ab18472a30966cfcd160ef91d35aa62420911e31ca30e7ec3452949e',
    'storage.php' => '074f96126397e91b68f407feb3989cc147d9b81aeffb6790766486b9433639af',
    'README.md' => '52de66e733a0ba4a41f004a24c8804c15102187578e7e45e0a3f3c8d99eea617',
];
$names = array_keys($expected); sort($names);
if (is_link($directory) || array_values(array_diff(scandir($directory), ['.', '..'])) !== $names) { WP_CLI::error('UNEXPECTED_PACKAGE_FILES'); }
foreach ($expected as $name => $hash) {
    if (is_link($directory . '/' . $name) || hash_file('sha256', $directory . '/' . $name) !== $hash) { WP_CLI::error('PACKAGE_HASH_MISMATCH'); }
}
WP_CLI::line('PACKAGE_FILES_VERIFIED');
AC_PACKAGE_VERIFY_PHP
printf '%s\n' END_EDITORIAL_PROPOSALS_INSTALL_INACTIVE_V1
