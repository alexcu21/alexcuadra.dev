<?php
use function AlexCuadra\EditorialBlogProposalLab\base_hash;
use const AlexCuadra\EditorialBlogProposalLab\PREPARE_SCOPE;
function fixture(): array {
    $actor=['id'=>2,'authenticated'=>true,'capabilities'=>[PREPARE_SCOPE=>true]];
    $terms=[['id'=>46,'taxonomy'=>'category','locale'=>'en','public'=>true],['id'=>160,'taxonomy'=>'category','locale'=>'es','public'=>true]];
    $source=['id'=>239,'type'=>'post','status'=>'publish','password'=>'','locale'=>'en','title'=>'Synthetic storage story',
        'content'=>'<p>Private proposal source snapshot</p>','excerpt'=>'','categories'=>[46],'tags'=>[],'translations'=>['en'=>239]];
    $request=['request_id'=>str_repeat('a',32),'source_id'=>239,'locale'=>'en','base_hash'=>base_hash($source,$terms),
        'operation'=>'update-post','fields'=>['title'=>'Proposed title']];
    return [$actor,$source,$terms,$request];
}
