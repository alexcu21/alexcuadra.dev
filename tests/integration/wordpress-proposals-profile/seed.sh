#!/bin/sh
set -eu
test ! -f /target/.proposals-acceptance-seeded
test -d /source-plugins
mkdir -p /target/wp-content/plugins /target/wp-content/mu-plugins
# All source plugin files were checked against the private backup manifest by the runner.
cp -R /source-plugins/. /target/wp-content/plugins/
cp /acceptance/fixture.php /target/wp-content/mu-plugins/proposals-acceptance.php
mkdir /target/wp-content/plugins/alexcuadra-editorial-proposals
for file in alexcuadra-editorial-proposals.php storage.php README.md; do
    cp "/candidate-source/$file" /target/wp-content/plugins/alexcuadra-editorial-proposals/
done
touch /target/.proposals-acceptance-seeded
chown -R 33:33 /target/wp-content /target/.proposals-acceptance-seeded
