<?php
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local lab only'); }
if (get_option('portfolio_lab_manifest')) { echo "Fixtures already exist.\n"; return; }
PLL()->options->set('post_types', ['project', 'articles']);
PLL()->options->set('taxonomies', ['category', 'post_tag']);
PLL()->options->save_all();
foreach ([['en', 'en_US', 'English'], ['es', 'es_ES', 'Español']] as $lang) {
    $result = PLL()->model->add_language(['name' => $lang[2], 'slug' => $lang[0],
        'locale' => $lang[1], 'rtl' => false, 'term_group' => $lang[0] === 'en' ? 0 : 1]);
    if (is_wp_error($result)) { throw new RuntimeException('Cannot configure lab language'); }
}
PLL()->options->set('default_lang', 'en');
PLL()->options->save_all();
$reader = wp_create_user('lab_reader', wp_generate_password(32), 'reader@example.test');
if (is_wp_error($reader)) { throw new RuntimeException('Cannot create lab reader'); }
(new WP_User($reader))->set_role('subscriber');
$password = WP_Application_Passwords::create_new_application_password($reader, ['name' => 'isolated-security-tests']);
if (is_wp_error($password)) { throw new RuntimeException('Cannot create lab application password'); }
update_option('portfolio_lab_manifest', ['readerId' => $reader, 'username' => 'lab_reader',
    'password' => $password[0], 'fixtures' => []]);
echo "Local languages and subscriber configured.\n";
