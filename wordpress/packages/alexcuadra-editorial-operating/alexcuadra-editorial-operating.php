<?php
/**
 * Plugin Name: AlexCuadra Editorial Operating (local candidate)
 * Description: Explicitly opted-in local acceptance of owned editorial operations. Production execution remains disabled.
 * Version: 0.1.0-rc.1
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
function controller(){
    if(!defined('AC_EDITORIAL_OPERATING_LOCAL')||AC_EDITORIAL_OPERATING_LOCAL!==true){throw new \RuntimeException('operating_package_disabled');}
    load_modules();X\local_wp_guard();global $wpdb;
    $j=new X\MariaDbOperation($wpdb,'AlexCuadra\\EditorialSourceExperiment\\clear_polylang_source_cache');
    return new X\OperatingContract($wpdb,$j,'AlexCuadra\\EditorialSourceExperiment\\polylang_source_snapshot',__NAMESPACE__.'\\field_schema','AlexCuadra\\EditorialSourceExperiment\\clear_polylang_source_cache');
}
if(!defined('AC_EDITORIAL_OPERATING_LOCAL')||AC_EDITORIAL_OPERATING_LOCAL!==true){return;}
load_modules();
try{X\local_wp_guard();}catch(\Throwable $e){return;}
add_action('wp_abilities_api_categories_init',static function(){wp_register_ability_category('alexcuadra-operating',['label'=>'Owned editorial operations','description'=>'Explicit local operating acceptance.']);});
add_action('wp_abilities_api_init',static function(){X\register_operating_abilities(__NAMESPACE__.'\\controller');});
