<?php
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local lab only'); }
$manifest = get_option('portfolio_lab_manifest');
if (!empty($manifest['fixtures'])) { echo "Fixtures already seeded.\n"; return; }
foreach (['post', 'page', 'project', 'articles'] as $type) {
    foreach (['publish', 'draft', 'private', 'future', 'password'] as $state) {
        $marker = 'LAB_' . strtoupper($type . '_' . $state) . '_SPANISH_CONTENT';
        $en = wp_insert_post(['post_type' => $type, 'post_status' => 'publish', 'post_author' => 1,
            'post_title' => 'Synthetic EN ' . $type . ' ' . $state, 'post_content' => 'PUBLIC_EN_' . $type . '_' . $state], true);
        $args = ['post_type' => $type, 'post_status' => $state === 'password' ? 'publish' : $state,
            'post_author' => 1, 'post_title' => 'Synthetic ES ' . $type . ' ' . $state, 'post_content' => $marker];
        if ($state === 'future') { $args['post_date'] = '2035-01-01 12:00:00'; $args['post_date_gmt'] = '2035-01-01 12:00:00'; }
        if ($state === 'password') { $args['post_password'] = 'synthetic-lab-password'; }
        $es = wp_insert_post($args, true);
        if (is_wp_error($en) || is_wp_error($es)) { throw new RuntimeException('Cannot seed fixture'); }
        pll_set_post_language($en, 'en'); pll_set_post_language($es, 'es');
        pll_save_post_translations(['en' => $en, 'es' => $es]);
        update_field('field_security_lab_note', 'PUBLIC_ACF_EN_' . $type . '_' . $state, $en);
        update_field('field_security_lab_note', $marker . '_ACF', $es);
        $manifest['fixtures'][] = ['type' => $type, 'state' => $state, 'en' => $en, 'es' => $es, 'marker' => $marker];
    }
}
update_option('portfolio_lab_manifest', $manifest);
$graphql = get_option('graphql_general_settings', []);
$graphql['restrict_endpoint_to_logged_in_users'] = 'on';
update_option('graphql_general_settings', $graphql);
flush_rewrite_rules();
echo "Seeded " . count($manifest['fixtures']) . " synthetic translation pairs.\n";
