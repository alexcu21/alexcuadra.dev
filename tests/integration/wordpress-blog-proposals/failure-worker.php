<?php
require_once '/blog-proposals/wordpress-repository.php';
use AlexCuadra\EditorialBlogProposalLab\WordPressRepository;
use function AlexCuadra\EditorialBlogProposalLab\wordpress_projection;
global $wpdb;
[$owner,$key,$mode,$sourceId]=$args;wp_set_current_user((int)$owner);
$projection=wordpress_projection((int)$sourceId);$repo=new WordPressRepository($wpdb);
$request=['request_id'=>$key,'source_id'=>(int)$sourceId,'locale'=>'en','base_hash'=>$projection['base_hash'],'operation'=>'update-post','fields'=>['title'=>'Fault proposal']];
$property=new ReflectionProperty(wpdb::class,'dbh');$original=$property->getValue($wpdb);$triggered=false;
add_filter('query',static function($sql)use($mode,$property,$original,&$triggered){
    global $wpdb;
    if($triggered||!preg_match('/^INSERT INTO \w+ac_blog_lab_requests\b/',$sql)){return $sql;}$triggered=true;
    if($mode==='crash-before'){echo "PROPOSAL_FAILURE_BARRIER\n";flush();fgets(STDIN);}
    if(in_array($mode,['kill-connection','reset-mode-and-kill'],true)){
        $killer=new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);$killer->query('KILL CONNECTION '.mysqli_thread_id($original));$killer->close();
        if($mode==='reset-mode-and-kill'){mysqli_report(MYSQLI_REPORT_OFF);}
    }
    if($mode==='replace-handle'){$property->setValue($wpdb,new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME));}
    if($mode==='close-handle'){$original->close();}
    if($mode==='transaction-control'){return 'COMMIT';}
    if($mode==='guard-order'){add_filter('query',static fn($query)=>$query,PHP_INT_MAX);}
    return $sql;
},10);
echo "PROPOSAL_FAILURE_READY\n";flush();fgets(STDIN);
try{
    $result=$repo->create($request);
    if($mode==='crash-after'){echo "PROPOSAL_FAILURE_COMMITTED\n";flush();fgets(STDIN);}
    echo 'PROPOSAL_FAILURE_RESULT='.json_encode(['ok'=>true,'result'=>$result]),"\n";
}catch(DomainException $e){echo 'PROPOSAL_FAILURE_RESULT='.json_encode(['ok'=>false,'error'=>$e->getMessage(),'handleUnchanged'=>$property->getValue($wpdb)===$original]),"\n";}
