<?php
/** Pure local planner; trusted reader projections only. No WordPress hooks or persistence. */
namespace AlexCuadra\ProjectClassificationProposalLab;

const TAXONOMIES = ['categories' => 'project_category', 'technologies' => 'project_technology'];
const SCOPES = ['read', 'alexcuadra_read_public_projects', 'alexcuadra_prepare_project_changes'];

function reject(string $code) { throw new \DomainException('project_classification_proposal_' . $code); }
function exact(array $value, array $keys): void {
    $actual = array_keys($value); sort($actual); sort($keys);
    if ($actual !== $keys) { reject('schema'); }
}
function positive($value): bool { return is_int($value) && $value > 0; }
function language($value): void { if (!in_array($value, ['en', 'es'], true)) { reject('locale'); } }
function canonical($value) {
    if (!is_array($value)) { return $value; }
    if (!array_is_list($value)) { ksort($value, SORT_STRING); }
    foreach ($value as $key => $item) { $value[$key] = canonical($item); }
    return $value;
}
function digest(array $value): string {
    return hash('sha256', json_encode(canonical($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
function text($value): void {
    if (!is_string($value) || $value === '' || strlen($value) > 2048 || !preg_match('//u', $value)
        || preg_match('/[\x00-\x1F\x7F\x{0080}-\x{009F}]/u', $value)) { reject('term'); }
}
function ids($value): array {
    if (!is_array($value) || !array_is_list($value) || count($value) > 100) { reject('terms'); }
    foreach ($value as $id) { if (!positive($id)) { reject('terms'); } }
    if (count(array_unique($value, SORT_REGULAR)) !== count($value)) { reject('terms'); }
    sort($value, SORT_NUMERIC); return $value;
}
function term(array $row, string $taxonomy, string $locale): array {
    exact($row, ['id', 'name', 'slug', 'taxonomy', 'locale', 'translations']);
    if (!positive($row['id']) || $row['taxonomy'] !== $taxonomy || $row['locale'] !== $locale) { reject('classification'); }
    text($row['name']); text($row['slug']);
    if (!is_array($row['translations']) || !array_is_list($row['translations']) || count($row['translations']) > 1) { reject('relationships'); }
    foreach ($row['translations'] as $target) {
        if (!is_array($target)) { reject('relationships'); }
        exact($target, ['id', 'name', 'slug', 'locale']); language($target['locale']);
        if (!positive($target['id']) || $target['id'] === $row['id'] || $target['locale'] === $locale) { reject('relationships'); }
        text($target['name']); text($target['slug']);
    }
    return canonical($row);
}
function terms($rows, string $taxonomy, string $locale, int $limit): array {
    if (!is_array($rows) || !array_is_list($rows) || count($rows) > $limit) { reject('catalog'); }
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) { reject('catalog'); }
        $item = term($row, $taxonomy, $locale);
        if (isset($out[$item['id']])) { reject('catalog'); }
        $out[$item['id']] = $item;
    }
    ksort($out, SORT_NUMERIC); return array_values($out);
}
/** Catalog contains both complete target-language lists, not one page of either list. */
function context(array $source, array $catalog): array {
    exact($source, ['project_id', 'locale', 'categories', 'technologies']);
    if (!positive($source['project_id'])) { reject('source'); }
    language($source['locale']); exact($catalog, array_keys(TAXONOMIES));
    $allIds = [];
    foreach (TAXONOMIES as $field => $taxonomy) {
        $catalog[$field] = terms($catalog[$field], $taxonomy, $source['locale'], 200);
        $source[$field] = terms($source[$field], $taxonomy, $source['locale'], 100);
        $byId = array_column($catalog[$field], null, 'id');
        foreach ($byId as $id => $item) {
            if (isset($allIds[$id])) { reject('catalog'); }
            $allIds[$id] = true;
        }
        foreach ($source[$field] as $item) {
            if (($byId[$item['id']] ?? null) !== $item) { reject('classification'); }
        }
    }
    return ['source' => $source, 'catalog' => $catalog];
}
function base_hash(array $source, array $catalog): string { return digest(context($source, $catalog)); }
function authorize(array $actor): void {
    exact($actor, ['id', 'authenticated', 'capabilities']);
    if (!positive($actor['id']) || $actor['authenticated'] !== true || !is_array($actor['capabilities'])) { reject('forbidden'); }
    foreach (SCOPES as $scope) { if (($actor['capabilities'][$scope] ?? null) !== true) { reject('forbidden'); } }
}
/** Normalization also supplies a future durable repository with a stable replay payload. */
function normalize_request(array $request): array {
    exact($request, ['request_id', 'project_id', 'locale', 'base_hash', 'fields']);
    if (!is_string($request['request_id']) || !preg_match('/^[a-f0-9]{32}$/D', $request['request_id'])
        || !is_string($request['base_hash']) || !preg_match('/^[a-f0-9]{64}$/D', $request['base_hash'])
        || !positive($request['project_id'])) { reject('input'); }
    language($request['locale']);
    if (!is_array($request['fields']) || !$request['fields'] || array_diff(array_keys($request['fields']), array_keys(TAXONOMIES))) { reject('fields'); }
    foreach ($request['fields'] as $field => $value) { $request['fields'][$field] = ids($value); }
    return canonical($request);
}
function prepare(array $actor, array $source, array $catalog, array $request): array {
    authorize($actor); $request = normalize_request($request); $ctx = context($source, $catalog);
    $source = $ctx['source']; $catalog = $ctx['catalog'];
    if ($request['project_id'] !== $source['project_id'] || $request['locale'] !== $source['locale']) { reject('source'); }
    if (!hash_equals(digest($ctx), $request['base_hash'])) { reject('stale_base'); }
    $changes = [];
    foreach (TAXONOMIES as $field => $taxonomy) {
        if (!array_key_exists($field, $request['fields'])) { continue; }
        $byId = array_column($catalog[$field], null, 'id'); $after = [];
        foreach ($request['fields'][$field] as $id) {
            if (!isset($byId[$id])) { reject('classification'); }
            $after[] = $byId[$id];
        }
        if ($after !== $source[$field]) { $changes[$field] = ['taxonomy' => $taxonomy, 'locale' => $source['locale'], 'before' => $source[$field], 'after' => $after]; }
    }
    if (!$changes) { reject('no_changes'); }
    $plan = ['format' => 'project-classification-proposal-lab-v1', 'owner_id' => $actor['id'], 'version' => 1,
        'project_id' => $source['project_id'], 'locale' => $source['locale'], 'base_hash' => $request['base_hash'],
        'request_id' => $request['request_id'], 'request_hash' => digest(['owner_id' => $actor['id'], 'request' => $request]),
        'changes' => $changes, 'approval' => 'not-requested', 'source_write_performed' => false];
    $plan['document_hash'] = digest($plan); return $plan;
}
