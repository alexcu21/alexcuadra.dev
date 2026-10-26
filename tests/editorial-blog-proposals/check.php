<?php
require __DIR__ . '/../../wordpress/experimental/editorial-blog-proposals/planner.php';
use function AlexCuadra\EditorialBlogProposalLab\{prepare, base_hash, digest};
use const AlexCuadra\EditorialBlogProposalLab\PREPARE_SCOPE;

$cases = [];
function check($condition, $name) {
    global $cases;
    if (!$condition) { throw new RuntimeException('FAILED:' . $name); }
    $cases[] = $name;
}
function denied(callable $fn, $code, $name) {
    try { $fn(); } catch (DomainException $e) { check($e->getMessage() === 'blog_proposal_' . $code, $name); return; }
    throw new RuntimeException('NOT_REJECTED:' . $name);
}
$actor = ['id' => 2, 'authenticated' => true, 'capabilities' => [PREPARE_SCOPE => true]];
$terms = [
    ['id' => 46, 'taxonomy' => 'category', 'locale' => 'en', 'public' => true],
    ['id' => 160, 'taxonomy' => 'category', 'locale' => 'es', 'public' => true],
    ['id' => 3, 'taxonomy' => 'post_tag', 'locale' => 'en', 'public' => true],
    ['id' => 163, 'taxonomy' => 'post_tag', 'locale' => 'es', 'public' => true],
    ['id' => 47, 'taxonomy' => 'category', 'locale' => 'en', 'public' => true],
    ['id' => 164, 'taxonomy' => 'category', 'locale' => 'es', 'public' => false],
];
$source = ['id' => 239, 'type' => 'post', 'status' => 'publish', 'password' => '', 'locale' => 'en',
    'title' => 'Synthetic story', 'content' => '<!-- wp:paragraph --><p>Original</p><!-- /wp:paragraph -->',
    'excerpt' => 'Original excerpt', 'categories' => [46], 'tags' => [3], 'translations' => ['en' => 239, 'es' => 228]];
$request = ['request_id' => str_repeat('a', 32), 'source_id' => 239, 'locale' => 'en',
    'base_hash' => base_hash($source, $terms), 'operation' => 'update-post', 'fields' => ['title' => 'Revised story']];
$original = serialize([$actor, $source, $terms, $request]);
$plan = prepare($actor, $source, $terms, $request);
check($plan['changes'] === ['title' => ['before' => 'Synthetic story', 'after' => 'Revised story']], 'review_has_exact_before_after');
check($plan['owner_id'] === 2 && $plan['version'] === 1 && $plan['approval'] === 'not-requested', 'private_owner_version_without_approval');
check($plan['source_write_performed'] === false && $plan['translation_intent'] === null, 'no_source_write_or_translation_side_effect');
check(serialize([$actor, $source, $terms, $request]) === $original, 'inputs_preserved');
check(prepare($actor, $source, $terms, $request) === $plan, 'same_request_has_same_plan');
$reordered = array_reverse($request, true);
check(prepare($actor, $source, array_reverse($terms), $reordered) === $plan, 'map_and_catalog_order_are_canonical');
$copy = $plan; unset($copy['document_hash']);
check(digest($copy) === $plan['document_hash'], 'document_hash_binds_review');
$copy['changes']['title']['after'] = 'tampered';
check(digest($copy) !== $plan['document_hash'], 'review_tampering_changes_hash');
$a = $actor; $a['id'] = 9;
check(prepare($a, $source, $terms, $request)['request_hash'] !== $plan['request_hash'], 'request_hash_is_owner_bound');
$r = $request; $r['fields']['title'] = 'Different';
check(prepare($actor, $source, $terms, $r)['request_hash'] !== $plan['request_hash'], 'same_key_different_payload_has_different_hash');

