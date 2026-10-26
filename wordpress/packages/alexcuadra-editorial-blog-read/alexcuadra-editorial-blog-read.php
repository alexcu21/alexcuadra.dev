<?php
/**
 * Plugin Name: Alex Cuadra Editorial Blog Read
 * Description: Scoped read-only metadata for native public blog posts.
 * Version: 0.1.0-rc.1
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 */
namespace AlexCuadra\EditorialBlogRead;
if (!defined('ABSPATH')) { exit; }
// Closed by default. Installation/activation never assigns user capabilities.
$target = 'https://content.alexcuadra.dev';
$local = defined('AC_EDITORIAL_BLOG_READ_LAB') && AC_EDITORIAL_BLOG_READ_LAB === true
    && wp_get_environment_type() === 'local' && rtrim(home_url(), '/') === 'http://wordpress'
    && rtrim(site_url(), '/') === 'http://wordpress';
$live = defined('AC_EDITORIAL_BLOG_READ_SITE') && AC_EDITORIAL_BLOG_READ_SITE === $target
    && rtrim(home_url(), '/') === $target && rtrim(site_url(), '/') === $target;
if (is_multisite() || (!$local && !$live)) { return; }
require_once __DIR__ . '/reader.php';
