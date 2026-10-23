<?php
/**
 * Plugin Name: AlexCuadra Editorial Operating (acceptance candidate)
 * Description: Owned editorial operations with explicit destination admission and independent approvals.
 * Version: 0.1.0-rc.3
 * Requires at least: 7.1.3
 * Requires PHP: 8.5
 */
namespace AlexCuadra\EditorialOperatingPackage;
use AlexCuadra\EditorialSourceExperiment as X;
if(!defined('ABSPATH')){return;}
// No schema writes, caps, activation hooks or file exports on load/activation.
function load_modules(){
    foreach(['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities'] as $file){require_once __DIR__.'/includes/'.$file.'.php';}
}
function field_schema(){
    X\polylang_admission();$f=acf_get_field('field_676dcb2aec8d4');
    if(!$f||$f['name']!=='project_tagline'){throw new \RuntimeException('acf_schema_changed');}
    return hash('sha256',serialize($f));
}
function opted_in(){return (defined('AC_EDITORIAL_OPERATING_LOCAL')&&AC_EDITORIAL_OPERATING_LOCAL===true)||defined('AC_EDITORIAL_OPERATING_SITE');}
function controller(){
    if(!opted_in()){throw new \RuntimeException('operating_package_disabled');}
    load_modules();X\local_wp_guard();global $wpdb;
    $j=new X\MariaDbOperation($wpdb,'AlexCuadra\\EditorialSourceExperiment\\clear_polylang_source_cache');
    return new X\OperatingContract($wpdb,$j,'AlexCuadra\\EditorialSourceExperiment\\polylang_source_snapshot',__NAMESPACE__.'\\field_schema','AlexCuadra\\EditorialSourceExperiment\\clear_polylang_source_cache');
}
if(!opted_in()){return;}
load_modules();
// Destination plugins (including Polylang) must finish loading before admission.
function admitted(){try{X\local_wp_guard();return true;}catch(\Throwable $e){return false;}}
add_action('wp_abilities_api_categories_init',static function(){if(admitted()){wp_register_ability_category('alexcuadra-operating',['label'=>'Owned editorial operations','description'=>'Explicitly admitted editorial operations.']);}});
add_action('wp_abilities_api_init',static function(){if(admitted()){X\register_operating_abilities(__NAMESPACE__.'\\controller');}});
