<?php
/** Trusted native projection; exact local laboratory only, no source bodies returned. */
namespace AlexCuadra\ProjectClassificationProposalLab;
require_once __DIR__.'/planner.php';

function wordpress_lab_guard(): void {
    if (!defined('ABSPATH') || !defined('AC_PROJECT_CLASSIFICATION_PROPOSALS_WORDPRESS_LAB')
        || AC_PROJECT_CLASSIFICATION_PROPOSALS_WORDPRESS_LAB !== true
        || !function_exists('wp_get_environment_type') || wp_get_environment_type() !== 'local'
        || home_url() !== 'http://wordpress' || site_url() !== 'http://wordpress' || is_multisite()
        || !defined('DB_HOST') || DB_HOST !== 'db') { reject('wordpress_lab_only'); }
}
function wordpress_actor(): array {
    wordpress_lab_guard();
    $caps=[];foreach(SCOPES as $scope){$caps[$scope]=current_user_can($scope);}
    $actor=['id'=>get_current_user_id(),'authenticated'=>is_user_logged_in(),'capabilities'=>$caps];
    authorize($actor);return $actor;
}
/** Reuse the read-only native reader's bounded public-witness and reciprocal-translation policy. */
function wordpress_projection(int $id,string $locale): array {
    wordpress_actor();if(!positive($id)){reject('input');}language($locale);
    if(!function_exists('\AlexCuadra\ProjectClassificationLab\inventory')
        || !\AlexCuadra\ProjectClassificationLab\laboratory()){reject('dependencies');}
    $ready=\AlexCuadra\ProjectClassificationLab\ready();if(is_wp_error($ready)){reject('dependencies');}
    global $wpdb;
    $collect=static function()use($id,$locale,$wpdb){
        // Validate current status/password/type from storage rather than a cached WP_Post.
        $row=$wpdb->get_row($wpdb->prepare("SELECT ID,post_type,post_status,post_password FROM {$wpdb->posts} WHERE ID=%d",$id));
        if($wpdb->last_error){reject('storage_failure');}
        if(!$row || !\AlexCuadra\ProjectClassificationLab\visible(new \WP_Post($row),$locale)){reject('source');}
        $inventory=\AlexCuadra\ProjectClassificationLab\inventory();if(is_wp_error($inventory)){reject('catalog');}
        $source=['project_id'=>$id,'locale'=>$locale];$catalog=[];
        foreach(TAXONOMIES as $field=>$tax){
            $assigned=\AlexCuadra\ProjectClassificationLab\assigned([$id],$tax,$locale,100);
            if(is_wp_error($assigned)){reject('classification');}
            $catalog[$field]=[];$source[$field]=[];
            foreach($inventory[$locale][$tax] as $term){
                $item=\AlexCuadra\ProjectClassificationLab\term_metadata($term,$locale,$inventory);
                if(is_wp_error($item)){reject('relationships');}$catalog[$field][]=$item;
                if(isset($assigned[$term->term_id])){$source[$field][]=$item;}
            }
            if(count($assigned)!==count($source[$field])){reject('classification');}
        }
        return context($source,$catalog);
    };
    $first=$collect();$second=$collect();
    if(digest($first)!==digest($second)){reject('context_changed');}
    return $first+['base_hash'=>digest($first)];
}
