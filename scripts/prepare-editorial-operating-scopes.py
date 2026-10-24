#!/usr/bin/env python3
"""Prepare exact scoped grants, revocation and read-only acceptance; never SSH."""
import hashlib,json
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
def main():
    manifest=json.loads((ROOT/'artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.manifest.json').read_text())
    assert hashlib.sha256((ROOT/'artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.zip').read_bytes()).hexdigest()==manifest['sha256']
    library=(ROOT/'wordpress/operations/editorial-operating-install-check.php').read_text().split('$phase=$args[0]',1)[0]
    defs=(ROOT/'wordpress/operations/editorial-operating-activation-check.php').read_text().split('$phase=$args[0]',1)[0].removeprefix('<?php\n').replace('namespace AlexCuadra\\EditorialSourceExperiment;\n','',1)
    check=(ROOT/'wordpress/operations/editorial-operating-scopes-check.php').read_text().removeprefix('<?php\n').replace('namespace AlexCuadra\\EditorialSourceExperiment;\n','',1)
    files={f['path'].removeprefix('alexcuadra-editorial-operating/'):{'bytes':f['bytes'],'sha256':f['sha256']} for f in manifest['files']}
    code=library+defs+"\nconst DEPLOY_INSTALL_SHA256 = '"+manifest['sha256']+"';\nconst DEPLOY_INSTALL_FILES = <<<'PACKAGE_FILES'\n"+json.dumps(files,separators=(',',':'))+"\nPACKAGE_FILES;\n"+check
    grant='''#!/bin/sh
# Separate authorization: exact three direct scopes. Pause is an operator attestation.
set -eu
umask 077
test "${1-}" = --external-writers-stopped
test "$#" -eq 1
cd "$HOME/domains/alexcuadra.dev/public_html/content"
work="$(mktemp -d "$HOME/.ac-editorial-deploy/operating-scopes.XXXXXXXX")"
run="$(basename "$work")"
printf '%s\\n' EDITORIAL_OPERATING_GRANT_SCOPES_V1
cat > "$work/check.php" <<'OPERATING_SCOPES_CHECK_PHP'
'''+code+'''OPERATING_SCOPES_CHECK_PHP
config_path="$(wp --path=. config path)"
before="$(wp --path=. --user=1 eval-file "$work/check.php" before "$config_path" none "$run" external-writers-stopped)"
baseline="$(printf '%s\\n' "$before" | sed -n 's/^OPERATING_SCOPES_BASELINE=//p')"
test -n "$baseline"
printf '%s\\n' "$baseline" > "$work/baseline.base64"
printf 'OPERATING_GRANT_RUN=%s\\n' "$run"
changed="$(wp --path=. --user=1 eval-file "$work/check.php" grant "$config_path" "$baseline" "$run" external-writers-stopped)"
test "$(printf '%s\\n' "$changed" | sed -n '/^OPERATING_SCOPES_MUTATION_COMMITTED$/p')" = OPERATING_SCOPES_MUTATION_COMMITTED
after="$(wp --path=. --user=1 eval-file "$work/check.php" after "$config_path" "$baseline" "$run" external-writers-stopped)"
printf '%s\\n' "$after"
test -n "$(printf '%s\\n' "$after" | sed -n 's/^OPERATING_SCOPES_RESULT=//p')"
printf '%s\\n' END_EDITORIAL_OPERATING_GRANT_SCOPES_V1
'''
    accept='''#!/bin/sh
# Read-only global initial acceptance; never infer pause from elapsed time or availability.
set -eu
umask 077
test "${1-}" = --external-writers-stopped
test "$#" -eq 1
cd "$HOME/domains/alexcuadra.dev/public_html/content"
work="$(mktemp -d "$HOME/.ac-editorial-deploy/operating-accept.XXXXXXXX")"
printf '%s\\n' EDITORIAL_OPERATING_ACCEPT_STORAGE_V1
cat > "$work/check.php" <<'OPERATING_SCOPES_CHECK_PHP'
'''+code+'''OPERATING_SCOPES_CHECK_PHP
config_path="$(wp --path=. config path)"
after="$(wp --path=. --user=1 eval-file "$work/check.php" accept "$config_path" none initial-acceptance external-writers-stopped)"
printf '%s\\n' "$after"
test -n "$(printf '%s\\n' "$after" | sed -n 's/^OPERATING_STORAGE_ACCEPTANCE_RESULT=//p')"
printf '%s\\n' "$after" > "$work/acceptance.result"
printf '%s\\n' END_EDITORIAL_OPERATING_ACCEPT_STORAGE_V1
'''
    revoke='''#!/bin/sh
# Revoke only the three new true direct scopes; keep all other permissions/data.
set -eu
umask 077
run="${1-}"
case "$run" in operating-scopes.*) ;; *) exit 1;; esac
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
printf '%s\\n' EDITORIAL_OPERATING_REVOKE_SCOPES_V1
config_path="$(wp --path=. config path)"
before="$(wp --path=. --user=1 eval-file "$work/check.php" before-revoke "$config_path" none "$run")"
baseline="$(printf '%s\\n' "$before" | sed -n 's/^OPERATING_SCOPES_BASELINE=//p')"
test -n "$baseline"
record="$(mktemp "$work/revocation-baseline.XXXXXXXX")"
printf '%s\\n' "$baseline" > "$record"
changed="$(wp --path=. --user=1 eval-file "$work/check.php" revoke "$config_path" "$baseline" "$run")"
test "$(printf '%s\\n' "$changed" | sed -n '/^OPERATING_SCOPES_MUTATION_COMMITTED$/p')" = OPERATING_SCOPES_MUTATION_COMMITTED
after="$(wp --path=. --user=1 eval-file "$work/check.php" revoked "$config_path" "$baseline" "$run")"
printf '%s\\n' "$after"
test -n "$(printf '%s\\n' "$after" | sed -n 's/^OPERATING_SCOPES_REVOKED=//p')"
printf '%s\\n' END_EDITORIAL_OPERATING_REVOKE_SCOPES_V1
'''
    for name,text in [('editorial-operating-grant-scopes.sh',grant),('editorial-operating-accept-storage.sh',accept),('editorial-operating-revoke-scopes.sh',revoke)]: (ROOT/'wordpress/operations'/name).write_text(text)
    print(json.dumps({'candidateSha256':manifest['sha256'],'scopes':[{'user':1,'scope':'approve'},{'user':2,'scope':'apply'},{'user':2,'scope':'recover'}],'externalPauseRequired':True,'connectsToHosting':False}))
if __name__=='__main__':main()
