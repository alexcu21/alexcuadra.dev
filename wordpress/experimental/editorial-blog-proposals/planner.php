<?php
/** Pure proposal planner. No WordPress bootstrap, storage, rendering or source writes. */
namespace AlexCuadra\EditorialBlogProposalLab;

const PREPARE_SCOPE = 'alexcuadra_prepare_post_changes';
const FIELDS = ['title', 'content', 'excerpt', 'categories', 'tags'];

function reject($code) { throw new \DomainException('blog_proposal_' . $code); }
function exact(array $value, array $keys) {
    $actual = array_keys($value); sort($actual); sort($keys);
    if ($actual !== $keys) { reject('schema'); }
}
function positive($value) { return is_int($value) && $value > 0; }
function locale($value) { if (!in_array($value, ['en', 'es'], true)) { reject('locale'); } }
function text($value, $limit) {
    if (!is_string($value) || strlen($value) > $limit || !preg_match('//u', $value)
        || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{0080}-\x{009F}]/u', $value)) { reject('text'); }
}
function canonical($value) {
    if (!is_array($value)) { return $value; }
    if (!array_is_list($value)) { ksort($value, SORT_STRING); }
    foreach ($value as $key => $item) { $value[$key] = canonical($item); }
    return $value;
}
function digest(array $value) {
    return hash('sha256', json_encode(canonical($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
function ids($value) {
    if (!is_array($value) || !array_is_list($value) || count($value) > 100) { reject('terms'); }
    foreach ($value as $id) { if (!positive($id)) { reject('terms'); } }
    if (count(array_unique($value, SORT_REGULAR)) !== count($value)) { reject('terms'); }
    sort($value, SORT_NUMERIC); return $value;
}
function catalog(array $rows) {
    if (!array_is_list($rows) || count($rows) > 1000) { reject('catalog'); }
    $result = [];
    foreach ($rows as $row) {
        if (!is_array($row)) { reject('catalog'); }
        exact($row, ['id', 'taxonomy', 'locale', 'public']);
        if (!positive($row['id']) || !in_array($row['taxonomy'], ['category', 'post_tag'], true)
            || !is_bool($row['public'])) { reject('catalog'); }
        locale($row['locale']);
        if (isset($result[$row['id']])) { reject('catalog'); }
        $result[$row['id']] = $row;
    }
    ksort($result, SORT_NUMERIC); return $result;
}
function fields(array $fields, $language, array $terms) {
    if (!$fields || array_diff(array_keys($fields), FIELDS)) { reject('fields'); }
    $result = [];
    foreach (FIELDS as $field) {
        if (!array_key_exists($field, $fields)) { continue; }
        $value = $fields[$field];
        if (in_array($field, ['categories', 'tags'], true)) {
            $value = ids($value);
            foreach ($value as $id) {
                $term = $terms[$id] ?? null;
                if (!$term || !$term['public'] || $term['locale'] !== $language
                    || $term['taxonomy'] !== ($field === 'categories' ? 'category' : 'post_tag')) { reject('classification'); }
            }
        } else {
            text($value, ['title' => 1000, 'content' => 262144, 'excerpt' => 8192][$field]);
            if ($field === 'title' && trim($value) === '') { reject('title'); }
        }
        $result[$field] = $value;
    }
    return $result;
}
function snapshot(array $source, array $terms) {
    exact($source, ['id', 'type', 'status', 'password', 'locale', 'title', 'content', 'excerpt', 'categories', 'tags', 'translations']);
    if (!positive($source['id']) || $source['type'] !== 'post' || $source['status'] !== 'publish' || $source['password'] !== '') { reject('source'); }
    locale($source['locale']);
    $group = $source['translations'];
    if (!is_array($group) || array_diff(array_keys($group), ['en', 'es']) || ($group[$source['locale']] ?? null) !== $source['id']) { reject('relationships'); }
    foreach ($group as $id) { if (!positive($id)) { reject('relationships'); } }
    if (count(array_unique($group, SORT_REGULAR)) !== count($group)) { reject('relationships'); }
    $values = fields(array_intersect_key($source, array_flip(FIELDS)), $source['locale'], $terms);
    return array_replace($source, $values);
}
/** Input snapshots/catalog must come from a trusted future adapter, never MCP arguments. */
function base_hash(array $source, array $termRows) {
    $terms = catalog($termRows);
    return digest(['format' => 'blog-source-base-v1', 'source' => snapshot($source, $terms), 'terms' => $terms]);
}
/** Produces version 1 only. Durable ownership, replay and revisions are not implemented here. */
function prepare(array $actor, array $source, array $termRows, array $request) {
    exact($actor, ['id', 'authenticated', 'capabilities']);
    if (!positive($actor['id']) || $actor['authenticated'] !== true || !is_array($actor['capabilities'])
        || ($actor['capabilities'][PREPARE_SCOPE] ?? false) !== true) { reject('forbidden'); }
    exact($request, ['request_id', 'source_id', 'locale', 'base_hash', 'operation', 'fields']);
    if (!is_string($request['request_id']) || !preg_match('/^[a-f0-9]{32}$/D', $request['request_id'])
        || !is_string($request['base_hash']) || !preg_match('/^[a-f0-9]{64}$/D', $request['base_hash'])
        || !positive($request['source_id']) || !is_array($request['fields'])
        || !in_array($request['operation'], ['update-post', 'create-translation'], true)) { reject('input'); }
    locale($request['locale']);
    $terms = catalog($termRows); $source = snapshot($source, $terms);
    $base = base_hash($source, $termRows);
    if ($request['source_id'] !== $source['id']) { reject('source'); }
    if (!hash_equals($base, $request['base_hash'])) { reject('stale_base'); }
    $after = fields($request['fields'], $request['locale'], $terms);
    $create = $request['operation'] === 'create-translation';
    if ($create) {
        if ($request['locale'] === $source['locale'] || isset($source['translations'][$request['locale']])) { reject('translation_exists'); }
        exact($after, FIELDS);
    } elseif ($request['locale'] !== $source['locale']) { reject('locale'); }
    $changes = [];
    foreach ($after as $field => $value) {
        $before = $create ? null : $source[$field];
        if ($create || $before !== $value) { $changes[$field] = ['before' => $before, 'after' => $value]; }
    }
    if (!$changes) { reject('no_changes'); }
    $normalized = array_replace($request, ['fields' => $after]);
    $document = ['format' => 'blog-private-proposal-plan-v1', 'owner_id' => $actor['id'], 'version' => 1,
        'request_id' => $request['request_id'], 'request_hash' => digest(['owner_id' => $actor['id'], 'request' => $normalized]),
        'source_id' => $source['id'], 'source_locale' => $source['locale'], 'locale' => $request['locale'],
        'base_hash' => $base, 'operation' => $request['operation'], 'changes' => $changes,
        'translation_intent' => $create ? ['source_group' => canonical($source['translations']), 'target_locale' => $request['locale'], 'target_id' => null] : null,
        'approval' => 'not-requested', 'source_write_performed' => false];
    $document['document_hash'] = digest($document);
    return $document;
}