$a = $actor; $a['authenticated'] = false;
denied(fn() => prepare($a, $source, $terms, $request), 'forbidden', 'anonymous_denied');
$a = $actor; $a['id'] = 0;
denied(fn() => prepare($a, $source, $terms, $request), 'forbidden', 'missing_owner_denied');
foreach (['read', 'alexcuadra_read_public_posts', 'alexcuadra_prepare_project_changes', 'manage_options'] as $cap) {
    $a = $actor; $a['capabilities'] = [$cap => true];
    denied(fn() => prepare($a, $source, $terms, $request), 'forbidden', 'scope_does_not_expand_' . $cap);
}
$a = $actor; $a['capabilities'][PREPARE_SCOPE] = 1;
denied(fn() => prepare($a, $source, $terms, $request), 'forbidden', 'scope_requires_boolean_true');
foreach (['slug', 'status', 'author', 'meta', 'acf', 'sql'] as $field) {
    $r = $request; $r['fields'][$field] = 'forbidden';
    denied(fn() => prepare($actor, $source, $terms, $r), 'fields', 'unapproved_field_' . $field);
}
$r = $request; $r['actor'] = $actor;
denied(fn() => prepare($actor, $source, $terms, $r), 'schema', 'actor_not_a_client_request_field');
$r = $request; $r['fields'] = [];
denied(fn() => prepare($actor, $source, $terms, $r), 'fields', 'empty_patch_denied');
$r = $request; $r['fields'] = ['title' => $source['title']];
denied(fn() => prepare($actor, $source, $terms, $r), 'no_changes', 'no_op_denied');
$r = $request; $r['source_id'] = 228;
denied(fn() => prepare($actor, $source, $terms, $r), 'source', 'wrong_source_denied');
$r = $request; $r['locale'] = 'es';
denied(fn() => prepare($actor, $source, $terms, $r), 'locale', 'update_exact_language_only');
$r = $request; $r['locale'] = 'fr';
denied(fn() => prepare($actor, $source, $terms, $r), 'locale', 'unknown_language_denied');
foreach (['request_id' => 'unsafe key', 'source_id' => '239', 'base_hash' => 'invalid', 'operation' => 'publish'] as $field => $value) {
    $r = $request; $r[$field] = $value;
    denied(fn() => prepare($actor, $source, $terms, $r), 'input', 'invalid_input_' . $field);
}
foreach (['draft', 'private', 'future', 'trash'] as $status) {
    $s = $source; $s['status'] = $status;
    denied(fn() => prepare($actor, $s, $terms, $request), 'source', 'nonpublic_source_' . $status);
}
$s = $source; $s['type'] = 'project';
denied(fn() => prepare($actor, $s, $terms, $request), 'source', 'project_not_native_post');
$s = $source; $s['password'] = 'secret';
denied(fn() => prepare($actor, $s, $terms, $request), 'source', 'protected_source_denied');
foreach (['title' => 'Concurrent title', 'content' => 'Concurrent body', 'excerpt' => 'Concurrent excerpt', 'categories' => [], 'tags' => [], 'translations' => ['en' => 239]] as $field => $value) {
    $s = $source; $s[$field] = $value;
    denied(fn() => prepare($actor, $s, $terms, $request), 'stale_base', 'concurrent_change_' . $field);
}
$t = $terms; $t[1]['public'] = false;
denied(fn() => prepare($actor, $source, $t, $request), 'stale_base', 'catalog_drift_invalidates_base');
foreach ([['en' => 241], ['en' => 239, 'es' => 239], ['en' => 239, 'fr' => 500]] as $group) {
    $s = $source; $s['translations'] = $group;
    denied(fn() => prepare($actor, $s, $terms, $request), 'relationships', 'inconsistent_group_' . count($cases));
}
foreach ([[160], [3], [999], [164]] as $value) {
    $r = $request; $r['fields'] = ['categories' => $value];
    denied(fn() => prepare($actor, $source, $terms, $r), 'classification', 'wrong_term_assignment_' . $value[0]);
}
foreach ([[46,46], ['46'], [0], [-1], array_fill(0,101,46), ['key' => 46]] as $value) {
    $r = $request; $r['fields'] = ['categories' => $value];
    denied(fn() => prepare($actor, $source, $terms, $r), 'terms', 'invalid_term_ids_' . count($cases));
}
$r = $request; $r['fields'] = ['categories' => [47,46], 'tags' => [], 'excerpt' => ''];
$p = prepare($actor, $source, $terms, $r);
check($p['changes']['categories']['after'] === [46,47] && $p['changes']['tags']['after'] === [] && $p['changes']['excerpt']['after'] === '', 'canonical_terms_and_explicit_clears');
$r2 = $r; $r2['fields']['categories'] = [46,47];
check(prepare($actor, $source, $terms, $r2) === $p, 'term_order_does_not_change_plan');
foreach (["bad\x00text", "bad\xC3\x28", "bad\u{0085}text", str_repeat('a',262145)] as $value) {
    $r = $request; $r['fields'] = ['content' => $value];
    denied(fn() => prepare($actor, $source, $terms, $r), 'text', 'invalid_or_oversized_text_' . count($cases));
}
$r = $request; $r['fields'] = ['title' => '   '];
denied(fn() => prepare($actor, $source, $terms, $r), 'title', 'blank_title_denied');
$r = $request; $r['fields'] = ['content' => "<!-- wp:html --><script>alert('x')</script><!-- /wp:html -->\nEspañol 🙂"];
$p = prepare($actor, $source, $terms, $r);
check($p['changes']['content']['after'] === $r['fields']['content'], 'html_block_unicode_stored_as_literal_not_sanitized_or_executed');

