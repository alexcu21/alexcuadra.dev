#!/usr/bin/env python3
"""Prepare a bounded, CMS read-only editor-lock hypothesis test; never SSH."""
import hashlib
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def main():
    grant = (ROOT/'wordpress/operations/editorial-operating-grant-scopes.sh').read_text()
    code = grant.split("<<'OPERATING_SCOPES_CHECK_PHP'\n", 1)[1].split('$phase=$args[0]', 1)[0]
    manifest = json.loads((ROOT/'artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.manifest.json').read_text())
    assert hashlib.sha256((ROOT/'artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.zip').read_bytes()).hexdigest() == manifest['sha256']
    assert "const DEPLOY_INSTALL_SHA256 = '" + manifest['sha256'] + "';" in code
    check = (ROOT/'wordpress/operations/editorial-operating-lock-check.php').read_text().removeprefix('<?php\n').replace('namespace AlexCuadra\\EditorialSourceExperiment;\n', '', 1)
    wrapper = '''#!/bin/sh
# Reads CMS only; reconstructs candidate hashes in memory, never writes metadata.
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
work="$(mktemp -d "$HOME/.ac-editorial-deploy/operating-lock.XXXXXXXX")"
printf '%s\\n' EDITORIAL_OPERATING_LOCK_DIAGNOSIS_V1
cat > "$work/check.php" <<'OPERATING_LOCK_PHP'
''' + code + check + '''OPERATING_LOCK_PHP
config_path="$(wp --path=. config path)"
result="$(wp --path=. --user=1 eval-file "$work/check.php" "$config_path" "$original/baseline.base64" "$run")"
printf '%s\\n' "$result"
test -n "$(printf '%s\\n' "$result" | sed -n 's/^OPERATING_LOCK_DIAGNOSIS=//p')"
printf '%s\\n' END_EDITORIAL_OPERATING_LOCK_DIAGNOSIS_V1
'''
    (ROOT/'wordpress/operations/editorial-operating-diagnose-editor-lock.sh').write_text(wrapper)
    print(json.dumps({'cmsReadOnly':True,'boundedHypothesisTest':True,'baselineReplaced':False,'sshExecuted':False}))


if __name__ == '__main__':
    main()
