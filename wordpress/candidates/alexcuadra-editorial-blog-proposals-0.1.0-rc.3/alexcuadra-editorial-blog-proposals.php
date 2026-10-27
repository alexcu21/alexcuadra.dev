<?php
/**
 * Plugin Name: Alex Cuadra Editorial Blog Proposals
 * Description: Private owned post proposal plans; no source writer or public publication.
 * Version: 0.1.0-rc.3
 * Requires at least: 7.1.3
 * Requires PHP: 8.5
 * License: GPL-2.0-or-later
 */
namespace AlexCuadra\EditorialBlogProposals;
if(!defined('ABSPATH')){exit;}
require_once __DIR__.'/admission.php';
// Activation does not initialize tables, enroll an owner or assign a capability.
if(!site_admitted()||!profile_report()['ready']){return;}
require_once __DIR__.'/wordpress-repository.php';
require_once __DIR__.'/abilities.php';
