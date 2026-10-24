#!/usr/bin/env python3
"""Prepare reviewable inactive-install script from sealed rc.3; never SSH."""
import hashlib
import json
from pathlib import Path
import zipfile
ROOT=Path(__file__).resolve().parents[1]
VERSION='0.1.0-rc.3'
SLUG='alexcuadra-editorial-operating'
STAGE='operating-0.1.0-rc.3-20261024'
def main():
    archive=ROOT/'artifacts/wordpress'/f'{SLUG}-{VERSION}.zip'
    manifest=json.loads(archive.with_suffix('.manifest.json').read_text())
    assert hashlib.sha256(archive.read_bytes()).hexdigest()==manifest['sha256']
    with zipfile.ZipFile(archive) as z:
        admission=z.read(f'{SLUG}/includes/production-admission.php').decode()
    expected={f['path'].removeprefix(SLUG+'/'):{'bytes':f['bytes'],'sha256':f['sha256']} for f in manifest['files']}
    helper=(ROOT/'wordpress/operations/editorial-operating-install-check.php').read_text()
    helper=helper.removeprefix('<?php\n').replace('namespace AlexCuadra\\EditorialSourceExperiment;\n','',1)
    code=admission+"\nconst DEPLOY_INSTALL_SHA256 = '"+manifest['sha256']+"';\nconst DEPLOY_INSTALL_FILES = <<<'PACKAGE_FILES'\n"+json.dumps(expected,separators=(',',':'))+"\nPACKAGE_FILES;\n"+helper
    script='''#!/bin/sh
# Prepared for explicit approval of inactive installation only. Alex executes SSH.
set -eu
umask 077
cd "$HOME/domains/alexcuadra.dev/public_html/content"
archive="$HOME/.ac-editorial-deploy/STAGE/candidate.zip"
test -f "$archive"
test ! -L "$archive"
actual="$(sha256sum "$archive")"
test "${actual%% *}" = ZIP_HASH
test ! -e wp-content/plugins/alexcuadra-editorial-operating
test ! -L wp-content/plugins/alexcuadra-editorial-operating
available_kib="$(df -Pk wp-content/plugins | awk 'END {print $4}')"
case "$available_kib" in ''|*[!0-9]*) exit 1;; esac
test "$available_kib" -ge 16384
work="$(mktemp -d "$HOME/.ac-editorial-deploy/operating-install.XXXXXXXX")"
printf '%s\\n' EDITORIAL_OPERATING_INSTALL_INACTIVE_V1
cat > "$work/check.php" <<'OPERATING_INSTALL_CHECK_PHP'
'''+code+'''OPERATING_INSTALL_CHECK_PHP
before="$(wp --path=. eval-file "$work/check.php" before)"
baseline="$(printf '%s\\n' "$before" | sed -n 's/^OPERATING_INSTALL_BASELINE=//p')"
test -n "$baseline"
printf '%s\\n' "$baseline" > "$work/baseline.base64"
printf '%s\\n' OPERATING_INSTALL_BASELINE_CAPTURED
wp --path=. plugin install "$archive"
wp --path=. plugin get alexcuadra-editorial-operating --fields=name,version,status --format=json
after="$(wp --path=. eval-file "$work/check.php" after "$baseline")"
printf '%s\\n' "$after"
receipt="$(printf '%s\\n' "$after" | sed -n 's/^OPERATING_INSTALL_INACTIVE_RESULT=//p')"
test -n "$receipt"
printf '%s\\n' END_EDITORIAL_OPERATING_INSTALL_INACTIVE_V1
'''
    script=script.replace('STAGE',STAGE).replace('ZIP_HASH',manifest['sha256'])
    target=ROOT/'wordpress/operations/editorial-operating-install-inactive.sh';target.write_text(script)
    print(json.dumps({'script':str(target),'candidateSha256':manifest['sha256'],'activation':False,'connectsToHosting':False}))
if __name__=='__main__':main()
