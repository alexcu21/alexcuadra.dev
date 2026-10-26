#!/bin/sh
set -eu
mode=${1:-cli}
case "$mode" in cli|--transport) ;; *) exit 2 ;; esac
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
if [ "$mode" = --transport ]; then
  compose run --rm --no-deps --entrypoint sh cli -c 'cp /proposal-lab/http-plugin.php wp-content/mu-plugins/blog-proposals-http.php'
fi
phase(){
  if ! report=$(compose run --rm --no-deps cli eval-file "/proposal-lab/$1.php"); then
    printf '%s\n' "$report" >&2; exit 1
  fi
  if ! printf '%s\n' "$report" | python3 tests/integration/wordpress-blog-proposals/verify-report.py "$2"; then
    printf '%s\n' "$report" >&2; exit 1
  fi
}
phase check EDITORIAL_BLOG_PROPOSAL_WP_RESULT=
if [ "$mode" = --transport ]; then
  phase failures EDITORIAL_BLOG_PROPOSAL_FAULT_RESULT=
  phase http EDITORIAL_BLOG_PROPOSAL_HTTP_RESULT=
fi
