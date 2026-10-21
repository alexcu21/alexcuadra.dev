<?php
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') {
    throw new RuntimeException('ISOLATED_LAB_REQUIRED');
}
$cases = [];
$check = static function ($name, $condition) use (&$cases) {
    if (!$condition) { throw new RuntimeException('FAILED: ' . $name); }
    $cases[] = $name;
};
PLL()->options->set('post_types', ['project']);
PLL()->options->save_all();
foreach ([['en', 'en_US', 'English'], ['es', 'es_ES', 'Español']] as $language) {
    if (!in_array($language[0], pll_languages_list(), true)) {
        $result = PLL()->model->add_language(['name' => $language[2], 'slug' => $language[0],
            'locale' => $language[1], 'rtl' => false, 'term_group' => $language[0] === 'en' ? 0 : 1]);
        if (is_wp_error($result)) { throw new RuntimeException('LANGUAGE_SETUP_FAILED'); }
    }
}
PLL()->options->set('default_lang', 'en'); PLL()->options->save_all();
$marker = 'edread' . bin2hex(random_bytes(5));
$ids = [];
$create = static function ($label, $locale, $status = 'publish', $password = '', $type = 'project') use ($marker, &$ids) {
    $id = wp_insert_post(['post_type' => $type, 'post_status' => $status, 'post_title' => "$marker $label",
        'post_content' => 'PRIVATE_BODY_AND_SHORTCODE [secret value="PRIVATE_TOKEN"]', 'post_password' => $password,
        'post_date' => $status === 'future' ? gmdate('Y-m-d H:i:s', time() + DAY_IN_SECONDS) : current_time('mysql')], true);
    if (is_wp_error($id)) { throw new RuntimeException('FIXTURE_CREATE_FAILED'); }
    if ($locale) { pll_set_post_language($id, $locale); }
    else { wp_set_object_terms($id, [], 'language'); }
    update_post_meta($id, 'private_contact', 'PRIVATE_RECIPIENT');
    $ids[] = $id;
    return $id;
};
$en = $create('Public A', 'en'); $es = $create('Público A', 'es');
pll_save_post_translations(['en' => $en, 'es' => $es]);
$second = $create('Public B', 'en'); $third = $create('Public C', 'en');
$draft = $create('Draft', 'en', 'draft'); $private = $create('Private', 'en', 'private');
$protected = $create('Protected', 'en', 'publish', 'synthetic-password');
$future = $create('Future', 'en', 'future'); $unknown = $create('Unknown language', null);
$page = $create('Other type', 'en', 'publish', '', 'page');
$privateEn = $create('Private translation source', 'en'); $privateEs = $create('Private ES', 'es', 'draft');
pll_save_post_translations(['en' => $privateEn, 'es' => $privateEs]);
$passwordEn = $create('Password translation source', 'en'); $passwordEs = $create('Protected ES', 'es', 'publish', 'synthetic-password');
pll_save_post_translations(['en' => $passwordEn, 'es' => $passwordEs]);
$readerId = wp_create_user($marker, wp_generate_password(32), "$marker@example.test");
if (is_wp_error($readerId)) { throw new RuntimeException('READER_SETUP_FAILED'); }
$reader = new WP_User($readerId); $reader->set_role('subscriber');
$cap = \AlexCuadra\EditorialRead\CAPABILITY;
$list = wp_get_ability('alexcuadra/list-public-projects');
$get = wp_get_ability('alexcuadra/get-public-project');
$check('abilities_registered', $list instanceof WP_Ability && $get instanceof WP_Ability);
$check('readonly_annotations', $list->get_meta()['annotations'] === ['readonly' => true, 'destructive' => false, 'idempotent' => true]);
$check('mcp_only_exposure', $list->get_meta()['mcp']['public'] === true && $list->get_meta()['public'] === false && $list->get_meta()['show_in_rest'] === false);
$check('activation_does_not_grant_subscriber_capability', !get_role('subscriber')->has_cap($cap) && !$reader->has_cap($cap));
wp_set_current_user(0);
$check('anonymous_denied', is_wp_error($list->execute(['locale' => 'en'])));
wp_set_current_user($readerId);
$check('subscriber_without_specific_capability_denied', is_wp_error($get->execute(['locale' => 'en', 'id' => $en])));
$check('direct_callback_also_denied', is_wp_error(\AlexCuadra\EditorialRead\get_project(['locale' => 'en', 'id' => $en])));
wp_set_current_user(1);
$check('administrator_not_automatically_authorized', is_wp_error($list->execute(['locale' => 'en'])));
$reader->add_cap($cap); wp_set_current_user($readerId);
$check('reader_still_cannot_edit_or_manage_options', !current_user_can('edit_pages') && !current_user_can('manage_options'));
$listInput = ['locale' => 'en', 'search' => $marker];
$seen = []; $next = 1; $pages = 0;
while ($next !== null && $pages < 10) {
    $data = $list->execute($listInput + ['page' => $next, 'per_page' => 2]);
    if (is_wp_error($data)) { throw new RuntimeException('LIST_EXECUTION_FAILED: ' . $data->get_error_code()); }
    foreach ($data['items'] as $item) { $seen[] = $item['id']; }
    $next = $data['next_page']; $pages++;
}
$expected = [$en, $second, $third, $privateEn, $passwordEn]; sort($expected);
$check('pagination_complete_unique_and_public_only', $seen === $expected && count($seen) === count(array_unique($seen)) && $pages === 3);
$spanish = $list->execute(['locale' => 'es', 'search' => $marker]);
$check('spanish_list_is_exact_language', !is_wp_error($spanish) && array_column($spanish['items'], 'id') === [$es]);
$empty = $list->execute(['locale' => 'en', 'search' => $marker . 'nothing']);
$check('empty_inventory_is_explicit', $empty === ['items' => [], 'next_page' => null]);
$bodySearch = $list->execute(['locale' => 'en', 'search' => 'PRIVATE_BODY_AND_SHORTCODE']);
$check('search_never_queries_body_or_meta_fields', $bodySearch === ['items' => [], 'next_page' => null]);
$public = $get->execute(['locale' => 'en', 'id' => $en]);
$check('public_metadata_has_exact_fields', !is_wp_error($public) && array_keys($public) === ['id', 'title', 'slug', 'locale', 'translations']);
$check('only_reciprocal_public_translation_announced', $public['translations'] === [['id' => $es, 'slug' => get_post($es)->post_name, 'locale' => 'es']]);
$check('body_meta_and_recipients_not_exposed', !str_contains(wp_json_encode($public), 'PRIVATE'));
foreach ([$draft, $private, $protected, $future, $unknown, $page, 2147483647] as $id) {
    $result = $get->execute(['locale' => 'en', 'id' => $id]);
    $check('nonpublic_missing_or_other_type_404_' . count($cases), is_wp_error($result) && $result->get_error_code() === 'alexcuadra_project_not_found');
}
$wrong = $get->execute(['locale' => 'es', 'id' => $en]);
$check('wrong_language_has_no_fallback', is_wp_error($wrong) && $wrong->get_error_code() === 'alexcuadra_project_not_found');
foreach ([$privateEn, $passwordEn] as $id) {
    $result = $get->execute(['locale' => 'en', 'id' => $id]);
    $check('nonpublic_translation_excluded_' . count($cases), !is_wp_error($result) && $result['translations'] === []);
}
foreach ([['locale' => 'fr'], ['locale' => 'en', 'page' => 0], ['locale' => 'en', 'page' => 101],
    ['locale' => 'en', 'page' => null], ['locale' => 'en', 'per_page' => 21], ['locale' => 'en', 'page' => '1'],
    ['locale' => 'en', 'post_status' => 'private'], ['locale' => 'en', 'search' => str_repeat('a', 101)]] as $input) {
    $check('invalid_list_schema_rejected_' . count($cases), is_wp_error($list->execute($input)));
    $check('invalid_direct_list_callback_rejected_' . count($cases), is_wp_error(\AlexCuadra\EditorialRead\list_projects($input)));
}
$check('unicode_search_within_character_bound', !is_wp_error($list->execute(['locale' => 'en', 'search' => str_repeat('á', 80)])));
$check('arbitrary_fields_rejected', is_wp_error($get->execute(['locale' => 'en', 'id' => $en, 'fields' => ['private_contact']])));
$check('invalid_get_id_rejected', is_wp_error($get->execute(['locale' => 'en', 'id' => -1])));

