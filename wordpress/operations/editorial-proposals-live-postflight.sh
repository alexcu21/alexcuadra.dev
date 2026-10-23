#!/bin/sh
# Final read-only server postflight. Alex runs SSH; no scope/data changes.
set -eu
cd "$HOME/domains/alexcuadra.dev/public_html/content"
printf '%s\n' EDITORIAL_PROPOSALS_LIVE_POSTFLIGHT_V1
wp --path=. --user=1 eval-file - <<'AC_LIVE_POSTFLIGHT_PHP'
<?php
// Approved phase B2: fingerprints/read-only postflight, never grants capabilities.
if (!defined('WP_CLI') || !WP_CLI || home_url() !== 'https://content.alexcuadra.dev'
    || site_url() !== home_url() || is_multisite()) { throw new RuntimeException('EXPECTED_CLI_SITE'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
function ac_proposals_activation_snapshot() {
    global $wpdb;
    $ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type='project' ORDER BY ID");
    if ($wpdb->last_error || !is_array($ids) || !in_array('146', array_map('strval', $ids), true) || !in_array('196', array_map('strval', $ids), true)) { WP_CLI::error('SOURCE_IDS_UNAVAILABLE'); }
    $list = implode(',', array_map('intval', $ids)); $source = [];
    foreach (["SELECT * FROM $wpdb->posts WHERE ID IN ($list) ORDER BY ID",
        "SELECT * FROM $wpdb->postmeta WHERE post_id IN ($list) ORDER BY meta_id",
        "SELECT * FROM $wpdb->term_relationships WHERE object_id IN ($list) ORDER BY object_id,term_taxonomy_id"] as $query) {
        $rows = $wpdb->get_results($query, ARRAY_A);
        if ($wpdb->last_error || !is_array($rows)) { WP_CLI::error('SOURCE_SNAPSHOT_FAILED'); }
        $source[] = $rows;
    }
    $user = get_userdata(2); $role = get_role('subscriber');
    if (!$user || !$role || $user->roles !== ['subscriber']) { WP_CLI::error('TARGET_ACCOUNT_CHANGED'); }
    $caps = $user->caps; ksort($caps); $roleCaps = $role->capabilities; ksort($roleCaps);
    $plugins = array_values(array_diff(get_option('active_plugins', []), ['alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php'])); sort($plugins);
    $hash = static function ($value) { return hash('sha256', serialize($value)); };
    return ['projectCount' => count($ids), 'sourceHash' => $hash($source), 'user2CapsHash' => $hash($caps),
        'subscriberRoleHash' => $hash($roleCaps), 'otherActivePluginsHash' => $hash($plugins),
        'stylesheet' => get_stylesheet(), 'template' => get_template(),
        'readPluginSha256' => hash_file('sha256', WP_PLUGIN_DIR . '/alexcuadra-editorial-read/alexcuadra-editorial-read.php')];
}

$operator = get_current_user_id();
if ($operator !== 1 || !current_user_can('manage_options')) { WP_CLI::error('EXPECTED_OPERATOR_ONE'); }
$expectedHash = 'c38acd1690f173488f075443d03a80c96b6b483a5bd407040cbbd40a7ff462af';
$expectedVersion = '737a0558179ee7df25706593918782eefb24297135fa4860090af450e4560b44';
$initialVersion = '4adb6146536c24eade19dda7799ec4ff13579e9ca1d26ac94e9c6149270bbfb7';
$state = ac_proposals_activation_snapshot();
if ($state['projectCount'] !== 14 || $state['sourceHash'] !== $expectedHash || !AlexCuadra\EditorialProposals\storage_schema_valid()) { WP_CLI::error('SOURCES_OR_SCHEMA_CHANGED'); }
$baselines = [];
foreach (glob(getenv('HOME') . '/.ac-editorial-deploy/activation.*/baseline.base64') ?: [] as $file) {
    if (is_link($file) || !is_file($file)) { continue; }
    $value = json_decode(base64_decode(trim(file_get_contents($file)), true), true);
    if (is_array($value) && ($value['sourceHash'] ?? null) === $expectedHash) { $baselines[hash('sha256', serialize($value))] = $value; }
}
if (count($baselines) !== 1) { WP_CLI::error('UNIQUE_ACTIVATION_BASELINE_REQUIRED'); }
$original = reset($baselines);
$direct = get_userdata(2)->caps;
if (empty($direct['alexcuadra_prepare_project_changes'])) { WP_CLI::error('EXPECTED_SCOPE_MISSING'); }
unset($direct['alexcuadra_prepare_project_changes']); ksort($direct);
if (hash('sha256', serialize($direct)) !== $original['user2CapsHash']) { WP_CLI::error('UNEXPECTED_DIRECT_CAPABILITIES'); }
$compare = $state; unset($compare['user2CapsHash'], $original['user2CapsHash']);
if ($compare !== $original) { WP_CLI::error('ROLE_DEPENDENCIES_OR_SOURCES_CHANGED'); }
$post = get_post(227);
if (!$post || $post->post_type !== 'ac_private_proposal' || $post->post_status !== 'draft' || (int) $post->post_author !== 2) { WP_CLI::error('SMOKE_RECORD_INVALID'); }
try {
    wp_set_current_user(2);
    $loaded = AlexCuadra\EditorialProposals\load_owned(227, 'en');
    if (is_wp_error($loaded)) { WP_CLI::error('SMOKE_PAYLOAD_UNAVAILABLE'); }
    $payload = $loaded['payload'];
    if ($payload['project_id'] !== 146 || $payload['version'] !== $expectedVersion || count($payload['history']) !== 3
        || array_column($payload['history'], 'operation') !== ['create','update','restore']
        || $payload['history'][2]['restored_from'] !== $initialVersion || $payload['history'][0]['patch'] !== $payload['patch']) { WP_CLI::error('SMOKE_HISTORY_INVALID'); }
    $usage = AlexCuadra\EditorialProposals\owner_usage(2);
    if (is_wp_error($usage) || $usage['proposals'] !== 1 || $usage['requests'] !== 3) { WP_CLI::error('UNEXPECTED_SMOKE_USAGE'); }
    foreach (['manage_options','install_plugins','activate_plugins','edit_users','promote_users','edit_posts','edit_pages','publish_posts','publish_pages'] as $cap) {
        if (current_user_can($cap)) { WP_CLI::error('UNEXPECTED_EDIT_OR_ADMIN_CAPABILITY'); }
    }
    foreach ([146 => 'en', 196 => 'es'] as $id => $locale) {
        if (current_user_can('edit_post', $id) || current_user_can('delete_post', $id)
            || is_wp_error(AlexCuadra\EditorialRead\get_project(['id'=>$id,'locale'=>$locale]))) { WP_CLI::error('SOURCE_PERMISSION_CHANGED'); }
    }
    if (AlexCuadra\EditorialProposals\permission() !== true) { WP_CLI::error('PREPARER_NOT_READY'); }
    wp_set_current_user(1);
    if (!is_wp_error(AlexCuadra\EditorialProposals\permission())) { WP_CLI::error('ADMIN_WITHOUT_SCOPE_NOT_DENIED'); }
    $type = get_post_type_object('ac_private_proposal');
    if (!$type || $type->public || $type->publicly_queryable || $type->show_ui || $type->show_in_rest || $type->show_in_graphql) { WP_CLI::error('CPT_PRIVACY_FLAGS_INVALID'); }
    if (!function_exists('graphql')) { WP_CLI::error('GRAPHQL_REQUIRED'); }
    $privacy = [];
    foreach ([2,1,0] as $identity) {
        wp_set_current_user($identity);
        $public = graphql(['query'=>'{project(id:"146",idType:DATABASE_ID){databaseId}}']);
        $publicOk = empty($public['errors']) && ($public['data']['project']['databaseId'] ?? null) === 146;
        if (!$publicOk && $identity !== 0) { WP_CLI::error('GRAPHQL_PUBLIC_CONTROL_FAILED'); }
        if (!$publicOk && $identity === 0 && !empty($public['errors'])) { $privacy['anonymous'] = 'access-denied'; continue; }
        foreach ([['contentNode','query($id:ID!){contentNode(id:$id,idType:DATABASE_ID){databaseId}}','227'],
            ['node','query($id:ID!){node(id:$id){id}}',base64_encode('post:227')]] as [$field,$query,$id]) {
            $result = graphql(['query'=>$query,'variables'=>['id'=>$id]]);
            if (!array_key_exists($field, $result['data'] ?? []) || $result['data'][$field] !== null) { WP_CLI::error('GRAPHQL_PRIVATE_NODE_NOT_HIDDEN'); }
        }
        $after = null; $complete = false;
        for ($page=0; $page<10; $page++) {
            $result = graphql(['query'=>'query($after:String){contentNodes(first:100,after:$after){nodes{databaseId}pageInfo{hasNextPage endCursor}}}', 'variables'=>['after'=>$after]]);
            $connection = $result['data']['contentNodes'] ?? null;
            if (!empty($result['errors']) || !is_array($connection) || in_array(227, array_column($connection['nodes'], 'databaseId'), true)) { WP_CLI::error('GRAPHQL_COLLECTION_NOT_PRIVATE'); }
            if (!$connection['pageInfo']['hasNextPage']) { $complete = true; break; }
            $cursor = $connection['pageInfo']['endCursor'];
            if (!is_string($cursor) || $cursor === $after) { WP_CLI::error('GRAPHQL_PAGINATION_UNAVAILABLE'); }
            $after = $cursor;
        }
        if (!$complete) { WP_CLI::error('GRAPHQL_COLLECTION_BOUND_EXCEEDED'); }
        $privacy[(string)$identity] = 'private-nodes-hidden-complete-collection';
    }
    wp_set_current_user(0);
    $routes = rest_get_server()->get_routes();
    foreach (array_keys($routes) as $route) {
        if (str_contains($route, 'ac_private_proposal')) { WP_CLI::error('PRIVATE_CPT_REST_ROUTE_PRESENT'); }
    }
    $response = rest_do_request(new WP_REST_Request('GET', '/wp/v2/posts/227'));
    if (!in_array($response->get_status(), [401,403,404], true)) { WP_CLI::error('GENERIC_REST_PRIVATE_RECORD_NOT_HIDDEN'); }
} finally { wp_set_current_user($operator); }
$final = ac_proposals_activation_snapshot();
if ($final !== $state) { WP_CLI::error('POSTFLIGHT_CHANGED_SOURCE_OR_PERMISSIONS'); }
WP_CLI::line('PROPOSALS_LIVE_POSTFLIGHT_RESULT=' . wp_json_encode(['version'=>'0.1.0-rc.2','proposalId'=>227,'ownerId'=>2,
    'projectId'=>146,'locale'=>'en','state'=>'draft','applied'=>false,'revisionCount'=>3,
    'ownerProposalCount'=>$usage['proposals'],'ownerRequestCount'=>$usage['requests'],'ownerLogicalBytes'=>$usage['bytes'],
    'projectCount'=>14,'sourceHash'=>$expectedHash,'sourcesUnchanged'=>true,'onlyPrepareScopeAdded'=>true,
    'subscriberRoleUnchanged'=>true,'otherActivePluginsUnchanged'=>true,'readPluginUnchanged'=>true,
    'sourceEditAdminDenied'=>true,'adminWithoutPrepareDenied'=>true,'restPrivacyVerified'=>true,
    'graphqlPrivacy'=>$privacy,'graphqlContext'=>'server-side CLI','graphqlHttpVerified'=>false,
    'operatorCreatedNoProposal'=>true,'operatorWroteNoData'=>true]));

AC_LIVE_POSTFLIGHT_PHP
printf '%s\n' END_EDITORIAL_PROPOSALS_LIVE_POSTFLIGHT_V1
