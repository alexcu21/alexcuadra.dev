<?php
/** Reuses the existing isolated HTTP client pattern; never emits credentials. */
$endpoint = 'http://wordpress/?rest_route=/mcp/mcp-adapter-default-server';
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
