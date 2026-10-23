<?php
/** Plugin Name: Private restoration offline guard (trusted test code only) */
if (!defined('AC_PRIVATE_RESTORE') || AC_PRIVATE_RESTORE !== true || wp_get_environment_type() !== 'local') {
    throw new RuntimeException('PRIVATE_RESTORE_CONTEXT_REQUIRED');
}
add_filter('pre_http_request', static function () { return new WP_Error('private_restore_offline', 'EXTERNAL_HTTP_BLOCKED'); }, PHP_INT_MIN);
add_filter('pre_wp_mail', static function () { return false; }, PHP_INT_MIN);
