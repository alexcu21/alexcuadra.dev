#!/bin/sh
set -eu
project=${BLOG_PACKAGE_LAB_PROJECT:-alexcuadra-blog-read-package-lab-20261026}
case "$project" in alexcuadra-blog-read-package-lab-*) ;; *) exit 2 ;; esac
compose(){ docker compose -p "$project" -f tests/integration/wordpress-blog-read/compose.yaml -f tests/integration/wordpress-blog-read/compose.release.yaml "$@"; }
trap 'compose stop >/dev/null 2>&1 || true' EXIT HUP INT TERM
node tests/integration/wordpress-blog-read/prepare-assets.mjs
compose up -d --wait wordpress
if compose run --rm --no-deps cli core is-installed; then
  echo FRESH_PACKAGE_LAB_REQUIRED >&2; exit 1
fi
compose run --rm --no-deps cli core install --url=http://wordpress --title=SyntheticBlogPackageLab \
  --admin_user=lab_admin --admin_password=synthetic-package-only --admin_email=admin@example.test --skip-email
compose run --rm --no-deps --entrypoint sh cli /lab/install.sh
compose run --rm --no-deps cli eval-file /lab/release-fixture.php setup
preflight(){
  report=$(compose run --rm --no-deps -e "BLOG_LAB_ENABLED=$2" cli eval-file /blog-preflight.php 2 "$1")
  printf '%s\n' "$report" | python3 tests/integration/wordpress-blog-read/verify-preflight.py "$1"
}
preflight before false
compose run --rm --no-deps cli plugin install /blog-release.zip
preflight after-install false
compose run --rm --no-deps cli plugin activate alexcuadra-editorial-blog-read
preflight after-enable true
compose run --rm --no-deps cli eval-file /lab/release-fixture.php grant
preflight after-scope true
report=$(compose run --rm --no-deps cli eval-file /lab/check.php)
printf '%s\n' "$report" | python3 tests/integration/wordpress-blog-read/verify-report.py EDITORIAL_BLOG_READ_RESULT=
report=$(compose run --rm --no-deps cli eval-file /lab/http.php)
printf '%s\n' "$report" | python3 tests/integration/wordpress-blog-read/verify-report.py EDITORIAL_BLOG_HTTP_RESULT=
compose run --rm --no-deps cli eval-file /lab/release-fixture.php cleanup
compose run --rm --no-deps cli plugin deactivate alexcuadra-editorial-blog-read
preflight after-install false
