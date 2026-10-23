<?php
global $wpdb;$checks=0;
foreach(['prepare-project-application','inspect-project-review','apply-project-review','prepare-project-recovery','recover-project-review','get-project-effect-state'] as $name){if(wp_get_ability('alexcuadra/'.$name)!==null){throw new RuntimeException('disabled_package_registered_ability');}$checks++;}
try{AlexCuadra\EditorialOperatingPackage\controller();throw new RuntimeException('disabled_controller_allowed');}catch(RuntimeException $e){if($e->getMessage()!=='operating_package_disabled'){throw $e;}$checks++;}
$count=$wpdb->get_var("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('{$wpdb->prefix}ac_exp_operating_reviews','{$wpdb->prefix}ac_exp_source_events')");if((int)$count!==0){throw new RuntimeException('disabled_package_created_storage');}$checks++;
echo 'SOURCE_PACKAGE_DISABLED_RESULT='.wp_json_encode(['checks'=>$checks,'passed'=>true,'abilitiesRegistered'=>false,'storageCreated'=>false]).PHP_EOL;
