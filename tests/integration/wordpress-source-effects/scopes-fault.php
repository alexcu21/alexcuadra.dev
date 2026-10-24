<?php
// Synthetic-only hook failure after the first capability write: verify actual helper rollback.
namespace AlexCuadra\EditorialSourceExperiment;
$saved=$args[0];$config=$args[1];
$code=file_get_contents($saved);$code=explode('$phase=$args[0]',$code,2)[0];
eval(substr($code,5));
foreach(['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities'] as $module){require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/'.$module.'.php';}
deploy_scope_context(true);$before=deploy_scope_caps_hash(deploy_scope_caps());
$baseline=['snapshot'=>deploy_scope_snapshot($config),'expectedCaps'=>deploy_scope_caps_hash(deploy_scope_expected(deploy_scope_caps(),true))];
$hit=false;$hook=static function($meta,$id,$key)use(&$hit){if($id===1&&$key===$GLOBALS['wpdb']->prefix.'capabilities'){$hit=true;throw new \RuntimeException('SYNTHETIC_SCOPE_FAULT');}};
add_action('updated_user_meta',$hook,10,3);
try{deploy_scope_mutate(true,$baseline,$config);throw new \RuntimeException('FAULT_NOT_INJECTED');}
catch(\RuntimeException $e){if($e->getMessage()!=='SYNTHETIC_SCOPE_FAULT'){throw $e;}}
finally{remove_action('updated_user_meta',$hook,10);}
deploy_install_require($hit&&deploy_scope_caps_hash(deploy_scope_caps())===$before&&deploy_scope_snapshot($config)===$baseline['snapshot'],'FAULT_ROLLBACK_FAILED');
echo "SCOPES_FAULT_ROLLBACK_VERIFIED\n";
