<?php
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local lab only'); }
$manifest = get_option('portfolio_lab_manifest');
$versions = ['wordpress' => get_bloginfo('version'), 'phpCli' => PHP_VERSION];
foreach (get_plugins() as $file => $plugin) {
    if (in_array(explode('/', $file)[0], ['wp-graphql', 'advanced-custom-fields', 'wpgraphql-acf', 'polylang', 'wp-graphql-polylang'], true)) {
        $versions[explode('/', $file)[0]] = $plugin['Version'];
    }
}
$manifest['versions'] = $versions;
echo wp_json_encode($manifest);
