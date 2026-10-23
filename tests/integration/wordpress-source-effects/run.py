#!/usr/bin/env python3
"""New isolated source/effect lab, cached images and verified code only; never SSH."""
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[3]
HERE = Path(__file__).resolve().parent
SOURCE = Path('/private/tmp/ac-wp-private-restore-_tidfs0c/source')


def main():
    os.umask(0o077)
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
    env = {'WORDPRESS_DB_HOST':'db','WORDPRESS_DB_USER':'wordpress','WORDPRESS_DB_PASSWORD':'synthetic-proposals-only','WORDPRESS_DB_NAME':'wordpress',
           'WORDPRESS_CONFIG_EXTRA': "define('WP_ENVIRONMENT_TYPE','local');define('WP_HOME','http://wordpress');define('WP_SITEURL','http://wordpress');define('AC_EDITORIAL_SOURCE_EXPERIMENT',true);define('DISABLE_WP_CRON',true);define('WP_HTTP_BLOCK_EXTERNAL',true);define('AUTOMATIC_UPDATER_DISABLED',true);define('DISALLOW_FILE_MODS',true);"}
    mounts = [{'type':'volume','source':'wp-data','target':'/var/www/html'},
              {'type':'bind','source':str(HERE),'target':'/integration','read_only':True},
              {'type':'bind','source':str(ROOT/'wordpress/experimental/editorial-source-adapter'),'target':'/experiment','read_only':True},
              {'type':'bind','source':str(ROOT/'docs/wordpress/acf-current-2026-10-18.json'),'target':'/fields.json','read_only':True}]
    for name, service in settings['services'].items():
        if service.get('ports'): raise ValueError('HOST_PORTS_FORBIDDEN')
        run(['docker','image','inspect',service['image']])
        service['logging']={'driver':'none'};service['pull_policy']='never'
        if name in ['wordpress','cli']: service['environment']=env;service['volumes']=list(mounts)
    for plugin in verified:
        settings['services']['cli']['volumes'].append({'type':'bind','source':str(SOURCE/'wp-content/plugins'/plugin),'target':'/verified-'+plugin,'read_only':True})
    config = work/'compose.json';config.write_text(json.dumps(settings))
    compose=['docker','compose','-p',work.name.replace('_','-'),'-f',str(config),'--profile','tools']
    def cli(*args, shell=False):
        command=compose+['run','--rm','--no-deps','--pull','never']
        if shell: command+=['--entrypoint','sh']
        return run(command+['cli',*args])
    started=False
    try:
        started=True;run(compose+['up','-d','--wait','--pull','never','wordpress'])
        cli('-c','mkdir -p wp-content/mu-plugins && cp /integration/fixture.php wp-content/mu-plugins/source-effects-fixture.php && cp -R /verified-advanced-custom-fields wp-content/plugins/advanced-custom-fields && cp -R /verified-litespeed-cache wp-content/plugins/litespeed-cache',shell=True)
        cli('core','install','--url=http://wordpress','--title=SyntheticSourceEffects','--admin_user=effects_admin','--admin_password=synthetic-effects-only','--admin_email=effects@example.invalid','--skip-email')
        cli('plugin','activate','advanced-custom-fields','litespeed-cache')
        output=cli('eval-file','/integration/check.php')
        marker='SOURCE_EFFECTS_MARIADB_RESULT='
        results=[json.loads(line[len(marker):]) for line in output.splitlines() if line.startswith(marker)]
        if len(results)!=1 or results[0].get('passed') is not True or results[0].get('checks',0)<45: raise ValueError('MISSING_RESULT')
        report={'format':'editorial-source-effects-mariadb-v1','result':results[0],'verifiedSourcePlugins':verified,'hostPorts':False,'externalNetwork':False,'sshExecuted':False,'sourceDatabaseImported':False}
        (work/'result.json').write_text(json.dumps(report,indent=2)+'\n')
        print(json.dumps(report,sort_keys=True),flush=True)
    finally:
        if started: run(compose+['stop'])
        log.close()


if __name__=='__main__':
    main()
