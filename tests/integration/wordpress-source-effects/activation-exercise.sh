#!/bin/sh
set -eu
umask 077
phase=setup
trap 'code=$?; if test "$code" -ne 0; then printf "ACTIVATION_EXERCISE_FAILURE=%s:%s\n" "$phase" "$code" >&2; fi' 0
stage="$HOME/.ac-editorial-deploy/operating-0.1.0-rc.3-20261024"
mkdir -p "$stage" "$HOME/domains/alexcuadra.dev/public_html"
ln -s /var/www/html "$HOME/domains/alexcuadra.dev/public_html/content"
cp /operating-candidate.zip "$stage/candidate.zip"
sh /deploy-install.sh > "$stage/install.log" 2>&1
php -r '$s=file_get_contents($argv[1]);exit(str_contains($s,"END_EDITORIAL_OPERATING_INSTALL_INACTIVE_V1")?0:1);' "$stage/install.log"
root=/var/www/html/wp-content/plugins/alexcuadra-editorial-operating
phase=package-drift
printf '%s\n' extra > "$root/extra.txt"
if sh /activate-storage.sh > "$stage/package-drift.log" 2>&1; then exit 91; fi
php -r 'unlink($argv[1]);' "$root/extra.txt"
phase=existing-grant
wp --path=/var/www/html eval '$u=get_userdata(2);$u->add_cap("alexcuadra_apply_project_changes");'
if sh /activate-storage.sh > "$stage/grant.log" 2>&1; then exit 92; fi
wp --path=/var/www/html eval '$u=get_userdata(2);$u->remove_cap("alexcuadra_apply_project_changes");'
phase=wrong-optin
wp --path=/var/www/html config set AC_EDITORIAL_OPERATING_SITE https://wrong.invalid --type=constant
if sh /activate-storage.sh > "$stage/optin.log" 2>&1; then exit 93; fi
wp --path=/var/www/html config delete AC_EDITORIAL_OPERATING_SITE --type=constant
phase=activate-migrate
sh /activate-storage.sh > "$stage/active.log" 2>&1
cat "$stage/active.log"
run="$(php -r '$s=file_get_contents($argv[1]);preg_match("/^OPERATING_ACTIVATION_STORAGE_RESULT=(.+)$/m",$s,$m);$r=json_decode($m[1]??"",true,32,JSON_THROW_ON_ERROR);if(!$r["active"]||!$r["newStorageEmpty"]||$r["tableCount"]!==6||$r["permissionDenialsVerified"]!==18||!$r["otherConfigCodeUnchanged"]||$r["capabilitiesAdded"]||$r["editorialDataWritten"]||$r["globalStorageAcceptancePerformed"]){exit(94);}echo $r["activationRun"];' "$stage/active.log")"
test -n "$run"
phase=repeat-activation
if sh /activate-storage.sh > "$stage/repeat.log" 2>&1; then exit 95; fi
phase=migration-replay
replay="$(wp --path=/var/www/html --user=1 eval '$c=AlexCuadra\EditorialOperatingPackage\controller();$m=$c->install();if($m["phase"]!=="ready"){WP_CLI::error("REPLAY_FAILED");}WP_CLI::line("READY_REPLAY_VERIFIED");')"
test "$replay" = READY_REPLAY_VERIFIED
phase=bad-rollback-run
if sh /disable-operating.sh '../../outside' > "$stage/bad-run.log" 2>&1; then exit 96; fi
phase=external-edits-and-grants
wp --path=/var/www/html eval 'global $wpdb;$id=(int)$wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_type=\"project\" ORDER BY ID LIMIT 1");$wpdb->update($wpdb->posts,["post_content"=>"External synthetic change to retain"],["ID"=>$id]);$u=get_userdata(2);$u->add_cap("alexcuadra_apply_project_changes");'
phase=rollback
sh /disable-operating.sh "$run" > "$stage/disabled.log" 2>&1
cat "$stage/disabled.log"
php -r '$s=file_get_contents($argv[1]);preg_match("/^OPERATING_DISABLE_RESULT=(.+)$/m",$s,$m);$r=json_decode($m[1]??"",true,32,JSON_THROW_ON_ERROR);exit(!$r["active"]&&$r["siteOptinAbsent"]&&$r["tablesRetained"]===6&&$r["otherConfigCodeUnchanged"]&&!$r["tablesDropped"]?0:97);' "$stage/disabled.log"
phase=external-changes-retained
retained="$(wp --path=/var/www/html eval 'global $wpdb;$body=$wpdb->get_var("SELECT post_content FROM $wpdb->posts WHERE post_type=\"project\" ORDER BY ID LIMIT 1");if($body!=="External synthetic change to retain"||!get_userdata(2)->has_cap("alexcuadra_apply_project_changes")){WP_CLI::error("EXTERNAL_CHANGE_LOST");}WP_CLI::line("EXTERNAL_CHANGES_RETAINED");')"
test "$retained" = EXTERNAL_CHANGES_RETAINED
phase=repeat-rollback
sh /disable-operating.sh "$run" > "$stage/disabled-repeat.log" 2>&1
php -r '$s=file_get_contents($argv[1]);exit(str_contains($s,"END_EDITORIAL_OPERATING_DISABLE_V1")?0:98);' "$stage/disabled-repeat.log"
printf '%s\n' 'SOURCE_ACTIVATION_DEPLOYMENT_RESULT={"checks":10,"externalEditsAndGrantsPreserved":true,"passed":true,"exactPreparedScripts":true,"packageGrantAndOptinDriftRejected":true,"sixEmptyTablesVerified":true,"permissionDenialsVerified":18,"migrationReplayVerified":true,"repeatActivationRefused":true,"badRollbackRunRejected":true,"rollbackAndRepeatVerified":true,"configurationSemanticsPreserved":true,"newCapabilitiesAdded":false,"sshExecuted":false}'
