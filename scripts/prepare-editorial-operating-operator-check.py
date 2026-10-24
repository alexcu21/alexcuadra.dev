#!/usr/bin/env python3
"""Build the read-only live operator check. Never connect to the server."""
import hashlib
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def main():
    grant = (ROOT / 'wordpress/operations/editorial-operating-grant-scopes.sh').read_text()
    definitions = grant.split("<<'OPERATING_SCOPES_CHECK_PHP'\n", 1)[1].split('$phase=$args[0]', 1)[0]
    manifest = json.loads((ROOT / 'artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.manifest.json').read_text())
    assert hashlib.sha256((ROOT / 'artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.zip').read_bytes()).hexdigest() == manifest['sha256']
    assert "const DEPLOY_INSTALL_SHA256 = '" + manifest['sha256'] + "';" in definitions
    check = (ROOT / 'wordpress/operations/editorial-operating-operator-check.php').read_text().removeprefix('<?php\n').replace('namespace AlexCuadra\\EditorialSourceExperiment;\n', '', 1)
    wrapper = '''#!/bin/sh
# CMS reads only; retains a small private helper outside the public directory.
set -eu
umask 077
test "$#" -eq 0
cd "$HOME/domains/alexcuadra.dev/public_html/content"
test -d "$HOME/.ac-editorial-deploy"
test ! -L "$HOME/.ac-editorial-deploy"
work="$(mktemp -d "$HOME/.ac-editorial-deploy/operating-operator.XXXXXXXX")"
printf '%s\\n' EDITORIAL_OPERATING_OPERATOR_CHECK_V1
cat > "$work/check.php" <<'OPERATING_OPERATOR_PHP'
''' + definitions + check + '''
foreach(['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities'] as $module){require_once WP_PLUGIN_DIR.'/alexcuadra-editorial-operating/includes/'.$module.'.php';}
echo 'OPERATING_OPERATOR_CHECK_RESULT='.wp_json_encode(deploy_operator_check($args[0]??'')).PHP_EOL;
OPERATING_OPERATOR_PHP
config_path="$(wp --path=. config path)"
result="$(wp --path=. --user=1 eval-file "$work/check.php" "$config_path")"
printf '%s\\n' "$result"
test -n "$(printf '%s\\n' "$result" | sed -n 's/^OPERATING_OPERATOR_CHECK_RESULT=//p')"
printf '%s\\n' END_EDITORIAL_OPERATING_OPERATOR_CHECK_V1
'''
    out = ROOT / 'wordpress/operations/editorial-operating-check-operator.sh'
    out.write_text(wrapper)
    print(json.dumps({'helperBytes': len(wrapper.encode()), 'sshExecuted': False, 'candidateUnchanged': True}))


if __name__ == '__main__':
    main()
