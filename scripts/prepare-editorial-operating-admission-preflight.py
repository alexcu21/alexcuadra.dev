#!/usr/bin/env python3
"""Render metadata-only preflight from the sealed code; never connects to hosting."""
import hashlib
import json
from pathlib import Path
import zipfile
ROOT=Path(__file__).resolve().parents[1]
VERSION='0.1.0-rc.3'
SLUG='alexcuadra-editorial-operating'
def main():
    archive=ROOT/'artifacts/wordpress'/f'{SLUG}-{VERSION}.zip'
    manifest=json.loads(archive.with_suffix('.manifest.json').read_text())
    assert hashlib.sha256(archive.read_bytes()).hexdigest()==manifest['sha256']
    with zipfile.ZipFile(archive) as z:
        source=z.read(f'{SLUG}/includes/production-admission.php').decode()
    payload=r'''
// This evaluates a prospective post-install profile; it never enables the guard.
if(!defined('WP_CLI')||!WP_CLI){throw new \RuntimeException('CLI_REQUIRED');}
$observed=production_profile();$checks=[];
$checks['candidateAbsent']=!isset(get_plugins()['alexcuadra-editorial-operating/alexcuadra-editorial-operating.php']);
$checks['operatingOptinsAbsent']=!defined('AC_EDITORIAL_OPERATING_SITE')&&!defined('AC_EDITORIAL_OPERATING_LOCAL')&&!defined('AC_EDITORIAL_SOURCE_EXPERIMENT');
$prospective=$observed;
$prospective['plugins'][]=['file'=>'alexcuadra-editorial-operating/alexcuadra-editorial-operating.php','version'=>OPERATING_VERSION,'active'=>true,'sha256'=>'PLANNED_MAIN_HASH'];
$prospective['loadedPlugins']['alexcuadra-editorial-operating/alexcuadra-editorial-operating.php']=true;ksort($prospective['loadedPlugins']);
try{validate_production_profile($prospective);$checks['prospectiveRuntimeProfile']=true;}catch(\Throwable $e){$checks['prospectiveRuntimeProfile']=false;}
$type=get_post_type_object('project');$checks['projectPublicAndTranslatable']=$type&&$type->public&&function_exists('pll_is_translated_post_type')&&pll_is_translated_post_type('project');
$field=function_exists('acf_get_field')?acf_get_field('field_676dcb2aec8d4'):null;
$checks['taglineSchemaAvailable']=is_array($field)&&($field['name']??null)==='project_tagline'&&($field['type']??null)==='textarea';
$checks['polylangFiltersAbsent']=true;foreach(['pll_copy_post_metas','is_protected_meta','pll_pre_current_user_can_synchronize_post'] as $hook){if(has_filter($hook)!==false){$checks['polylangFiltersAbsent']=false;}}
global $wpdb,$wp_filter;$tables=[];
foreach(['source_events','source_effects','operating_reviews','operating_approvals','operating_requests','operating_apps'] as $name){$n=$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$wpdb->prefix.'ac_exp_'.$name));if($wpdb->last_error||$n===null){throw new \RuntimeException('METADATA_QUERY_FAILED');}$tables[$name]=(int)$n;}
$checks['operatingTablesAbsent']=array_sum($tables)===0;
$n=$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->options WHERE option_name='ac_exp_operating_storage'");if($wpdb->last_error||$n===null){throw new \RuntimeException('METADATA_QUERY_FAILED');}$checks['operatingMarkerAbsent']=(int)$n===0;
$u=get_userdata(2);$checks['targetBaseline']=$u&&$u->roles===['subscriber'];
foreach(['read','alexcuadra_read_public_projects','alexcuadra_prepare_project_changes'] as $cap){if(!$u||!$u->has_cap($cap)){$checks['targetBaseline']=false;}}
foreach(['alexcuadra_apply_project_changes','alexcuadra_recover_project_changes','alexcuadra_approve_public_project_changes','edit_posts','manage_options'] as $cap){if(!$u||$u->has_cap($cap)){$checks['targetBaseline']=false;}}
$operator=get_userdata(1);$checks['operatorAvailable']=$operator&&$operator->has_cap('manage_options');
$found=[];foreach(($wp_filter['transition_post_status']->callbacks??[]) as $priority=>$entries){foreach($entries as $entry){$fn=$entry['function'];if(is_array($fn)&&is_object($fn[0])&&get_class($fn[0])==='LiteSpeed\\Purge'&&$fn[1]==='purge_publish'){$found[]=[$priority,$entry];}}}
$checks['nativePurgeCallback']=count($found)===1&&$found[0][0]===10&&$found[0][1]['accepted_args']===3&&hash_file('sha256',(new \ReflectionMethod($found[0][1]['function'][0],'purge_publish'))->getFileName())==='b9a53d0221687eafa935245b592138f989c143036b56bce00e633ff8fe5bc5a6';
echo 'EDITORIAL_OPERATING_ADMISSION_PREFLIGHT='.wp_json_encode(['format'=>'editorial-operating-admission-preflight-v1','observedAtUtc'=>gmdate('c'),'candidateVersion'=>OPERATING_VERSION,'candidateSha256'=>'PLANNED_ZIP_HASH','checks'=>$checks,'technicalChecksPassed'=>!in_array(false,array_map('boolval',$checks),true),'observedProfile'=>$observed,'prospectiveProfileOnly'=>true,'installationAuthorized'=>false,'optinChanged'=>false,'ddlExecuted'=>false,'capabilitiesChanged'=>false,'editorialDataWritten'=>false,'credentialsOrContentExported'=>false]).PHP_EOL;
'''
    main=next(f for f in manifest['files'] if f['path']==f'{SLUG}/{SLUG}.php')
    payload=payload.replace('PLANNED_MAIN_HASH',main['sha256']).replace('PLANNED_ZIP_HASH',manifest['sha256'])
    shell='''#!/bin/sh
# Alex executes SSH. Prospective metadata only: no install/config/DDL/grants/export.
set -eu
cd "$HOME/domains/alexcuadra.dev/public_html/content"
test -f wp-load.php
printf '%s\\n' EDITORIAL_OPERATING_ADMISSION_PREFLIGHT_V1
wp --path=. eval-file - <<'EDITORIAL_OPERATING_ADMISSION_PHP'
'''+source+payload+'''EDITORIAL_OPERATING_ADMISSION_PHP
printf '%s\\n' END_EDITORIAL_OPERATING_ADMISSION_PREFLIGHT_V1
'''
    target=ROOT/'wordpress/operations/editorial-operating-admission-preflight.sh'
    target.write_text(shell)
    print(json.dumps({'script':str(target),'candidateVersion':VERSION,'candidateSha256':manifest['sha256'],'connectsToHosting':False}))
if __name__=='__main__':main()
