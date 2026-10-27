<?php
define('ABSPATH',__DIR__);
$cases=[
    'closed'=>[],
    'live'=>['flag'=>'https://content.alexcuadra.dev','expected'=>true],
    'wrong_flag'=>['flag'=>'https://other.test'],
    'wrong_home'=>['flag'=>'https://content.alexcuadra.dev','home'=>'https://other.test'],
    'wrong_site'=>['flag'=>'https://content.alexcuadra.dev','site'=>'https://other.test'],
    'multisite'=>['flag'=>'https://content.alexcuadra.dev','multisite'=>true],
    'lab'=>['lab'=>true,'env'=>'local','home'=>'http://wordpress','site'=>'http://wordpress','expected'=>true],
    'lab_in_production'=>['lab'=>true],
    'lab_wrong_db'=>['lab'=>true,'env'=>'local','home'=>'http://wordpress','site'=>'http://wordpress','db'=>'other'],
    'lab_wrong_home'=>['lab'=>true,'env'=>'local','home'=>'http://other','site'=>'http://wordpress'],
    'string_lab_flag'=>['lab'=>'true','env'=>'local','home'=>'http://wordpress','site'=>'http://wordpress'],
    'live_subdirectory'=>['flag'=>'https://content.alexcuadra.dev','site'=>'https://content.alexcuadra.dev/content'],
    'live_trailing_slash'=>['flag'=>'https://content.alexcuadra.dev','home'=>'https://content.alexcuadra.dev/','site'=>'https://content.alexcuadra.dev/','expected'=>true],
];
$name=$argv[1]??'';$config=$cases[$name]??null;if($config===null){exit(2);}
function home_url(){global $config;return $config['home']??'https://content.alexcuadra.dev';}
function site_url(){global $config;return $config['site']??'https://content.alexcuadra.dev';}
function is_multisite(){global $config;return $config['multisite']??false;}
function wp_get_environment_type(){global $config;return $config['env']??'production';}
define('DB_HOST',$config['db']??'db');
if(isset($config['flag'])){define('AC_EDITORIAL_BLOG_PROPOSALS_SITE',$config['flag']);}
if(isset($config['lab'])){define('AC_EDITORIAL_BLOG_PROPOSALS_LAB',$config['lab']);}
require __DIR__.'/../../wordpress/packages/alexcuadra-editorial-blog-proposals/admission.php';
$allowed=\AlexCuadra\EditorialBlogProposals\site_admitted();
if($allowed!==($config['expected']??false)){throw new RuntimeException('GUARD_FAILED');}
echo json_encode(['case'=>$name,'admitted'=>$allowed,'passed'=>true]),"\n";
