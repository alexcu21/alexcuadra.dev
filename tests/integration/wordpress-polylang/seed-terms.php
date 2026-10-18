<?php
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local lab only'); }
$manifest = get_option('portfolio_lab_manifest');
if (!empty($manifest['terms'])) { echo "Term fixtures already exist.\n"; return; }
$en = wp_insert_term('LAB_CATEGORY_EN', 'category', ['slug' => 'lab-category-en']);
$es = wp_insert_term('LAB_CATEGORY_ES', 'category', ['slug' => 'lab-category-es']);
if (is_wp_error($en) || is_wp_error($es)) { throw new RuntimeException('Cannot create term fixtures'); }
$en = $en['term_id']; $es = $es['term_id'];
pll_set_term_language($en, 'en'); pll_set_term_language($es, 'es');
pll_save_term_translations(['en' => $en, 'es' => $es]);
foreach ($manifest['fixtures'] as $f) {
    if ($f['type'] === 'post' && $f['state'] === 'publish') {
        wp_set_object_terms($f['en'], [$en], 'category');
        wp_set_object_terms($f['es'], [$es], 'category');
    }
}
$manifest['terms'] = ['en' => $en, 'es' => $es];
update_option('portfolio_lab_manifest', $manifest);
echo "Synthetic category translations created.\n";
