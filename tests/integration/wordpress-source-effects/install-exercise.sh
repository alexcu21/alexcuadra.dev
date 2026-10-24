#!/bin/sh
# Exact prepared script, root confined to a new disposable CLI container.
set -eu
umask 077
phase=setup
trap 'code=$?; if test "$code" -ne 0; then printf "INSTALL_EXERCISE_FAILURE=%s:%s\n" "$phase" "$code" >&2; fi' 0
stage="$HOME/.ac-editorial-deploy/operating-0.1.0-rc.3-20261024"
mkdir -p "$stage" "$HOME/domains/alexcuadra.dev/public_html"
ln -s /var/www/html "$HOME/domains/alexcuadra.dev/public_html/content"
phase=corrupt-archive
printf '%s\n' corrupt > "$stage/candidate.zip"
if sh /deploy-install.sh > "$stage/corrupt.log" 2>&1; then exit 81; fi
cp /operating-candidate.zip "$stage/candidate.zip"
phase=missing-success-receipt
cat > "$stage/inject-postflight.sh" <<'INJECT'
#!/bin/sh
wp(){
    command wp "$@"
    case " $* " in *" plugin install "*) printf '%s\n' injected > /var/www/html/wp-content/plugins/alexcuadra-editorial-operating/extra.txt;; esac
}
. /deploy-install.sh
INJECT
if sh "$stage/inject-postflight.sh" > "$stage/postflight-rejected.log" 2>&1; then exit 87; fi
php -r '$s=file_get_contents($argv[1]);exit(str_contains($s,"INSTALL_UNEXPECTED_FILE")&&!str_contains($s,"END_EDITORIAL_OPERATING_INSTALL_INACTIVE_V1")?0:1);' "$stage/postflight-rejected.log"
set -- "$HOME"/.ac-editorial-deploy/operating-install.*
test "$#" -eq 1
rejected_work="$1"
mv /var/www/html/wp-content/plugins/alexcuadra-editorial-operating /var/www/html/wp-content/rejected-inactive-candidate
phase=valid-install
sh /deploy-install.sh
phase=repeat
if sh /deploy-install.sh > "$stage/repeat.log" 2>&1; then exit 82; fi
phase=baseline-path
set -- "$HOME"/.ac-editorial-deploy/operating-install.*
test "$#" -eq 2
work="$1"
if test "$work" = "$rejected_work"; then work="$2"; fi
baseline="$(cat "$work/baseline.base64")"
root=/var/www/html/wp-content/plugins/alexcuadra-editorial-operating
phase=extra-file
printf '%s\n' unexpected > "$root/extra.txt"
wp --path=/var/www/html eval-file "$work/check.php" after "$baseline" > "$stage/extra.log" 2>&1 || true
php -r '$s=file_get_contents($argv[1]);exit(str_contains($s,"INSTALL_UNEXPECTED_FILE")&&!preg_match("/^OPERATING_INSTALL_INACTIVE_RESULT=/m",$s)?0:1);' "$stage/extra.log"
php -r 'unlink($argv[1]);' "$root/extra.txt"
phase=symlink
ln -s README.md "$root/extra-link"
wp --path=/var/www/html eval-file "$work/check.php" after "$baseline" > "$stage/symlink.log" 2>&1 || true
php -r '$s=file_get_contents($argv[1]);exit(str_contains($s,"INSTALL_PACKAGE_SYMLINK")&&!preg_match("/^OPERATING_INSTALL_INACTIVE_RESULT=/m",$s)?0:1);' "$stage/symlink.log"
php -r 'unlink($argv[1]);' "$root/extra-link"
phase=tamper
cp "$root/README.md" "$work/original-README.md"
printf '%s\n' tamper >> "$root/README.md"
wp --path=/var/www/html eval-file "$work/check.php" after "$baseline" > "$stage/tamper.log" 2>&1 || true
php -r '$s=file_get_contents($argv[1]);exit(str_contains($s,"INSTALL_PACKAGE_HASH")&&!preg_match("/^OPERATING_INSTALL_INACTIVE_RESULT=/m",$s)?0:1);' "$stage/tamper.log"
cp "$work/original-README.md" "$root/README.md"
phase=missing-file
mv "$root/README.md" "$work/missing-README.md"
wp --path=/var/www/html eval-file "$work/check.php" after "$baseline" > "$stage/missing.log" 2>&1 || true
php -r '$s=file_get_contents($argv[1]);exit(str_contains($s,"INSTALL_MISSING_FILE")&&!preg_match("/^OPERATING_INSTALL_INACTIVE_RESULT=/m",$s)?0:1);' "$stage/missing.log"
mv "$work/missing-README.md" "$root/README.md"
phase=restored-verification
wp --path=/var/www/html eval-file "$work/check.php" after "$baseline"
phase=baseline-permissions
test "$(stat -c '%a' "$work")" = 700
test "$(stat -c '%a' "$work/baseline.base64")" = 600
printf '%s\n' 'SOURCE_INACTIVE_DEPLOYMENT_RESULT={"checks":9,"missingSuccessReceiptRefused":true,"passed":true,"exactPreparedScript":true,"corruptArchiveRejected":true,"repeatRefused":true,"extraSymlinkTamperedMissingFilesRejected":true,"restoredPackageReverified":true,"baselinePrivate":true,"activation":false,"sshExecuted":false}'
