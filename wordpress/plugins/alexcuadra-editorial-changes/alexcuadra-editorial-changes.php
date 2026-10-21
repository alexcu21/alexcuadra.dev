<?php
/**
 * Plugin Name: Alex Cuadra Editorial Changes Laboratory
 * Description: Laboratory-only project proposals, independently approved application and conditional recovery.
 * Version: 0.5.0-lab
 * Requires at least: 6.9
 * Requires PHP: 8.0
 */
namespace AlexCuadra\EditorialChanges;

if (!defined('ABSPATH')) { exit; }
// Deliberately inert on the shared site: this is a prototype, not a deployable release.
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { return; }

const CAPABILITY = 'alexcuadra_prepare_project_changes';
const TYPE = 'ac_project_change';
const PAYLOAD = '_ac_editorial_change_payload';
// Bounded single-row journal: never silently discard older snapshots to accept a write.
const MAX_REVISIONS = 32;
const MAX_PAYLOAD_BYTES = 2097152;
const TAGLINE_KEY = 'field_676dcb2aec8d4'; // Existing Projects export; revalidate before any future apply operation.
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/reviews.php';
require_once __DIR__ . '/applications.php';
add_action('init', __NAMESPACE__ . '\\install_storage', 1);

function error($code, $status) { return new \WP_Error('ac_change_' . $code, $code, ['status' => $status]); }
function permission($input = null) {
    return is_user_logged_in() && current_user_can('read') && current_user_can('alexcuadra_read_public_projects')
        && current_user_can(CAPABILITY) ? true : error('forbidden', 403);
}
function validate($input, $kind) {
    $contracts = ['create' => ['project_id', 'locale', 'patch'], 'get' => ['proposal_id', 'locale'],
        'update' => ['proposal_id', 'locale', 'expected_version', 'patch'],
        'history' => ['proposal_id', 'locale', 'expected_version', 'page', 'per_page'],
        'revision' => ['proposal_id', 'locale', 'revision_version'],
        'restore' => ['proposal_id', 'locale', 'expected_version', 'revision_version']];
    if (!isset($contracts[$kind])) { return error('invalid_input', 400); }
    $keys = $contracts[$kind];
    if (in_array($kind, ['create', 'update', 'restore'], true)) { $keys[] = 'request_id'; }
    if (!is_array($input) || array_diff(array_keys($input), $keys) || array_diff($keys, array_keys($input))
        || !in_array($input['locale'], ['en', 'es'], true)) { return error('invalid_input', 400); }
    $id = $input[$kind === 'create' ? 'project_id' : 'proposal_id'];
    if (!is_int($id) || $id < 1) { return error('invalid_input', 400); }
    if (in_array($kind, ['create', 'update', 'restore'], true) && (!is_string($input['request_id'])
        || !preg_match('/^[a-f0-9]{32}$/D', $input['request_id']))) { return error('invalid_input', 400); }
    foreach (['expected_version', 'revision_version'] as $token) {
        if (in_array($token, $keys, true) && (!is_string($input[$token])
            || !preg_match('/^[a-f0-9]{64}$/D', $input[$token]))) { return error('invalid_input', 400); }
    }
    if ($kind === 'history' && (!is_int($input['page']) || $input['page'] < 1 || $input['page'] > MAX_REVISIONS
        || !is_int($input['per_page']) || $input['per_page'] < 1 || $input['per_page'] > 10)) { return error('invalid_input', 400); }
    if (in_array($kind, ['create', 'update'], true)) {
        if (!is_array($input['patch']) || !$input['patch'] || array_diff(array_keys($input['patch']), ['body_html', 'tagline'])) {
            return error('invalid_patch', 400);
        }
        foreach ($input['patch'] as $key => $value) {
            $limit = $key === 'body_html' ? 40000 : 500;
            if (!is_string($value) || strlen($value) > $limit * 4 || !preg_match('//u', $value)
                || preg_match_all('/./us', $value) > $limit || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $value)) {
                return error('invalid_patch', 400);
            }
        }
    }
    return true;
}
function normalize_patch($patch) {
    $result = [];
    foreach ($patch as $key => $value) {
        $result[$key] = $key === 'tagline' ? sanitize_textarea_field($value) : wp_kses($value, [
            'p' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'ul' => [], 'ol' => ['start' => true], 'li' => [],
            'strong' => [], 'em' => [], 'blockquote' => [], 'pre' => [], 'code' => [], 'br' => [],
            'a' => ['href' => true, 'title' => true],
        ], ['http', 'https']);
    }
    return $result;
}
function source($id, $locale) {
    if (!function_exists('pll_get_post_language') || !function_exists('pll_get_post_translations')) { return error('dependencies', 503); }
    $post = get_post($id);
    if (!$post || $post->post_type !== 'project' || $post->post_status !== 'publish' || $post->post_password !== ''
        || !is_post_publicly_viewable($post) || !current_user_can('read_post', $id)
        || pll_get_post_language($id, 'slug') !== $locale) { return error('not_found', 404); }
    $translations = pll_get_post_translations($id); ksort($translations);
    // Fingerprint only: existing source bodies/ACF values are never returned by these abilities.
    return hash('sha256', wp_json_encode([$post->ID, $post->post_title, $post->post_name, $post->post_content,
        $post->post_status, $post->post_password, $locale, $translations,
        get_post_meta($id, 'project_tagline', true), get_post_meta($id, '_project_tagline', true)]));
}
function dto($id, $payload) {
    $state = application_state($id);
    if (is_wp_error($state)) { return $state; }
    return ['proposal_id' => $id, 'project_id' => $payload['project_id'], 'locale' => $payload['locale'],
        'version' => $payload['version'], 'base_version' => $payload['base_version'], 'patch' => $payload['patch'],
        'state' => $state, 'applied' => $state !== 'draft'];
}
function revision($payload, $operation, $parent = null, $restored = null) {
    return ['version' => $payload['version'], 'parent_version' => $parent, 'operation' => $operation,
        'restored_from' => $restored, 'recorded_at' => $operation === 'baseline' ? null : gmdate('Y-m-d\TH:i:s\Z'), 'patch' => $payload['patch']];
}
function valid_history($payload) {
    $history = $payload['history'];
    if (!is_array($history) || !$history || array_keys($history) !== range(0, count($history) - 1)
        || count($history) > MAX_REVISIONS) { return false; }
    $seen = []; $parent = null;
    foreach ($history as $i => $entry) {
        if (!is_array($entry) || array_keys($entry) !== ['version', 'parent_version', 'operation', 'restored_from', 'recorded_at', 'patch']
            || !is_string($entry['version']) || !preg_match('/^[a-f0-9]{64}$/D', $entry['version']) || isset($seen[$entry['version']])
            || $entry['parent_version'] !== $parent
            || !in_array($entry['operation'], $i === 0 ? ['create', 'baseline'] : ['update', 'restore'], true)
            || ($entry['operation'] === 'baseline' ? $entry['recorded_at'] !== null : (!is_string($entry['recorded_at'])
                || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $entry['recorded_at'])))
            || is_wp_error(validate(['project_id' => $payload['project_id'], 'locale' => $payload['locale'], 'patch' => $entry['patch'], 'request_id' => str_repeat('0', 32)], 'create'))
            || normalize_patch($entry['patch']) !== $entry['patch']) { return false; }
        if ($entry['operation'] === 'restore') {
            if (!is_string($entry['restored_from']) || !isset($seen[$entry['restored_from']])
                || $entry['patch'] !== $seen[$entry['restored_from']]) { return false; }
        } elseif ($entry['restored_from'] !== null) { return false; }
        $seen[$entry['version']] = $entry['patch']; $parent = $entry['version'];
    }
    $last = $history[count($history) - 1];
    return $last['version'] === $payload['version'] && $last['patch'] === $payload['patch'];
}
function load_owned($id, $locale, $check_base = true) {
    global $wpdb;
    $post = get_post($id);
    if (!$post || $post->post_type !== TYPE || $post->post_status !== 'draft'
        || (int) $post->post_author !== get_current_user_id() || $post->post_password !== '') { return error('not_found', 404); }
    $rows = $wpdb->get_results($wpdb->prepare("SELECT meta_id,meta_value FROM $wpdb->postmeta WHERE post_id=%d AND meta_key=%s", $id, PAYLOAD), ARRAY_A);
    if (!is_array($rows) || count($rows) !== 1) { return error('payload_unavailable', 503); }
    if (strlen($rows[0]['meta_value']) > MAX_PAYLOAD_BYTES) { return error('payload_unavailable', 503); }
    $payload = json_decode($rows[0]['meta_value'], true);
    $legacy = is_array($payload) && array_keys($payload) === ['project_id', 'locale', 'base_version', 'version', 'patch'];
    if (!is_array($payload) || (!$legacy && array_keys($payload) !== ['project_id', 'locale', 'base_version', 'version', 'patch', 'history'])
        || !is_int($payload['project_id']) || !in_array($payload['locale'], ['en', 'es'], true)
        || !is_string($payload['base_version']) || !preg_match('/^[a-f0-9]{64}$/D', $payload['base_version'])
        || !is_string($payload['version']) || !preg_match('/^[a-f0-9]{64}$/D', $payload['version'])
        || is_wp_error(validate(['project_id' => $payload['project_id'], 'locale' => $payload['locale'], 'patch' => $payload['patch'], 'request_id' => str_repeat('0', 32)], 'create'))) {
        return error('payload_unavailable', 503);
    }
    if ($payload['locale'] !== $locale) { return error('not_found', 404); }
    $current = source($payload['project_id'], $locale);
    if (is_wp_error($current)) { return $current; }
    $applied = application_load_proposal($id);
    if (is_wp_error($applied)) { return $applied; }
    if ($check_base && !$applied && !hash_equals($payload['base_version'], $current)) { return error('source_changed', 409); }
    // Legacy 0.1 proposals expose only their surviving snapshot; earlier versions cannot be invented.
    if ($legacy) { $payload['history'] = [revision($payload, 'baseline')]; }
    if (!valid_history($payload)) { return error('payload_unavailable', 503); }
    return ['payload' => $payload, 'row' => $rows[0]];
}
function execute($input, $kind) {
    $allowed = permission($input); if (is_wp_error($allowed)) { return $allowed; }
    $valid = validate($input, $kind); if (is_wp_error($valid)) { return $valid; }
    $prepared = $kind === 'create' ? source($input['project_id'], $input['locale']) : load_owned($input['proposal_id'], $input['locale']);
    if (is_wp_error($prepared)) { return $prepared; }
    return in_array($kind, ['create', 'update', 'restore'], true) ? transact($input, $kind, $prepared) : execute_prepared($input, $kind, $prepared);
}
// Internal executor; MCP only registers execute(), which supplies authorization, validation and transaction.
function execute_prepared($input, $kind, $prepared, &$touched = null) {
    if ($kind === 'create') {
        $base = $prepared;
        $payload = ['project_id' => $input['project_id'], 'locale' => $input['locale'], 'base_version' => $base,
            'version' => bin2hex(random_bytes(32)), 'patch' => normalize_patch($input['patch'])];
        $payload['history'] = [revision($payload, 'create')];
        $id = wp_insert_post(wp_slash(['post_type' => TYPE, 'post_status' => 'draft', 'post_author' => get_current_user_id(),
            'post_title' => 'Project change proposal', 'post_content' => '', 'comment_status' => 'closed', 'ping_status' => 'closed']), true);
        if (is_wp_error($id) || !$id) { return error('create_failed', 503); }
        $touched = $id;
        if (!add_post_meta($id, PAYLOAD, wp_slash(wp_json_encode($payload)), true)) { return error('create_failed', 503); }
        return dto($id, $payload);
    }
    $loaded = $prepared;
    $payload = $loaded['payload'];
    $state = application_state($input['proposal_id']); if (is_wp_error($state)) { return $state; }
    if (in_array($kind, ['update', 'restore'], true) && $state !== 'draft') { return error('proposal_frozen', 409); }
    if ($kind === 'get') { return dto($input['proposal_id'], $payload); }
    if ($kind === 'history') {
        if (!hash_equals($payload['version'], $input['expected_version'])) { return error('proposal_changed', 409); }
        $total = count($payload['history']); $offset = ($input['page'] - 1) * $input['per_page'];
        $entries = array_slice($payload['history'], $offset, $input['per_page']);
        foreach ($entries as &$entry) { unset($entry['patch']); } unset($entry);
        return ['proposal_id' => $input['proposal_id'], 'current_version' => $payload['version'], 'revisions' => $entries,
            'total' => $total, 'next_page' => $offset + count($entries) < $total ? $input['page'] + 1 : null];
    }
    if (in_array($kind, ['revision', 'restore'], true)) {
        $selected = null;
        foreach ($payload['history'] as $entry) {
            if (hash_equals($entry['version'], $input['revision_version'])) { $selected = $entry; break; }
        }
        if ($selected === null) { return error('not_found', 404); }
        if ($kind === 'revision') {
            return ['proposal_id' => $input['proposal_id'], 'project_id' => $payload['project_id'], 'locale' => $payload['locale'],
                'current_version' => $payload['version'], 'revision' => $selected,
                'state' => application_state($input['proposal_id']), 'applied' => application_state($input['proposal_id']) !== 'draft'];
        }
    }
    if (!hash_equals($payload['version'], $input['expected_version'])) { return error('proposal_changed', 409); }
    if (count($payload['history']) >= MAX_REVISIONS) { return error('history_full', 409); }
    $parent = $payload['version'];
    $payload['patch'] = $kind === 'restore' ? $selected['patch'] : array_merge($payload['patch'], normalize_patch($input['patch']));
    $payload['version'] = bin2hex(random_bytes(32));
    $payload['history'][] = revision($payload, $kind, $parent, $kind === 'restore' ? $selected['version'] : null);
    $encoded = wp_json_encode($payload);
    if (!is_string($encoded) || strlen($encoded) > MAX_PAYLOAD_BYTES) { return error('history_full', 409); }
    global $wpdb;
    // Single-row compare-and-swap. Owner/type/status are checked again in the same SQL mutation.
    $changed = $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta m INNER JOIN $wpdb->posts p ON p.ID=m.post_id
        SET m.meta_value=%s WHERE m.meta_id=%d AND m.post_id=%d AND m.meta_key=%s AND BINARY m.meta_value=BINARY %s
        AND p.post_author=%d AND p.post_type=%s AND p.post_status='draft' AND p.post_password=''",
        $encoded, (int) $loaded['row']['meta_id'], $input['proposal_id'], PAYLOAD,
        $loaded['row']['meta_value'], get_current_user_id(), TYPE));
    if ($changed !== 1) { return error($changed === false ? 'write_failed' : 'proposal_changed', $changed === false ? 503 : 409); }
    wp_cache_delete($input['proposal_id'], 'post_meta'); clean_post_cache($input['proposal_id']);
    return dto($input['proposal_id'], $payload);
}

