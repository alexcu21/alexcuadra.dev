#!/bin/sh
set -eu
export WP_CLI_CACHE_DIR=/tmp/wp-cli-cache
wp plugin install wp-graphql --version=2.24.2 --activate
wp plugin install advanced-custom-fields --version=6.8.10 --activate
wp plugin install wpgraphql-acf --version=3.0.0 --activate
wp plugin install polylang --version=3.8.11 --activate
wp plugin install https://github.com/valu-digital/wp-graphql-polylang/archive/refs/tags/v0.7.1.zip
mkdir -p wp-content/mu-plugins
cp /lab/fixture-plugin.php wp-content/mu-plugins/portfolio-lab.php
wp rewrite structure '/%postname%/'
wp eval-file /lab/setup.php
wp eval-file /lab/seed.php
wp eval-file /lab/seed-terms.php
wp plugin activate wp-graphql-polylang
