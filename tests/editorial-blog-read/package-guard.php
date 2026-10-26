<?php
define('ABSPATH', __DIR__);
$cases = [
    'closed' => [],
    'live' => ['flag'=>'https://content.alexcuadra.dev','expected'=>2],
    'wrong_flag' => ['flag'=>'https://other.test'],
    'wrong_home' => ['flag'=>'https://content.alexcuadra.dev','home'=>'https://other.test'],
    'wrong_site' => ['flag'=>'https://content.alexcuadra.dev','site'=>'https://other.test'],
    'multisite' => ['flag'=>'https://content.alexcuadra.dev','multisite'=>true],
    'local' => ['lab'=>true,'environment'=>'local','home'=>'http://wordpress','site'=>'http://wordpress','expected'=>2],
    'lab_in_production' => ['lab'=>true],
    'lab_wrong_site' => ['lab'=>true,'environment'=>'local','home'=>'http://wordpress','site'=>'http://other.test'],
];
$name=$argv[1]??'closed';$config=$cases[$name]??null;if($config===null){exit(2);}
$hooks=[];
function wp_get_environment_type(){global $config;return $config['environment']??'production';}
function home_url(){global $config;return $config['home']??'https://content.alexcuadra.dev';}
function site_url(){global $config;return $config['site']??'https://content.alexcuadra.dev';}
function is_multisite(){global $config;return $config['multisite']??false;}
function add_action($name,$callback){global $hooks;$hooks[]=$name;}
if(isset($config['flag'])){define('AC_EDITORIAL_BLOG_READ_SITE',$config['flag']);}
if(isset($config['lab'])){define('AC_EDITORIAL_BLOG_READ_LAB',$config['lab']);}
require __DIR__.'/../../wordpress/packages/alexcuadra-editorial-blog-read/alexcuadra-editorial-blog-read.php';
if(count($hooks)!==($config['expected']??0)){throw new RuntimeException('UNEXPECTED_PACKAGE_EXPOSURE');}
echo json_encode(['case'=>$name,'registeredHooks'=>count($hooks),'passed'=>true])."\n";
