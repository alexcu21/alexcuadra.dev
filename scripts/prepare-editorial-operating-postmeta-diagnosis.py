#!/usr/bin/env python3
"""Build bounded read-only postmeta evidence. Never connect to the hosting."""
import hashlib
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def main():
    original = (ROOT/'wordpress/operations/editorial-operating-grant-scopes.sh').read_text()
    definitions = original.split("<<'OPERATING_SCOPES_CHECK_PHP'\n", 1)[1].split('$phase=$args[0]', 1)[0]
    manifest = json.loads((ROOT/'artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.manifest.json').read_text())
    assert hashlib.sha256((ROOT/'artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.zip').read_bytes()).hexdigest() == manifest['sha256']
    assert "const DEPLOY_INSTALL_SHA256 = '" + manifest['sha256'] + "';" in definitions
    check = (ROOT/'wordpress/operations/editorial-operating-postmeta-check.php').read_text().removeprefix('<?php\n').replace('namespace AlexCuadra\\EditorialSourceExperiment;\n', '', 1)
    wrapper = '''#!/bin/sh
# CMS reads only. Saves hashes in a new private observation, never replacing the grant baseline.
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
work="$(mktemp -d "$HOME/.ac-editorial-deploy/operating-postmeta.XXXXXXXX")"
printf '%s\\n' EDITORIAL_OPERATING_POSTMETA_DIAGNOSIS_V1
cat > "$work/check.php" <<'OPERATING_POSTMETA_PHP'
''' + definitions + check + '''OPERATING_POSTMETA_PHP
config_path="$(wp --path=. config path)"
for mode in capture compare; do
    result="$(wp --path=. --user=1 eval-file "$work/check.php" "$config_path" "$original/baseline.base64" "$run" "$mode" "$work/observation.json")"
    printf '%s\\n' "$result"
    test -n "$(printf '%s\\n' "$result" | sed -n 's/^OPERATING_POSTMETA_DIAGNOSIS=//p')"
done
printf '%s\\n' END_EDITORIAL_OPERATING_POSTMETA_DIAGNOSIS_V1
'''
    (ROOT/'wordpress/operations/editorial-operating-diagnose-postmeta.sh').write_text(wrapper)
    print(json.dumps({'cmsReadOnly': True, 'baselineReplaced': False, 'observations': 2, 'sshExecuted': False}))


if __name__ == '__main__':
    main()