// Force a contradictory query result to verify fail-closed behavior, not silent partial output.
$filter = static function ($posts, $query) use ($draft) { return $query->get('post_type') === 'project' ? [get_post($draft)] : $posts; };
add_filter('posts_results', $filter, 10, 2);
$contradiction = $list->execute($listInput);
remove_filter('posts_results', $filter, 10);
$check('contradictory_inventory_fails_closed', is_wp_error($contradiction) && $contradiction->get_error_code() === 'alexcuadra_read_visibility');

$terms = wp_get_object_terms($es, 'post_translations', ['fields' => 'ids']);
wp_remove_object_terms($es, $terms, 'post_translations');
$orphan = $get->execute(['locale' => 'en', 'id' => $en]);
$check('nonreciprocal_translation_is_omitted', !is_wp_error($orphan) && $orphan['translations'] === []);
pll_save_post_translations(['en' => $en, 'es' => $es]);
unregister_post_type('project');
$unavailable = $get->execute(['locale' => 'en', 'id' => $en]);
$check('missing_post_type_fails_closed', is_wp_error($unavailable) && $unavailable->get_error_code() === 'alexcuadra_read_dependencies');
register_post_type('project', ['public' => true, 'show_in_rest' => true, 'capability_type' => 'page',
    'map_meta_cap' => true, 'supports' => ['title', 'editor']]);
