<?php
// Real Apache HTTP requests inside the dedicated Docker network, never to the host/CMS.
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') {
    throw new RuntimeException('ISOLATED_HTTP_LAB_REQUIRED');
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$endpoint = 'http://wordpress/?rest_route=/mcp/mcp-adapter-default-server';
$plugin = 'alexcuadra-editorial-blog-read/alexcuadra-editorial-blog-read.php';
$cap = \AlexCuadra\EditorialBlogRead\CAPABILITY;
$cases = []; $sessions = []; $users = []; $credentials = []; $sequence = 0; $lastDiagnostic = [];
$check = static function ($name, $ok) use (&$cases) {
    if (!$ok) { throw new RuntimeException('FAILED: ' . $name); }
    $cases[] = $name;
};
$request = static function ($payload, $auth = null, $session = null, $method = 'POST', $url = null, $protocol = '2025-11-25') use ($endpoint, &$lastDiagnostic) {
    $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json, text/event-stream'];
    if ($auth !== null) { $headers['Authorization'] = $auth; }
    if ($session !== null) {
        $headers['Mcp-Session-Id'] = $session;
        if ($protocol !== null) { $headers['MCP-Protocol-Version'] = $protocol; }
    }
    $result = wp_remote_request($url ?? $endpoint, ['method' => $method, 'headers' => $headers,
        'body' => $payload === null ? null : wp_json_encode($payload), 'timeout' => 15, 'redirection' => 0]);
    if (is_wp_error($result)) { throw new RuntimeException('HTTP_LAB_NETWORK_FAILED'); }
    $json = json_decode(wp_remote_retrieve_body($result), true);
    $lastDiagnostic = ['httpStatus' => wp_remote_retrieve_response_code($result), 'rpcErrorCode' => $json['error']['code'] ?? null,
        'restErrorCode' => isset($json['code']) && preg_match('/^[a-z0-9_-]+$/', $json['code']) ? $json['code'] : null];
    return ['status' => wp_remote_retrieve_response_code($result),
        'json' => $json,
        'session' => wp_remote_retrieve_header($result, 'mcp-session-id')];
};
$rpc = static function ($method, $params, $auth = null, $session = null) use (&$sequence, $request) {
    return $request(['jsonrpc' => '2.0', 'id' => ++$sequence, 'method' => $method, 'params' => $params], $auth, $session);
};
$initialize = static function ($auth = null) use ($rpc, &$sessions) {
    $response = $rpc('initialize', ['protocolVersion' => '2025-11-25', 'capabilities' => new stdClass(),
        'clientInfo' => ['name' => 'isolated-editorial-http-tests', 'version' => '0.1.0']], $auth);
    if ($response['session']) { $sessions[] = ['id' => $response['session'], 'auth' => $auth]; }
    return $response;
};
$data = static function ($response) {
    $result = $response['json']['result'] ?? [];
    if (isset($result['structuredContent'])) { return $result['structuredContent']; }
    foreach ($result['content'] ?? [] as $item) {
        if (($item['type'] ?? null) === 'text') {
            $decoded = json_decode($item['text'], true);
            if (is_array($decoded)) { return $decoded; }
        }
    }
    return null;
};
$denied = static function ($response) use ($data) {
    return in_array($response['status'], [400, 401, 403, 404], true) || isset($response['json']['error'])
        || ($response['json']['result']['isError'] ?? false) === true || ($data($response)['success'] ?? null) === false;
};
$tool = static function ($name, $arguments, $auth, $session) use ($rpc) {
    return $rpc('tools/call', ['name' => $name, 'arguments' => $arguments], $auth, $session);
};
$execute = static function ($ability, $parameters, $auth, $session) use ($tool) {
    return $tool('mcp-adapter-execute-ability', ['ability_name' => $ability, 'parameters' => $parameters], $auth, $session);
};
$newCredential = static function ($userId) use (&$credentials) {
    $created = WP_Application_Passwords::create_new_application_password($userId, ['name' => 'isolated-http-test']);
    if (is_wp_error($created)) { throw new RuntimeException('APPLICATION_PASSWORD_SETUP_FAILED'); }
    $credentials[] = ['user' => $userId, 'uuid' => $created[1]['uuid']];
    return 'Basic ' . base64_encode(get_userdata($userId)->user_login . ':' . $created[0]);
};
$marker = 'httpread' . bin2hex(random_bytes(5));
$report = ['environment' => 'isolated-docker-http', 'httpTransportVerified' => false,
    'sharedCmsModified' => false, 'sharedPermissionsModified' => false, 'hostPortsPublished' => false, 'emailSent' => false];
