<?php
/** Plugin Name: Synthetic private proposal HTTP abilities (LAB ONLY) */
namespace AlexCuadra\BlogProposalHttpFixture;
require_once '/blog-proposals/wordpress-repository.php';
use AlexCuadra\EditorialBlogProposalLab as B;
B\wordpress_lab_guard();

function permission(){try{B\wordpress_actor();return true;}catch(\DomainException $e){return new \WP_Error('proposal_scope_required','Private proposal unavailable.',['status'=>403]);}}
function execute($kind,$input){
    try{
        $repo=new B\WordPressRepository($GLOBALS['wpdb']);
        if($kind==='inspect-post-context'){
            $p=B\wordpress_projection($input['source_id']);
            return ['source_id'=>$p['source']['id'],'locale'=>$p['source']['locale'],'base_hash'=>$p['base_hash'],
                'fields'=>array_intersect_key($p['source'],array_flip(B\FIELDS)),
                'linked_locales'=>array_keys($p['source']['translations']),'terms'=>$p['terms']];
        }
        return match($kind){
            'create-post-proposal'=>$repo->create($input),
            'update-post-proposal'=>$repo->update($input['proposal_id'],$input['expected_version'],$input['request']),
            'get-post-proposal'=>$repo->get($input['proposal_id'],$input['version']??null),
            'list-post-proposals'=>$repo->listing($input['after']??0,$input['limit']??10),
        };
    }catch(\DomainException $e){return new \WP_Error('private_proposal_rejected','Private proposal unavailable.',['status'=>403]);}
}
add_action('wp_abilities_api_categories_init',static function(){wp_register_ability_category('blog-proposal-lab',['label'=>'Private proposal lab','description'=>'Synthetic isolated transport only.']);});
add_action('wp_abilities_api_init',static function(){
    $integer=['type'=>'integer','minimum'=>1];
    $fields=['type'=>'object','additionalProperties'=>false,'minProperties'=>1,'properties'=>[
        'title'=>['type'=>'string','maxLength'=>1000],'content'=>['type'=>'string','maxLength'=>262144],'excerpt'=>['type'=>'string','maxLength'=>8192],
        'categories'=>['type'=>'array','maxItems'=>100,'uniqueItems'=>true,'items'=>$integer],
        'tags'=>['type'=>'array','maxItems'=>100,'uniqueItems'=>true,'items'=>$integer]]];
    $request=['type'=>'object','additionalProperties'=>false,'required'=>['request_id','source_id','locale','base_hash','operation','fields'],'properties'=>[
        'request_id'=>['type'=>'string','pattern'=>'^[a-f0-9]{32}$'],'source_id'=>$integer,'locale'=>['type'=>'string','enum'=>['en','es']],
        'base_hash'=>['type'=>'string','pattern'=>'^[a-f0-9]{64}$'],'operation'=>['type'=>'string','enum'=>['update-post','create-translation']],'fields'=>$fields]];
    $schemas=[
        'inspect-post-context'=>['type'=>'object','additionalProperties'=>false,'required'=>['source_id'],'properties'=>['source_id'=>$integer]],
        'create-post-proposal'=>$request,
        'update-post-proposal'=>['type'=>'object','additionalProperties'=>false,'required'=>['proposal_id','expected_version','request'],'properties'=>['proposal_id'=>$integer,'expected_version'=>['type'=>'integer','minimum'=>1,'maximum'=>16],'request'=>$request]],
        'get-post-proposal'=>['type'=>'object','additionalProperties'=>false,'required'=>['proposal_id'],'properties'=>['proposal_id'=>$integer,'version'=>['type'=>'integer','minimum'=>1,'maximum'=>16]]],
        'list-post-proposals'=>['type'=>'object','additionalProperties'=>false,'properties'=>['after'=>['type'=>'integer','minimum'=>0],'limit'=>['type'=>'integer','minimum'=>1,'maximum'=>20]]],
    ];
    foreach($schemas as$kind=>$schema){wp_register_ability('alexcuadra-lab/'.$kind,[
        'label'=>$kind,'description'=>'Synthetic private plans only; never writes a source post.','category'=>'blog-proposal-lab',
        'input_schema'=>$schema,'output_schema'=>['type'=>'object'],
        'permission_callback'=>__NAMESPACE__.'\\permission','execute_callback'=>static fn($input)=>execute($kind,$input),
        'meta'=>['public'=>false,'show_in_rest'=>false,'mcp'=>['public'=>true,'type'=>'tool'],
            'annotations'=>['readonly'=>!in_array($kind,['create-post-proposal','update-post-proposal'],true),'destructive'=>false,'idempotent'=>true]]
    ]);}
});
