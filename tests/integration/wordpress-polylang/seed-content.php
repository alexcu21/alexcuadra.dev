<?php
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local lab only'); }
$manifest = get_option('portfolio_lab_manifest');
foreach ($manifest['fixtures'] as $fixture) {
    foreach (['en', 'es'] as $language) {
        $id = $fixture[$language];
        if ($fixture['type'] === 'project') {
            update_field('field_676dcb2aec8d4', 'SYNTHETIC_TAGLINE_' . strtoupper($language), $id);
        }
        if ($fixture['type'] === 'articles') {
            update_field('field_677087569b36b', 'SYNTHETIC_QUOTE_' . strtoupper($language), $id);
            if ($fixture['state'] === 'publish') {
                wp_set_object_terms($id, [$manifest['terms'][$language]], 'category');
            }
        }
        if ($fixture['type'] === 'page') {
            update_field('field_676ca65229d5f', 'SYNTHETIC_NAME_' . strtoupper($language), $id);
            update_field('field_67707e6bed233', 'SYNTHETIC_INTRO_' . strtoupper($language), $id);
        }
    }
}
echo "Synthetic portfolio fields seeded.\n";
