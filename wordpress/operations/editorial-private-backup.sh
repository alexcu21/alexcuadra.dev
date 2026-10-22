#!/usr/bin/env bash
# Stream private archive to stdout. Executed by Alex only, never by the agent.
set -Eeuo pipefail
umask 077
cms="$HOME/domains/alexcuadra.dev/public_html/content"
cd "$cms"
for tool in wp php mysqldump tar gzip mktemp; do command -v "$tool" >/dev/null; done
test -f wp-load.php
tar_help="$(tar --help)"
[[ "$tar_help" == *'--verbatim-files-from'* ]]
work="$(mktemp -d "$HOME/.ac-editorial-backup.XXXXXXXX")"
case "$work" in "$HOME"/.ac-editorial-backup.*) ;; *) exit 2 ;; esac
stage=prepare
cleanup() {
    status=$?
    if test "$status" -ne 0; then printf 'EDITORIAL_BACKUP_FAILED_STAGE=%s\n' "$stage" >&2; fi
    rm -rf -- "$work"
    exit "$status"
}
trap cleanup EXIT
trap 'exit 129' HUP
trap 'exit 130' INT
trap 'exit 143' TERM
printf 'EDITORIAL_BACKUP_TEMP=%s\n' "$work" >&2
mkdir "$work/backup"
cat > "$work/helper.php" <<'AC_BACKUP_HELPER'
@@PHP_HELPER@@
AC_BACKUP_HELPER
wp --path=. --skip-plugins --skip-themes eval-file "$work/helper.php" prepare "$work" > "$work/bootstrap.log" 2>&1
IFS= read -r dbname < "$work/database-name"
stage=database
# No credentials in argv/env/stdout; the first client option selects a private temporary config.
mysqldump --defaults-extra-file="$work/client.cnf" --single-transaction --quick --skip-lock-tables --hex-blob --routines --events --triggers --no-tablespaces -- "$dbname" |
    php "$work/helper.php" collect-sql "$work"
rm -f -- "$work/client.cnf"
stage=seal
wp --path=. --skip-plugins --skip-themes eval-file "$work/helper.php" verify-schema "$work" >> "$work/bootstrap.log" 2>&1
php "$work/helper.php" seal "$work"
stage=archive
tar --create --gzip --file=- --hard-dereference --no-recursion --null --verbatim-files-from --directory="$cms" --files-from="$work/files.list" \
    --directory="$work" backup backup/manifest.json backup/database.sql
php "$work/helper.php" check-files "$work"
printf '%s\n' 'EDITORIAL_PRIVATE_BACKUP_STREAM_COMPLETE' >&2
