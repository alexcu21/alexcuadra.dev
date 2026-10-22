#!/bin/sh
# Only copy fixed plugin directories from an existing synthetic lab. No config/DB/credentials.
set -eu
case "${ACCEPTANCE_SEED_LAYOUT:-retained-lab}" in
    retained-lab) plugins=/seed/wp-content/plugins ;;
    public-cache-v1) plugins=/seed/plugins ;;
    *) exit 2 ;;
esac
test ! -f /target/.proposals-acceptance-seeded
mkdir -p /target/wp-content/plugins /target/wp-content/mu-plugins
for plugin in polylang mcp-adapter wp-graphql advanced-custom-fields wpgraphql-acf wp-graphql-polylang; do
    test -d "$plugins/$plugin" || { printf 'MISSING_SYNTHETIC_SEED_PLUGIN:%s\n' "$plugin" >&2; exit 1; }
    test ! -L "$plugins/$plugin"
    test ! -e "/target/wp-content/plugins/$plugin"
done
for plugin in polylang mcp-adapter wp-graphql advanced-custom-fields wpgraphql-acf wp-graphql-polylang; do
    cp -R "$plugins/$plugin" /target/wp-content/plugins/
done
cp /acceptance/fixture.php /target/wp-content/mu-plugins/proposals-acceptance.php
test ! -e /target/wp-content/plugins/alexcuadra-editorial-proposals
mkdir /target/wp-content/plugins/alexcuadra-editorial-proposals
for file in alexcuadra-editorial-proposals.php storage.php README.md; do
    cp "/candidate-source/$file" /target/wp-content/plugins/alexcuadra-editorial-proposals/
done
touch /target/.proposals-acceptance-seeded
chown -R 33:33 /target/wp-content /target/.proposals-acceptance-seeded