$rest = rest_do_request(new WP_REST_Request('GET', '/wp-abilities/v1/abilities'));
$check('rest_discovery_excludes_mcp_only_abilities', $rest->get_status() === 200
    && !str_contains(wp_json_encode($rest->get_data()), 'alexcuadra/list-public-projects')
    && !str_contains(wp_json_encode($rest->get_data()), 'alexcuadra/get-public-project'));

global $wpdb;
$snapshot = static function () use ($wpdb, $ids) {
    $idList = implode(',', array_map('intval', $ids));
    return hash('sha256', serialize([$wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID IN ($idList) ORDER BY ID", ARRAY_A),
        $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id IN ($idList) ORDER BY meta_id", ARRAY_A),
        $wpdb->get_results("SELECT * FROM $wpdb->term_relationships WHERE object_id IN ($idList) ORDER BY object_id, term_taxonomy_id", ARRAY_A)]));
};
$before = $snapshot();
$discover = wp_get_ability('mcp-adapter/discover-abilities')->execute();
$check('official_adapter_discovers_read_abilities', !is_wp_error($discover) && count(array_intersect(
    ['alexcuadra/list-public-projects', 'alexcuadra/get-public-project'], array_column($discover['abilities'], 'name'))) === 2);
$info = wp_get_ability('mcp-adapter/get-ability-info')->execute(['ability_name' => 'alexcuadra/get-public-project']);
$check('official_adapter_exposes_restricted_schema', !is_wp_error($info) && $info['input_schema']['additionalProperties'] === false);
$executed = wp_get_ability('mcp-adapter/execute-ability')->execute(['ability_name' => 'alexcuadra/get-public-project',
    'parameters' => ['locale' => 'en', 'id' => $en]]);
$check('official_adapter_executes_authorized_read', !is_wp_error($executed) && $executed['success'] === true && $executed['data']['id'] === $en);
$check('reads_do_not_mutate_editorial_records', $snapshot() === $before);
$reader->remove_cap($cap); wp_set_current_user(0); wp_set_current_user($readerId);
$denied = wp_get_ability('mcp-adapter/execute-ability')->execute(['ability_name' => 'alexcuadra/get-public-project',
    'parameters' => ['locale' => 'en', 'id' => $en]]);
$check('revocation_denies_adapter_execution', is_wp_error($denied) || $denied['success'] === false);
$check('subscriber_role_remains_unchanged', !get_role('subscriber')->has_cap($cap));

$result = ['environment' => 'isolated-docker', 'wordpress' => get_bloginfo('version'),
    'polylang' => POLYLANG_VERSION, 'passed' => count($cases), 'failed' => 0, 'cases' => $cases,
    'officialAdapterRegistryVerified' => true, 'httpTransportVerified' => false,
    'sharedCmsModified' => false, 'sharedPermissionsModified' => false, 'emailSent' => false];
WP_CLI::line('EDITORIAL_READ_RESULT=' . wp_json_encode($result));
