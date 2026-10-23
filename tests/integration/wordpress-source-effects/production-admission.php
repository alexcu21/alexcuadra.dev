<?php
use AlexCuadra\EditorialSourceExperiment as X;
use AlexCuadra\EditorialOperatingPackage as K;
require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/operating-contract.php';
require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/operating-abilities.php';
global $wpdb;$phase=getenv('AC_ADMISSION_PHASE');$GLOBALS['production_checks']=0;
function prod_check($ok,$label){$GLOBALS['production_checks']++;if(!$ok){throw new RuntimeException('PRODUCTION_ADMISSION: '.$label);}}
function prod_denied($fn,$code){try{$fn();}catch(RuntimeException $e){prod_check($e->getMessage()===$code,'expected '.$code.' got '.$e->getMessage());return;}throw new RuntimeException('PRODUCTION_ADMISSION: allowed');}
function prod_snapshot(){global $wpdb;$hash=hash_init('sha256');foreach(['posts','postmeta','usermeta','options','terms','term_taxonomy','term_relationships'] as $key){$rows=$wpdb->get_results("SELECT * FROM {$wpdb->$key}",ARRAY_A);if($wpdb->last_error){throw new RuntimeException('snapshot_sql');}$rows=array_map('serialize',$rows);sort($rows);hash_update($hash,serialize($rows));}foreach(['source_events','source_effects','operating_reviews','operating_approvals','operating_requests','operating_apps'] as $name){$rows=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}ac_exp_$name",ARRAY_A);if($wpdb->last_error){throw new RuntimeException('snapshot_sql');}$rows=array_map('serialize',$rows);sort($rows);hash_update($hash,serialize($rows));}return hash_final($hash);}
$before=prod_snapshot();prod_check(!function_exists('link'),'actual PHP link disabled');prod_check(!file_exists(WPMU_PLUGIN_DIR.'/source-effects-fixture.php'),'lab MU fixture absent');
if($phase==='admitted'){
    try{X\production_wp_guard();}catch(Throwable $e){echo 'ADMISSION_DIAGNOSTIC='.wp_json_encode(array_intersect_key(X\production_profile(),array_flip(['loadedPlugins','loadedMu']))).PHP_EOL;throw $e;}prod_check(true,'runtime production profile accepted on isolated DB');
    foreach(X\operating_ability_specs() as $name=>$spec){prod_check(wp_get_ability('alexcuadra/'.$name) instanceof WP_Ability,'normal production registration');}
    $c=K\controller();$owner=get_user_by('login','mcp_operating_owner')->ID;wp_set_current_user($owner);
    $proposal=(int)$wpdb->get_var($wpdb->prepare("SELECT proposal_id FROM {$wpdb->prefix}ac_exp_operating_apps WHERE owner_id=%d",$owner));
    $r=wp_get_ability('mcp-adapter/execute-ability')->execute(['ability_name'=>'alexcuadra/get-project-effect-state','parameters'=>['proposal_id'=>$proposal,'locale'=>'en']]);
    prod_check(!is_wp_error($r)&&($r['success']??false)&&$r['data']['effect_state']==='pending','normal installed adapter reads production-admitted controller');
    $drift=static fn($active)=>array_values(array_diff($active,['wpgraphql-ide/wpgraphql-ide.php']));add_filter('option_active_plugins',$drift);
    try{prod_denied(fn()=>$c->effectStatus($proposal,'en'),'operating_destination_changed');}finally{remove_filter('option_active_plugins',$drift);}
    prod_check($c->effectStatus($proposal,'en')['effect_state']==='pending','fresh guard accepts restored profile');
    prod_denied(fn()=>X\write_source([],static fn()=>null,static fn()=>null,static fn()=>null),'invalid_document');
    wp_set_current_user(1);$audit=$c->auditOwner($owner);prod_check($audit['owner_id']===$owner,'production guard permits audited transaction');
    $private='/tmp/production-export-'.bin2hex(random_bytes(8));mkdir($private,0700);chmod($private,0700);
    $export=$c->exportOwner($owner,$private.'/owner.ndjson',true);prod_check($export['verified']&&X\verify_operating_export($private.'/owner.ndjson')===$export,'production-admitted read-only export works without link');
    wp_set_current_user(0);
}else{
    $code=$phase==='local-environment'?'operating_destination_changed':'operating_destination_optin_required';
    prod_denied(fn()=>K\controller(),$code);
    foreach(X\operating_ability_specs() as $name=>$spec){prod_check(wp_get_ability('alexcuadra/'.$name)===null,'invalid production context registers no ability');}
}
prod_check(prod_snapshot()===$before,'no source schema ledger grants options or roles mutated');
echo 'SOURCE_PRODUCTION_ADMISSION_RESULT='.wp_json_encode(['phase'=>$phase,'checks'=>$GLOBALS['production_checks'],'passed'=>true,'dataUnchanged'=>true,'actualHostingAccessed'=>false]).PHP_EOL;
