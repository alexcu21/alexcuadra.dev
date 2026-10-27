<?php
/** Exercise the non-laboratory admission branch using synthetic WordPress only. */
use AlexCuadra\EditorialBlogProposals as B;
if(!B\laboratory_environment()){throw new RuntimeException('LAB_REQUIRED');}
$cases=[];$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED:'.$name);}$cases[]=$name;};
$fixture=WPMU_PLUGIN_DIR.'/000-blog-site-guard.php';$disabled=WPMU_PLUGIN_DIR.'/000-blog-site-guard.disabled';
if(file_exists($disabled)||!rename($fixture,$disabled)){throw new RuntimeException('TEMP_FIXTURE_RENAME_FAILED');}
$prior=getenv('BLOG_SITE_PROFILE_LAB');
try{
 putenv('BLOG_SITE_PROFILE_LAB=');
 $check('laboratory_exception_absent',!B\laboratory_environment());
 $check('live_without_site_optin_denied',!B\site_admitted());
 $check('measured_live_profile_admitted',B\profile_report()['ready']);
 define('AC_EDITORIAL_BLOG_PROPOSALS_SITE','https://content.alexcuadra.dev');
 $check('exact_live_site_optin_admitted',B\site_admitted());
 $bad=static fn()=> 'https://unrelated.example.test';add_filter('option_home',$bad);
 $check('wrong_home_denied',!B\site_admitted());remove_filter('option_home',$bad);
 add_filter('option_siteurl',$bad);$check('wrong_siteurl_denied',!B\site_admitted());remove_filter('option_siteurl',$bad);
 $query=static fn($sql)=>$sql;add_filter('query',$query,99);
 $check('unknown_query_callback_denied_live',!B\profile_report()['ready']);remove_filter('query',$query,99);
 $check('live_profile_restored',B\profile_report()['ready']);
 $main=WP_PLUGIN_DIR.'/alexcuadra-editorial-blog-proposals/alexcuadra-editorial-blog-proposals.php';$bytes=file_get_contents($main);
 try{file_put_contents($main,$bytes."\n// synthetic own drift\n");$check('candidate_main_drift_denied',!B\profile_report()['ready']);}finally{file_put_contents($main,$bytes);}
}finally{
 putenv('BLOG_SITE_PROFILE_LAB='.$prior);rename($disabled,$fixture);
}
$check('laboratory_safety_restored',B\laboratory_environment()&&B\profile_report()['ready']);
WP_CLI::line('SITE_RELEASE_ADMISSION_RESULT='.wp_json_encode(['passed'=>count($cases),'failed'=>0,'cases'=>$cases,'realDestinationUsed'=>false]));
