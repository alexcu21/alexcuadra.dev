<?php
/** Local experiment only. No plugin header, hooks, abilities, migration or activation. */
namespace AlexCuadra\EditorialSourceExperiment;

const TAGLINE_KEY = 'field_676dcb2aec8d4';

function fail($code) { throw new \RuntimeException($code); }

function validate_fields($fields) {
    if (!is_array($fields) || !$fields || array_diff(array_keys($fields), ['body_html', 'tagline'])) { fail('invalid_fields'); }
    if (array_key_exists('body_html', $fields) && (!is_string($fields['body_html']) || strlen($fields['body_html']) > 160000 || !preg_match('//u',$fields['body_html']) || preg_match_all('/./us',$fields['body_html']) > 40000)) { fail('invalid_body'); }
    if (array_key_exists('tagline', $fields)) {
        $meta = $fields['tagline'];
        if (!is_array($meta) || count($meta) !== 2 || !isset($meta['project_tagline'], $meta['_project_tagline'])) { fail('invalid_tagline'); }
        foreach (['project_tagline', '_project_tagline'] as $key) {
            $row = $meta[$key];
            if (!is_array($row) || count($row) !== 2 || !array_key_exists('value', $row) || !isset($row['present']) || !is_bool($row['present'])
                || ($row['present'] ? !is_string($row['value']) : $row['value'] !== null)) { fail('invalid_meta_snapshot'); }
        }
        if ($meta['_project_tagline']['present'] && $meta['_project_tagline']['value'] !== TAGLINE_KEY) { fail('invalid_acf_reference'); }
    }
}

/**
 * read($id,$locale,$fields,true): fresh locked snapshot in review_source shape.
 * schema(): validated schema fingerprint. cache($id): clear WP/ACF value caches.
 * Caller must own transaction, approval, effect isolation and rollback. The separate
 * MariaDB coordinator is experimental; never register this function as an ability.
 */
function write_source(array $document, callable $read, callable $schema, callable $cache) {
    if(defined('AC_EDITORIAL_OPERATING_SITE')){
        if(!function_exists(__NAMESPACE__.'\\local_wp_guard')){fail('operating_installed_package_required');}local_wp_guard();
    }elseif (!defined('AC_EDITORIAL_SOURCE_EXPERIMENT') || AC_EDITORIAL_SOURCE_EXPERIMENT !== true
        || wp_get_environment_type() !== 'local' || (home_url() !== 'http://wordpress'
            && (!function_exists(__NAMESPACE__.'\\local_origin_admitted') || !local_origin_admitted()))) { fail('local_experiment_only'); }
    if (!isset($document['project_id'], $document['locale'], $document['source_version'], $document['schema_version'], $document['before'], $document['after'])
        || !is_int($document['project_id']) || $document['project_id'] < 1 || !in_array($document['locale'], ['en','es'], true)
        || !in_array($document['operation'] ?? null, ['apply','recover'], true)) { fail('invalid_document'); }
    validate_fields($document['before']); validate_fields($document['after']);
    $keys = array_keys($document['before']); $other = array_keys($document['after']); sort($keys); sort($other);
    if ($keys !== $other) { fail('field_set_changed'); }
    $id = $document['project_id']; $fields = array_fill_keys($keys, '');
    if ($schema() !== $document['schema_version']) { fail('schema_changed'); }
    $before = $read($id, $document['locale'], $fields, true);
    if (is_wp_error($before)) { fail('source_unavailable'); }
    if ($before['version'] !== $document['source_version'] || $before['before'] !== $document['before']) { fail('source_changed'); }
    try {
        // A metadata-only operation must not emit post-transition/save hooks.
        if (array_key_exists('body_html', $document['after'])) {
            $changed = wp_update_post(wp_slash(['ID'=>$id, 'post_content'=>$document['after']['body_html']]), true);
            if (is_wp_error($changed) || (int) $changed !== $id) { fail('body_write_failed'); }
        }
        if (array_key_exists('tagline', $document['after'])) {
            $meta = $document['after']['tagline'];
            if ($meta['project_tagline']['present']) { update_field(TAGLINE_KEY, wp_slash($meta['project_tagline']['value']), $id); }
            else { delete_field(TAGLINE_KEY, $id); }
            if ($meta['_project_tagline']['present']) { update_post_meta($id, '_project_tagline', wp_slash($meta['_project_tagline']['value'])); }
            else { delete_post_meta($id, '_project_tagline'); }
        }
    } finally { $cache($id); }
    // WP APIs may return false for an unchanged value; fresh state is authoritative.
    $after = $read($id, $document['locale'], $fields, true);
    if (is_wp_error($after)) { fail('source_unavailable'); }
    if ($after['before'] !== $document['after']) { fail('postcondition_failed'); }
    $a = $before['rows']; $b = $after['rows'];
    if (array_key_exists('body_html', $fields)) {
        foreach (['post_content','post_modified','post_modified_gmt'] as $key) { unset($a['post'][$key], $b['post'][$key]); }
    }
    if (array_key_exists('tagline', $fields)) {
        $unrelated = static function ($rows) { return array_values(array_filter($rows, static function ($row) {
            return !in_array($row['meta_key'], ['project_tagline','_project_tagline'], true);
        })); };
        $a['meta'] = $unrelated($a['meta']); $b['meta'] = $unrelated($b['meta']);
    }
    if ($a !== $b) { fail('unrelated_source_changed'); }
    if ($schema() !== $document['schema_version']) { fail('schema_changed'); }
    return $after;
}
