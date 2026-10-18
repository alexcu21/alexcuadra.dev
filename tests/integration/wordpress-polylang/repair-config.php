<?php
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local lab only'); }
PLL()->options->set('post_types', ['project', 'articles']);
PLL()->options->set('taxonomies', ['category', 'post_tag']);
PLL()->options->set('default_lang', 'en');
PLL()->options->save_all();
$settings = get_option('graphql_general_settings', []);
unset($settings['restrict_endpoint_to_authenticated_users']);
$settings['restrict_endpoint_to_logged_in_users'] = 'on';
update_option('graphql_general_settings', $settings);
echo "Corrected isolated lab settings.\n";
