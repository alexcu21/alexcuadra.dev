<?php
class WP_Error {public function __construct(...$args){}}
function wp_get_environment_type(){return $GLOBALS['mode']==='wrong_environment'?'production':'local';}
function home_url(){return $GLOBALS['mode']==='production_url'?'https://content.alexcuadra.dev':'http://wordpress';}
function site_url(){return $GLOBALS['mode']==='wrong_siteurl'?'http://elsewhere':'http://wordpress';}
function is_multisite(){return $GLOBALS['mode']==='multisite';}
function is_user_logged_in(){return true;}
function current_user_can(...$args){return true;}
function add_action(...$args){$GLOBALS['hooks']++;}
if(($argv[1]??'')==='worker'){
 $GLOBALS['mode']=$argv[2];$GLOBALS['hooks']=0;
 if($mode!=='no_wordpress'){define('ABSPATH','/synthetic/');}
 if($mode!=='missing_flag'){define('AC_PROJECT_CLASSIFICATION_LAB',$mode!=='false_flag');}
 define('DB_HOST',$mode==='wrong_database'?'hosting.example':'db');
 require dirname(__DIR__,3).'/wordpress/experimental/editorial-project-classification/reader.php';
 echo json_encode(['hooks'=>$hooks,'permitted'=>AlexCuadra\ProjectClassificationLab\permission()===true]);exit;
}
$cases=[];
foreach(['valid','no_wordpress','missing_flag','false_flag','wrong_environment','production_url','wrong_siteurl','wrong_database','multisite']as$mode){
 $p=proc_open([PHP_BINARY,__FILE__,'worker',$mode],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$status=proc_close($p);
 $r=json_decode($out,true);$wanted=$mode==='valid';if($status!==0||!is_array($r)||$r['hooks']!==($wanted?2:0)||$r['permitted']!==$wanted){throw new RuntimeException('GUARD_FAILED:'.$mode);}$cases[]=$mode;
}
echo 'PROJECT_CLASSIFICATION_GUARD_RESULT='.json_encode(['passed'=>count($cases),'failed'=>0,'cases'=>$cases]).PHP_EOL;
