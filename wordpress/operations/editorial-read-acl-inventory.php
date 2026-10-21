<?php
// Read-only, selected hook origins and visibility booleans. No callback bodies/profile/options.
if (!defined('WP_CLI') || !WP_CLI || rtrim(home_url(), '/') !== 'https://content.alexcuadra.dev') {
    throw new RuntimeException('EXPECTED_WORDPRESS_CLI_SITE');
}
global $wp_filter;
$origins = [];
foreach (['user_has_cap', 'map_meta_cap', 'is_post_type_viewable', 'is_post_status_viewable',
    'pre_get_posts', 'posts_pre_query', 'posts_results', 'the_posts', 'mcp_adapter_check_permission'] as $hook) {
    $origins[$hook] = [];
    foreach (($wp_filter[$hook]->callbacks ?? []) as $callbacks) {
        foreach ($callbacks as $item) {
            $callback = $item['function'];
            try {
                if (is_array($callback)) { $reflection = new ReflectionMethod($callback[0], $callback[1]); }
                elseif (is_object($callback) && !$callback instanceof Closure) { $reflection = new ReflectionMethod($callback, '__invoke'); }
                elseif (is_string($callback) && str_contains($callback, '::')) { $reflection = new ReflectionMethod($callback); }
                else { $reflection = new ReflectionFunction($callback); }
                $file = $reflection->getFileName();
                $root = wp_normalize_path(ABSPATH); $file = $file ? wp_normalize_path($file) : '';
                $source = str_starts_with($file, $root) ? substr($file, strlen($root)) : 'outside-wordpress-or-internal';
                $origins[$hook][] = ['source' => $source, 'line' => $reflection->getStartLine()];
            } catch (ReflectionException $ignored) { $origins[$hook][] = ['source' => 'unresolved']; }
        }
    }
}
$previous = get_current_user_id();
try {
    wp_set_current_user(2);
    $visibility = [];
    foreach ([146 => 'en', 196 => 'es'] as $id => $locale) {
        $post = get_post($id);
        $visibility[] = ['id' => $id, 'published' => $post && $post->post_status === 'publish',
            'unprotected' => $post && $post->post_password === '',
            'publiclyViewable' => $post && is_post_publicly_viewable($post),
            'readPostAllowed' => current_user_can('read_post', $id),
            'languageMatches' => $post && pll_get_post_language($id, 'slug') === $locale];
    }
} finally { wp_set_current_user($previous); }
$theme = wp_get_theme();
WP_CLI::line('EDITORIAL_ACL_INVENTORY=' . wp_json_encode(['theme' => ['stylesheet' => $theme->get_stylesheet(),
    'version' => $theme->get('Version')], 'selectedHookOrigins' => $origins, 'sampleProjectVisibility' => $visibility,
    'completeAclAudit' => false]));
