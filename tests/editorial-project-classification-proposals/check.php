<?php
require __DIR__ . '/../../wordpress/experimental/editorial-project-classification-proposals/planner.php';
use function AlexCuadra\ProjectClassificationProposalLab\{prepare, base_hash, digest, canonical};
use const AlexCuadra\ProjectClassificationProposalLab\SCOPES;
$cases = [];
function check(bool $ok, string $name): void {
    global $cases;
    if (!$ok) { throw new RuntimeException('FAILED: ' . $name); }
    $cases[] = $name;
}
function denied(callable $fn, string $code, string $name): void {
    try { $fn(); } catch (DomainException $e) { check($e->getMessage() === 'project_classification_proposal_' . $code, $name); return; }
    throw new RuntimeException('NOT REJECTED: ' . $name);
}
function row(int $id, string $taxonomy, string $locale = 'en'): array {
    return ['id' => $id, 'name' => 'Synthetic term ' . $id, 'slug' => 'term-' . $id, 'taxonomy' => $taxonomy, 'locale' => $locale, 'translations' => []];
}
$actor = ['id' => 2, 'authenticated' => true, 'capabilities' => array_fill_keys(SCOPES, true)];
$catalog = ['categories' => [row(10, 'project_category'), row(20, 'project_category')],
    'technologies' => [row(30, 'project_technology'), row(40, 'project_technology')]];
