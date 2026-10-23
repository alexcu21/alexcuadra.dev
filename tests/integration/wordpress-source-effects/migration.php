<?php
require_once '/integration/support.php';require_once '/experiment/operating-contract.php';
use AlexCuadra\EditorialSourceExperiment as X;
global $wpdb;$GLOBALS['joint_checks']=0;
function migration_check($ok){$GLOBALS['joint_checks']++;if(!$ok){throw new RuntimeException('joint_migration_check');}}
function migration_fail($fn,$code){try{$fn();}catch(RuntimeException $e){migration_check($e->getMessage()===$code);return;}throw new RuntimeException('joint_expected_'.$code);}
wp_set_current_user(1);$j=source_operation($wpdb);
migration_fail(fn()=>X\require_operating_storage_ready($wpdb),'contract_storage_not_ready');
$defs=X\operating_storage_definitions($wpdb);migration_check(count($defs)===6);
$seen=[];foreach(array_keys($defs) as $name){migration_check((int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',X\migration_table_name($wpdb,$name)))===0);}
foreach(array_keys($defs) as $stage){
    migration_fail(function()use($wpdb,$j,$stage){X\migrate_operating_storage($wpdb,$j,function($name)use($stage){if($name===$stage){throw new RuntimeException('joint_interrupt');}});},'joint_interrupt');
    migration_fail(fn()=>X\require_operating_storage_ready($wpdb),'contract_storage_not_ready');
    if($stage==='source_events'){
        $table=X\migration_table_name($wpdb,$stage);$wpdb->query("ALTER TABLE $table ADD drift INT NULL");
        try{migration_fail(fn()=>X\migrate_operating_storage($wpdb,$j),'experiment_schema_changed');migration_check((int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',X\migration_table_name($wpdb,'source_effects')))===0);}finally{$wpdb->query("ALTER TABLE $table DROP drift");}
    }
    $seen[$stage]=true;
    foreach(array_keys($seen) as $name){migration_check($wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',X\migration_table_name($wpdb,$name)))==='InnoDB');}
}
$ready=X\migrate_operating_storage($wpdb,$j);migration_check($ready['phase']==='ready');migration_check(X\migrate_operating_storage($wpdb,$j)===$ready);X\require_operating_storage_ready($wpdb);$j->schema();
$u=wp_get_current_user();$u->add_cap('alexcuadra_approve_public_project_changes');wp_set_current_user(0);wp_set_current_user(1);
$c=new X\OperatingContract($wpdb,$j,'snapshot','field_schema','cache_clear');
try{
 migration_fail(fn()=>$c->acceptStorage(false),'contract_quiescence_required');
 $accepted=$c->acceptStorage(true);migration_check($accepted['accepted']&&$accepted['totals']['owners']===0&&$accepted['snapshot']==='single-read-only-repeatable-read');
 $observer=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$name=X\storage_barrier_name($wpdb);$observer->query("DO GET_LOCK('$name',0)");try{migration_fail(fn()=>$c->acceptStorage(true),'contract_storage_busy');migration_fail(fn()=>X\migrate_operating_storage($wpdb,$j),'contract_storage_busy');}finally{$observer->query("DO RELEASE_LOCK('$name')");}
 try{X\owned_transaction($wpdb,function()use($wpdb){$wpdb->query("UPDATE $wpdb->options SET option_value='forbidden' WHERE option_name='blogname'");},true);throw new RuntimeException('readonly_write_allowed');}catch(mysqli_sql_exception $e){migration_check($e->getCode()===1792);}
}finally{get_userdata(1)->remove_cap('alexcuadra_approve_public_project_changes');wp_set_current_user(0);wp_set_current_user(1);}
echo 'SOURCE_JOINT_MIGRATION_RESULT='.wp_json_encode(['checks'=>$GLOBALS['joint_checks'],'passed'=>true,'tables'=>6,'ddlInterruptionCheckpoints'=>6,'realProcessKill'=>false]).PHP_EOL;
