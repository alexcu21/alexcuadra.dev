#!/bin/sh
set -eu
mode=${1:-functional}
case "$mode" in functional|lifecycle|probe) ;; *) exit 2 ;; esac
project=${BLOG_PROPOSAL_RELEASE_PROJECT:-alexcuadra-blog-proposal-release-20261026-$mode}
case "$project" in alexcuadra-blog-proposal-release-*) ;; *) exit 2 ;; esac
compose(){ docker compose -p "$project" -f tests/integration/wordpress-blog-read/compose.yaml -f tests/integration/wordpress-blog-proposals-release/compose.yaml "$@"; }
trap 'compose stop >/dev/null 2>&1 || true' EXIT HUP INT TERM
python3 tests/integration/wordpress-blog-proposals-release/prepare.py
node tests/integration/wordpress-blog-read/prepare-assets.mjs
compose up -d --wait wordpress
if compose run --rm --no-deps cli core is-installed; then echo FRESH_RELEASE_LAB_REQUIRED >&2;exit 1;fi
compose run --rm --no-deps cli core install --url=http://wordpress --title=SyntheticProposalRelease --admin_user=lab_admin --admin_password=synthetic-release-only --admin_email=admin@example.test --skip-email
compose run --rm --no-deps --entrypoint sh cli /lab/install.sh
compose run --rm --no-deps cli plugin install /blog-read-release.zip --activate
if [ "$mode" = probe ];then compose run --rm --no-deps cli eval-file /release-lab/fixture.php metadata;exit 0;fi
preflight(){
  if report=$(compose run --rm --no-deps -e "BLOG_PROPOSALS_ENABLED=$2" cli eval-file /proposal-operations/blog-proposal-preflight.php 2 "$1"); then status=0;else status=$?;fi
  printf '%s\n' "$report"
  printf '%s\n' "$report" | python3 tests/integration/wordpress-blog-proposals-release/verify-preflight.py "$1" "${3:-ready}" "$status"
}
if [ "$mode" = lifecycle ];then
  compose run --rm --no-deps cli eval-file /release-lab/fixture.php setup
  preflight before false
fi
compose run --rm --no-deps cli plugin install /blog-proposal-release.zip
if [ "$mode" = lifecycle ];then preflight after-install false;fi
compose run --rm --no-deps cli plugin activate alexcuadra-editorial-blog-proposals
if [ "$mode" = functional ];then
  for phase in check failures http;do
    case "$phase" in check) marker=EDITORIAL_BLOG_PROPOSAL_WP_RESULT= ;; failures) marker=EDITORIAL_BLOG_PROPOSAL_FAULT_RESULT= ;; http) marker=EDITORIAL_BLOG_PROPOSAL_HTTP_RESULT= ;; esac
    if ! report=$(compose run --rm --no-deps cli eval-file "/package-lab/$phase.php");then printf '%s\n' "$report" >&2;exit 1;fi
    printf '%s\n' "$report" | python3 tests/integration/wordpress-blog-proposals/verify-report.py "$marker"
  done
  compose run --rm --no-deps cli eval-file /release-lab/contracts.php
else
  preflight after-enable true
  compose run --rm --no-deps cli eval-file /release-lab/fixture.php install-storage
  preflight after-storage true
  compose run --rm --no-deps cli eval-file /release-lab/fixture.php enroll
  preflight after-owner true
  compose run --rm --no-deps cli eval-file /release-lab/fixture.php grant
  preflight after-scope true
  compose run --rm --no-deps cli eval-file /release-lab/fixture.php poison
  preflight after-scope true blocked
  compose run --rm --no-deps cli eval-file /release-lab/fixture.php assert-poison
  compose run --rm --no-deps cli eval-file /release-lab/fixture.php repair
  preflight after-scope true
  compose run --rm --no-deps cli eval-file /release-lab/fixture.php seed-private
  compose run --rm --no-deps cli eval-file /release-lab/fixture.php withdraw
  compose run --rm --no-deps cli plugin deactivate alexcuadra-editorial-blog-proposals
  preflight withdrawn false
  compose run --rm --no-deps cli eval-file /release-lab/fixture.php assert-retained
fi
