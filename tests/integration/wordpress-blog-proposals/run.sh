#!/bin/sh
set -eu
project=${BLOG_PROPOSAL_LAB_PROJECT:-alexcuadra-blog-proposals-lab-20261026}
case "$project" in alexcuadra-blog-proposals-lab-*) ;; *) exit 2 ;; esac
compose(){ docker compose -p "$project" -f tests/integration/wordpress-blog-read/compose.yaml -f tests/integration/wordpress-blog-proposals/compose.yaml "$@"; }
trap 'compose stop >/dev/null 2>&1 || true' EXIT HUP INT TERM
node tests/integration/wordpress-blog-read/prepare-assets.mjs
compose up -d --wait wordpress
if compose run --rm --no-deps cli core is-installed; then
  echo FRESH_BLOG_PROPOSAL_LAB_REQUIRED >&2; exit 1
fi
compose run --rm --no-deps cli core install --url=http://wordpress --title=SyntheticBlogProposalLab \
  --admin_user=lab_admin --admin_password=synthetic-proposal-only --admin_email=admin@example.test --skip-email
compose run --rm --no-deps --entrypoint sh cli /lab/install.sh
report=$(compose run --rm --no-deps cli eval-file /proposal-lab/check.php)
if ! printf '%s\n' "$report" | python3 tests/integration/wordpress-blog-read/verify-report.py EDITORIAL_BLOG_PROPOSAL_WP_RESULT=; then
  printf '%s\n' "$report" >&2
  exit 1
fi
