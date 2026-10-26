<?php
require '/blog-proposals/wordpress-repository.php';
use AlexCuadra\EditorialBlogProposalLab\WordPressRepository;
use function AlexCuadra\EditorialBlogProposalLab\wordpress_projection;
[$owner,$key,$mode,$id,$sourceId,$title]=$args;
wp_set_current_user((int)$owner);$repo=new WordPressRepository($GLOBALS['wpdb']);
$projection=wordpress_projection((int)$sourceId);
$request=['request_id'=>$key,'source_id'=>(int)$sourceId,'locale'=>'en','base_hash'=>$projection['base_hash'],
    'operation'=>'update-post','fields'=>['title'=>$title]];
echo "BLOG_PROPOSAL_WORKER_READY\n";flush();fgets(STDIN);
try {
    $result=$mode==='create'?$repo->create($request):$repo->update((int)$id,1,$request);
    echo 'BLOG_PROPOSAL_WORKER_RESULT='.json_encode(['ok'=>true,'result'=>$result]),"\n";
}catch(DomainException $e){echo 'BLOG_PROPOSAL_WORKER_RESULT='.json_encode(['ok'=>false,'error'=>$e->getMessage()]),"\n";}
