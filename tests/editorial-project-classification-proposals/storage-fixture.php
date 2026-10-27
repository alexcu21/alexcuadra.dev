<?php
use function AlexCuadra\ProjectClassificationProposalLab\base_hash;
use const AlexCuadra\ProjectClassificationProposalLab\SCOPES;
function fixture(): array {
    $term = static fn(int $id, string $taxonomy) => ['id'=>$id,'name'=>'Synthetic '.$id,'slug'=>'synthetic-'.$id,
        'taxonomy'=>$taxonomy,'locale'=>'en','translations'=>[]];
    $actor=['id'=>2,'authenticated'=>true,'capabilities'=>array_fill_keys(SCOPES,true)];
    $catalog=['categories'=>[$term(10,'project_category'),$term(20,'project_category')],
        'technologies'=>[$term(30,'project_technology'),$term(40,'project_technology')]];
    $source=['project_id'=>100,'locale'=>'en','categories'=>[$catalog['categories'][0]],'technologies'=>[$catalog['technologies'][0]]];
    $request=['request_id'=>str_repeat('a',32),'project_id'=>100,'locale'=>'en','base_hash'=>base_hash($source,$catalog),
        'fields'=>['categories'=>[20]]];
    return [$actor,$source,$catalog,$request];
}
