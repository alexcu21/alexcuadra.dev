#!/usr/bin/env python3
"""New isolated source/effect lab, cached images and verified code only; never SSH."""
import hashlib
import importlib.util
import json
import os
import re
from pathlib import Path
import shutil
import sys
import zipfile
import subprocess
import tempfile

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[3]
HERE = Path(__file__).resolve().parent
SOURCE = Path('/private/tmp/ac-wp-private-restore-_tidfs0c/source')


def main():
    os.umask(0o077)
    full = sys.argv[1:]==["--full-profile"]
    if sys.argv[1:] not in [[],["--full-profile"]]: raise ValueError("INVALID_ARGUMENTS")
    if os.environ.get('DOCKER_HOST') or shutil.disk_usage('/private/tmp').free < 8*1024**3:
        raise ValueError('LOCAL_DOCKER_AND_8_GIB_REQUIRED')
    spec = importlib.util.spec_from_file_location('profile', HERE.parent/'wordpress-proposals-profile/run.py')
    profile = importlib.util.module_from_spec(spec); spec.loader.exec_module(profile)
    provenance = json.loads((SOURCE.parent/'result.json').read_text())
    if provenance.get('restorationVerified') is not True or provenance['archiveSha256'] != profile.ARCHIVE_SHA:
        raise ValueError('VERIFIED_RESTORATION_REQUIRED')
    manifest = json.loads((SOURCE/'backup/manifest.json').read_text())
    verified = {}
    for plugin in ['advanced-custom-fields','litespeed-cache']:
        count, size = profile.verify_component(SOURCE/'wp-content/plugins'/plugin, manifest, f'wp-content/plugins/{plugin}/')
        verified[plugin] = {'files':count,'bytes':size}
    recorded = json.loads((ROOT/'docs/qa/MCP_BACKUP_PROFILE_2026-10-22.json').read_text())
    hosting = None
    if full:
        count,size=profile.verify_plugins(SOURCE/'wp-content/plugins',manifest,recorded)
        verified={'all-source-plugins':{'files':count,'bytes':size}}
        for component in ['themes','mu-plugins']:
            count,size=profile.verify_component(SOURCE/'wp-content'/component,manifest,'wp-content/'+component+'/')
            verified[component]={'files':count,'bytes':size}
        previous=json.loads((ROOT/'docs/qa/MCP_PROPOSALS_HOSTING_COMPONENTS_RESULT_2026-10-22.json').read_text())
        if previous['sourceArchiveSha256']!=profile.ARCHIVE_SHA: raise ValueError('HOSTING_PROVENANCE_MISMATCH')
        hosting=previous['hostingComponents']
        if hosting['stylesheet']!='twentytwentyfour' or hosting['stylesheetVersion']!='1.6' or hosting['template']!='twentytwentyfour' or hosting['templateVersion']!='1.6': raise ValueError('HOSTING_PROFILE_DRIFT')
        # Reuse the already verified metadata result; never re-import source SQL.
        hosting['stylesheet']='twentytwentyfour'; hosting['template']='twentytwentyfour';hosting['version']='1.6'
        css=SOURCE/'wp-content/themes/twentytwentyfour/style.css'
        if 'Version: 1.6' not in css.read_text()[:8192]: raise ValueError('THEME_VERSION_DRIFT')
        for dropin in ['object-cache.php','advanced-cache.php','db.php','sunrise.php','db-error.php','install.php','maintenance.php','php-error.php','fatal-error-handler.php']:
            if 'wp-content/'+dropin in manifest['files']: raise ValueError('SOURCE_DROPIN_REQUIRES_REVIEW')
        package=ROOT/'artifacts/wordpress/alexcuadra-editorial-proposals-0.1.0-rc.2.zip'
        package_meta=json.loads(package.with_suffix('.manifest.json').read_text())
        if package_meta['sha256']!='c31e6814864be0692035de7b46322fc2fc0159d2104e0b4d25404c3b15859f07' or hashlib.sha256(package.read_bytes()).hexdigest()!=package_meta['sha256']: raise ValueError('CANDIDATE_HASH_DRIFT')
        with zipfile.ZipFile(package) as archive:
            for entry in package_meta['files']:
                content=(ROOT/'wordpress/plugins'/entry['path']).read_bytes()
                if archive.read(entry['path'])!=content or hashlib.sha256(content).hexdigest()!=entry['sha256']: raise ValueError('CANDIDATE_FILE_DRIFT')
    work = Path(tempfile.mkdtemp(prefix='ac-source-effects-',dir='/private/tmp'))
    print('PRIVATE_EFFECTS_WORK='+str(work),flush=True)
    log = (work/'private-process.log').open('wb')
    def run(args):
        result = subprocess.run(args,cwd=ROOT,stdout=subprocess.PIPE,stderr=log)
        if result.returncode:
            log.write(result.stdout); log.flush()
            raise RuntimeError('LOCAL_EFFECTS_STAGE_FAILED')
        return result.stdout.decode()
    settings = json.loads(run(['docker','compose','-f',str(HERE.parent/'wordpress-mcp-proposals/compose.yaml'),'--profile','tools','config','--format','json']))
    settings.pop('name',None); settings['services'].pop('seed');settings['volumes'].pop('seed-data')
    for volume in settings['volumes'].values(): volume.pop('name',None)
    for network in settings['networks'].values():
        network.pop('name',None)
        if not network.get('internal'): raise ValueError('INTERNAL_NETWORK_REQUIRED')
    reuse = os.environ.get('AC_SOURCE_EFFECTS_REUSE_NETWORK')
    if reuse:
        if not re.fullmatch(r'ac-source-effects-[a-z0-9-]+_default',reuse): raise ValueError('OWN_SOURCE_NETWORK_REQUIRED')
        observed=json.loads(run(['docker','network','inspect',reuse]))
        if len(observed)!=1: raise ValueError('NETWORK_NOT_FOUND')
        net=observed[0]
        if not net.get('Internal') or net.get('Driver')!='bridge' or net.get('Scope')!='local' or net.get('Containers') or net.get('Labels',{}).get('com.docker.compose.project')!=reuse[:-8]:
            raise ValueError('EMPTY_OWN_INTERNAL_NETWORK_REQUIRED')
        if set(settings['networks'])!={'default'}: raise ValueError('SINGLE_INTERNAL_NETWORK_REQUIRED')
        settings['networks']={'default':{'external':True,'name':reuse}}
    env = {'AC_SOURCE_EFFECTS_HTTP':'synthetic-v1','WORDPRESS_DB_HOST':'db','WORDPRESS_DB_USER':'wordpress','WORDPRESS_DB_PASSWORD':'synthetic-proposals-only','WORDPRESS_DB_NAME':'wordpress',
           'WORDPRESS_CONFIG_EXTRA': "define('WP_ENVIRONMENT_TYPE','local');define('WP_HOME','http://wordpress');define('WP_SITEURL','http://wordpress');define('AC_EDITORIAL_SOURCE_EXPERIMENT',true);define('DISABLE_WP_CRON',true);define('WP_HTTP_BLOCK_EXTERNAL',true);define('WP_ACCESSIBLE_HOSTS','wordpress');define('AUTOMATIC_UPDATER_DISABLED',true);define('DISALLOW_FILE_MODS',true);"}
    mounts = [{'type':'volume','source':'wp-data','target':'/var/www/html'},
              {'type':'bind','source':str(HERE),'target':'/integration','read_only':True},
              {'type':'bind','source':str(ROOT/'wordpress/experimental/editorial-source-adapter'),'target':'/experiment','read_only':True},
              {'type':'bind','source':str(ROOT/'docs/wordpress/acf-current-2026-10-18.json'),'target':'/fields.json','read_only':True}]
    for name, service in settings['services'].items():
        if service.get('ports'): raise ValueError('HOST_PORTS_FORBIDDEN')
        run(['docker','image','inspect',service['image']])
        service['logging']={'driver':'none'};service['pull_policy']='never'
        if name in ['wordpress','cli']: service['environment']=env;service['volumes']=list(mounts)
    if not full:
        for plugin in verified:
            settings['services']['cli']['volumes'].append({'type':'bind','source':str(SOURCE/'wp-content/plugins'/plugin),'target':'/verified-'+plugin,'read_only':True})
    if full:
        env.update(AC_SOURCE_EFFECTS_FULL='verified-backup-v1',AC_SOURCE_EFFECTS_NETWORK='internal-only')
        env['WORDPRESS_CONFIG_EXTRA']=env['WORDPRESS_CONFIG_EXTRA'].replace("'http://wordpress'","'https://content.alexcuadra.dev'")+"define('AC_EDITORIAL_PROPOSALS_SITE','https://content.alexcuadra.dev');"
        # Environment dicts were assigned by reference to both WP and CLI.
        recorded['mainHashes']={p['file']:hashlib.sha256((SOURCE/'wp-content/plugins'/p['file']).read_bytes()).hexdigest() for p in recorded['plugins']}
        recorded['muHashes']={m['file']:m['sha256'] for m in hosting['muPlugins']}
        recorded['candidateFiles']={e['path']:e['sha256'] for e in package_meta['files']}
        profile_file=work/'recorded-profile.json';profile_file.write_text(json.dumps(recorded))
        for service in ['wordpress','cli']:
            settings['services'][service]['volumes'].append({'type':'bind','source':str(profile_file),'target':'/recorded-profile.json','read_only':True})
            settings['services'][service]['volumes'].append({'type':'bind','source':str(ROOT/'wordpress/plugins/alexcuadra-editorial-proposals'),'target':'/candidate-source','read_only':True})
        settings['services']['cli']['volumes']=[v for v in settings['services']['cli']['volumes'] if not v['target'].startswith('/verified-')]
        for component in ['plugins','mu-plugins','themes']:
            settings['services']['cli']['volumes'].append({'type':'bind','source':str(SOURCE/'wp-content'/component),'target':'/verified-'+component,'read_only':True})
    config = work/'compose.json';config.write_text(json.dumps(settings))
    compose=['docker','compose','-p',work.name.replace('_','-'),'-f',str(config),'--profile','tools']
    def cli(*args, shell=False):
        command=compose+['run','--rm','--no-deps','--pull','never']
        if shell: command+=['--entrypoint','sh']
        return run(command+['cli',*args])
    profile_before=profile_after=None
    def read_profile():
        out=cli('eval-file','/integration/full-profile.php')
        marker='SOURCE_FULL_PROFILE_RESULT='
        values=[json.loads(line[len(marker):]) for line in out.splitlines() if line.startswith(marker)]
        if len(values)!=1 or not values[0].get('passed'): raise ValueError('MISSING_PROFILE_RESULT')
        return values[0]
    started=False
    try:
        started=True;run(compose+['up','-d','--wait','--pull','never','wordpress'])
        if not full:
            cli('-c','mkdir -p wp-content/mu-plugins && cp /integration/fixture.php wp-content/mu-plugins/source-effects-fixture.php && cp -R /verified-advanced-custom-fields wp-content/plugins/advanced-custom-fields && cp -R /verified-litespeed-cache wp-content/plugins/litespeed-cache',shell=True)
        else:
            cli('-c','mkdir -p wp-content/mu-plugins && cp -R /verified-plugins/. wp-content/plugins/ && cp -R /verified-mu-plugins/. wp-content/mu-plugins/ && cp -R /verified-themes/. wp-content/themes/ && cp /integration/fixture.php wp-content/mu-plugins/source-effects-fixture.php && cp -R /candidate-source wp-content/plugins/alexcuadra-editorial-proposals',shell=True)
        cli('core','install','--url='+('https://content.alexcuadra.dev' if full else 'http://wordpress'),'--title=SyntheticSourceEffects','--admin_user=effects_admin','--admin_password=synthetic-effects-only','--admin_email=effects@example.invalid','--skip-email')
        if not full:
            cli('plugin','activate','advanced-custom-fields','litespeed-cache')
        else:
            cli('theme','activate','twentytwentyfour')
            for plugin in recorded['plugins']:
                if plugin['active']: cli('plugin','activate',plugin['file'])
            cli('plugin','activate','alexcuadra-editorial-proposals')
            cli('--user=effects_admin','eval','$r=AlexCuadra\\EditorialProposals\\install_storage();if($r!==true){WP_CLI::error("CANDIDATE_STORAGE_FAILED");}')
            cli('eval-file','/integration/full-setup.php')
            profile_before=read_profile()
        output=cli('eval-file','/integration/check.php')
        marker='SOURCE_EFFECTS_MARIADB_RESULT='
        results=[json.loads(line[len(marker):]) for line in output.splitlines() if line.startswith(marker)]
        if len(results)!=1 or results[0].get('passed') is not True or results[0].get('checks',0)<45: raise ValueError('MISSING_RESULT')
        http_output=cli('eval-file','/integration/transport.php')
        http_marker='SOURCE_EFFECTS_HTTP_RESULT='
        http_results=[json.loads(line[len(http_marker):]) for line in http_output.splitlines() if line.startswith(http_marker)]
        if len(http_results)!=1 or http_results[0].get('passed') is not True or http_results[0].get('checks',0)<30:
            raise ValueError('MISSING_HTTP_RESULT')
        bilingual=None;operating=None
        if full:
            bilingual_output=cli('eval-file','/integration/bilingual.php')
            bilingual_marker='SOURCE_EFFECTS_BILINGUAL_RESULT='
            bilingual_results=[json.loads(line[len(bilingual_marker):]) for line in bilingual_output.splitlines() if line.startswith(bilingual_marker)]
            if len(bilingual_results)!=1 or not bilingual_results[0].get('passed') or bilingual_results[0].get('checks',0)<15: raise ValueError('MISSING_BILINGUAL_RESULT')
            bilingual=bilingual_results[0]
            operating_output=cli('eval-file','/integration/operating.php')
            operating_marker='SOURCE_OPERATING_CONTRACT_RESULT='
            operating_results=[json.loads(line[len(operating_marker):]) for line in operating_output.splitlines() if line.startswith(operating_marker)]
            if len(operating_results)!=1 or not operating_results[0].get('passed') or operating_results[0].get('checks',0)<131: raise ValueError('MISSING_OPERATING_RESULT')
            operating=operating_results[0]
            profile_after=read_profile()
            if profile_before!=profile_after: raise ValueError('PROFILE_CHANGED_DURING_TESTS')
            # Read-only source mounts must still match the private manifest.
            profile.verify_plugins(SOURCE/'wp-content/plugins',manifest,recorded)
        report={'operatingContract':operating,'sourceProfileBefore':profile_before,'sourceProfileAfter':profile_after,'fullSourceProfile':full,'bilingual':bilingual,'httpAndProcessDeath':http_results[0],'format':'editorial-source-effects-mariadb-v1','result':results[0],'verifiedSourcePlugins':verified,'hostPorts':False,'externalNetwork':False,'sshExecuted':False,'sourceDatabaseImported':False,'reusedInternalNetwork':reuse is not None}
        (work/'result.json').write_text(json.dumps(report,indent=2)+'\n')
        print(json.dumps(report,sort_keys=True),flush=True)
    finally:
        if started: run(compose+['stop'])
        log.close()


if __name__=='__main__':
    main()