add_action('init', static function () {
    register_post_type(TYPE, ['public' => false, 'publicly_queryable' => false, 'show_ui' => false,
        'show_in_rest' => false, 'show_in_graphql' => false, 'exclude_from_search' => true,
        'rewrite' => false, 'query_var' => false, 'supports' => [],
        'capability_type' => ['ac_project_change', 'ac_project_changes'], 'map_meta_cap' => true,
        'capabilities' => ['create_posts' => 'do_not_allow']]);
});
add_action('wp_abilities_api_categories_init', static function () {
    wp_register_ability_category('alexcuadra-change-lab', ['label' => 'Project changes laboratory', 'description' => 'Private proposals and independently approved source application/recovery, isolated laboratory only.']);
});
add_action('wp_abilities_api_init', static function () {
    $locale = ['type' => 'string', 'enum' => ['en', 'es']]; $id = ['type' => 'integer', 'minimum' => 1];
    $token = ['type' => 'string', 'pattern' => '^[a-f0-9]{64}$'];
    $patch = ['type' => 'object', 'additionalProperties' => false, 'minProperties' => 1, 'properties' => [
        'body_html' => ['type' => 'string', 'maxLength' => 40000], 'tagline' => ['type' => 'string', 'maxLength' => 500]]];
    $output = ['type' => 'object', 'additionalProperties' => false, 'required' => ['proposal_id', 'project_id', 'locale', 'version', 'base_version', 'patch', 'state', 'applied'],
        'properties' => ['proposal_id' => $id, 'project_id' => $id, 'locale' => $locale, 'version' => $token,
            'base_version' => $token, 'patch' => $patch, 'state' => ['type' => 'string', 'enum' => ['draft', 'applied', 'recovered']], 'applied' => ['type' => 'boolean']]];
    $revision_properties = ['version' => $token, 'parent_version' => ['type' => ['string', 'null'], 'pattern' => '^[a-f0-9]{64}$'],
        'operation' => ['type' => 'string', 'enum' => ['create', 'baseline', 'update', 'restore']],
        'restored_from' => ['type' => ['string', 'null'], 'pattern' => '^[a-f0-9]{64}$'],
        'recorded_at' => ['type' => ['string', 'null']]];
    $revision_metadata = ['type' => 'object', 'additionalProperties' => false,
        'properties' => $revision_properties, 'required' => array_keys($revision_properties)];
    $revision_snapshot = $revision_metadata;
    $revision_snapshot['properties']['patch'] = $patch; $revision_snapshot['required'][] = 'patch';
    $history_output = ['type' => 'object', 'additionalProperties' => false,
        'required' => ['proposal_id', 'current_version', 'revisions', 'total', 'next_page'],
        'properties' => ['proposal_id' => $id, 'current_version' => $token,
            'revisions' => ['type' => 'array', 'items' => $revision_metadata, 'maxItems' => 10],
            'total' => ['type' => 'integer', 'minimum' => 1, 'maximum' => MAX_REVISIONS],
            'next_page' => ['type' => ['integer', 'null'], 'minimum' => 1]]];
    $revision_output = ['type' => 'object', 'additionalProperties' => false,
        'required' => ['proposal_id', 'project_id', 'locale', 'current_version', 'revision', 'state', 'applied'],
        'properties' => ['proposal_id' => $id, 'project_id' => $id, 'locale' => $locale, 'current_version' => $token,
            'revision' => $revision_snapshot, 'state' => ['type' => 'string', 'enum' => ['draft', 'applied', 'recovered']],
            'applied' => ['type' => 'boolean']]];
    foreach (['create' => 'create-project-change', 'get' => 'get-project-change', 'update' => 'update-project-change',
        'history' => 'list-project-change-revisions', 'revision' => 'get-project-change-revision',
        'restore' => 'restore-project-change-revision'] as $kind => $name) {
        $properties = $kind === 'create' ? ['project_id' => $id, 'locale' => $locale, 'patch' => $patch]
            : ['proposal_id' => $id, 'locale' => $locale];
        if ($kind === 'update') { $properties += ['expected_version' => $token, 'patch' => $patch]; }
        if (in_array($kind, ['history', 'restore'], true)) { $properties['expected_version'] = $token; }
        if (in_array($kind, ['revision', 'restore'], true)) { $properties['revision_version'] = $token; }
        if ($kind === 'history') { $properties += ['page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => MAX_REVISIONS],
            'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10]]; }
        $readonly = in_array($kind, ['get', 'history', 'revision'], true);
        if (!$readonly) { $properties['request_id'] = ['type' => 'string', 'pattern' => '^[a-f0-9]{32}$']; }
        wp_register_ability('alexcuadra/' . $name, ['label' => $name, 'description' => 'Prepare, inspect history or restore a private owned proposal as a new version. Never modifies the source project or publishes content.',
            'category' => 'alexcuadra-change-lab', 'permission_callback' => __NAMESPACE__ . '\\permission',
            'input_schema' => ['type' => 'object', 'additionalProperties' => false, 'properties' => $properties, 'required' => array_keys($properties)],
            'output_schema' => $kind === 'history' ? $history_output : ($kind === 'revision' ? $revision_output : $output),
            'execute_callback' => static function ($input) use ($kind) { return execute($input, $kind); },
            'meta' => ['public' => false, 'show_in_rest' => false, 'mcp' => ['public' => true, 'type' => 'tool'],
                'annotations' => ['readonly' => $readonly, 'destructive' => false, 'idempotent' => true]]]);
    }
});
