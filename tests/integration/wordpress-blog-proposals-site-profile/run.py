#!/usr/bin/env python3
"""Boot the verified code profile with fresh synthetic data; never contact the hosting."""
import hashlib
import json
import os
import re
from pathlib import Path
import shutil
import subprocess
import tarfile
import tempfile
import zipfile

ROOT=Path(__file__).resolve().parents[3]
HERE=Path(__file__).resolve().parent
PROJECT=os.environ.get('BLOG_SITE_PROFILE_PROJECT','alexcuadra-blog-site-profile-20261026')
NETWORK='alexcuadra-blog-proposal-release-20261026-functional-r2_default'
BASE=ROOT/'tests/integration/wordpress-blog-read/compose.yaml'

def digest(path):return hashlib.sha256(path.read_bytes()).hexdigest()
def command(argv,**kwargs):
    result=subprocess.run(argv,text=True,capture_output=True,**kwargs)
    if result.returncode:raise RuntimeError('LAB_COMMAND_FAILED:'+result.stderr[:1500]+result.stdout[:1500])
    return result.stdout

def main():
    os.umask(0o077)
    if not re.fullmatch(r'alexcuadra-blog-site-profile-[a-z0-9-]+',PROJECT):raise ValueError('OWN_PROFILE_PROJECT_REQUIRED')
    if os.environ.get('DOCKER_HOST') or shutil.disk_usage('/private/tmp').free<2*1024**3:raise ValueError('LOCAL_DOCKER_AND_SPACE_REQUIRED')
    inventory=json.loads((ROOT/'docs/qa/blog-proposal-site-code-inventory-2026-10-26.json').read_text())
    pointer=json.loads((ROOT/'node_modules/.cache/editorial-blog-proposals-site-profile/current.json').read_text());source=Path(pointer['directory'])
    if pointer['archiveSha256']!=inventory['sourceArchiveSha256']:raise ValueError('CODE_PROVENANCE_DRIFT')
    archive=Path('/Users/Alex/PrivateBackups/alexcuadra.dev/20261023T000108Z-72f0e69c/wordpress.tar.gz')
    if digest(archive)!=inventory['sourceArchiveSha256']:raise ValueError('ARCHIVE_DRIFT')
    with tarfile.open(archive,'r:gz')as t:manifest=json.load(t.extractfile('backup/manifest.json'))
    expected={n:e for n,e in manifest['files'].items() if n.startswith(('wp-content/plugins/','wp-content/themes/','wp-content/mu-plugins/')) and e['type']=='file'}
    for name,entry in expected.items():
        p=source/name
        if p.is_symlink() or not p.is_file() or p.stat().st_size!=entry['bytes'] or digest(p)!=entry['sha256']:raise ValueError('SOURCE_CODE_DRIFT')
    net=json.loads(command(['docker','network','inspect',NETWORK]))[0]
    if not net['Internal'] or net.get('Containers') or net['Labels'].get('com.docker.compose.project')!='alexcuadra-blog-proposal-release-20261026-functional-r2':raise ValueError('EMPTY_OWN_INTERNAL_NETWORK_REQUIRED')
    controls=ROOT/'node_modules/.cache/editorial-blog-proposals-site-profile/controls';controls.mkdir(parents=True,exist_ok=True,mode=0o700)
    work=Path(tempfile.mkdtemp(prefix='blog-site-profile-',dir=controls));overlays=work/'overlays';overlays.mkdir()
    for item in inventory['plugins'].values():
        origin=item['origin']
        if origin['kind']!='sealed-zip':continue
        path=ROOT/'artifacts/wordpress'/origin['artifact'];meta=json.loads(path.with_suffix('.manifest.json').read_text())
        if digest(path)!=origin['sha256']:raise ValueError('SEALED_ARCHIVE_DRIFT')
        with zipfile.ZipFile(path)as z:
            if sorted(z.namelist())!=sorted(e['path']for e in meta['files']):raise ValueError('SEALED_EXTRA_FILE')
            for e in meta['files']:
                data=z.read(e['path'])
                if hashlib.sha256(data).hexdigest()!=e['sha256']:raise ValueError('SEALED_FILE_DRIFT')
                out=overlays/e['path'];out.parent.mkdir(parents=True,exist_ok=True);out.write_bytes(data)
    receipt=json.loads((ROOT/'docs/qa/blog-proposal-destination-before-2026-10-26.json').read_text())['result']['profile']
    profile=work/'profile.json';profile.write_text(json.dumps(receipt))
    env={'BLOG_SITE_PROFILE_LAB':'verified-code-only','WORDPRESS_CONFIG_EXTRA':"""
define('WP_ENVIRONMENT_TYPE','local');define('WP_HOME','https://content.alexcuadra.dev');define('WP_SITEURL','https://content.alexcuadra.dev');
define('DISABLE_WP_CRON',true);define('WP_HTTP_BLOCK_EXTERNAL',true);define('AUTOMATIC_UPDATER_DISABLED',true);define('DISALLOW_FILE_MODS',true);
define('AC_EDITORIAL_PROPOSALS_SITE','https://content.alexcuadra.dev');define('AC_EDITORIAL_BLOG_READ_SITE','https://content.alexcuadra.dev');define('AC_EDITORIAL_OPERATING_SITE','https://content.alexcuadra.dev');
$_SERVER['HTTP_HOST']='content.alexcuadra.dev';$_SERVER['HTTPS']='on';$_SERVER['SERVER_PORT']=443;
"""}
    env['WORDPRESS_CONFIG_EXTRA']=env['WORDPRESS_CONFIG_EXTRA'].replace('$','$$')
    volumes=[str(source/'wp-content')+':/verified-profile:ro',str(overlays)+':/sealed-overlays:ro',str(HERE)+':/site-profile-lab:ro',
        str(profile)+':/profile-input.json:ro',str(ROOT/'docs/qa/blog-proposal-site-code-inventory-2026-10-26.json')+':/profile-inventory.json:ro',str(ROOT/'wordpress/operations')+':/proposal-operations:ro']
    settings={'services':{name:{'environment':env,'volumes':volumes}for name in ['wordpress','cli']},
        'networks':{'default':{'external':True,'name':NETWORK}},'volumes':{'wp-data':{'driver_opts':{'o':'size=768m'}}}}
    config=work/'compose.json';config.write_text(json.dumps(settings))
    base=['docker','compose','-p',PROJECT,'-f',str(BASE),'-f',str(config)]
    def compose(*args):return command(base+list(args))
    def cli(*args):return compose('run','--rm','--no-deps','cli',*args)
    try:
        compose('up','-d','--wait','wordpress')
        # Initial base image only; copied code cannot run until setup below activates it.
        probe=subprocess.run(base+['run','--rm','--no-deps','cli','core','is-installed'],capture_output=True)
        if probe.returncode==0:raise ValueError('FRESH_SITE_PROFILE_REQUIRED')
        cli('core','install','--url=https://content.alexcuadra.dev','--title=SyntheticSiteProfile','--admin_user=lab_admin','--admin_password=synthetic-site-only','--admin_email=admin@example.test','--skip-email')
        compose('run','--rm','--no-deps','--user','0:0','--entrypoint','sh','cli','-ec',
            'cp -R /verified-profile/plugins/. wp-content/plugins/; cp -R /verified-profile/themes/. wp-content/themes/; mkdir -p wp-content/mu-plugins; cp -R /verified-profile/mu-plugins/. wp-content/mu-plugins/; cp /site-profile-lab/guard.php wp-content/mu-plugins/000-blog-site-guard.php; cp -R /sealed-overlays/. wp-content/plugins/; cp /profile-input.json .lab-profile.json; cp /profile-inventory.json .lab-inventory.json; chown -R 33:33 wp-content .lab-profile.json .lab-inventory.json')
        cli('eval-file','/site-profile-lab/setup.php')
        out=cli('eval-file','/site-profile-lab/check.php')
        marker='BLOG_SITE_PROFILE_BOOT_RESULT=';lines=[l[len(marker):]for l in out.splitlines()if l.startswith(marker)]
        if len(lines)!=1:raise ValueError('MISSING_BOOT_RESULT')
        report=json.loads(lines[0])
        if report['failed']!=0 or report['passed']<=0 or report['passed']!=len(report['cases']):raise ValueError('INCOMPLETE_BOOT_RESULT')
        check=subprocess.run(base+['run','--rm','--no-deps','cli','eval-file','/proposal-operations/blog-proposal-preflight.php','2','before'],capture_output=True,text=True)
        marker2='BLOG_PROPOSAL_PREFLIGHT_RESULT=';lines=[l[len(marker2):]for l in check.stdout.splitlines()if l.startswith(marker2)]
        if check.returncode==0 or len(lines)!=1:raise ValueError('EXPECTED_PROFILE_BLOCK_MISSING')
        blocked=json.loads(lines[0]);failed=[k for k,v in blocked['checks'].items()if v is False]
        if blocked['ready'] or failed!=['testedProfile']:raise ValueError('UNEXPECTED_PREFLIGHT_DIFFERENCES')
        result={'format':'blog-site-profile-bootstrap-v1','codeInventoryArchiveSha256':inventory['sourceArchiveSha256'],'bootstrap':report,
            'blockedPreflight':blocked,'profileBlockReproduced':True,'realCmsChanged':False,'proposalCrudTested':False,
            'hostDatabaseImported':False,'privateFixtureMuExcludedFromLiveIdentityComparison':True,'network':NETWORK,'networkInternal':True,'hostPorts':False}
        (ROOT/'docs/qa/blog-proposal-site-profile-bootstrap-2026-10-26.json').write_text(json.dumps(result,indent=2)+'\n')
        print(json.dumps({'passed':report['passed'],'failed':0,'profileBlockReproduced':True,'newScopeGranted':False,'proposalCrudTested':False}))
    finally:compose('stop')

if __name__=='__main__':main()
