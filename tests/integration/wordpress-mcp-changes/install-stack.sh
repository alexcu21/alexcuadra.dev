#!/bin/sh
set -eu
export WP_CLI_CACHE_DIR=/tmp/wp-cli-cache
for entry in wp-graphql:2.24.2 advanced-custom-fields:6.8.10 wpgraphql-acf:3.0.0; do
    plugin=${entry%:*}
    version=${entry#*:}
    if wp plugin is-installed "$plugin"; then
        test "$(wp plugin get "$plugin" --field=version)" = "$version"
        wp plugin activate "$plugin"
    else
        wp plugin install "$plugin" --version="$version" --activate
    fi
done
# The tag/versions match the earlier local compatibility lab and recorded site inventory.
if ! wp plugin is-installed wp-graphql-polylang; then
    wp plugin install https://github.com/valu-digital/wp-graphql-polylang/archive/refs/tags/v0.7.1.zip
fi
test "$(wp plugin get wp-graphql-polylang --field=version)" = 0.7.1
cp /changes-lab/stack-fixture.php wp-content/mu-plugins/editorial-stack.php
wp eval 'PLL()->options->set("post_types", ["project"]); PLL()->options->save_all(); foreach ([["en","en_US","English"],["es","es_ES","Español"]] as $l) { if (!in_array($l[0],pll_languages_list(),true)) { PLL()->model->add_language(["slug"=>$l[0],"locale"=>$l[1],"name"=>$l[2],"rtl"=>false,"term_group"=>$l[0]==="en"?0:1]); } } PLL()->options->set("default_lang","en"); PLL()->options->save_all();'
wp plugin activate wp-graphql-polylang
wp eval '$s=get_option("graphql_general_settings",[]); $s["restrict_endpoint_to_logged_in_users"]="on"; update_option("graphql_general_settings",$s);'
