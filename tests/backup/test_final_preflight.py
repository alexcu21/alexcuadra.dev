import base64
import importlib.util
import json
from pathlib import Path
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('prepare_preflight', ROOT / 'scripts/prepare-editorial-proposals-final-preflight.py')
prepare = importlib.util.module_from_spec(spec)
spec.loader.exec_module(prepare)

BOOTSTRAP = r'''<?php
define('WP_CLI', true); define('ARRAY_A', 'ARRAY_A'); define('ABSPATH', getenv('FIXTURE') . '/'); define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
$scenario = getenv('SCENARIO'); $expectedFixture = json_decode(base64_decode(getenv('EXPECTED')), true);
if ($scenario === 'optin') { define('AC_EDITORIAL_PROPOSALS_SITE', 'https://content.alexcuadra.dev'); }
$wp_version = '7.1.3'; $operator = 17;
function home_url() { global $scenario; return $scenario === 'preview' ? 'https://preview.invalid' : 'https://content.alexcuadra.dev'; }
function site_url() { return home_url(); } function is_multisite() { return false; }
function get_plugins() { global $expectedFixture, $scenario; $out=[]; foreach ($expectedFixture['plugins'] as $file=>$p) { $out[$file]=['Version'=>$scenario==='version'?'changed':$p['version']]; } return $out; }
function is_plugin_active($file) { global $expectedFixture; return $expectedFixture['plugins'][$file]['active']??false; }
function get_stylesheet() { return 'synthetic'; } function get_template() { return 'synthetic'; }
function wp_get_theme() { return new class {function get($key) {return '1.0';}}; }
function get_option($key,$default=false) { global $scenario; return $key==='ac_proposals_storage_version'&&$scenario==='marker'?'0.1':$default; }
function get_current_user_id() { global $operator; return $operator; } function wp_set_current_user($id) {global $operator; $operator=$id;}
function get_users($query) {return [17];} function user_can($id,$cap) {return $id===17&&$cap==='manage_options';}
function get_userdata($id) {
    global $scenario; $caps=['subscriber'=>true,'read'=>true,'level_0'=>true,'alexcuadra_read_public_projects'=>true];
    if ($scenario==='missing-read') {unset($caps['alexcuadra_read_public_projects']);}
    if ($scenario==='prepare') {$caps['alexcuadra_prepare_project_changes']=true;}
    return (object)['ID'=>2,'roles'=>[$scenario==='editor'?'editor':'subscriber'],'allcaps'=>$caps];
}
function current_user_can($cap,...$args) { global $scenario; return $cap==='read_post'||($scenario==='object-edit'&&$cap==='edit_post')||($scenario==='admin'&&$cap==='manage_options')||!empty(get_userdata(2)->allcaps[$cap]); }
function get_post($id) {return (object)['post_type'=>'project','post_status'=>'publish','post_password'=>''];}
function pll_get_post_language($id,$field) {return $id===146?'en':'es';}
function get_role($name) {return new class {function has_cap($name) {global $scenario; return $scenario==='global-scope'&&str_starts_with($name,'alexcuadra_');}};}
function get_post_type_object($type) {return (object)['cap'=>(object)['edit_posts'=>'edit_pages','create_posts'=>'edit_pages']];}
function post_type_exists($type) {return $type==='project';} function is_post_type_viewable($type) {return true;}
function pll_is_translated_post_type($type) {return true;} function wp_is_application_passwords_available_for_user($user) {return true;}
function wp_get_ability() {} function wp_register_ability() {} function wp_json_encode($value) {return json_encode($value);}
class WP_CLI { static function line($s) {echo $s,"\n";} }
class FakeDB {
    public $prefix='fixture_', $posts='fixture_posts', $postmeta='fixture_postmeta', $last_error='';
    function prepare($query,...$args) {return $query;}
    function suppress_errors($value) {return false;}
    function get_var($query) {
        global $scenario,$expectedFixture;
        if (!str_starts_with($query,'SELECT ')) {throw new RuntimeException('WRITE_QUERY_FORBIDDEN');}
        $this->last_error=$scenario==='sql-failure'?'synthetic failure':'';
        if ($this->last_error) {return null;}
        return str_contains($query,'VERSION()')?$expectedFixture['databaseServer']:($scenario==='collision'?'1':'0');
    }
    function get_results($query,$format) {
        global $scenario;
        if (!str_starts_with($query,'SELECT ')) {throw new RuntimeException('WRITE_QUERY_FORBIDDEN');}
        $this->last_error=$scenario==='sql-failure'?'synthetic failure':'';
        return $this->last_error?null:[['ENGINE'=>$scenario==='myisam'?'MyISAM':'InnoDB'],['ENGINE'=>'InnoDB']];
    }
}
$wpdb = new FakeDB(); require getenv('HELPER');
if ($operator!==17) {throw new RuntimeException('CURRENT_USER_NOT_RESTORED');}
'''


class FinalPreflightTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.php = subprocess.check_output(['php', '-r', 'echo PHP_VERSION;'], text=True)

    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.folder = Path(self.temp.name)
        paths = ['wp-content/plugins/demo/main.php', 'wp-content/mu-plugins/mu.php', 'wp-content/themes/synthetic/style.css', 'wp-content/index.php', 'wp-admin/includes/plugin.php']
        files = {}
        import hashlib
        for name in paths:
            path=self.folder/name;path.parent.mkdir(parents=True, exist_ok=True);path.write_bytes(b'<?php // synthetic only\n')
            files[name]={'type':'file','bytes':path.stat().st_size,'sha256':hashlib.sha256(path.read_bytes()).hexdigest()}
        self.expected = {'wordpress':'7.1.3','php':self.php,'databaseServer':'11.8.9-fixture',
                         'plugins':{'demo/main.php':{'version':'1.0','active':True}},
                         'stylesheet':'synthetic','template':'synthetic','themeVersion':'1.0',
                         'trees':{name:prepare.tree_digest(files,'wp-content/'+name+'/') for name in ['plugins','mu-plugins','themes']}}
        self.expected['trees']['content-root']=prepare.tree_digest(files,'wp-content/',True)

    def run_case(self,scenario):
        import os
        encoded=base64.b64encode(json.dumps(self.expected).encode()).decode()
        helper=self.folder/'helper.php';helper.write_text((ROOT/'wordpress/operations/editorial-proposals-final-preflight.php').read_text().replace('@@EXPECTED_PROFILE@@',encoded))
        result=subprocess.run(['php'],input=BOOTSTRAP,text=True,capture_output=True,
                              env={**os.environ,'FIXTURE':str(self.folder),'HELPER':str(helper),'EXPECTED':encoded,'SCENARIO':scenario})
        self.assertEqual(result.returncode,0,result.stderr)
        return json.loads(next(line.split('=',1)[1] for line in result.stdout.splitlines() if line.startswith('EDITORIAL_PROPOSALS_FINAL_PREFLIGHT=')))

    def test_matching_profile_passes_without_authorizing_installation(self):
        report=self.run_case('valid')
        self.assertTrue(report['technicalChecksPassed'])
        self.assertFalse(report['installationAuthorized'])

    def test_roles_scopes_version_schema_and_sql_failures_close_readiness(self):
        for scenario in ['editor','prepare','admin','object-edit','missing-read','global-scope','version','collision','myisam','sql-failure','preview','optin','marker']:
            with self.subTest(scenario=scenario):
                self.assertFalse(self.run_case(scenario)['technicalChecksPassed'])

    def test_file_drift_fails_without_printing_content(self):
        (self.folder/'wp-content/mu-plugins/mu.php').write_text('PRIVATE_SYNTHETIC_SENTINEL')
        report=self.run_case('valid')
        self.assertFalse(report['fileChecks']['mu-plugins'])
        self.assertNotIn('PRIVATE_SYNTHETIC_SENTINEL',json.dumps(report))

    def test_extra_file_fails(self):
        (self.folder/'wp-content/plugins/unexpected.php').write_text('extra')
        self.assertFalse(self.run_case('valid')['fileChecks']['plugins'])

    def test_symlink_fails(self):
        (self.folder/'wp-content/plugins/link.php').symlink_to(self.folder/'wp-content/index.php')
        self.assertFalse(self.run_case('valid')['fileChecks']['plugins'])


if __name__=='__main__':unittest.main()
