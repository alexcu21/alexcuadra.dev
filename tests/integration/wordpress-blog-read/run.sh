#!/bin/sh
set -eu
compose_file=tests/integration/wordpress-blog-read/compose.yaml
compose() { docker compose -f "$compose_file" "$@"; }
node tests/integration/wordpress-blog-read/prepare-assets.mjs
# Stop only this dedicated project, including on a failed assertion. No pruning.
trap 'compose stop >/dev/null 2>&1 || true' EXIT HUP INT TERM
compose up -d --wait wordpress
if ! compose run --rm --no-deps cli core is-installed; then
  compose run --rm --no-deps cli core install --url=http://wordpress --title=SyntheticBlogReadLab \
    --admin_user=lab_admin --admin_password=synthetic-blog-admin-only --admin_email=admin@example.test --skip-email
fi
compose run --rm --no-deps --entrypoint sh cli /lab/install.sh
report=$(compose run --rm --no-deps cli eval-file /lab/check.php)
printf '%s\n' "$report" | python3 tests/integration/wordpress-blog-read/verify-report.py EDITORIAL_BLOG_READ_RESULT=
report=$(compose run --rm --no-deps cli eval-file /lab/http.php)
printf '%s\n' "$report" | python3 tests/integration/wordpress-blog-read/verify-report.py EDITORIAL_BLOG_HTTP_RESULT=
