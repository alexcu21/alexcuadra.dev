#!/bin/sh
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
printf '%s\n' EDITORIAL_OPERATING_DISABLE_V1
before="$(wp --path=. --user=1 eval-file "$work/check.php" pre-rollback "$config_path" "$baseline" "$run")"
present="$(printf '%s\n' "$before" | sed -n 's/^OPERATING_ROLLBACK_SITE_PRESENT=//p')"
case "$present" in 0|1) ;; *) exit 1;; esac
rollback_baseline="$(printf '%s\n' "$before" | sed -n 's/^OPERATING_ROLLBACK_BASELINE=//p')"
test -n "$rollback_baseline"
record="$(mktemp "$work/rollback-baseline.XXXXXXXX")"
printf '%s\n' "$rollback_baseline" > "$record"
wp --path=. plugin deactivate alexcuadra-editorial-operating
if test "$present" = 1; then wp --path=. config delete AC_EDITORIAL_OPERATING_SITE --type=constant; fi
after="$(wp --path=. --user=1 eval-file "$work/check.php" rolled-back "$config_path" "$rollback_baseline" "$run")"
printf '%s\n' "$after"
receipt="$(printf '%s\n' "$after" | sed -n 's/^OPERATING_DISABLE_RESULT=//p')"
test -n "$receipt"
printf '%s\n' END_EDITORIAL_OPERATING_DISABLE_V1
