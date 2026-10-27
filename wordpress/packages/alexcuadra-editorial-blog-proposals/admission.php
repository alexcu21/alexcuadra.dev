<?php
namespace AlexCuadra\EditorialBlogProposals;
if(!defined('ABSPATH')){exit;}

function site_admitted(): bool {
    if(is_multisite()){return false;}
    $home=rtrim(home_url(),'/');$site=rtrim(site_url(),'/');
    $lab=defined('AC_EDITORIAL_BLOG_PROPOSALS_LAB')&&AC_EDITORIAL_BLOG_PROPOSALS_LAB===true
        &&wp_get_environment_type()==='local'&&$home==='http://wordpress'&&$site===$home&&defined('DB_HOST')&&DB_HOST==='db';
    $live=defined('AC_EDITORIAL_BLOG_PROPOSALS_SITE')&&AC_EDITORIAL_BLOG_PROPOSALS_SITE==='https://content.alexcuadra.dev'
        &&$home===AC_EDITORIAL_BLOG_PROPOSALS_SITE&&$site===$home;
    return $lab||$live;
}
/** Metadata only. Never changes configuration, user scopes or storage. */
function profile_report(): array {
    global $wpdb,$wp_version;
    require_once ABSPATH.'wp-admin/includes/plugin.php';
    $allowed=['polylang/polylang.php'=>'3.9.1','mcp-adapter/mcp-adapter.php'=>'0.7.1',
        'alexcuadra-editorial-blog-read/alexcuadra-editorial-blog-read.php'=>'0.1.0-rc.1',
        'alexcuadra-editorial-blog-proposals/alexcuadra-editorial-blog-proposals.php'=>'0.1.0-rc.1'];
    $active=get_option('active_plugins',[]);sort($active);$plugins=[];$versions=true;
    foreach($active as$path){$file=realpath(WP_PLUGIN_DIR.'/'.$path);$inside=$file&&str_starts_with($file,realpath(WP_PLUGIN_DIR).DIRECTORY_SEPARATOR);
        $v=$inside&&is_file($file)?get_plugin_data($file,false,false)['Version']:null;
        $plugins[$path]=$v;$versions=$versions&&isset($allowed[$path])&&$allowed[$path]===$v;}
    $required=in_array('polylang/polylang.php',$active,true)&&in_array('mcp-adapter/mcp-adapter.php',$active,true);
    $local=wp_get_environment_type()==='local'&&home_url()==='http://wordpress'&&site_url()==='http://wordpress'&&defined('DB_HOST')&&DB_HOST==='db';
    $mu=get_mu_plugins();$muNames=array_keys($mu);sort($muNames);
    $muHashes=[];foreach($muNames as$name){$file=WPMU_PLUGIN_DIR.'/'.$name;$muHashes[$name]=is_file($file)?hash_file('sha256',$file):null;}
    // The one fixture blocks mail and registers project for negative type tests. Never admitted live.
    $muReady=$muNames===[]||($local&&$muNames===['editorial-blog-read-lab.php']);
    $theme=wp_get_theme();$dir=$theme->get_stylesheet_directory();
    $themeInfo=['stylesheet'=>$theme->get_stylesheet(),'template'=>$theme->get_template(),'version'=>$theme->get('Version'),
        'styleSha256'=>is_file($dir.'/style.css')?hash_file('sha256',$dir.'/style.css'):null,
        'functionsSha256'=>is_file($dir.'/functions.php')?hash_file('sha256',$dir.'/functions.php'):null];
    $themeReady=$themeInfo===['stylesheet'=>'twentytwentyfive','template'=>'twentytwentyfive','version'=>'1.5',
        'styleSha256'=>'f21ff7e12ce351b9a2e081f517233bc46287b2d9afaabe3cb0f6cdf2653a721e',
        'functionsSha256'=>'51681ddc22490e05066da69a44e74bd8ee78249f6322d8c6efcd777d19641f84'];
    $dropins=array_keys(get_dropins());sort($dropins);
    $wpdbSource=is_object($wpdb)?(new \ReflectionClass($wpdb))->getFileName():null;
    $wpdbHash=$wpdbSource&&is_file($wpdbSource)?hash_file('sha256',$wpdbSource):null;
    $callbacks=[];$queryReady=true;
    global $wp_filter;
    foreach($wp_filter['query']->callbacks??[] as$priority=>$entries){foreach($entries as$entry){
        $fn=$entry['function'];$core=is_array($fn)&&$fn[0]===$wpdb&&$fn[1]==='remove_placeholder_escape';
        $file=null;if($fn instanceof \Closure){$file=(new \ReflectionFunction($fn))->getFileName();}
        $own=$file&&realpath($file)===realpath(__DIR__.'/connection-fence.php');
        $label=is_string($fn)?$fn:(is_array($fn)?(is_object($fn[0])?get_class($fn[0]):$fn[0]).'::'.$fn[1]:($fn instanceof \Closure?'closure':'invokable'));
        try{$reflection=is_array($fn)?new \ReflectionMethod($fn[0],$fn[1]):($fn instanceof \Closure||is_string($fn)?new \ReflectionFunction($fn):new \ReflectionMethod($fn,'__invoke'));$source=$reflection->getFileName();}catch(\ReflectionException $e){$source=null;}
        $internal=$source&&str_starts_with($source,ABSPATH);
        $callbacks[]=['priority'=>(int)$priority,'kind'=>$core?'core-placeholder':($own?'own-fence':'other'),
            'callback'=>$label,'source'=>$internal?substr($source,strlen(ABSPATH)):null,'sourceSha256'=>$internal&&is_file($source)?hash_file('sha256',$source):null];
        $queryReady=$queryReady&&($core||$own||$local);
    }}
    $checks=['singleSite'=>!is_multisite(),'testedWordPress'=>$wp_version==='7.1.3',
        'testedPhp'=>PHP_MAJOR_VERSION===8&&PHP_MINOR_VERSION===5,'coreWpdb'=>is_object($wpdb)&&get_class($wpdb)==='wpdb'&&$wpdbHash==='e15403e90032dd0508811301505e491d4212de5152581a98f51b744a1a3903bd',
        'dependencies'=>$required,'testedActivePlugins'=>$versions,'testedMuPlugins'=>$muReady,
        'standardObjectCache'=>!wp_using_ext_object_cache()&&!file_exists(WP_CONTENT_DIR.'/object-cache.php'),'noDropins'=>$dropins===[],'testedTheme'=>$themeReady,
        'queryFilters'=>$queryReady];
    return ['checks'=>$checks,'ready'=>!in_array(false,$checks,true),'wordpress'=>$wp_version,'php'=>PHP_VERSION,
        'activePlugins'=>$plugins,'muPluginNames'=>$muNames,'muPluginSha256'=>$muHashes,'queryCallbacks'=>$callbacks,'theme'=>$themeInfo,
        'dropinNames'=>$dropins,'wpdbSourceSha256'=>$wpdbHash,'instrumentedLocalProfile'=>$local,'sourceRenderingPerformed'=>false];
}
function admission_rows($db,string $sql,array $args=[]): array {
    if($db!==($GLOBALS['wpdb']??null)||get_class($db)!=='wpdb'){throw new \DomainException('admission_connection');}
    $old=$db->suppress_errors(true);
    try{$rows=$db->get_results($args?$db->prepare($sql,...$args):$sql,ARRAY_A);if($db->last_error){throw new \DomainException('admission_query');}return $rows;}
    finally{$db->suppress_errors($old);}
}
/** Closed structure: columns, indices, foreign keys, engine/collation; no row bodies. */
function schema_report($db): array {
    if(!preg_match('/^[a-zA-Z0-9_]+$/D',$db->prefix)){throw new \DomainException('admission_prefix');}
    $p=$db->prefix.'ac_blog_proposal_';
    $spec=[
        'owners'=>['owner_id'=>['bigint',false]],
        'proposals'=>['id'=>['bigint',true],'owner_id'=>['bigint',false],'version'=>['int',false]],
        'revisions'=>['proposal_id'=>['bigint',false],'owner_id'=>['bigint',false],'version'=>['int',false],'document'=>['mediumtext',false],'document_hash'=>['char64',false]],
        'requests'=>['owner_id'=>['bigint',false],'request_id'=>['char32',false],'request_hash'=>['char64',false],'proposal_id'=>['bigint',false],'version'=>['int',false]],
    ];
    $index=[
        'owners'=>['0:owner_id'],
        'proposals'=>['0:id','0:id,owner_id','1:owner_id'],
        'revisions'=>['0:proposal_id,version','0:proposal_id,owner_id,version','1:owner_id'],
        'requests'=>['0:owner_id,request_id','1:proposal_id,owner_id,version'],
    ];
    $foreign=[
        'owners'=>[],
        'proposals'=>['owner_id:'.$p.'owners:owner_id'],
        'revisions'=>['proposal_id:'.$p.'proposals:id','owner_id:'.$p.'proposals:owner_id'],
        'requests'=>['proposal_id:'.$p.'revisions:proposal_id','owner_id:'.$p.'revisions:owner_id','version:'.$p.'revisions:version'],
    ];
    $checks=[];$present=0;$tables=[];
    foreach($spec as$suffix=>$columns){
        $name=$p.$suffix;$table=admission_rows($db,'SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',[$name]);
        if(!$table){$checks[$suffix]=false;continue;}$present++;
        $valid=$table[0]['ENGINE']==='InnoDB'&&$table[0]['TABLE_COLLATION']==='utf8mb4_bin';
        $rows=admission_rows($db,'SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,COLLATION_NAME,CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s ORDER BY ORDINAL_POSITION',[$name]);
        $valid=$valid&&array_column($rows,'COLUMN_NAME')===array_keys($columns);
        foreach($rows as$row){$expected=$columns[$row['COLUMN_NAME']]??null;if(!$expected){$valid=false;continue;}[$type,$auto]=$expected;
            $integer=in_array($type,['bigint','int'],true);
            $match=$integer?(bool)preg_match('/^'.$type.'(?:\([0-9]+\))? unsigned$/D',$row['COLUMN_TYPE']):
                ($type==='mediumtext'?$row['COLUMN_TYPE']==='mediumtext':$row['COLUMN_TYPE']==='char('.substr($type,4).')');
            $valid=$valid&&$match&&$row['IS_NULLABLE']==='NO'&&$row['COLUMN_DEFAULT']===null&&$row['EXTRA']===($auto?'auto_increment':'')
                &&($integer?$row['COLLATION_NAME']===null:$row['COLLATION_NAME']==='utf8mb4_bin');
        }
        $rows=admission_rows($db,'SELECT INDEX_NAME,NON_UNIQUE,COLUMN_NAME,SUB_PART FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s ORDER BY INDEX_NAME,SEQ_IN_INDEX',[$name]);
        $indices=[];foreach($rows as$row){$key=$row['INDEX_NAME'];$indices[$key]['unique']=$row['NON_UNIQUE'];$indices[$key]['columns'][]=$row['COLUMN_NAME'];if($row['SUB_PART']!==null){$valid=false;}}
        $actual=[];foreach($indices as$v){$actual[]=$v['unique'].':'.implode(',',$v['columns']);}sort($actual);$wanted=$index[$suffix];sort($wanted);$valid=$valid&&$actual===$wanted;
        $rows=admission_rows($db,'SELECT CONSTRAINT_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY CONSTRAINT_NAME,ORDINAL_POSITION',[$name]);
        $actual=[];foreach($rows as$r){$actual[]=$r['COLUMN_NAME'].':'.$r['REFERENCED_TABLE_NAME'].':'.$r['REFERENCED_COLUMN_NAME'];}sort($actual);$wanted=$foreign[$suffix];sort($wanted);$valid=$valid&&$actual===$wanted;
        $valid=$valid&&count(array_unique(array_column($rows,'CONSTRAINT_NAME')))===($suffix==='owners'?0:1);
        foreach(admission_rows($db,'SELECT DELETE_RULE,UPDATE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=%s',[$name])as$rule){$valid=$valid&&in_array($rule['DELETE_RULE'],['RESTRICT','NO ACTION'],true)&&in_array($rule['UPDATE_RULE'],['RESTRICT','NO ACTION'],true);}
        $triggers=admission_rows($db,'SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE=%s',[$name]);
        $local=wp_get_environment_type()==='local'&&home_url()==='http://wordpress';
        foreach($triggers as$t){if(!$local||$t['TRIGGER_NAME']!=='blog_lab_fail_receipt'){$valid=false;}}
        $checks[$suffix]=$valid;$tables[$suffix]=['engine'=>$table[0]['ENGINE'],'collation'=>$table[0]['TABLE_COLLATION']];
    }
    return ['present'=>$present,'checks'=>$checks,'ready'=>$present===4&&!in_array(false,$checks,true),'tables'=>$tables];
}
