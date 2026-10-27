<?php
namespace AlexCuadra\EditorialBlogProposals;
if(!defined('ABSPATH')){exit;}

function laboratory_environment(): bool {
    return wp_get_environment_type()==='local' && defined('DB_HOST') && DB_HOST==='db'
        && getenv('BLOG_SITE_PROFILE_LAB')==='verified-code-only'
        && home_url()==='https://content.alexcuadra.dev' && site_url()===home_url();
}
function site_admitted(): bool {
    if(is_multisite()){return false;}
    $home=rtrim(home_url(),'/');$site=rtrim(site_url(),'/');
    $lab=laboratory_environment() && defined('AC_EDITORIAL_BLOG_PROPOSALS_LAB') && AC_EDITORIAL_BLOG_PROPOSALS_LAB===true;
    $live=defined('AC_EDITORIAL_BLOG_PROPOSALS_SITE') && AC_EDITORIAL_BLOG_PROPOSALS_SITE==='https://content.alexcuadra.dev'
        && $home===AC_EDITORIAL_BLOG_PROPOSALS_SITE && $site===$home && !laboratory_environment();
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
    $local=laboratory_environment();
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
    // Exact destination entry fingerprints, independent of enabling the new scope.
    $policy=json_decode('{"plugins":{"advanced-custom-fields/acf.php":{"version":"6.8.10","mainSha256":"e3c7114e354ea19850463db3191c2d60ee244e57501d5008cd0617f7c0fb7e64"},"alexcuadra-editorial-blog-read/alexcuadra-editorial-blog-read.php":{"version":"0.1.0-rc.1","mainSha256":"7a50acba34429ec6cedcf467fbdcfae8e2702abf6420f170235f019664024f73"},"alexcuadra-editorial-operating/alexcuadra-editorial-operating.php":{"version":"0.1.0-rc.3","mainSha256":"77cf118d953992a0d99e20ace6c5a31278ea637f0bfaa07ed1ee9970e0c4a3bf"},"alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php":{"version":"0.1.0-rc.2","mainSha256":"a9a1b498ab18472a30966cfcd160ef91d35aa62420911e31ca30e7ec3452949e"},"alexcuadra-editorial-read/alexcuadra-editorial-read.php":{"version":"0.1.0","mainSha256":"9e4def28919b5dc896037fa13dddf62b4f63e4a725095fe12b3e0d8419f22619"},"hostinger-easy-onboarding/hostinger-easy-onboarding.php":{"version":"3.0.5","mainSha256":"5e2927308eaa142bb79149603aeb3e4b8b0ad33db6e6c59f4cfbd5b0761d0a19"},"hostinger-reach/hostinger-reach.php":{"version":"1.8.7","mainSha256":"dffaaeea0c33214046f7f62b8d104d927ad17be0942c4af4a67d0e9ae734f48b"},"hostinger/hostinger.php":{"version":"3.0.80","mainSha256":"5d1dd6e5d9677e6c53732765f343eb5fd573d13fb0314e67d30afeecf24d264e"},"litespeed-cache/litespeed-cache.php":{"version":"7.9.2","mainSha256":"ad57729d8736659c99068f4727d299c6c6b29b55d019d67a5c417918c3370f2f"},"mcp-adapter/mcp-adapter.php":{"version":"0.7.1","mainSha256":"ad0388cd18385c66a8efc7ec433047e6787e75cd2d0bbf2332333258717b4620"},"polylang/polylang.php":{"version":"3.9.1","mainSha256":"39cf938c68eb3c34078c8b1ea1dd900889458cb03b4977a9a9ff92f7b3e01379"},"wordpress-beta-tester/wp-beta-tester.php":{"version":"4.0.1","mainSha256":"536e1073be4b7cefff0f5158aa14f6684f03d049e808db567aad963556641a40"},"wp-graphql-polylang-0.7.1/wp-graphql-polylang.php":{"version":"0.7.1","mainSha256":"6e2933f965a072969e7d0dbf4a9e623e2a7e7e1ee1da6ea416bad7a18b7688a4"},"wp-graphql/wp-graphql.php":{"version":"2.25.0","mainSha256":"d1bfa232b509aebf130fc7f7f090618f5577d296df8d3d6bfef0af55618a4408"},"wpgraphql-acf/wpgraphql-acf.php":{"version":"3.0.0","mainSha256":"6ef3736685ea63e4707e631886dea03dd8cee05c7e76b87cc1f6b0eae72db842"},"wpgraphql-ide/wpgraphql-ide.php":{"version":"5.7.0","mainSha256":"b22c9f1d2474665577b7d2842214ad64835fa43cd7a91f8dd30d5561dd054236"}},"muPluginSha256":{"hostinger-auto-updates.php":"2492da0336ed34b4377b2a2de882603280e0fde70df712b08fcd2ddef588a5bf","hostinger-preview-domain.php":"ccedfe9be0bce8e941274a519bfea4ca686a7cc0a1f0cedccdbb39384d8764f4"},"theme":{"stylesheet":"twentytwentyfour","template":"twentytwentyfour","version":"1.6","styleSha256":"fd3c8a4e86382cbd69046dd4670112c7b17817bdff3671bf29d3e49a874c1fe2","functionsSha256":"6cec0f112f34ff3a043fe1e16c2d235ce905a735d46039a50879e8fd469f2bce"},"selfMainSha256":"62346abeeb919130faea84b8db88876140125cb837eaa06e0062a0fcf423c42a","fixtureMuSha256":"285beda78da96fa89a30ae9f1c7b9c24f62cbe40f8c79759241698e13bd2f7d5"}',true,32,JSON_THROW_ON_ERROR);
    $self='alexcuadra-editorial-blog-proposals/alexcuadra-editorial-blog-proposals.php';
    $actual=$plugins;$selfVersion=$actual[$self]??null;unset($actual[$self]);ksort($actual);
    $wanted=[];$mainReady=true;
    foreach($policy['plugins']as$name=>$entry){
        $wanted[$name]=$entry['version'];$path=WP_PLUGIN_DIR.'/'.$name;$real=realpath($path);
        $mainReady=$mainReady&&$real&&str_starts_with($real,realpath(WP_PLUGIN_DIR).DIRECTORY_SEPARATOR)
            &&is_file($real)&&!is_link($path)&&hash_file('sha256',$real)===$entry['mainSha256'];
    }
    ksort($wanted);$versions=$actual===$wanted&&$mainReady;
    if($selfVersion!==null){$versions=$versions&&$selfVersion==='0.1.0-rc.2'&&hash_file('sha256',WP_PLUGIN_DIR.'/'.$self)===$policy['selfMainSha256'];}
    $expectedMu=$policy['muPluginSha256'];
    if($local){$expectedMu['000-blog-site-guard.php']=$policy['fixtureMuSha256'];}
    ksort($expectedMu);ksort($muHashes);$muReady=$muHashes===$expectedMu;
    $themeReady=$themeInfo===$policy['theme'];
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
        $local=laboratory_environment();
        foreach($triggers as$t){if(!$local||$t['TRIGGER_NAME']!=='blog_lab_fail_receipt'){$valid=false;}}
        $checks[$suffix]=$valid;$tables[$suffix]=['engine'=>$table[0]['ENGINE'],'collation'=>$table[0]['TABLE_COLLATION']];
    }
    return ['present'=>$present,'checks'=>$checks,'ready'=>$present===4&&!in_array(false,$checks,true),'tables'=>$tables];
}
