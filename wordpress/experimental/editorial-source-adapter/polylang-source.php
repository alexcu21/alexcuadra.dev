<?php
/** Local bilingual source reader. No plugin hooks or public API. */
namespace AlexCuadra\EditorialSourceExperiment;
function polylang_admission() {
    local_wp_guard();
    if(!defined('POLYLANG_VERSION')||POLYLANG_VERSION!=='3.9.1'||!pll_is_translated_post_type('project')
        || PLL()->options->get('sync')!==[]){throw new \RuntimeException('polylang_profile_changed');}
    foreach(['pll_copy_post_metas','is_protected_meta','pll_pre_current_user_can_synchronize_post'] as $hook){
        if(has_filter($hook)!==false){throw new \RuntimeException('polylang_profile_changed');}
    }
}
function polylang_seen($ids=[]) {
    static $seen=[];
    foreach($ids as $id){$seen[(int)$id]=true;}
    return array_keys($seen);
}
function clear_polylang_source_cache($id) {
    $ids=array_unique(array_merge([(int)$id],polylang_seen()));
    foreach($ids as $item){clean_post_cache($item);wp_cache_delete($item,'post_meta');if(function_exists('acf_flush_value_cache')){acf_flush_value_cache($item,'project_tagline');}}
}
function polylang_source_snapshot($id,$locale,$fields,$locking) {
    global $wpdb;
    polylang_admission();
    if(!is_int($id)||$id<1||!in_array($locale,['en','es'],true)||!is_array($fields)||!$fields||array_diff(array_keys($fields),['body_html','tagline'])){throw new \RuntimeException('invalid_source_selection');}
    clean_object_term_cache($id,'project');
    if(pll_get_post_language($id)!==$locale){throw new \RuntimeException('source_locale_changed');}
    $map=pll_get_post_translations($id);ksort($map);
    if(!$map||array_diff(array_keys($map),['en','es'])||($map[$locale]??null)!==$id){throw new \RuntimeException('translation_map_changed');}
    $ids=array_values($map);sort($ids,SORT_NUMERIC);polylang_seen($ids);
    foreach($map as $lang=>$partner){
        clean_object_term_cache($partner,'project');
        $reverse=pll_get_post_translations($partner);ksort($reverse);
        if(pll_get_post_language($partner)!==$lang||$reverse!==$map){throw new \RuntimeException('translation_map_changed');}
    }
    $list=implode(',',array_map('intval',$ids));$suffix=$locking?' FOR UPDATE':'';
    $read=static function($query)use($wpdb){$rows=$wpdb->get_results($query,ARRAY_A);if($wpdb->last_error||!is_array($rows)){throw new \RuntimeException('source_read_failed');}return $rows;};
    $posts=$read("SELECT * FROM $wpdb->posts WHERE ID IN ($list) ORDER BY ID$suffix");
    $metas=$read("SELECT post_id,meta_key,meta_value FROM $wpdb->postmeta WHERE post_id IN ($list) ORDER BY post_id,meta_id$suffix");
    $rels=$read("SELECT * FROM $wpdb->term_relationships WHERE object_id IN ($list) ORDER BY object_id,term_taxonomy_id$suffix");
    if(count($posts)!==count($ids)){throw new \RuntimeException('translation_source_missing');}
    $target=null;$others=[];
    foreach($posts as $post){
        if($post['post_type']!=='project'||$post['post_status']!=='publish'||$post['post_password']!==''){throw new \RuntimeException('source_not_public_project');}
        if((int)$post['ID']===$id){$target=$post;}else{$others[]=$post;}
    }
    $taxIds=array_values(array_unique(array_column($rels,'term_taxonomy_id')));sort($taxIds,SORT_NUMERIC);
    $tax=$taxIds?$read('SELECT * FROM '.$wpdb->term_taxonomy.' WHERE term_taxonomy_id IN ('.implode(',',array_map('intval',$taxIds)).") ORDER BY term_taxonomy_id$suffix"):[];
    $termIds=array_values(array_unique(array_column($tax,'term_id')));sort($termIds,SORT_NUMERIC);
    $terms=$termIds?$read('SELECT * FROM '.$wpdb->terms.' WHERE term_id IN ('.implode(',',array_map('intval',$termIds)).") ORDER BY term_id$suffix"):[];
    $meta=[];$otherMeta=[];$ownRels=[];$otherRels=[];
    foreach($metas as $row){if((int)$row['post_id']===$id){unset($row['post_id']);$meta[]=$row;}else{$otherMeta[]=$row;}}
    foreach($rels as $row){if((int)$row['object_id']===$id){$ownRels[]=$row;}else{$otherRels[]=$row;}}
    // Verify the API's map against the locked SQL language/group rows. API caches
    // alone must not admit a locale or translation changed by another editor.
    $taxById=[];$termById=[];
    foreach($tax as $row){$taxById[(int)$row['term_taxonomy_id']]=$row;}
    foreach($terms as $row){$termById[(int)$row['term_id']]=$row;}
    foreach($map as $lang=>$partner){
        $languageRows=0;$groupRows=0;
        foreach($rels as $rel){
            if((int)$rel['object_id']!==$partner){continue;}
            $t=$taxById[(int)$rel['term_taxonomy_id']];
            if($t['taxonomy']==='language'){
                $languageRows++;if(($termById[(int)$t['term_id']]['slug']??null)!==$lang){throw new \RuntimeException('translation_sql_changed');}
            }
            if($t['taxonomy']==='post_translations'){
                $groupRows++;$sqlMap=pll_maybe_unserialize($t['description']);
                if(!is_array($sqlMap)){throw new \RuntimeException('translation_sql_changed');}
                ksort($sqlMap);if($sqlMap!==$map){throw new \RuntimeException('translation_sql_changed');}
            }
        }
        if($languageRows!==1||$groupRows>1||(count($map)>1&&$groupRows!==1)){throw new \RuntimeException('translation_sql_changed');}
    }
    $values=[];
    if(array_key_exists('body_html',$fields)){$values['body_html']=$target['post_content'];}
    if(array_key_exists('tagline',$fields)){
        foreach(['project_tagline','_project_tagline'] as $key){$found=array_values(array_filter($meta,static fn($r)=>$r['meta_key']===$key));if(count($found)>1){throw new \RuntimeException('duplicate_meta');}$values['tagline'][$key]=['present'=>count($found)===1,'value'=>$found[0]['meta_value']??null];}
    }
    $rows=['post'=>$target,'meta'=>$meta,'terms'=>$ownRels,'translationPosts'=>$others,'translationMeta'=>$otherMeta,'translationRelations'=>$otherRels,'translationMap'=>$map,'taxonomyRows'=>$tax,'termRows'=>$terms];
    return ['version'=>hash('sha256',serialize($rows)),'before'=>$values,'rows'=>$rows];
}
