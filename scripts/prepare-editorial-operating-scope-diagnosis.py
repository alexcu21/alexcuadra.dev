#!/usr/bin/env python3
"""Prepare CMS read-only reconciliation against the original grant baseline; never SSH."""
from pathlib import Path
import hashlib
import json

ROOT = Path(__file__).resolve().parents[1]


def main():
    original = (ROOT/'wordpress/operations/editorial-operating-grant-scopes.sh').read_text()
    code = original.split("<<'OPERATING_SCOPES_CHECK_PHP'\n", 1)[1].split('$phase=$args[0]', 1)[0]
    manifest = json.loads((ROOT/'artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.manifest.json').read_text())
    assert hashlib.sha256((ROOT/'artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.zip').read_bytes()).hexdigest() == manifest['sha256']
    assert "const DEPLOY_INSTALL_SHA256 = '" + manifest['sha256'] + "';" in code
    diagnostic = r'''
$config=$args[0]??'';$baselinePath=$args[1]??'';$run=$args[2]??'';
deploy_install_require(is_file($baselinePath)&&!is_link($baselinePath),'DIAG_BASELINE_PATH');
$baseline=json_decode(base64_decode(trim(file_get_contents($baselinePath)),true),true,32,JSON_THROW_ON_ERROR);
deploy_install_require(is_array($baseline['snapshot']??null)&&is_array($baseline['expectedCaps']??null),'DIAG_BASELINE_FORMAT');
deploy_install_verify_files();
foreach(['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities'] as $module){require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/'.$module.'.php';}
deploy_scope_context(false);
$diagnosis=owned_transaction($GLOBALS['wpdb'],function()use($config,$baseline,$run){
    $snapshot=deploy_scope_snapshot($config);$caps=deploy_scope_caps();$hashes=deploy_scope_caps_hash($caps);
    $keys=array_unique(array_merge(array_keys($baseline['snapshot']),array_keys($snapshot)));$changed=[];
    foreach($keys as $key){if(!array_key_exists($key,$baseline['snapshot'])||!array_key_exists($key,$snapshot)||$baseline['snapshot'][$key]!==$snapshot[$key]){$changed[]=$key;}}
    $matches=[];$scopes=[];
    foreach(deploy_scope_map() as $id=>$names){$matches[$id]=isset($baseline['expectedCaps'][$id])&&$hashes[$id]===$baseline['expectedCaps'][$id];foreach($names as $name){$scopes[$id][$name]=$caps[$id][$name]??null;}}
    $strictCaps=$hashes===$baseline['expectedCaps'];$strictSnapshot=$snapshot===$baseline['snapshot'];
    return ['format'=>'editorial-operating-scope-diagnosis-v1','version'=>OPERATING_VERSION,'grantRun'=>$run,
        'expectedDirectCapabilitiesMatchByUser'=>$matches,'plannedDirectScopeValues'=>$scopes,
        'capabilitiesStrictMatch'=>$strictCaps,'snapshotStrictMatch'=>$strictSnapshot,
        'changedSnapshotSections'=>$changed,'snapshotValuesMatchIgnoringKeyOrder'=>!$changed,
        'matchesOriginalPostflightCondition'=>$strictCaps&&$strictSnapshot,
        'cmsWritesPerformed'=>false,'acceptancePerformed'=>false,'privateValuesExported'=>false];
},true);
echo 'OPERATING_SCOPES_DIAGNOSIS='.wp_json_encode($diagnosis).PHP_EOL;
'''
    wrapper = '''#!/bin/sh
# Reads CMS only. Keeps a small private helper on the server; does not change the original baseline.
set -eu
umask 077
run="${1-}"
test "$#" -eq 1
case "$run" in operating-scopes.*) ;; *) exit 1;; esac
case "$run" in *[!A-Za-z0-9.-]*) exit 1;; esac
test "${#run}" -le 80
cd "$HOME/domains/alexcuadra.dev/public_html/content"
original="$HOME/.ac-editorial-deploy/$run"
test -d "$original"
test ! -L "$original"
test -f "$original/baseline.base64"
test ! -L "$original/baseline.base64"
work="$(mktemp -d "$HOME/.ac-editorial-deploy/operating-diagnosis.XXXXXXXX")"
printf '%s\\n' EDITORIAL_OPERATING_SCOPES_DIAGNOSIS_V1
cat > "$work/check.php" <<'OPERATING_DIAGNOSIS_PHP'
''' + code + diagnostic + '''OPERATING_DIAGNOSIS_PHP
config_path="$(wp --path=. config path)"
result="$(wp --path=. --user=1 eval-file "$work/check.php" "$config_path" "$original/baseline.base64" "$run")"
printf '%s\\n' "$result"
test -n "$(printf '%s\\n' "$result" | sed -n 's/^OPERATING_SCOPES_DIAGNOSIS=//p')"
printf '%s\\n' END_EDITORIAL_OPERATING_SCOPES_DIAGNOSIS_V1
'''
    (ROOT/'wordpress/operations/editorial-operating-diagnose-scopes.sh').write_text(wrapper)
    print(json.dumps({'cmsReadOnly':True,'originalBaselineUnchanged':True,'sshExecuted':False}))


if __name__ == '__main__':
    main()
