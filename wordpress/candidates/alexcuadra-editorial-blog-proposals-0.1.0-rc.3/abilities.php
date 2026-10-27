<?php
namespace AlexCuadra\EditorialBlogProposals;
if(!defined('ABSPATH')){exit;}
function dto(array $properties,?array $required=null): array {
    return ['type'=>'object','additionalProperties'=>false,'properties'=>$properties,'required'=>$required??array_keys($properties)];
}
function contracts(): array {
    $id=['type'=>'integer','minimum'=>1];$version=['type'=>'integer','minimum'=>1,'maximum'=>16];
    $locale=['type'=>'string','enum'=>['en','es']];$hash=['type'=>'string','pattern'=>'^[a-f0-9]{64}$'];
    $ids=['type'=>'array','maxItems'=>100,'uniqueItems'=>true,'items'=>$id];
    $values=['title'=>['type'=>'string','maxLength'=>1000],'content'=>['type'=>'string','maxLength'=>262144],
        'excerpt'=>['type'=>'string','maxLength'=>8192],'categories'=>$ids,'tags'=>$ids];
    $patch=dto($values,[]);$patch['minProperties']=1;
    $request=dto(['request_id'=>['type'=>'string','pattern'=>'^[a-f0-9]{32}$'],'source_id'=>$id,'locale'=>$locale,'base_hash'=>$hash,
        'operation'=>['type'=>'string','enum'=>['update-post','create-translation']],'fields'=>$patch]);
    $changes=[];
    foreach($values as$field=>$schema){$before=$schema;$before['type']=[$schema['type'],'null'];$changes[$field]=dto(['before'=>$before,'after'=>$schema]);}
    $changes=dto($changes,[]);$changes['minProperties']=1;
    $group=dto(['en'=>$id,'es'=>$id],[]);$group['minProperties']=1;$group['maxProperties']=1;
    $intent=dto(['source_group'=>$group,'target_locale'=>$locale,'target_id'=>['type'=>'null']]);$intent['type']=['object','null'];
    $document=dto(['format'=>['type'=>'string','enum'=>['blog-private-proposal-plan-v1']],
        'owner_id'=>$id,'version'=>$version,'request_id'=>['type'=>'string','pattern'=>'^[a-f0-9]{32}$'],'request_hash'=>$hash,
        'source_id'=>$id,'source_locale'=>$locale,'locale'=>$locale,'base_hash'=>$hash,'operation'=>['type'=>'string','enum'=>['update-post','create-translation']],
        'changes'=>$changes,'translation_intent'=>$intent,'approval'=>['type'=>'string','enum'=>['not-requested']],
        'source_write_performed'=>['type'=>'boolean','enum'=>[false]],'proposal_id'=>$id,'document_hash'=>$hash]);
    $review=dto(['proposal_id'=>$id,'version'=>$version,'document'=>$document]);
    $term=dto(['id'=>$id,'taxonomy'=>['type'=>'string','enum'=>['category','post_tag']],'locale'=>$locale,'public'=>['type'=>'boolean','enum'=>[true]]]);
    $context=dto(['source_id'=>$id,'locale'=>$locale,'base_hash'=>$hash,'fields'=>dto($values),
        'linked_locales'=>['type'=>'array','minItems'=>1,'maxItems'=>2,'uniqueItems'=>true,'items'=>$locale],
        'terms'=>['type'=>'array','maxItems'=>1000,'items'=>$term]]);
    return [
        'inspect-post-context'=>['input'=>dto(['source_id'=>$id]),'output'=>$context,'readonly'=>true],
        'create-post-proposal'=>['input'=>$request,'output'=>$review,'readonly'=>false],
        'update-post-proposal'=>['input'=>dto(['proposal_id'=>$id,'expected_version'=>$version,'request'=>$request]),'output'=>$review,'readonly'=>false],
        'get-post-proposal'=>['input'=>dto(['proposal_id'=>$id,'version'=>$version],['proposal_id']),'output'=>$review,'readonly'=>true],
        'list-post-proposals'=>['input'=>dto(['after'=>['type'=>'integer','minimum'=>0],'limit'=>['type'=>'integer','minimum'=>1,'maximum'=>20]],[]),
            'output'=>dto(['items'=>['type'=>'array','maxItems'=>20,'items'=>dto(['proposal_id'=>$id,'version'=>$version])],
                'next_after'=>['type'=>['integer','null'],'minimum'=>1]]),'readonly'=>true],
    ];
}
function ability_permission(){try{wordpress_actor();return true;}catch(\DomainException $e){return new \WP_Error('proposal_scope_required','Private proposal unavailable.',['status'=>403]);}}
function ability_execute(string $kind,array $input){
    try{
        $repo=new WordPressRepository($GLOBALS['wpdb']);
        if($kind==='inspect-post-context'){
            $p=wordpress_projection($input['source_id']);
            return ['source_id'=>$p['source']['id'],'locale'=>$p['source']['locale'],'base_hash'=>$p['base_hash'],
                'fields'=>array_intersect_key($p['source'],array_flip(FIELDS)),'linked_locales'=>array_keys($p['source']['translations']),'terms'=>$p['terms']];
        }
        return match($kind){'create-post-proposal'=>$repo->create($input),
            'update-post-proposal'=>$repo->update($input['proposal_id'],$input['expected_version'],$input['request']),
            'get-post-proposal'=>$repo->get($input['proposal_id'],$input['version']??null),
            'list-post-proposals'=>$repo->listing($input['after']??0,$input['limit']??10)};
    }catch(\DomainException $e){return new \WP_Error('private_proposal_rejected','Private proposal unavailable.',['status'=>403]);}
}
add_action('wp_abilities_api_categories_init',static function(){wp_register_ability_category('alexcuadra-blog-proposals',['label'=>'Private blog proposals','description'=>'Owned private plans; no source publication.']);});
add_action('wp_abilities_api_init',static function(){foreach(contracts() as$kind=>$c){wp_register_ability('alexcuadra-blog/'.$kind,[
    'label'=>$kind,'description'=>'Scoped private post plan; never writes a source post.','category'=>'alexcuadra-blog-proposals',
    'input_schema'=>$c['input'],'output_schema'=>$c['output'],'permission_callback'=>__NAMESPACE__.'\\ability_permission',
    'execute_callback'=>static fn($input)=>ability_execute($kind,$input),
    'meta'=>['public'=>false,'show_in_rest'=>false,'mcp'=>['public'=>true,'type'=>'tool'],
        'annotations'=>['readonly'=>$c['readonly'],'destructive'=>false,'idempotent'=>true]]
]);}});