$single = $source; $single['translations'] = ['en' => 239];
$translation = $request; $translation['base_hash'] = base_hash($single, $terms); $translation['locale'] = 'es';
$translation['operation'] = 'create-translation';
$translation['fields'] = ['title' => 'Historia sintética', 'content' => '<p>Contenido explícito</p>', 'excerpt' => '', 'categories' => [160], 'tags' => [163]];
$p = prepare($actor, $single, $terms, $translation);
check($p['translation_intent'] === ['source_group' => ['en' => 239], 'target_locale' => 'es', 'target_id' => null], 'translation_intent_no_id_reserved');
check(count($p['changes']) === 5 && array_column($p['changes'], 'before') === [null,null,null,null,null], 'translation_has_all_explicit_fields_no_existing_target');
check($p['source_id'] === 239 && $p['source_locale'] === 'en' && $p['locale'] === 'es', 'translation_binds_source_and_target_languages');
foreach (['title','content','excerpt','categories','tags'] as $field) {
    $r = $translation; unset($r['fields'][$field]);
    denied(fn() => prepare($actor, $single, $terms, $r), 'schema', 'translation_requires_' . $field);
}
$r = $translation; $r['fields']['categories'] = [46];
denied(fn() => prepare($actor, $single, $terms, $r), 'classification', 'translation_cannot_copy_source_term_ids');
$r = $translation; $r['locale'] = 'en'; $r['fields']['categories'] = [46]; $r['fields']['tags'] = [3];
denied(fn() => prepare($actor, $single, $terms, $r), 'translation_exists', 'same_language_creation_denied');
$r = $translation; $r['base_hash'] = base_hash($source, $terms);
denied(fn() => prepare($actor, $source, $terms, $r), 'translation_exists', 'existing_linked_translation_denied');
denied(fn() => prepare($actor, $source, $terms, $translation), 'stale_base', 'concurrently_linked_translation_invalidates_review');
$es = $single; $es['id'] = 228; $es['locale'] = 'es'; $es['translations'] = ['es' => 228]; $es['categories'] = [160]; $es['tags'] = [163];
$r = $translation; $r['source_id'] = 228; $r['base_hash'] = base_hash($es, $terms); $r['locale'] = 'en'; $r['fields']['categories'] = [46]; $r['fields']['tags'] = [3];
check(prepare($actor, $es, $terms, $r)['translation_intent']['target_locale'] === 'en', 'spanish_to_english_creation_supported');
$t = $terms; $t[] = $terms[0];
denied(fn() => base_hash($source, $t), 'catalog', 'duplicate_catalog_ids_denied');
$t = $terms; $t[0]['taxonomy'] = 'project_category';
denied(fn() => base_hash($source, $t), 'catalog', 'project_taxonomy_catalog_denied');

echo json_encode(['environment' => 'pure-php-synthetic-projections', 'php' => PHP_VERSION, 'passed' => count($cases), 'failed' => 0,
    'wordpress' => false, 'database' => false, 'network' => false, 'sourceWrites' => false, 'durableIdempotencyTested' => false,
    'cases' => $cases], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
