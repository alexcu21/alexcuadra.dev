import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
BOOTSTRAP = r'''<?php
namespace AlexCuadra\EditorialProposals {
function storage_schema_valid(){return true;}
function permission(){return $GLOBALS['scenario']==='permission-failure'?new \WP_Error():true;}
}
namespace AlexCuadra\EditorialRead { function get_project($input){return [];} }
namespace {
define('WP_CLI',true); define('ABSPATH',getenv('FIXTURE').'/'); define('AC_EDITORIAL_PROPOSALS_SITE','https://content.alexcuadra.dev');
$scenario=getenv('SCENARIO'); $current=$scenario==='wrong-operator'?2:1; $actions=[];
$caps=['subscriber'=>true,'alexcuadra_read_public_projects'=>true];
if($scenario==='existing'){$caps['alexcuadra_prepare_project_changes']=true;}
class WP_Error {}
class WP_CLI {static function line($s){echo $s,"\n";} static function error($s){fwrite(STDERR,$s);exit(1);}}
class Target {
public $roles=['subscriber']; public $caps;
function __construct(){ $this->caps=$GLOBALS['caps']; }
function add_cap($cap,$value){$GLOBALS['actions'][]=['add',2,$cap,$GLOBALS['current']];$GLOBALS['caps'][$cap]=$value;}
function remove_cap($cap){$GLOBALS['actions'][]=['remove',2,$cap,$GLOBALS['current']];unset($GLOBALS['caps'][$cap]);}
}
function get_userdata($id){return new Target();} function get_current_user_id(){return $GLOBALS['current'];}
function wp_set_current_user($id){$GLOBALS['current']=$id;} function clean_user_cache($id){}
function user_can($id,$cap){return !empty($GLOBALS['caps'][$cap]);}
function current_user_can($cap,...$args){return $GLOBALS['current']===1?$cap==='manage_options':($cap==='read'||user_can(2,$cap));}
function get_role($name){return new class {function has_cap($cap){return $GLOBALS['scenario']==='global-scope'&&$cap==='alexcuadra_prepare_project_changes';}};}
function home_url(){return 'https://content.alexcuadra.dev';}function site_url(){return home_url();}function is_multisite(){return false;}
function is_plugin_active($file){return true;}function get_option($name){return '0.1';}
function wp_json_encode($data){return json_encode($data);}function is_wp_error($v){return $v instanceof WP_Error;}
register_shutdown_function(function(){echo 'FIXTURE_STATE=',json_encode(['caps'=>$GLOBALS['caps'],'actions'=>$GLOBALS['actions'],'operator'=>$GLOBALS['current']]),"\n";});
$code=file_get_contents(getenv('HELPER'));
$start=strpos($code,'function ac_proposals_activation_snapshot()');$end=strpos($code,'$scope =',$start);
$stub=<<<'STUB'
function ac_proposals_activation_snapshot(){
 $caps=$GLOBALS['caps'];ksort($caps);
 return ['projectCount'=>14,'sourceHash'=>$GLOBALS['scenario']==='source-drift'&&!empty($GLOBALS['actions'])?'changed':'c38acd1690f173488f075443d03a80c96b6b483a5bd407040cbbd40a7ff462af',
 'user2CapsHash'=>hash('sha256',serialize($caps)),'subscriberRoleHash'=>'same','otherActivePluginsHash'=>'same'];
}
STUB;
$code=substr($code,0,$start).$stub.substr($code,$end);eval('?>'.$code);
}
'''


class ScopeGrantTests(unittest.TestCase):
    def run_case(self, scenario):
        with tempfile.TemporaryDirectory() as folder:
            path=Path(folder)/'wp-admin/includes/plugin.php';path.parent.mkdir(parents=True);path.write_text('<?php')
            result=subprocess.run(['php'],input=BOOTSTRAP,text=True,capture_output=True,env={**os.environ,'FIXTURE':folder,
                'SCENARIO':scenario,'HELPER':str(ROOT/'wordpress/operations/editorial-proposals-grant-scope.php')})
        state=json.loads(next(line.split('=',1)[1] for line in result.stdout.splitlines() if line.startswith('FIXTURE_STATE=')))
        return result,state

    def test_success_adds_only_expected_capability_as_operator_one(self):
        result,state=self.run_case('valid')
        self.assertEqual(result.returncode,0,result.stderr)
        self.assertEqual(state['actions'],[['add',2,'alexcuadra_prepare_project_changes',1]])
        self.assertTrue(state['caps']['alexcuadra_prepare_project_changes'])
        self.assertEqual(state['operator'],1)

    def test_existing_direct_grant_is_not_removed_or_overwritten(self):
        result,state=self.run_case('existing')
        self.assertEqual(result.returncode,1)
        self.assertEqual(state['actions'],[])
        self.assertTrue(state['caps']['alexcuadra_prepare_project_changes'])

    def test_global_scope_is_rejected_without_mutation(self):
        result,state=self.run_case('global-scope')
        self.assertEqual(result.returncode,1)
        self.assertEqual(state['actions'],[])

    def test_wrong_operator_is_rejected_without_mutation(self):
        result,state=self.run_case('wrong-operator')
        self.assertEqual(result.returncode,1)
        self.assertEqual(state['actions'],[])

    def test_failed_postflight_removes_only_new_scope_and_preserves_read(self):
        for scenario in ['source-drift','permission-failure']:
            with self.subTest(scenario=scenario):
                result,state=self.run_case(scenario)
                self.assertEqual(result.returncode,1)
                self.assertEqual(state['actions'],[['add',2,'alexcuadra_prepare_project_changes',1],['remove',2,'alexcuadra_prepare_project_changes',1]])
                self.assertNotIn('alexcuadra_prepare_project_changes',state['caps'])
                self.assertTrue(state['caps']['alexcuadra_read_public_projects'])
                self.assertEqual(state['operator'],1)


if __name__=='__main__':unittest.main()
