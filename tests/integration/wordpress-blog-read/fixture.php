<?php
/** Plugin Name: Synthetic blog read integration fixture */
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { return; }
add_filter('pre_wp_mail', '__return_true');
add_action('init', static function () {
    register_post_type('project', ['public' => true, 'show_in_rest' => true, 'supports' => ['title','editor']]);
}, 0);
