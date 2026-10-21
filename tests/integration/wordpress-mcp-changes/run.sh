#!/bin/sh
set -eu
mode=${1:-all}
case "$mode" in all|--http-only|--graphql-stack|--application-stack) ;; *) exit 2 ;; esac
compose() {
    if [ "$mode" = --application-stack ]; then
        docker compose -f tests/integration/wordpress-mcp-read/compose.yaml \
            -f tests/integration/wordpress-mcp-read/compose.site-stack.yaml \
            -f tests/integration/wordpress-mcp-changes/compose.yaml \
            -f tests/integration/wordpress-mcp-changes/compose.graphql.yaml \
            -f tests/integration/wordpress-mcp-changes/compose.applications.yaml "$@"
        return
    fi
    if [ "$mode" = --graphql-stack ]; then
        docker compose -f tests/integration/wordpress-mcp-read/compose.yaml \
            -f tests/integration/wordpress-mcp-read/compose.site-stack.yaml \
            -f tests/integration/wordpress-mcp-changes/compose.yaml \
            -f tests/integration/wordpress-mcp-changes/compose.graphql.yaml "$@"
        return
    fi
    docker compose -f tests/integration/wordpress-mcp-read/compose.yaml \
        -f tests/integration/wordpress-mcp-read/compose.site-stack.yaml \
        -f tests/integration/wordpress-mcp-changes/compose.yaml "$@"
}
compose up -d --wait wordpress
if ! compose run --rm --no-deps cli core is-installed; then
    compose run --rm --no-deps cli core install --url=http://wordpress --title=SyntheticChangesLab \
        --admin_user=lab_admin --admin_password=synthetic-change-admin-only --admin_email=admin@example.test --skip-email
fi
compose run --rm --no-deps --entrypoint sh cli /lab/install.sh
if [ "$mode" = --graphql-stack ] || [ "$mode" = --application-stack ]; then
    compose run --rm --no-deps --entrypoint sh cli /changes-lab/install-stack.sh
fi
compose run --rm --no-deps --entrypoint sh cli -c 'mkdir -p wp-content/plugins/alexcuadra-editorial-changes && cp /changes-plugin/*.php wp-content/plugins/alexcuadra-editorial-changes/ && wp plugin activate alexcuadra-editorial-changes && php -l /changes-plugin/alexcuadra-editorial-changes.php'
compose run --rm --no-deps --entrypoint sh cli -c 'cp /changes-lab/http-barrier.php wp-content/mu-plugins/changes-http-race.php && php -l /changes-lab/http.php'
if [ "$mode" != --http-only ]; then
    compose run --rm --no-deps --entrypoint sh cli -c 'php /changes-lab/guard.php production && php /changes-lab/guard.php other-origin'
    compose run --rm --no-deps cli eval-file /changes-lab/check.php
    compose run --rm --no-deps cli eval-file /changes-lab/history.php
    compose run --rm --no-deps cli eval-file /changes-lab/quotas.php
fi
compose run --rm --no-deps cli eval-file /changes-lab/http.php
if [ "$mode" = --graphql-stack ] || [ "$mode" = --application-stack ]; then
    compose run --rm --no-deps cli eval-file /changes-lab/stack.php
    compose run --rm --no-deps cli eval-file /changes-lab/reviews.php
    if [ "$mode" = --application-stack ]; then
        compose run --rm --no-deps cli eval-file /changes-lab/applications.php
    fi
fi
