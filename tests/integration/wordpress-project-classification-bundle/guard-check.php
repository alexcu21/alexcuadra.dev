<?php
class WP_Error {public function __construct(...$args){}}
function live_mode(){return str_starts_with($GLOBALS['mode'],'live_');}
function wp_get_environment_type(){return live_mode()||$GLOBALS['mode']==='wrong_environment'?'production':'local';}
function home_url(){return $GLOBALS['mode']==='live_wrong_home'?'https://wrong.example':(live_mode()||$GLOBALS['mode']==='full_profile_local'?'https://content.alexcuadra.dev':'http://wordpress');}
function site_url(){return in_array($GLOBALS['mode'],['wrong_siteurl','live_wrong_siteurl'],true)?'https://wrong.example':home_url();}
function is_multisite(){return $GLOBALS['mode']==='multisite';}
function is_user_logged_in(){return true;}
function current_user_can(...$args){return true;}
function add_action(...$args){$GLOBALS['hooks']++;}
if(($argv[1]??'')==='worker'){
 $mode=$argv[2];$hooks=0;
 if($mode!=='no_wordpress'){define('ABSPATH','/synthetic/');}
 if(!live_mode()&&$mode!=='missing_flag'){define('AC_PROJECT_CLASSIFICATION_LAB',$mode!=='false_flag');}
 if(live_mode()&&$mode!=='live_no_optin'){define('AC_EDITORIAL_PROJECT_CLASSIFICATION_SITE',$mode==='live_wrong_constant'?'https://wrong.example':'https://content.alexcuadra.dev');}
 define('DB_HOST',live_mode()||$mode==='wrong_database'?'hosting.example':'db');
 require dirname(__DIR__,3).'/wordpress/candidates/alexcuadra-editorial-project-classification-0.1.0-rc.1/reader.php';
 echo json_encode(['hooks'=>$hooks,'permitted'=>AlexCuadra\EditorialProjectClassification\permission()===true]);exit;
}
$cases=[];
foreach(['valid','full_profile_local','no_wordpress','missing_flag','false_flag','wrong_environment','wrong_siteurl','wrong_database','multisite','live_valid','live_no_optin','live_wrong_constant','live_wrong_home','live_wrong_siteurl']as$mode){
 $p=proc_open([PHP_BINARY,__FILE__,'worker',$mode],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$status=proc_close($p);
 $r=json_decode($out,true);$wanted=in_array($mode,['valid','full_profile_local','live_valid'],true);if($status!==0||!is_array($r)||$r['hooks']!==($wanted?2:0)||$r['permitted']!==$wanted){throw new RuntimeException('GUARD_FAILED:'.$mode);}$cases[]=$mode;
}
echo 'CLASSIFICATION_BUNDLE_GUARD_RESULT='.json_encode(['passed'=>count($cases),'failed'=>0,'cases'=>$cases]).PHP_EOL;
