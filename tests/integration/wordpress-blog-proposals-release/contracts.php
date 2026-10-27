<?php
use AlexCuadra\EditorialBlogProposals as B;
$cases=[];$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED:'.$name);}$cases[]=$name;};
$contracts=B\contracts();
$closed=static function($schema)use(&$closed){
    if(!is_array($schema)){return true;}$types=(array)($schema['type']??[]);
    if(in_array('object',$types,true)&&(($schema['additionalProperties']??null)!==false||!isset($schema['properties']))){return false;}
    foreach($schema as$value){if(is_array($value)&&!$closed($value)){return false;}}return true;
};
foreach($contracts as$name=>$contract){$check('closed_input_'.$name,$closed($contract['input']));$check('closed_output_'.$name,$closed($contract['output']));}
$source=['id'=>239,'type'=>'post','status'=>'publish','password'=>'','locale'=>'en','title'=>'Synthetic title','content'=>'Synthetic body','excerpt'=>'','categories'=>[],'tags'=>[],'translations'=>['en'=>239]];
$actor=['id'=>2,'authenticated'=>true,'capabilities'=>[B\PREPARE_SCOPE=>true]];
$request=['request_id'=>str_repeat('a',32),'source_id'=>239,'locale'=>'en','base_hash'=>B\base_hash($source,[]),'operation'=>'update-post','fields'=>['title'=>'Revised title']];
$doc=B\prepare($actor,$source,[],$request);$doc['proposal_id']=1;unset($doc['document_hash']);$doc['document_hash']=B\digest($doc);
$review=['proposal_id'=>1,'version'=>1,'document'=>$doc];
foreach(['create-post-proposal','update-post-proposal','get-post-proposal']as$name){$check('valid_review_'.$name,!is_wp_error(rest_validate_value_from_schema($review,$contracts[$name]['output'])));}
$bad=$review;$bad['secret']='unknown';$check('reject_unknown_review_field',is_wp_error(rest_validate_value_from_schema($bad,$contracts['get-post-proposal']['output'])));
$bad=$review;$bad['document']['secret']='unknown';$check('reject_unknown_document_field',is_wp_error(rest_validate_value_from_schema($bad,$contracts['get-post-proposal']['output'])));
$bad=$review;$bad['document']['source_write_performed']=true;$check('reject_source_write_claim',is_wp_error(rest_validate_value_from_schema($bad,$contracts['get-post-proposal']['output'])));
$bad=$review;$bad['document']['changes']['title']['secret']='unknown';$check('reject_unknown_nested_change',is_wp_error(rest_validate_value_from_schema($bad,$contracts['get-post-proposal']['output'])));
$bad=$request;$bad['owner_id']=9;$check('reject_client_owner',is_wp_error(rest_validate_value_from_schema($bad,$contracts['create-post-proposal']['input'])));
$bad=$request;$bad['fields']['slug']='unknown';$check('reject_client_slug',is_wp_error(rest_validate_value_from_schema($bad,$contracts['create-post-proposal']['input'])));
$context=['source_id'=>239,'locale'=>'en','base_hash'=>$request['base_hash'],'fields'=>array_intersect_key($source,array_flip(B\FIELDS)),'linked_locales'=>['en'],'terms'=>[]];
$check('valid_published_context',!is_wp_error(rest_validate_value_from_schema($context,$contracts['inspect-post-context']['output'])));
$context['postmeta']=[];$check('reject_context_postmeta',is_wp_error(rest_validate_value_from_schema($context,$contracts['inspect-post-context']['output'])));
$check('valid_empty_inventory',!is_wp_error(rest_validate_value_from_schema(['items'=>[],'next_after'=>null],$contracts['list-post-proposals']['output'])));
$tr=$request;$tr['locale']='es';$tr['operation']='create-translation';$tr['fields']=['title'=>'Traducción','content'=>'Contenido','excerpt'=>'','categories'=>[],'tags'=>[]];
$doc=B\prepare($actor,$source,[],$tr);$doc['proposal_id']=2;unset($doc['document_hash']);$doc['document_hash']=B\digest($doc);
$check('valid_nullable_translation_intent',!is_wp_error(rest_validate_value_from_schema(['proposal_id'=>2,'version'=>1,'document'=>$doc],$contracts['create-post-proposal']['output'])));
$bad=$doc;$bad['translation_intent']['target_id']=300;$check('reject_reserved_translation_post_id',is_wp_error(rest_validate_value_from_schema(['proposal_id'=>2,'version'=>1,'document'=>$bad],$contracts['create-post-proposal']['output'])));
$check('exact_five_private_tools',array_keys($contracts)===['inspect-post-context','create-post-proposal','update-post-proposal','get-post-proposal','list-post-proposals']);
$calls=[];foreach(glob(WP_PLUGIN_DIR.'/alexcuadra-editorial-blog-proposals/*.php')as$file){$tokens=token_get_all(file_get_contents($file));foreach($tokens as$token){if(is_array($token)&&$token[0]===T_STRING){$calls[]=$token[1];}}}
$check('no_source_writer_or_automatic_grants',!array_intersect($calls,['wp_insert_post','wp_update_post','wp_delete_post','pll_save_post_translations','wp_set_post_terms','wp_set_object_terms','update_post_meta','update_option','add_cap','add_role','register_activation_hook','register_uninstall_hook']));
$profile=B\profile_report();$check('admitted_coexisting_reader_profile',$profile['ready']&&isset($profile['activePlugins']['alexcuadra-editorial-blog-read/alexcuadra-editorial-blog-read.php']));
$extra=static fn($plugins)=>array_merge($plugins,['unreviewed/extra.php']);add_filter('option_active_plugins',$extra);
$check('unknown_active_plugin_fails_profile',!B\profile_report()['ready']);remove_filter('option_active_plugins',$extra);
$check('profile_restored_after_request_filter',B\profile_report()['ready']);
WP_CLI::line('BLOG_PROPOSAL_RELEASE_CONTRACT_RESULT='.wp_json_encode(['passed'=>count($cases),'failed'=>0,'contractsClosed'=>true,'cases'=>$cases]));
