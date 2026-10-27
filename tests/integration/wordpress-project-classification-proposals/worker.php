<?php
require_once '/classification-proposals/wordpress-repository.php';
use AlexCuadra\ProjectClassificationProposalLab\WordPressRepository;
use function AlexCuadra\ProjectClassificationProposalLab\wordpress_projection;
[$owner,$key,$mode,$id,$sourceId,$title]=$args;
wp_set_current_user((int)$owner);$repo=new WordPressRepository($GLOBALS['wpdb']);
$projection=wordpress_projection((int)$sourceId,'en');
$request=['request_id'=>$key,'project_id'=>(int)$sourceId,'locale'=>'en','base_hash'=>$projection['base_hash'],
    'fields'=>['categories'=>$title==='empty'?[]:[(int)$title]]];
echo "CLASSIFICATION_PROPOSAL_WORKER_READY\n";flush();fgets(STDIN);
try {
    $result=$mode==='create'?$repo->create($request):$repo->update((int)$id,1,$request);
    echo 'CLASSIFICATION_PROPOSAL_WORKER_RESULT='.json_encode(['ok'=>true,'result'=>$result]),"\n";
}catch(DomainException $e){echo 'CLASSIFICATION_PROPOSAL_WORKER_RESULT='.json_encode(['ok'=>false,'error'=>$e->getMessage()]),"\n";}
