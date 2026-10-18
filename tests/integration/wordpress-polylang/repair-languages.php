<?php
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local lab only'); }
$manifest = get_option('portfolio_lab_manifest');
foreach ($manifest['fixtures'] as $fixture) {
    if (!pll_is_translated_post_type($fixture['type'])) { throw new RuntimeException('Type not translatable'); }
    pll_set_post_language($fixture['en'], 'en');
    pll_set_post_language($fixture['es'], 'es');
    pll_save_post_translations(['en' => $fixture['en'], 'es' => $fixture['es']]);
}
echo "Synthetic fixture language relationships verified.\n";
