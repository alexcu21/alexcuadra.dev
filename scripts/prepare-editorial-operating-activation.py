#!/usr/bin/env python3
"""Prepare opt-in/activation/additive schema and disabling scripts; never SSH."""
import hashlib
import json
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
SLUG='alexcuadra-editorial-operating'
VERSION='0.1.0-rc.3'
def main():
    archive=ROOT/'artifacts/wordpress'/f'{SLUG}-{VERSION}.zip'
    manifest=json.loads(archive.with_suffix('.manifest.json').read_text());assert hashlib.sha256(archive.read_bytes()).hexdigest()==manifest['sha256']
    expected={f['path'].removeprefix(SLUG+'/'):{'bytes':f['bytes'],'sha256':f['sha256']} for f in manifest['files']}
    library=(ROOT/'wordpress/operations/editorial-operating-install-check.php').read_text().split("$phase=$args[0]",1)[0]
    check=(ROOT/'wordpress/operations/editorial-operating-activation-check.php').read_text().removeprefix('<?php\n').replace('namespace AlexCuadra\\EditorialSourceExperiment;\n','',1)
    code=library+"\nconst DEPLOY_INSTALL_SHA256 = '"+manifest['sha256']+"';\nconst DEPLOY_INSTALL_FILES = <<<'PACKAGE_FILES'\n"+json.dumps(expected,separators=(',',':'))+"\nPACKAGE_FILES;\n"+check
    activate='''#!/bin/sh
# Prepared for separate authorization: site opt-in + activation + six empty tables, no grants.
set -eu
umask 077
cd "$HOME/domains/alexcuadra.dev/public_html/content"
test -d "$HOME/.ac-editorial-deploy"
work="$(mktemp -d "$HOME/.ac-editorial-deploy/operating-activate.XXXXXXXX")"
run="$(basename "$work")"
printf '%s\\n' EDITORIAL_OPERATING_ACTIVATE_STORAGE_V1
cat > "$work/check.php" <<'OPERATING_ACTIVATION_CHECK_PHP'
'''+code+'''OPERATING_ACTIVATION_CHECK_PHP
config_path="$(wp --path=. config path)"
before="$(wp --path=. --user=1 eval-file "$work/check.php" before "$config_path")"
baseline="$(printf '%s\\n' "$before" | sed -n 's/^OPERATING_ACTIVATION_BASELINE=//p')"
test -n "$baseline"
printf '%s\\n' "$baseline" > "$work/baseline.base64"
printf '%s\\n' OPERATING_ACTIVATION_BASELINE_CAPTURED
printf 'OPERATING_ACTIVATION_RUN=%s\\n' "$run"
wp --path=. config set AC_EDITORIAL_OPERATING_SITE 'https://content.alexcuadra.dev' --type=constant
wp --path=. plugin activate alexcuadra-editorial-operating
migration="$(wp --path=. --user=1 eval-file "$work/check.php" migrate "$config_path" "$baseline" "$run")"
printf '%s\\n' "$migration"
test "$(printf '%s\\n' "$migration" | sed -n '/^OPERATING_MIGRATION_READY$/p')" = OPERATING_MIGRATION_READY
after="$(wp --path=. --user=1 eval-file "$work/check.php" after "$config_path" "$baseline" "$run")"
printf '%s\\n' "$after"
receipt="$(printf '%s\\n' "$after" | sed -n 's/^OPERATING_ACTIVATION_STORAGE_RESULT=//p')"
test -n "$receipt"
printf '%s\\n' END_EDITORIAL_OPERATING_ACTIVATE_STORAGE_V1
'''
    disable='''#!/bin/sh
# Disable only this deployment's plugin/opt-in; keep every table/record/backup.
set -eu
umask 077
run="${1-}"
case "$run" in operating-activate.*) ;; *) exit 1;; esac
case "$run" in *[!A-Za-z0-9.-]*) exit 1;; esac
test "${#run}" -le 80
cd "$HOME/domains/alexcuadra.dev/public_html/content"
work="$HOME/.ac-editorial-deploy/$run"
test -d "$work"
test ! -L "$work"
test -f "$work/check.php"
test ! -L "$work/check.php"
test -f "$work/baseline.base64"
test ! -L "$work/baseline.base64"
baseline="$(cat "$work/baseline.base64")"
config_path="$(wp --path=. config path)"
printf '%s\\n' EDITORIAL_OPERATING_DISABLE_V1
before="$(wp --path=. --user=1 eval-file "$work/check.php" pre-rollback "$config_path" "$baseline" "$run")"
present="$(printf '%s\\n' "$before" | sed -n 's/^OPERATING_ROLLBACK_SITE_PRESENT=//p')"
case "$present" in 0|1) ;; *) exit 1;; esac
rollback_baseline="$(printf '%s\\n' "$before" | sed -n 's/^OPERATING_ROLLBACK_BASELINE=//p')"
test -n "$rollback_baseline"
record="$(mktemp "$work/rollback-baseline.XXXXXXXX")"
printf '%s\\n' "$rollback_baseline" > "$record"
wp --path=. plugin deactivate alexcuadra-editorial-operating
if test "$present" = 1; then wp --path=. config delete AC_EDITORIAL_OPERATING_SITE --type=constant; fi
after="$(wp --path=. --user=1 eval-file "$work/check.php" rolled-back "$config_path" "$rollback_baseline" "$run")"
printf '%s\\n' "$after"
receipt="$(printf '%s\\n' "$after" | sed -n 's/^OPERATING_DISABLE_RESULT=//p')"
test -n "$receipt"
printf '%s\\n' END_EDITORIAL_OPERATING_DISABLE_V1
'''
    for name,text in [('editorial-operating-activate-storage.sh',activate),('editorial-operating-disable.sh',disable)]:
        (ROOT/'wordpress/operations'/name).write_text(text)
    print(json.dumps({'candidateSha256':manifest['sha256'],'newScopes':False,'connectsToHosting':False}))
if __name__=='__main__':main()