$source = ['project_id' => 100, 'locale' => 'en', 'categories' => [$catalog['categories'][0]], 'technologies' => [$catalog['technologies'][0]]];
$request = ['request_id' => str_repeat('a', 32), 'project_id' => 100, 'locale' => 'en', 'base_hash' => base_hash($source, $catalog), 'fields' => ['technologies' => [40, 30]]];
$original = serialize([$actor, $source, $catalog, $request]);
$plan = prepare($actor, $source, $catalog, $request);
check(canonical($plan['changes']['technologies']) === canonical(['taxonomy' => 'project_technology', 'locale' => 'en', 'before' => $source['technologies'], 'after' => $catalog['technologies']]), 'review binds exact taxonomy locale IDs and labels');
check(!isset($plan['changes']['categories']), 'omitted categories preserved');
check($plan['owner_id'] === 2 && $plan['version'] === 1 && $plan['approval'] === 'not-requested' && !$plan['source_write_performed'], 'private initial document without approval or write');
check(serialize([$actor, $source, $catalog, $request]) === $original, 'caller inputs untouched');
check(prepare($actor, $source, $catalog, $request) === $plan, 'deterministic result');
$r = $request; $r['fields']['technologies'] = [30, 40];
$c = $catalog; $c['technologies'] = array_reverse($c['technologies']);
check(prepare($actor, array_reverse($source, true), $c, array_reverse($r, true)) === $plan, 'set and map order independent');
$document = $plan; unset($document['document_hash']);
check(digest($document) === $plan['document_hash'], 'review integrity hash');
$document['changes']['technologies']['after'][0]['name'] = 'Changed';
check(digest($document) !== $plan['document_hash'], 'label tamper detected');
$a = $actor; $a['id'] = 9;
check(prepare($a, $source, $catalog, $request)['request_hash'] !== $plan['request_hash'], 'request bound to owner');
$r = $request; $r['fields'] = ['categories' => []];
check(prepare($actor, $source, $catalog, $r)['changes']['categories']['after'] === [], 'explicit empty list removes complete set');
$r = $request; $r['fields'] = ['categories' => [20], 'technologies' => []];
check(count(prepare($actor, $source, $catalog, $r)['changes']) === 2, 'both taxonomies reviewed together');
check(prepare($actor, $source, $catalog, $r)['request_hash'] !== $plan['request_hash'], 'same request ID different payload distinguishable');
foreach (SCOPES as $scope) {
    $a = $actor; unset($a['capabilities'][$scope]);
    denied(fn() => prepare($a, $source, $catalog, $request), 'forbidden', 'requires ' . $scope);
}
$a = $actor; $a['capabilities'] = ['manage_options' => true];
denied(fn() => prepare($a, $source, $catalog, $request), 'forbidden', 'admin alone insufficient');
$a = $actor; $a['authenticated'] = false;
denied(fn() => prepare($a, $source, $catalog, $request), 'forbidden', 'anonymous denied');
$a = $actor; $a['capabilities'][SCOPES[2]] = 1;
denied(fn() => prepare($a, $source, $catalog, $request), 'forbidden', 'scope strict boolean');
foreach (['tags', 'title', 'status', 'body_html', 'category', 'project_category', 'sql'] as $key) {
    $r = $request; $r['fields'][$key] = [];
    denied(fn() => prepare($actor, $source, $catalog, $r), 'fields', 'unknown field ' . $key);
}
foreach ([null, ['30'], [30, 30], [0], [true], [1 => 30], range(1, 101)] as $value) {
    $r = $request; $r['fields']['technologies'] = $value;
    denied(fn() => prepare($actor, $source, $catalog, $r), 'terms', 'invalid term list ' . count($cases));
}
foreach ([10, 999] as $id) {
    $r = $request; $r['fields'] = ['technologies' => [$id]];
    denied(fn() => prepare($actor, $source, $catalog, $r), 'classification', 'wrong taxonomy or unknown ID ' . $id);
}
$r = $request; $r['fields'] = [];
denied(fn() => prepare($actor, $source, $catalog, $r), 'fields', 'empty patch denied');
$r = $request; $r['fields'] = ['technologies' => [30]];
denied(fn() => prepare($actor, $source, $catalog, $r), 'no_changes', 'no op denied');
foreach (['owner_id' => 9, 'catalog' => $catalog, 'operation' => 'publish'] as $key => $value) {
    $r = $request; $r[$key] = $value;
    denied(fn() => prepare($actor, $source, $catalog, $r), 'schema', 'client cannot provide ' . $key);
}
foreach (['request_id' => 'bad', 'project_id' => '100', 'base_hash' => 'bad'] as $key => $value) {
    $r = $request; $r[$key] = $value;
    denied(fn() => prepare($actor, $source, $catalog, $r), 'input', 'malformed ' . $key);
}
foreach (['project_id' => 101, 'locale' => 'es'] as $key => $value) {
    $r = $request; $r[$key] = $value;
    denied(fn() => prepare($actor, $source, $catalog, $r), 'source', 'exact source ' . $key);
}
$r = $request; $r['locale'] = 'fr';
denied(fn() => prepare($actor, $source, $catalog, $r), 'locale', 'unsupported locale');
$s = $source; $s['technologies'] = [];
denied(fn() => prepare($actor, $s, $catalog, $request), 'stale_base', 'concurrent assignment change');
$c = $catalog; $c['technologies'][1]['name'] = 'Renamed';
denied(fn() => prepare($actor, $source, $c, $request), 'stale_base', 'catalog label change');
$c = $catalog; $c['technologies'][1]['translations'] = [['id' => 41, 'name' => 'ES', 'slug' => 'es', 'locale' => 'es']];
denied(fn() => prepare($actor, $source, $c, $request), 'stale_base', 'translation metadata change');
foreach (['taxonomy' => 'post_tag', 'locale' => 'es'] as $key => $value) {
    $c = $catalog; $c['technologies'][1][$key] = $value;
    denied(fn() => prepare($actor, $source, $c, $request), 'classification', 'reject catalog ' . $key);
}
$c = $catalog; $c['categories'][] = $c['categories'][0];
denied(fn() => prepare($actor, $source, $c, $request), 'catalog', 'duplicate catalog ID');
$c = $catalog; $c['technologies'][1]['id'] = 10;
denied(fn() => prepare($actor, $source, $c, $request), 'catalog', 'cross taxonomy ID collision');
$c = $catalog; $c['technologies'] = array_map(fn($id) => row($id, 'project_technology'), range(1000, 1200));
denied(fn() => prepare($actor, $source, $c, $request), 'catalog', 'oversized catalog');
$s = $source; $s['technologies'][0]['name'] = 'Forged';
denied(fn() => prepare($actor, $s, $catalog, $request), 'classification', 'source metadata agrees with catalog');
$c = $catalog; $c['technologies'][1]['description'] = 'secret';
denied(fn() => prepare($actor, $source, $c, $request), 'schema', 'private term fields excluded');
$s = $source; $s['body_html'] = 'secret';
denied(fn() => prepare($actor, $s, $catalog, $request), 'schema', 'source body excluded');
foreach (["bad\0name", "\xFF", str_repeat('x', 2049)] as $value) {
    $c = $catalog; $c['technologies'][1]['name'] = $value;
    denied(fn() => prepare($actor, $source, $c, $request), 'term', 'invalid text ' . count($cases));
}
$c = $catalog; $c['technologies'][1]['translations'] = [['id' => 40, 'name' => 'Bad', 'slug' => 'bad', 'locale' => 'es']];
denied(fn() => prepare($actor, $source, $c, $request), 'relationships', 'translation cannot refer to self');
$es = ['categories' => [row(11, 'project_category', 'es')], 'technologies' => [row(31, 'project_technology', 'es')]];
$s = ['project_id' => 101, 'locale' => 'es', 'categories' => $es['categories'], 'technologies' => []];
$r = $request; $r['project_id'] = 101; $r['locale'] = 'es'; $r['base_hash'] = base_hash($s, $es); $r['fields'] = ['technologies' => [31]];
check(prepare($actor, $s, $es, $r)['changes']['technologies']['after'][0]['locale'] === 'es', 'Spanish independent proposal');
$r['fields']['technologies'] = [30];
denied(fn() => prepare($actor, $s, $es, $r), 'classification', 'English ID not mapped automatically into Spanish');
echo json_encode(['format' => 'project-classification-proposal-planner-qa-v1', 'passed' => count($cases), 'cases' => $cases,
    'wordpressIntegration' => false, 'persistenceTested' => false, 'sourceWritesPerformed' => false], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
