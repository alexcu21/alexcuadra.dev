<?php
/** Local-only public project classification reader. Not an installable plugin. */
namespace AlexCuadra\ProjectClassificationLab;
function laboratory(): bool {
 return defined('ABSPATH')&&defined('AC_PROJECT_CLASSIFICATION_LAB')&&AC_PROJECT_CLASSIFICATION_LAB===true
  &&wp_get_environment_type()==='local'&&defined('DB_HOST')&&DB_HOST==='db'
  &&home_url()==='http://wordpress'&&site_url()==='http://wordpress'&&!is_multisite();
}
if(!laboratory()){return;}
const CAPABILITY='alexcuadra_read_public_projects';
const TAXONOMIES=['project_category','project_technology'];
const PROJECT_LIMIT=200;
const TERM_LIMIT=200;
function failure(string $code,int $status=503){return new \WP_Error('ac_project_classification_'.$code,'Public project classification unavailable.',['status'=>$status]);}
function permission($input=null){return laboratory()&&is_user_logged_in()&&current_user_can('read')&&current_user_can(CAPABILITY)?true:failure('forbidden',403);}
function ready(){
 foreach(['pll_get_post_language','pll_is_translated_post_type','pll_get_term_language','pll_get_term_translations','pll_is_translated_taxonomy']as$f){if(!function_exists($f)){return failure('dependencies');}}
 if(!post_type_exists('project')||!pll_is_translated_post_type('project')){return failure('dependencies');}
 foreach(TAXONOMIES as$t){$tax=get_taxonomy($t);$types=$tax?array_values(array_unique($tax->object_type)):[];sort($types);
  if(!$tax||!is_taxonomy_viewable($tax)||!pll_is_translated_taxonomy($t)||$types!==['project']){return failure('dependencies');}}
 return true;
}
function validate($input,bool $catalog){
 $keys=$catalog?['locale','taxonomy','after','limit']:['id','locale'];
 if(!is_array($input)||array_diff(array_keys($input),$keys)||!isset($input['locale'])||!in_array($input['locale'],['en','es'],true)){return failure('input',400);}
 if(!$catalog){return isset($input['id'])&&is_int($input['id'])&&$input['id']>0?true:failure('input',400);}
 if(!isset($input['taxonomy'])||!in_array($input['taxonomy'],TAXONOMIES,true)){return failure('input',400);}
 if(isset($input['after'])&&(!is_int($input['after'])||$input['after']<0)){return failure('input',400);}
 if(isset($input['limit'])&&(!is_int($input['limit'])||$input['limit']<1||$input['limit']>20)){return failure('input',400);}
 foreach(['after','limit']as$k){if(array_key_exists($k,$input)&&$input[$k]===null){return failure('input',400);}}
 return true;
}
function authorize($input,bool $catalog){$c=permission($input);if(is_wp_error($c)){return $c;}$c=validate($input,$catalog);return is_wp_error($c)?$c:ready();}
function visible($post,string $locale): bool {
 return $post instanceof \WP_Post&&$post->post_type==='project'&&$post->post_status==='publish'&&$post->post_password===''
  &&is_post_publicly_viewable($post)&&current_user_can('read_post',$post->ID)&&pll_get_post_language($post->ID,'slug')===$locale;
}
/** Exact raw assignments: no Polylang term filter may silently discard a conflicting language. */
function assigned(array $ids,string $taxonomy,string $locale,int $limit){
 if(!$ids){return [];}
 global $wpdb;
 $placeholders=implode(',',array_fill(0,count($ids),'%d'));
 $sql="SELECT DISTINCT tt.term_id FROM {$wpdb->term_relationships} r INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=r.term_taxonomy_id WHERE r.object_id IN ($placeholders) AND tt.taxonomy=%s ORDER BY tt.term_id ASC LIMIT %d";
 $old=$wpdb->suppress_errors(true);
 try{$result=$wpdb->get_col($wpdb->prepare($sql,...array_merge($ids,[$taxonomy,$limit+1])));$error=$wpdb->last_error;}finally{$wpdb->suppress_errors($old);}
 if($error||!is_array($result)){return failure('classification');}if(count($result)>$limit){return failure('bound',422);}
 $terms=[];foreach($result as$id){$term=get_term((int)$id,$taxonomy);
  if(!$term instanceof \WP_Term||$term->taxonomy!==$taxonomy||pll_get_term_language($term->term_id,'slug')!==$locale){return failure('classification');}
  $terms[$term->term_id]=$term;
 }return $terms;
}
/** Public witnesses only. No get_terms count/description/termmeta or unused terms are exposed. */
function inventory(){
 $inventory=[];
 foreach(['en','es']as$locale){
  $query=new \WP_Query(['post_type'=>'project','post_status'=>'publish','has_password'=>false,'lang'=>$locale,
   'posts_per_page'=>PROJECT_LIMIT+1,'orderby'=>'ID','order'=>'ASC','ignore_sticky_posts'=>true,'no_found_rows'=>true,
   'suppress_filters'=>false,'update_post_meta_cache'=>false,'update_post_term_cache'=>false]);
  if(count($query->posts)>PROJECT_LIMIT){return failure('bound',422);}
  $ids=[];foreach($query->posts as$post){if(!visible($post,$locale)){return failure('visibility');}$ids[]=$post->ID;}
  foreach(TAXONOMIES as$tax){$terms=assigned($ids,$tax,$locale,TERM_LIMIT);if(is_wp_error($terms)){return $terms;}$inventory[$locale][$tax]=$terms;}
 }
 return $inventory;
}
function term_metadata(\WP_Term $term,string $locale,array $inventory){
 $relations=pll_get_term_translations($term->term_id);
 if(!is_array($relations)||(int)($relations[$locale]??0)!==$term->term_id){return failure('relationships');}
 $translations=[];
 foreach(['en','es']as$language){
  if($language===$locale){continue;}$id=(int)($relations[$language]??0);$target=$inventory[$language][$term->taxonomy][$id]??null;
  if(!$target instanceof \WP_Term){continue;}
  $reverse=pll_get_term_translations($target->term_id);
  if(!is_array($reverse)){return failure('relationships');}
  if((int)($reverse[$locale]??0)!==$term->term_id||(int)($reverse[$language]??0)!==$target->term_id){continue;}
  $translations[]=['id'=>$target->term_id,'name'=>$target->name,'slug'=>$target->slug,'locale'=>$language];
 }
 return ['id'=>$term->term_id,'name'=>$term->name,'slug'=>$term->slug,'taxonomy'=>$term->taxonomy,'locale'=>$locale,'translations'=>$translations];
}
function get_classification($input){
 $check=authorize($input,false);if(is_wp_error($check)){return $check;}
 $post=get_post($input['id']);if(!visible($post,$input['locale'])){return failure('not_found',404);}
 $catalog=inventory();if(is_wp_error($catalog)){return $catalog;}$out=[];
 foreach(TAXONOMIES as$taxonomy){$terms=assigned([$post->ID],$taxonomy,$input['locale'],100);if(is_wp_error($terms)){return $terms;}
  $items=[];foreach($terms as$term){$item=term_metadata($term,$input['locale'],$catalog);if(is_wp_error($item)){return $item;}$items[]=$item;}$out[$taxonomy]=$items;
 }
 return ['project_id'=>$post->ID,'locale'=>$input['locale'],'categories'=>$out['project_category'],'technologies'=>$out['project_technology']];
}
function list_terms($input){
 $check=authorize($input,true);if(is_wp_error($check)){return $check;}$catalog=inventory();if(is_wp_error($catalog)){return $catalog;}
 $after=$input['after']??0;$limit=$input['limit']??20;$items=[];
 foreach($catalog[$input['locale']][$input['taxonomy']]as$id=>$term){if($id<=$after){continue;}
  $item=term_metadata($term,$input['locale'],$catalog);if(is_wp_error($item)){return $item;}$items[]=$item;
 }
 $more=count($items)>$limit;$items=array_slice($items,0,$limit);
 return ['items'=>$items,'next_after'=>$more?$items[count($items)-1]['id']:null];
}
function contracts(): array {
 $locale=['type'=>'string','enum'=>['en','es']];$id=['type'=>'integer','minimum'=>1];
 $object=static fn($props)=>['type'=>'object','additionalProperties'=>false,'properties'=>$props,'required'=>array_keys($props)];
 $relation=$object(['id'=>$id,'name'=>['type'=>'string'],'slug'=>['type'=>'string'],'locale'=>$locale]);
 $term=$object(['id'=>$id,'name'=>['type'=>'string'],'slug'=>['type'=>'string'],'taxonomy'=>['type'=>'string','enum'=>TAXONOMIES],
  'locale'=>$locale,'translations'=>['type'=>'array','maxItems'=>1,'items'=>$relation]]);
 $catalog=['type'=>'object','additionalProperties'=>false,'properties'=>['locale'=>$locale,'taxonomy'=>['type'=>'string','enum'=>TAXONOMIES],
  'after'=>['type'=>'integer','minimum'=>0],'limit'=>['type'=>'integer','minimum'=>1,'maximum'=>20]],'required'=>['locale','taxonomy']];
 return [
  'get-public-project-classification'=>['input'=>$object(['id'=>$id,'locale'=>$locale]),'output'=>$object(['project_id'=>$id,'locale'=>$locale,
   'categories'=>['type'=>'array','maxItems'=>100,'items'=>$term],'technologies'=>['type'=>'array','maxItems'=>100,'items'=>$term]]),'callback'=>__NAMESPACE__.'\\get_classification'],
  'list-public-project-terms'=>['input'=>$catalog,'output'=>$object(['items'=>['type'=>'array','maxItems'=>20,'items'=>$term],
   'next_after'=>['type'=>['integer','null'],'minimum'=>1]]),'callback'=>__NAMESPACE__.'\\list_terms'],
 ];
}
add_action('wp_abilities_api_categories_init',static function(){wp_register_ability_category('alexcuadra-project-classification',[
 'label'=>'Public project classification','description'=>'Read-only project-owned public terms.']);});
add_action('wp_abilities_api_init',static function(){foreach(contracts()as$name=>$c){wp_register_ability('alexcuadra/'.$name,[
 'label'=>$name,'description'=>'Read only project categories/technologies witnessed on public projects, with reciprocal public translations.',
 'category'=>'alexcuadra-project-classification','input_schema'=>$c['input'],'output_schema'=>$c['output'],'execute_callback'=>$c['callback'],
 'permission_callback'=>__NAMESPACE__.'\\permission','meta'=>['public'=>false,'show_in_rest'=>false,'mcp'=>['public'=>true,'type'=>'tool'],
 'annotations'=>['readonly'=>true,'destructive'=>false,'idempotent'=>true]]]);}});
