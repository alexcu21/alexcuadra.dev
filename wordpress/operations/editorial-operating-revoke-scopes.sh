#!/bin/sh
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
printf '%s\n' EDITORIAL_OPERATING_REVOKE_SCOPES_V1
config_path="$(wp --path=. config path)"
before="$(wp --path=. --user=1 eval-file "$work/check.php" before-revoke "$config_path" none "$run")"
baseline="$(printf '%s\n' "$before" | sed -n 's/^OPERATING_SCOPES_BASELINE=//p')"
test -n "$baseline"
record="$(mktemp "$work/revocation-baseline.XXXXXXXX")"
printf '%s\n' "$baseline" > "$record"
changed="$(wp --path=. --user=1 eval-file "$work/check.php" revoke "$config_path" "$baseline" "$run")"
test "$(printf '%s\n' "$changed" | sed -n '/^OPERATING_SCOPES_MUTATION_COMMITTED$/p')" = OPERATING_SCOPES_MUTATION_COMMITTED
after="$(wp --path=. --user=1 eval-file "$work/check.php" revoked "$config_path" "$baseline" "$run")"
printf '%s\n' "$after"
test -n "$(printf '%s\n' "$after" | sed -n 's/^OPERATING_SCOPES_REVOKED=//p')"
printf '%s\n' END_EDITORIAL_OPERATING_REVOKE_SCOPES_V1
