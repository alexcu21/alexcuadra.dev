<?php
require_once '/integration/support.php';
use function AlexCuadra\EditorialSourceExperiment\write_source;
$GLOBALS['bilingual_checks']=0;
function bi_check($ok,$label){$GLOBALS['bilingual_checks']++;if(!$ok){throw new RuntimeException('BILINGUAL_CHECK: '.$label);}}
function bi_failure(callable $fn,$code){try{$fn();}catch(RuntimeException $e){bi_check($e->getMessage()===$code,$code.' got '.$e->getMessage());return;}throw new RuntimeException('Expected '.$code);}
function bi_links($id){$map=pll_get_post_translations($id);ksort($map);return $map;}
function own_rows($id){global $wpdb;return [$wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d",$id),ARRAY_A),$wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id=%d ORDER BY meta_id",$id),ARRAY_A),$wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->term_relationships WHERE object_id=%d ORDER BY term_taxonomy_id",$id),ARRAY_A)];}
function bi_record($j,$event,$d){return $j->record($event,fn()=>['version'=>write_source($d,'snapshot','field_schema','cache_clear')['version']]);}
global $wpdb;$j=source_operation($wpdb);$ids=[];
foreach(['en','es'] as $lang){$id=wp_insert_post(['post_type'=>'project','post_status'=>'publish','post_title'=>'Paired '.$lang,'post_content'=>'Before '.$lang],true);pll_set_post_language($id,$lang);update_field('field_676dcb2aec8d4','Before tagline '.$lang,$id);update_post_meta($id,'unrelated','preserve '.$lang);$ids[$lang]=$id;}
pll_save_post_translations($ids);$beforeEs=own_rows($ids['es']);$beforeEn=own_rows($ids['en']);
bi_check(pll_get_post_language($ids['en'])==='en'&&pll_get_post_language($ids['es'])==='es','language assignments exact');
foreach(['body_html','tagline','both'] as $n=>$kind){
 $fields=$kind==='both'?['body_html','tagline']:[$kind];$d=doc($ids['en'],$fields,'en');
 if(isset($d['after']['body_html'])){$d['after']['body_html']='<p>EN change '.$kind.'</p>';}
 if(isset($d['after']['tagline'])){$d['after']['tagline']['project_tagline']['value']='EN tagline '.$kind;}
 bi_record($j,event_for(100+$n,$ids['en']),$d);
 bi_check(own_rows($ids['es'])===$beforeEs,'EN '.$kind.' preserves every ES row');
 bi_check(bi_links($ids['en'])===$ids&&bi_links($ids['es'])===$ids,'links preserved '.$kind);
}
$afterEn=own_rows($ids['en']);$d=doc($ids['es'],['body_html','tagline'],'es');$d['after']['body_html']='<p>ES change</p>';
bi_record($j,event_for(110,$ids['es']),$d);bi_check(own_rows($ids['en'])===$afterEn,'ES combined preserves every EN row');
bi_check(get_post($ids['es'])->post_content==='<p>ES change</p>','ES body applied exact target');
$d=doc($ids['es'],['body_html','tagline'],'es');$d['operation']='recover';$d['after']['body_html']='Before es';$d['after']['tagline']['project_tagline']['value']='Before tagline es';
bi_record($j,event_for(111,$ids['es'],'recover'),$d);bi_check(own_rows($ids['en'])===$afterEn,'ES recovery preserves EN');bi_check(get_post_meta($ids['es'],'project_tagline',true)==='Before tagline es','ES recovery exact tagline');
bi_failure(fn()=>snapshot($ids['es'],'en',['body_html'=>''],false),'source_locale_changed');
$d=doc($ids['en'],['body_html'],'en');$before=own_rows($ids['en']);PLL()->options->set('sync',['post_meta']);PLL()->options->save_all();
try{bi_failure(fn()=>bi_record($j,event_for(120,$ids['en']),$d),'polylang_profile_changed');}finally{PLL()->options->set('sync',[]);PLL()->options->save_all();}
bi_check(own_rows($ids['en'])===$before&&$j->effect(event_for(120,$ids['en'])['event_id'])===null,'sync drift rejects before any source write');
$unknown=static fn($value)=>$value;
foreach(['pll_copy_post_metas','pll_pre_current_user_can_synchronize_post'] as $hook){add_filter($hook,$unknown);
try{bi_failure(fn()=>bi_record($j,event_for(121,$ids['en']),$d),'polylang_profile_changed');}finally{remove_filter($hook,$unknown);}
bi_check(own_rows($ids['en'])===$before,'unknown synchronization filter rejects before source write');}

// A linked translation edited after review invalidates the source fingerprint.
$d=doc($ids['en'],['tagline'],'en');update_post_meta($ids['es'],'concurrent_editor','changed');
bi_failure(fn()=>bi_record($j,event_for(122,$ids['en']),$d),'source_changed');bi_check(own_rows($ids['en'])===$before,'translation conflict cannot overwrite EN');
// A callback modifying the linked Project inside the transaction is rolled back.
$d=doc($ids['en'],['body_html'],'en');$pairBefore=[own_rows($ids['en']),own_rows($ids['es'])];
$intruder=static function($postId)use($ids){if((int)$postId===$ids['en']){update_post_meta($ids['es'],'unexpected_hook','must rollback');}};
add_action('save_post_project',$intruder,50);
try{bi_failure(fn()=>bi_record($j,event_for(123,$ids['en']),$d),'unrelated_source_changed');}finally{remove_action('save_post_project',$intruder,50);}
bi_check([own_rows($ids['en']),own_rows($ids['es'])]===$pairBefore,'linked callback corruption rolls both Projects back');
bi_check(get_post_meta($ids['es'],'unexpected_hook',true)==='','linked value cache cleared after rollback');
bi_check($j->effect(event_for(123,$ids['en'])['event_id'])===null,'rejected linked mutation has no executable effect');
echo 'SOURCE_EFFECTS_BILINGUAL_RESULT='.wp_json_encode(['checks'=>$GLOBALS['bilingual_checks'],'passed'=>true,'polylang'=>POLYLANG_VERSION,'translationRowsPreserved'=>true,'unexpectedSyncAndFilterDenied'=>true,'linkedConflictDenied'=>true]).PHP_EOL;
