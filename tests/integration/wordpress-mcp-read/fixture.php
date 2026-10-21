<?php
/** Plugin Name: Synthetic editorial read laboratory */
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { return; }
add_action('init', static function () {
    register_post_type('project', ['public' => true, 'show_in_rest' => true, 'capability_type' => 'page',
        'map_meta_cap' => true, 'supports' => ['title', 'editor']]);
}, 0);
add_filter('pll_get_post_types', static function ($types) { $types['project'] = 'project'; return $types; });