try {
    // Native posts/taxonomies remain managed by Polylang defaults.
    foreach ([['en', 'en_US', 'English'], ['es', 'es_ES', 'Español']] as $lang) {
        if (!in_array($lang[0], pll_languages_list(), true)) {
            $added = PLL()->model->add_language(['name' => $lang[2], 'slug' => $lang[0], 'locale' => $lang[1],
                'rtl' => false, 'term_group' => $lang[0] === 'en' ? 0 : 1]);
            if (is_wp_error($added)) { throw new RuntimeException('LANGUAGE_SETUP_FAILED'); }
        }
    }
    PLL()->options->set('default_lang', 'en'); PLL()->options->save_all();
    $makePost = static function ($label, $locale, $status = 'publish', $password = '') use ($marker) {
        $id = wp_insert_post(['post_type' => 'post', 'post_status' => $status, 'post_title' => "$marker $label",
            'post_content' => 'PRIVATE_HTTP_BODY', 'post_password' => $password], true);
        if (is_wp_error($id)) { throw new RuntimeException('POST_SETUP_FAILED'); }
        pll_set_post_language($id, $locale); wp_set_object_terms($id, [], 'category'); wp_set_object_terms($id, [], 'post_tag'); update_post_meta($id, 'private_contact', 'PRIVATE_HTTP_RECIPIENT');
        return $id;
    };
    $en = $makePost('Public A', 'en'); $es = $makePost('Público A', 'es');
    $second = $makePost('Public B', 'en'); $private = $makePost('Private', 'en', 'private');
    $protected = $makePost('Protected', 'en', 'publish', 'synthetic-http-password');
    pll_save_post_translations(['en' => $en, 'es' => $es]);
    $makeTerm = static function ($taxonomy, $label, $locale) use ($marker) {
        $result = wp_insert_term("$marker $label", $taxonomy);
        if (is_wp_error($result)) { throw new RuntimeException('TERM_SETUP_FAILED'); }
        pll_set_term_language($result['term_id'], $locale); return $result['term_id'];
    };
    $category = $makeTerm('category', 'Category', 'en'); $categoryEs = $makeTerm('category', 'Categoría', 'es');
    $tag = $makeTerm('post_tag', 'Tag', 'en'); $tagEs = $makeTerm('post_tag', 'Etiqueta', 'es');
    pll_save_term_translations(['en'=>$category,'es'=>$categoryEs]); pll_save_term_translations(['en'=>$tag,'es'=>$tagEs]);
    wp_set_object_terms($en, [$category], 'category'); wp_set_object_terms($es, [$categoryEs], 'category');
    wp_set_object_terms($en, [$tag], 'post_tag'); wp_set_object_terms($es, [$tagEs], 'post_tag');
    $snapshot = static function () {
        global $wpdb;
        return hash('sha256', serialize([
            $wpdb->get_results("SELECT * FROM $wpdb->posts ORDER BY ID", ARRAY_A),
            $wpdb->get_results("SELECT * FROM $wpdb->postmeta ORDER BY meta_id", ARRAY_A),
            $wpdb->get_results("SELECT * FROM $wpdb->terms ORDER BY term_id", ARRAY_A),
            $wpdb->get_results("SELECT * FROM $wpdb->term_taxonomy ORDER BY term_taxonomy_id", ARRAY_A),
            $wpdb->get_results("SELECT * FROM $wpdb->termmeta ORDER BY meta_id", ARRAY_A),
            $wpdb->get_results("SELECT * FROM $wpdb->term_relationships ORDER BY object_id, term_taxonomy_id", ARRAY_A),
        ]));
    };
    $before = $snapshot();
    foreach (['reader', 'other'] as $name) {
        $id = wp_create_user("$marker$name", wp_generate_password(32), "$marker$name@example.test");
        if (is_wp_error($id)) { throw new RuntimeException('USER_SETUP_FAILED'); }
        $user = new WP_User($id); $user->set_role('subscriber'); $users[$name] = $user;
    }
    $auth = $newCredential($users['reader']->ID); $otherAuth = $newCredential($users['other']->ID);
    $unauth = $initialize();
    $check('anonymous_http_initialization_denied', in_array($unauth['status'], [401, 403], true));
    $invalid = $initialize('Basic ' . base64_encode($users['reader']->user_login . ':incorrect-synthetic-password'));
    $check('invalid_application_password_denied', in_array($invalid['status'], [401, 403], true));
    $start = $initialize($auth); $session = $start['session'];
    $check('subscriber_application_password_initializes_http_mcp', $start['status'] === 200 && isset($start['json']['result']['protocolVersion'])
        && is_string($session) && preg_match('/^[a-f0-9-]{36}$/i', $session));
    $notification = $request(['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], $auth, $session);
    $check('initialized_notification_accepted', in_array($notification['status'], [200, 202], true) && !isset($notification['json']['error']));
    foreach (['missing' => null, 'mismatched' => '2024-11-05'] as $label => $protocol) {
        $rejected = $request(['jsonrpc' => '2.0', 'id' => ++$sequence, 'method' => 'tools/list', 'params' => new stdClass()],
            $auth, $session, 'POST', null, $protocol);
        $check($label . '_protocol_header_denied', $rejected['status'] === 400 && isset($rejected['json']['error']));
    }
    $tools = $rpc('tools/list', new stdClass(), $auth, $session);
    $names = array_column($tools['json']['result']['tools'] ?? [], 'name'); sort($names);
    $check('default_server_lists_three_official_meta_tools', $names === ['mcp-adapter-discover-abilities', 'mcp-adapter-execute-ability', 'mcp-adapter-get-ability-info']);
    $missing = $rpc('tools/list', new stdClass(), $auth);
    $check('missing_session_header_denied', $missing['status'] === 400 && isset($missing['json']['error']));
    $bad = $rpc('tools/list', new stdClass(), $auth, '00000000-0000-4000-8000-000000000000');
    $check('unknown_session_denied', $denied($bad));
    $cross = $rpc('tools/list', new stdClass(), $otherAuth, $session);
    $check('session_cannot_be_reused_by_another_user', $denied($cross));
    $forbidden = $execute('alexcuadra/get-public-post', ['locale' => 'en', 'id' => $en], $auth, $session);
    $check('transport_access_does_not_grant_editorial_permission', $denied($forbidden));
    $users['reader']->add_cap($cap);
    $check('editorial_reader_remains_subscriber_without_edit_or_admin', $users['reader']->roles === ['subscriber']
        && !$users['reader']->has_cap('edit_posts') && !$users['reader']->has_cap('manage_options'));
    $discover = $data($tool('mcp-adapter-discover-abilities', new stdClass(), $auth, $session));
    $check('http_discovery_includes_two_editorial_abilities', count(array_intersect(
        ['alexcuadra/list-public-posts', 'alexcuadra/get-public-post'], array_column($discover['abilities'] ?? [], 'name'))) === 2);
    $info = $data($tool('mcp-adapter-get-ability-info', ['ability_name' => 'alexcuadra/get-public-post'], $auth, $session));
    $check('http_ability_schema_restricts_inputs', ($info['input_schema']['additionalProperties'] ?? null) === false);
    $first = $data($execute('alexcuadra/list-public-posts', ['locale' => 'en', 'search' => $marker, 'per_page' => 1], $auth, $session));
    $next = $data($execute('alexcuadra/list-public-posts', ['locale' => 'en', 'search' => $marker, 'per_page' => 1, 'page' => 2], $auth, $session));
    $check('http_pagination_returns_only_public_posts', ($first['success'] ?? false) && ($next['success'] ?? false)
        && array_column($first['data']['items'], 'id') === [$en] && $first['data']['next_page'] === 2
        && array_column($next['data']['items'], 'id') === [$second] && $next['data']['next_page'] === null);
    $postRead = $data($execute('alexcuadra/get-public-post', ['locale' => 'en', 'id' => $en], $auth, $session));
    $check('http_read_returns_exact_public_fields', ($postRead['success'] ?? false) && array_keys($postRead['data']) === ['id', 'title', 'slug', 'locale', 'translations', 'categories', 'tags']);
    $check('http_read_has_real_reciprocal_translation', ($postRead['data']['translations'][0]['id'] ?? null) === $es);
    $check('http_category_and_tag_translations', ($postRead['data']['categories'][0]['translations'][0]['id'] ?? null) === $categoryEs
        && ($postRead['data']['tags'][0]['translations'][0]['id'] ?? null) === $tagEs);
    $check('http_read_excludes_private_body_and_meta', !str_contains(wp_json_encode($postRead), 'PRIVATE_HTTP'));
    $spanish = $data($execute('alexcuadra/get-public-post', ['locale' => 'es', 'id' => $es], $auth, $session));
    $check('http_spanish_read_preserves_exact_language', ($spanish['success'] ?? false) && $spanish['data']['locale'] === 'es');
    foreach (['private' => $private, 'protected' => $protected, 'missing' => 2147483647] as $label => $id) {
        $check('http_' . $label . '_post_denied', $denied($execute('alexcuadra/get-public-post', ['locale' => 'en', 'id' => $id], $auth, $session)));
    }
    $check('http_wrong_language_denied', $denied($execute('alexcuadra/get-public-post', ['locale' => 'es', 'id' => $en], $auth, $session)));
    $check('http_unknown_fields_denied', $denied($execute('alexcuadra/get-public-post', ['locale' => 'en', 'id' => $en, 'fields' => ['private_contact']], $auth, $session)));
    $check('http_unbounded_pagination_denied', $denied($execute('alexcuadra/list-public-posts', ['locale' => 'en', 'per_page' => 9999], $auth, $session)));
    $users['reader']->remove_cap($cap);
    $check('capability_revocation_blocks_existing_http_session', $denied($execute('alexcuadra/get-public-post', ['locale' => 'en', 'id' => $en], $auth, $session)));
    $users['reader']->add_cap($cap);
    deactivate_plugins($plugin, true);
    $removed = $data($tool('mcp-adapter-discover-abilities', new stdClass(), $auth, $session));
    $check('plugin_deactivation_removes_http_abilities', count(array_intersect(
        ['alexcuadra/list-public-posts', 'alexcuadra/get-public-post'], array_column($removed['abilities'] ?? [], 'name'))) === 0);
    $check('deactivated_ability_cannot_execute', $denied($execute('alexcuadra/get-public-post', ['locale' => 'en', 'id' => $en], $auth, $session)));
    if (is_wp_error(activate_plugin($plugin, '', false, true))) { throw new RuntimeException('PLUGIN_RESTORE_FAILED'); }
    $restored = $data($execute('alexcuadra/get-public-post', ['locale' => 'en', 'id' => $en], $auth, $session));
    $check('plugin_reactivation_restores_authorized_read', ($restored['success'] ?? false) && ($restored['data']['id'] ?? null) === $en);
    $closed = $request(null, $auth, $session, 'DELETE');
    $check('http_delete_closes_session', in_array($closed['status'], [200, 204], true) && !isset($closed['json']['error']));
    $check('closed_session_cannot_be_reused', $denied($rpc('tools/list', new stdClass(), $auth, $session)));
    $last = $initialize($auth); $lastSession = $last['session'];
    $check('new_session_after_close_initializes', $last['status'] === 200 && is_string($lastSession) && $lastSession !== $session);
    WP_Application_Passwords::delete_application_password($users['reader']->ID, $credentials[0]['uuid']);
    $revoked = $rpc('tools/list', new stdClass(), $auth, $lastSession);
    $check('revoked_application_password_blocks_existing_session', in_array($revoked['status'], [401, 403], true));
    $check('http_reads_do_not_mutate_editorial_records', $snapshot() === $before);
    $report['httpTransportVerified'] = true;
} catch (Throwable $error) {
    // Only locally-authored check names are safe to save; never raw HTTP/credentials/errors.
    $report['failure'] = preg_match('/^(FAILED: [a-z0-9_]+|[A-Z_]+)$/', $error->getMessage()) ? $error->getMessage() : 'HTTP_LAB_FAILED';
    $report['diagnostic'] = $lastDiagnostic;
} finally {
    if (!is_plugin_active($plugin)) { activate_plugin($plugin, '', false, true); }
    foreach ($users as $user) { $user->remove_cap($cap); }
    // A fresh password of the same synthetic owner permits cleanup after the tested revocation.
    foreach ($users as $user) {
        try {
            $cleanupAuth = $newCredential($user->ID);
            foreach ($sessions as $stored) {
                if ($stored['auth'] !== null && str_starts_with($stored['auth'], 'Basic ')
                    && str_starts_with(base64_decode(substr($stored['auth'], 6)), $user->user_login . ':')) {
                    $request(null, $cleanupAuth, $stored['id'], 'DELETE');
                }
            }
        } catch (Throwable $ignored) {
            $report['sessionCleanupError'] = true;
        }
    }
    foreach ($credentials as $credential) { WP_Application_Passwords::delete_application_password($credential['user'], $credential['uuid']); }
    $report['syntheticCredentialsRevoked'] = true;
    $report['syntheticCapabilitiesRemoved'] = true;
    foreach ($users as $user) {
        $report['syntheticCredentialsRevoked'] = $report['syntheticCredentialsRevoked']
            && WP_Application_Passwords::get_user_application_passwords($user->ID) === [];
        $fresh = new WP_User($user->ID);
        $report['syntheticCapabilitiesRemoved'] = $report['syntheticCapabilitiesRemoved'] && !$fresh->has_cap($cap);
    }
    if (!$report['syntheticCredentialsRevoked'] || !$report['syntheticCapabilitiesRemoved']
        || isset($report['sessionCleanupError']) || !is_plugin_active($plugin)) {
        $report['failure'] = $report['failure'] ?? 'SYNTHETIC_CLEANUP_FAILED';
        $report['httpTransportVerified'] = false;
    } else {
        $cases[] = 'synthetic_credentials_removed_after_test';
        $cases[] = 'synthetic_capabilities_removed_after_test';
    }
}
$report['passed'] = count($cases); $report['failed'] = isset($report['failure']) ? 1 : 0; $report['cases'] = $cases;
WP_CLI::line('EDITORIAL_BLOG_HTTP_RESULT=' . wp_json_encode($report));
if ($report['failed']) { WP_CLI::halt(1); }
