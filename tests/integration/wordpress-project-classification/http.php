<?php
// Real Apache HTTP requests inside the dedicated Docker network, never to the host/CMS.
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') {
    throw new RuntimeException('ISOLATED_HTTP_LAB_REQUIRED');
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$endpoint = 'http://wordpress/?rest_route=/mcp/mcp-adapter-default-server';
$cap = \AlexCuadra\ProjectClassificationLab\CAPABILITY;
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
$marker='classification_http_'.bin2hex(random_bytes(4));
$report=['httpTransportVerified'=>false,'realCmsChanged'=>false,'realPermissionsChanged'=>false,'emailSent'=>false];
try{
    $fixture=get_option('project_classification_test_fixture');if(!is_array($fixture)){throw new RuntimeException('HTTP_FIXTURE_MISSING');}
    foreach(['reader','other']as$name){$id=wp_create_user($marker.$name,wp_generate_password(32),$marker.$name.'@example.test');if(is_wp_error($id)){throw new RuntimeException('USER_SETUP_FAILED');}$u=new WP_User($id);$u->set_role('subscriber');$users[$name]=$u;}
    $snapshot=static function(){global $wpdb;$all=[];foreach(['posts'=>'ID','postmeta'=>'meta_id','terms'=>'term_id','term_taxonomy'=>'term_taxonomy_id','term_relationships'=>'object_id,term_taxonomy_id','termmeta'=>'meta_id']as$t=>$order){$all[$t]=$wpdb->get_results("SELECT * FROM {$wpdb->$t} ORDER BY $order",ARRAY_A);}return hash('sha256',serialize($all));};$before=$snapshot();
    $auth=$newCredential($users['reader']->ID);$otherAuth=$newCredential($users['other']->ID);
    $anon=$initialize();$check('anonymous_http_denied',in_array($anon['status'],[401,403],true));
    $start=$initialize($auth);$session=$start['session'];$check('real_application_password_session',$start['status']===200&&is_string($session));
    $request(['jsonrpc'=>'2.0','method'=>'notifications/initialized'],$auth,$session);
    $query=['id'=>$fixture['en'],'locale'=>'en'];
    $check('transport_without_scope_denied',$denied($execute('alexcuadra/get-public-project-classification',$query,$auth,$session)));
    $users['reader']->add_cap($cap);
    $discover=$data($tool('mcp-adapter-discover-abilities',new stdClass(),$auth,$session));$check('http_catalog_has_two_tools',count(array_intersect(['alexcuadra/get-public-project-classification','alexcuadra/list-public-project-terms'],array_column($discover['abilities']??[],'name')))===2);
    $info=$data($tool('mcp-adapter-get-ability-info',['ability_name'=>'alexcuadra/get-public-project-classification'],$auth,$session));
    $check('http_schema_and_annotations',($info['input_schema']['additionalProperties']??null)===false&&($info['meta']['annotations']['readonly']??null)===true&&($info['meta']['annotations']['destructive']??null)===false);
    $classified=$data($execute('alexcuadra/get-public-project-classification',$query,$auth,$session));
    $check('http_assigned_project_terms',($classified['success']??false)&&array_keys($classified['data'])===['project_id','locale','categories','technologies']&&$classified['data']['categories'][0]['id']===$fixture['category']);
    $check('http_reciprocal_translation',$classified['data']['categories'][0]['translations'][0]['id']===$fixture['categoryEs']);
    $check('http_private_fields_excluded',!str_contains(wp_json_encode($classified),'PRIVATE'));
    $spanish=$data($execute('alexcuadra/get-public-project-classification',['id'=>$fixture['es'],'locale'=>'es'],$auth,$session));$check('http_spanish_terms',($spanish['success']??false)&&$spanish['data']['categories'][0]['id']===$fixture['categoryEs']);
    $page=$data($execute('alexcuadra/list-public-project-terms',['locale'=>'en','taxonomy'=>'project_category','limit'=>1],$auth,$session));
    $check('http_catalog_cursor',($page['success']??false)&&count($page['data']['items'])===1&&$page['data']['next_after']===$fixture['category']);
    $last=$data($execute('alexcuadra/list-public-project-terms',['locale'=>'en','taxonomy'=>'project_category','after'=>$fixture['category'],'limit'=>1],$auth,$session));
    $check('http_catalog_terminal_page',($last['success']??false)&&count($last['data']['items'])===1&&$last['data']['next_after']===null);
    $check('http_blog_taxonomy_rejected',$denied($execute('alexcuadra/list-public-project-terms',['locale'=>'en','taxonomy'=>'category'],$auth,$session)));
    $check('http_unknown_fields_rejected',$denied($execute('alexcuadra/get-public-project-classification',$query+['fields'=>['post_content']],$auth,$session)));
    $check('http_private_project_rejected',$denied($execute('alexcuadra/get-public-project-classification',['id'=>$fixture['private'],'locale'=>'es'],$auth,$session)));
    $check('http_wrong_language_rejected',$denied($execute('alexcuadra/get-public-project-classification',['id'=>$fixture['en'],'locale'=>'es'],$auth,$session)));
    $check('http_other_user_cannot_reuse_session',$denied($rpc('tools/list',new stdClass(),$otherAuth,$session)));
    $users['other']->add_cap('alexcuadra_read_public_posts');$other=$initialize($otherAuth);$otherSession=$other['session'];$request(['jsonrpc'=>'2.0','method'=>'notifications/initialized'],$otherAuth,$otherSession);
    $check('http_blog_scope_cannot_read_project_terms',$denied($execute('alexcuadra/get-public-project-classification',$query,$otherAuth,$otherSession)));
    $users['reader']->remove_cap($cap);$check('http_revocation_blocks_existing_session',$denied($execute('alexcuadra/get-public-project-classification',$query,$auth,$session)));
    $closed=$request(null,$auth,$session,'DELETE');$check('http_session_closed',in_array($closed['status'],[200,204],true));
    $check('http_closed_session_rejected',$denied($rpc('tools/list',new stdClass(),$auth,$session)));
    WP_Application_Passwords::delete_application_password($users['reader']->ID,$credentials[0]['uuid']);$revoked=$initialize($auth);$check('http_revoked_credential_rejected',in_array($revoked['status'],[401,403],true));
    $check('http_reads_preserve_editorial_tables',$snapshot()===$before);$report['httpTransportVerified']=true;
}catch(Throwable $error){$report['failure']=preg_match('/^(FAILED: [a-z0-9_]+|[A-Z_]+)$/',$error->getMessage())?$error->getMessage():'HTTP_TEST_FAILED';$report['diagnostic']=$lastDiagnostic;
}finally{
    foreach($users as$user){$user->remove_cap($cap);$user->remove_cap('alexcuadra_read_public_posts');}
    foreach($users as$user){try{$cleanup=$newCredential($user->ID);foreach($sessions as$s){if($s['auth']!==null&&str_starts_with(base64_decode(substr($s['auth'],6)),$user->user_login.':')){$request(null,$cleanup,$s['id'],'DELETE');}}}catch(Throwable $ignored){$report['sessionCleanupError']=true;}}
    foreach($credentials as$c){WP_Application_Passwords::delete_application_password($c['user'],$c['uuid']);}
    $clean=true;$scopes=true;foreach($users as$u){$clean=$clean&&WP_Application_Passwords::get_user_application_passwords($u->ID)===[];$scopes=$scopes&&!(new WP_User($u->ID))->has_cap($cap);}
    $report['syntheticCredentialsRevoked']=$clean;$report['syntheticScopeRemoved']=$scopes;
}
$report['passed']=count($cases);$report['failed']=isset($report['failure'])?1:0;$report['cases']=$cases;
WP_CLI::line('PROJECT_CLASSIFICATION_HTTP_RESULT='.wp_json_encode($report));if($report['failed']){WP_CLI::halt(1);}
