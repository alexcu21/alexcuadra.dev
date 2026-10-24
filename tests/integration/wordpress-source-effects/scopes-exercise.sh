#!/bin/sh
set -eu
umask 077
phase=setup
trap 'code=$?; if test "$code" -ne 0; then printf "SCOPES_EXERCISE_FAILURE=%s:%s\n" "$phase" "$code" >&2; fi' 0
stage="$HOME/.ac-editorial-deploy/operating-0.1.0-rc.3-20261024"
mkdir -p "$stage" "$HOME/domains/alexcuadra.dev/public_html"
ln -s /var/www/html "$HOME/domains/alexcuadra.dev/public_html/content"
cp /operating-candidate.zip "$stage/candidate.zip"
sh /deploy-install.sh > "$stage/install.log" 2>&1
sh /activate-storage.sh > "$stage/activation.log" 2>&1
php -r 'exit(str_contains(file_get_contents($argv[1]),"END_EDITORIAL_OPERATING_ACTIVATE_STORAGE_V1")?0:1);' "$stage/activation.log"
phase=missing-pause
if sh /grant-scopes.sh > "$stage/no-pause.log" 2>&1; then exit 91; fi
if sh /accept-storage.sh > "$stage/no-pause-accept.log" 2>&1; then exit 92; fi
phase=package-drift
root=/var/www/html/wp-content/plugins/alexcuadra-editorial-operating
printf extra > "$root/extra.txt"
if sh /grant-scopes.sh --external-writers-stopped > "$stage/drift.log" 2>&1; then exit 93; fi
php -r 'unlink($argv[1]);' "$root/extra.txt"
phase=existing-false-scope
wp --path=/var/www/html eval '$u=get_userdata(2);$u->add_cap("alexcuadra_apply_project_changes",false);'
if sh /grant-scopes.sh --external-writers-stopped > "$stage/defined.log" 2>&1; then exit 94; fi
php -r 'exit(str_contains(file_get_contents($argv[1]),"SCOPES_ALREADY_DEFINED")?0:1);' "$stage/defined.log"
wp --path=/var/www/html eval '$u=get_userdata(2);$u->remove_cap("alexcuadra_apply_project_changes");'
phase=global-scope
wp --path=/var/www/html eval 'get_role("subscriber")->add_cap("alexcuadra_apply_project_changes");'
if sh /grant-scopes.sh --external-writers-stopped > "$stage/role.log" 2>&1; then exit 95; fi
wp --path=/var/www/html eval 'get_role("subscriber")->remove_cap("alexcuadra_apply_project_changes");'
phase=fault-rollback
helper="$(find "$HOME/.ac-editorial-deploy" -path '*/operating-scopes.*/check.php' | head -1)"
config="$(wp --path=/var/www/html config path)"
fault="$(wp --path=/var/www/html --user=1 eval-file /integration/scopes-fault.php "$helper" "$config")"
test "$fault" = SCOPES_FAULT_ROLLBACK_VERIFIED
phase=grant
sh /grant-scopes.sh --external-writers-stopped > "$stage/granted.log" 2>&1
cat "$stage/granted.log"
run="$(php -r '$s=file_get_contents($argv[1]);preg_match("/^OPERATING_SCOPES_RESULT=(.+)$/m",$s,$m);$r=json_decode($m[1]??"",true,32,JSON_THROW_ON_ERROR);if($r["allowedPermissionChecks"]!==6||$r["deniedPermissionChecks"]!==12||!$r["onlyExpectedDirectCapabilitiesChanged"]||$r["editorialDataWritten"]){exit(96);}echo $r["grantRun"];' "$stage/granted.log")"
phase=diagnose-matching
sh /diagnose-scopes.sh "$run" > "$stage/diagnose-matching.log" 2>&1
php -r '$s=file_get_contents($argv[1]);preg_match("/^OPERATING_SCOPES_DIAGNOSIS=(.+)$/m",$s,$m);$r=json_decode($m[1]??"",true,32,JSON_THROW_ON_ERROR);exit($r["matchesOriginalPostflightCondition"]&&!$r["changedSnapshotSections"]&&!$r["cmsWritesPerformed"]&&!$r["acceptancePerformed"]?0:1);' "$stage/diagnose-matching.log"
phase=repeat-grant
if sh /grant-scopes.sh --external-writers-stopped > "$stage/repeat-grant.log" 2>&1; then exit 97; fi
phase=accept
sh /accept-storage.sh --external-writers-stopped > "$stage/accept.log" 2>&1
cat "$stage/accept.log"
php -r '$s=file_get_contents($argv[1]);preg_match("/^OPERATING_STORAGE_ACCEPTANCE_RESULT=(.+)$/m",$s,$m);$r=json_decode($m[1]??"",true,32,JSON_THROW_ON_ERROR);exit($r["acceptance"]["accepted"]&&$r["sourcesRolesPermissionsAndStorageUnchanged"]&&!$r["editorialDataWritten"]?0:98);' "$stage/accept.log"
phase=invalid-revoke
if sh /revoke-scopes.sh '../../outside' > "$stage/invalid-revoke.log" 2>&1; then exit 99; fi
phase=external-changes
wp --path=/var/www/html eval 'global $wpdb;$id=(int)$wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_type=\"project\" ORDER BY ID LIMIT 1");$wpdb->update($wpdb->posts,["post_content"=>"Retain external synthetic change"],["ID"=>$id]);get_userdata(2)->add_cap("synthetic_unrelated_permission");get_userdata(2)->add_cap("alexcuadra_recover_project_changes",false);'
phase=diagnose-drift
sh /diagnose-scopes.sh "$run" > "$stage/diagnose-drift.log" 2>&1
php -r '$s=file_get_contents($argv[1]);preg_match("/^OPERATING_SCOPES_DIAGNOSIS=(.+)$/m",$s,$m);$r=json_decode($m[1]??"",true,32,JSON_THROW_ON_ERROR);exit(!$r["matchesOriginalPostflightCondition"]&&in_array("posts",$r["changedSnapshotSections"],true)&&in_array("usermeta",$r["changedSnapshotSections"],true)&&$r["expectedDirectCapabilitiesMatchByUser"][1]&&!$r["expectedDirectCapabilitiesMatchByUser"][2]&&!$r["cmsWritesPerformed"]&&!$r["acceptancePerformed"]?0:1);' "$stage/diagnose-drift.log"
phase=revoke-and-repeat
sh /revoke-scopes.sh "$run" > "$stage/revoked.log" 2>&1
sh /revoke-scopes.sh "$run" > "$stage/revoked-repeat.log" 2>&1
php -r 'foreach(array_slice($argv,1) as $p){if(!str_contains(file_get_contents($p),"END_EDITORIAL_OPERATING_REVOKE_SCOPES_V1")){exit(1);}}' "$stage/revoked.log" "$stage/revoked-repeat.log"
retained="$(wp --path=/var/www/html eval 'global $wpdb;$u=get_userdata(2);if($wpdb->get_var("SELECT post_content FROM $wpdb->posts WHERE post_type=\"project\" ORDER BY ID LIMIT 1")!=="Retain external synthetic change"||!$u->has_cap("synthetic_unrelated_permission")||($u->caps["alexcuadra_recover_project_changes"]??null)!==false||isset($u->caps["alexcuadra_apply_project_changes"])||get_userdata(1)->has_cap("alexcuadra_approve_public_project_changes")){WP_CLI::error("EXTERNAL_CHANGE_LOST");}WP_CLI::line("EXTERNAL_CHANGES_RETAINED");')"
test "$retained" = EXTERNAL_CHANGES_RETAINED
printf '%s\n' 'SOURCE_SCOPES_DEPLOYMENT_RESULT={"checks":12,"diagnosticChecks":2,"passed":true,"exactPreparedScripts":true,"missingPauseRejected":true,"packageDriftRejected":true,"definedFalseAndGlobalScopeRejected":true,"faultAfterFirstWriteRolledBack":true,"onlyThreeCapabilitiesAdded":true,"repeatGrantRejected":true,"readOnlyInitialAcceptance":true,"badRevocationRunRejected":true,"revocationAndRepeatVerified":true,"externalContentUnrelatedPermissionAndFalseDenialPreserved":true,"sshExecuted":false}'
