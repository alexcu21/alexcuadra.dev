<?php
require_once (getenv('AC_SOURCE_EFFECTS_PACKAGE')?'/operating-package/includes/':'/experiment/').'production-admission.php';
use AlexCuadra\EditorialSourceExperiment as X;
$expected=X\production_expected_profile();$GLOBALS['admission_checks']=0;$loaded=[];foreach($expected['plugins'] as $r){$loaded[$r['file']]=$r['active'];}$loaded['alexcuadra-editorial-operating/alexcuadra-editorial-operating.php']=true;ksort($loaded);$muLoaded=[];foreach($expected['muPlugins'] as $r){$muLoaded[$r['file']]=true;}ksort($muLoaded);
function admission_check($ok){$GLOBALS['admission_checks']++;if(!$ok){throw new RuntimeException('ADMISSION_PROFILE_ASSERTION');}}
function admission_reject($p){try{X\validate_production_profile($p);}catch(Throwable $e){admission_check($e instanceof RuntimeException&&$e->getMessage()==='operating_destination_changed');return;}throw new RuntimeException('ADMISSION_PROFILE_ACCEPTED_DRIFT');}
$p=['home'=>X\OPERATING_SITE,'site'=>X\OPERATING_SITE,'environment'=>'production','wordpress'=>'7.1.3','php'=>'8.5.11','databaseVersion'=>'11.8.9-MariaDB-log','wpdbClass'=>'wpdb','objectCacheClass'=>'WP_Object_Cache','nativeCacheCode'=>true,'nativeWpdbCode'=>true,'loadedPlugins'=>$loaded,'loadedMu'=>$muLoaded,'themeLoaded'=>true,'themeFunctionsHash'=>hash_file('sha256',get_theme_file_path('functions.php')),'multisite'=>false,'externalObjectCache'=>false,
'dropins'=>array_fill_keys(['db.php','object-cache.php','advanced-cache.php','sunrise.php'],false),'plugins'=>array_merge($expected['plugins'],[['file'=>'alexcuadra-editorial-operating/alexcuadra-editorial-operating.php','version'=>X\OPERATING_VERSION,'active'=>true,'sha256'=>str_repeat('a',64)]]),'muPlugins'=>$expected['muPlugins'],'theme'=>$expected['theme'],'polylangSync'=>[],'engines'=>array_fill_keys(['posts','postmeta','options','terms','term_taxonomy','term_relationships','ac_proposal_owners','ac_proposal_requests'],'InnoDB'),'functions'=>array_fill_keys(['mysqli_query','mysqli_report','mysqli_fetch_row','fopen','fsync','wp_register_ability'],true)];
X\validate_production_profile($p);admission_check(true);
$q=$p;$q['databaseVersion']='11.8.9-MariaDB-ubu2404';$q['plugins']=array_reverse($q['plugins']);$q['muPlugins']=array_reverse($q['muPlugins']);$q['functions']['link']=false;X\validate_production_profile($q);admission_check(true);
foreach(['home'=>'https://wrong.invalid','site'=>X\OPERATING_SITE.'/preview','environment'=>'staging','wordpress'=>'7.1.4','php'=>'8.5.12','databaseVersion'=>'11.8.10-MariaDB-log','wpdbClass'=>'OtherDB','objectCacheClass'=>'OtherCache','nativeCacheCode'=>false,'nativeWpdbCode'=>false,'themeLoaded'=>false,'themeFunctionsHash'=>str_repeat('0',64),'multisite'=>true,'externalObjectCache'=>true,'polylangSync'=>['taxonomies']] as $k=>$v){$q=$p;$q[$k]=$v;admission_reject($q);}
$q=$p;$q['databaseVersion']='11.8.9-MySQL';admission_reject($q);
foreach(array_keys($p) as $k){$q=$p;unset($q[$k]);admission_reject($q);}
foreach(array_keys($p['functions']) as $k){$q=$p;$q['functions'][$k]=false;admission_reject($q);}
foreach(array_keys($p['engines']) as $k){$q=$p;$q['engines'][$k]='MyISAM';admission_reject($q);}
foreach(array_keys($p['dropins']) as $k){$q=$p;$q['dropins'][$k]=true;admission_reject($q);}
foreach(['stylesheet','template','version'] as $k){$q=$p;$q['theme'][$k]='changed';admission_reject($q);}
foreach(['version','active','sha256'] as $k){$q=$p;$q['plugins'][0][$k]=$k==='active'?false:'changed';admission_reject($q);}
$q=$p;array_pop($q['plugins']);admission_reject($q);
$q=$p;$q['plugins'][]=$q['plugins'][0];admission_reject($q);
$q=$p;$q['plugins'][]=['file'=>'unknown/unknown.php','version'=>'1','active'=>false,'sha256'=>str_repeat('b',64)];admission_reject($q);
$q=$p;$q['plugins'][count($q['plugins'])-1]['active']=false;admission_reject($q);
$q=$p;$q['plugins'][count($q['plugins'])-1]['version']='0.1.0-rc.1';admission_reject($q);
$q=$p;$q['muPlugins'][0]['sha256']=str_repeat('0',64);admission_reject($q);
$q=$p;array_pop($q['muPlugins']);admission_reject($q);
$q=$p;$q['muPlugins'][]=$q['muPlugins'][0];admission_reject($q);
foreach(['loadedPlugins','loadedMu'] as $key){foreach(array_keys($p[$key]) as $file){$q=$p;$q[$key][$file]=!$q[$key][$file];admission_reject($q);}}
echo 'SOURCE_ADMISSION_PROFILE_RESULT='.wp_json_encode(['checks'=>$GLOBALS['admission_checks'],'passed'=>true,'runtimeProfileDriftRejected'=>true,'noProductionBypassAdded'=>true]).PHP_EOL;
