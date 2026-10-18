#!/bin/sh
set -eu
# Run from the repository root. No production config is mounted or read.
compose_file=tests/integration/wordpress-polylang/compose.yaml
docker compose -f "$compose_file" up -d --wait wordpress
if ! docker compose -f "$compose_file" run --rm --no-deps cli core is-installed; then
  docker compose -f "$compose_file" run --rm --no-deps cli core install \
    --url=http://127.0.0.1:8087 --title=IsolatedSecurityLab --admin_user=lab_admin \
    --admin_password=synthetic-lab-admin-only --admin_email=admin@example.test --skip-email
fi
docker compose -f "$compose_file" run --rm --no-deps --entrypoint sh cli /lab/install-plugins.sh
docker compose -f "$compose_file" run --rm --no-deps cli eval-file /lab/repair-config.php
docker compose -f "$compose_file" run --rm --no-deps cli eval-file /lab/repair-languages.php
mkdir -p .wrangler
docker compose -f "$compose_file" run --rm --no-deps cli eval-file /lab/export-manifest.php > .wrangler/polylang-lab.json
node tests/integration/wordpress-polylang/password-control.mjs
docker compose -f "$compose_file" run --rm --no-deps cli plugin deactivate wp-graphql-polylang
node tests/integration/wordpress-polylang/password-control.mjs
docker compose -f "$compose_file" run --rm --no-deps cli plugin activate wp-graphql-polylang
# Deliberately exits nonzero for unresolved policy failures; never hide them.
node tests/integration/wordpress-polylang/check.mjs
