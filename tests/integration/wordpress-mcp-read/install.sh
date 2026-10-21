#!/bin/sh
set -eu
export WP_CLI_CACHE_DIR=/tmp/wp-cli-cache
mkdir -p wp-content/mu-plugins wp-content/plugins/alexcuadra-editorial-read
cp /lab/fixture.php wp-content/mu-plugins/editorial-read-lab.php
cp /read-plugin/alexcuadra-editorial-read.php wp-content/plugins/alexcuadra-editorial-read/
polylang_version=${EDITORIAL_LAB_POLYLANG_VERSION:-3.8.11}
case "$polylang_version" in 3.8.11|3.9.0) ;; *) exit 2 ;; esac
for entry in "polylang:$polylang_version" mcp-adapter:0.7.1; do
    plugin=${entry%:*}
    version=${entry#*:}
    if wp plugin is-installed "$plugin"; then
        test "$(wp plugin get "$plugin" --field=version)" = "$version"
        wp plugin activate "$plugin"
    else
        wp plugin install "$plugin" --version="$version" --activate
    fi
done
wp plugin activate alexcuadra-editorial-read
