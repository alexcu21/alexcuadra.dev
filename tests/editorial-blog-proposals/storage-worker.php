<?php
define('AC_BLOG_PROPOSALS_STORAGE_LAB',true);
require __DIR__.'/../../wordpress/experimental/editorial-blog-proposals/repository.php';
require __DIR__.'/storage-fixture.php';
use AlexCuadra\EditorialBlogProposalLab\Repository;
[$actor,$source,$terms,$request]=fixture();
$db=new \Pdo\Sqlite('sqlite:'.$argv[1]);$repo=new Repository($db);
$mode=$argv[2];$request['request_id']=$argv[3];
if($mode==='update'){$request['fields']['title']=$argv[4];}
if($mode==='create'){$request['fields']['title']='Same concurrent request';}
if($mode==='crash-before'){
    $db->createFunction('lab_pause',function(){echo "BARRIER\n";flush();fgets(STDIN);return 1;});
    $db->exec('CREATE TEMP TRIGGER pause_before_receipt BEFORE INSERT ON blog_requests BEGIN SELECT lab_pause(); END');
}
echo "READY\n";flush();fgets(STDIN);
try {
    $result=$mode==='update'?$repo->update($actor,1,1,$source,$terms,$request):$repo->create($actor,$source,$terms,$request);
    if($mode==='crash-after'){echo "COMMITTED\n";flush();fgets(STDIN);}
    echo json_encode(['ok'=>true,'result'=>$result]),"\n";
}catch(DomainException $e){echo json_encode(['ok'=>false,'error'=>$e->getMessage()]),"\n";}
