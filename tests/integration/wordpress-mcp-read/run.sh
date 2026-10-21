#!/bin/sh
set -eu
# No host HTTP port, production configuration or credentials are mounted.
compose_file=tests/integration/wordpress-mcp-read/compose.yaml
mode=${1:-all}
case "$mode" in all|--http-only|--site-stack) ;; *) exit 2 ;; esac
compose() {
    if [ "$mode" = --site-stack ]; then
        docker compose -f "$compose_file" -f tests/integration/wordpress-mcp-read/compose.site-stack.yaml "$@"
    else
        docker compose -f "$compose_file" "$@"
    fi
}
compose up -d --wait wordpress
if ! compose run --rm --no-deps cli core is-installed; then
    compose run --rm --no-deps cli core install \
        --url=http://wordpress --title=SyntheticEditorialReadLab --admin_user=lab_admin \
        --admin_password=synthetic-read-admin-only --admin_email=admin@example.test --skip-email
fi
compose run --rm --no-deps --entrypoint sh cli /lab/install.sh
if [ "$mode" != --http-only ]; then
    compose run --rm --no-deps cli eval-file /lab/check.php
fi
compose run --rm --no-deps cli eval-file /lab/http.php
