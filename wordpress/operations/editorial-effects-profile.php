<?php
/** Read-only CLI profile; not an ability or an installation approval. No callbacks invoked. */
if (!defined('WP_CLI') || !WP_CLI || !function_exists('home_url')) { throw new RuntimeException('WORDPRESS_CLI_REQUIRED'); }
if (!in_array(rtrim(home_url(), '/'), ['https://content.alexcuadra.dev', 'http://wordpress'], true) || is_multisite()) { WP_CLI::error('EXPECTED_SINGLE_SITE_OR_ISOLATED_FIXTURE'); }
if (!function_exists('PLL')) { WP_CLI::error('POLYLANG_REQUIRED'); }
global $wp_filter;
$sync = PLL()->options['sync'];
if (!is_array($sync)) { WP_CLI::error('UNEXPECTED_SYNC_CONFIGURATION'); }
$selected = ['post_meta', 'comment_status', 'ping_status', 'menu_order', 'post_date', 'post_parent', 'sticky_posts', '_thumbnail_id', '_wp_page_template', 'taxonomies'];
$flags = [];
foreach ($selected as $key) { $flags[$key] = in_array($key, $sync, true); }
$types = [];
foreach (['hostingerNotification' => ['Hostinger\\Mcp\\Handlers\\WebsitePageUpdated', 'ALLOWED_POST_TYPES'],
    'hostingerLlmsFile' => ['Hostinger\\LlmsTxtGenerator\\LlmsTxtGenerator', 'HOSTINGER_LLMSTXT_SUPPORTED_POST_TYPES']] as $name => [$class, $constant]) {
    if (!class_exists($class)) { WP_CLI::error('EXPECTED_HANDLER_MISSING'); }
    $r = new ReflectionClass($class);
    if (!$r->hasConstant($constant) || !is_array($value = $r->getConstant($constant))) { WP_CLI::error('EXPECTED_TYPE_ALLOWLIST_MISSING'); }
    $types[$name] = ['projectAllowed' => $name === 'hostingerNotification' ? in_array('project', $value, true) : array_key_exists('project', $value),
        'fileHash' => hash_file('sha256', $r->getFileName())];
}
$hooks = [];
foreach (['transition_post_status', 'notify_mcp', 'litespeed_purge_post', 'litespeed_api_purge_post', 'litespeed_purged_post', 'litespeed_delay_purge',
    'pll_copy_post_metas', 'pll_pre_current_user_can_synchronize_post', 'is_protected_meta', 'clean_post_cache', 'updated_post_meta'] as $name) {
    $hooks[$name] = [];
    foreach (($wp_filter[$name]->callbacks ?? []) as $priority => $callbacks) {
        foreach ($callbacks as $entry) {
            $cb = $entry['function'];
            try {
                if (is_array($cb)) { $r = new ReflectionMethod($cb[0], $cb[1]); }
                elseif (is_object($cb) && !$cb instanceof Closure) { $r = new ReflectionMethod($cb, '__invoke'); }
                elseif (is_string($cb) && str_contains($cb, '::')) { $r = new ReflectionMethod($cb); }
                else { $r = new ReflectionFunction($cb); }
                $file = $r->getFileName(); $relative = $file && str_starts_with($file, ABSPATH) ? substr($file, strlen(ABSPATH)) : null;
                $knownRoot = $relative && preg_match('~^(wp-includes/|wp-content/(plugins|mu-plugins|themes)/)~', $relative);
                $symbol = $r instanceof ReflectionMethod ? ($r->getDeclaringClass()->isAnonymous() ? 'anonymous-class' : $r->getDeclaringClass()->getName()) . '::' . $r->getName() : ($cb instanceof Closure ? '{closure}' : $r->getName());
                $hooks[$name][] = ['symbol' => $symbol, 'priority' => (int) $priority, 'acceptedArgs' => $entry['accepted_args'],
                    'source' => $knownRoot ? $relative : 'outside-reviewed-roots', 'line' => $r->getStartLine(), 'fileHash' => $knownRoot ? hash_file('sha256', $file) : null];
            } catch (ReflectionException $e) { $hooks[$name][] = ['unresolved' => true, 'priority' => (int) $priority]; }
        }
    }
}
WP_CLI::line('EDITORIAL_EFFECTS_PROFILE=' . wp_json_encode([
    'format' => 'editorial-effects-profile-v1', 'observedAtUtc' => gmdate('Y-m-d\TH:i:s\Z'),
    'context' => ['wpCli' => true, 'isAdmin' => is_admin(), 'doingCron' => wp_doing_cron(), 'doingAjax' => wp_doing_ajax(), 'restRequest' => defined('REST_REQUEST') && REST_REQUEST],
    'polylangSync' => $flags, 'polylangSyncOtherCount' => count(array_diff($sync, $selected)), 'hostingerTypeGuards' => $types, 'hooks' => $hooks,
    'objectCache' => ['externalBackendInUse' => wp_using_ext_object_cache(), 'dropinPresent' => is_file(WP_CONTENT_DIR . '/object-cache.php')],
    'callbacksInvokedByOperator' => false, 'editorialWritesPerformedByOperator' => false, 'installationEligible' => false,
]));
