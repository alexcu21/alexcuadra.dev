#!/bin/sh
set -eu
export WP_CLI_CACHE_DIR=/tmp/wp-cli-blog-cache
mkdir -p wp-content/mu-plugins
cp /lab/fixture.php wp-content/mu-plugins/editorial-blog-read-lab.php
for entry in polylang:3.9.1 mcp-adapter:0.7.1; do
  plugin=${entry%:*}
  version=${entry#*:}
  if wp plugin is-installed "$plugin"; then
    test "$(wp plugin get "$plugin" --field=version)" = "$version"
  else
    wp plugin install "/lab-assets/$plugin.$version.zip"
  fi
  wp plugin activate "$plugin"
done
if [ "${BLOG_READ_SKIP_CANDIDATE:-0}" != 1 ]; then
  mkdir -p wp-content/plugins/alexcuadra-editorial-blog-read
  cp /blog-candidate/alexcuadra-editorial-blog-read.php wp-content/plugins/alexcuadra-editorial-blog-read/
  wp plugin activate alexcuadra-editorial-blog-read
fi
