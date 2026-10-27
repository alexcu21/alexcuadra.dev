#!/usr/bin/env python3
"""Boot the verified code profile with fresh synthetic data; never contact the hosting."""
import hashlib
import argparse
import base64
import importlib.util
import json
import os
import re
from pathlib import Path
import shutil
import subprocess
import sys
import tarfile
import tempfile
import zipfile

ROOT=Path(__file__).resolve().parents[3]
HERE=Path(__file__).resolve().parent
PROJECT=os.environ.get('BLOG_SITE_PROFILE_PROJECT','alexcuadra-blog-site-profile-20261026')
NETWORK='alexcuadra-blog-proposal-release-20261026-functional-r2_default'
BASE=ROOT/'tests/integration/wordpress-blog-read/compose.yaml'
sys.dont_write_bytecode=True

def digest(path):return hashlib.sha256(path.read_bytes()).hexdigest()
def command(argv,**kwargs):
    result=subprocess.run(argv,text=True,capture_output=True,**kwargs)
    if result.returncode:raise RuntimeError('LAB_COMMAND_FAILED:'+result.stderr[:1500]+result.stdout[:1500])
    return result.stdout

def main():
    parser=argparse.ArgumentParser();parser.add_argument('--functional',action='store_true');parser.add_argument('--release',choices=['functional','lifecycle','recovery']);options=parser.parse_args()
    if options.release:options.functional=True
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
    candidate=None;testdir=None
    if options.functional:
        spec=importlib.util.spec_from_file_location('site_candidate',HERE/'candidate.py');module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
        candidate,testdir,policy=module.prepare(work,receipt,inventory)
        if options.release:
            archive=ROOT/'artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.2.zip'
            release_manifest=json.loads(archive.with_suffix('.manifest.json').read_text())
            if digest(archive)!=release_manifest['sha256']:raise ValueError('RELEASE_ARCHIVE_DRIFT')
            for p in candidate.iterdir():p.unlink()
            with zipfile.ZipFile(archive)as z:
                if sorted(z.namelist())!=sorted(e['path']for e in release_manifest['files']):raise ValueError('RELEASE_FILE_LIST_DRIFT')
                for e in release_manifest['files']:
                    data=z.read(e['path'])
                    if hashlib.sha256(data).hexdigest()!=e['sha256']:raise ValueError('RELEASE_ENTRY_DRIFT')
                    (candidate/e['path'].split('/',1)[1]).write_bytes(data)
            (testdir/'contracts.php').write_bytes((ROOT/'tests/integration/wordpress-blog-proposals-release/contracts.php').read_bytes())
            policy={'destinationReceiptSha256':release_manifest['destinationReceiptSha256'],'admissionSha256':digest(candidate/'admission.php'),'mode':'exact-entry-profile-rc2','wholePluginTreesVerified':False}
    env={'BLOG_SITE_PROFILE_LAB':'verified-code-only','WORDPRESS_CONFIG_EXTRA':"""
define('WP_ENVIRONMENT_TYPE','local');define('WP_HOME','https://content.alexcuadra.dev');define('WP_SITEURL','https://content.alexcuadra.dev');
define('DISABLE_WP_CRON',true);define('WP_HTTP_BLOCK_EXTERNAL',true);define('AUTOMATIC_UPDATER_DISABLED',true);define('DISALLOW_FILE_MODS',true);
define('WP_DEBUG_DISPLAY',false);define('WP_DEBUG_LOG','/var/www/html/site-profile-debug.log');
define('AC_EDITORIAL_PROPOSALS_SITE','https://content.alexcuadra.dev');define('AC_EDITORIAL_BLOG_READ_SITE','https://content.alexcuadra.dev');define('AC_EDITORIAL_OPERATING_SITE','https://content.alexcuadra.dev');
$_SERVER['HTTP_HOST']='content.alexcuadra.dev';$_SERVER['HTTPS']='on';$_SERVER['SERVER_PORT']=443;
"""}
    if options.functional:env['WORDPRESS_CONFIG_EXTRA']+="define('AC_BLOG_PROPOSALS_SITE_PROFILE_LAB',true);define('WP_ACCESSIBLE_HOSTS','wordpress');"
    if options.release:
        env['WORDPRESS_CONFIG_EXTRA']+="define('AC_EDITORIAL_BLOG_PROPOSALS_LAB',getenv('BLOG_SITE_RELEASE_ENABLED')==='1');"
        env['BLOG_SITE_RELEASE_ENABLED']='1'
    env['WORDPRESS_CONFIG_EXTRA']=env['WORDPRESS_CONFIG_EXTRA'].replace('$','$$')
    volumes=[str(source/'wp-content')+':/verified-profile:ro',str(overlays)+':/sealed-overlays:ro',str(HERE)+':/site-profile-lab:ro',
        str(profile)+':/profile-input.json:ro',str(ROOT/'docs/qa/blog-proposal-site-code-inventory-2026-10-26.json')+':/profile-inventory.json:ro',str(ROOT/'wordpress/operations')+':/proposal-operations:ro']
    if options.release:
        volumes.extend([str(ROOT/'wordpress/operations/blog-proposal-rc2')+':/release-operations:ro',str(ROOT/'artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.2.zip')+':/site-release.zip:ro'])
    if options.functional:volumes.extend([str(candidate)+':/site-candidate:ro',str(testdir)+':/site-tests:ro'])
    settings={'services':{name:{'environment':env,'volumes':volumes}for name in ['wordpress','cli']},
        'networks':{'default':{'external':True,'name':NETWORK}},'volumes':{'wp-data':{'driver_opts':{'o':'size=768m'}}}}
    if options.release=='recovery':
        for service in ['wordpress','cli']:settings['services'][service]['volumes'].append('recovery-private:/recovery-private')
        settings['volumes']['recovery-private']={'driver_opts':{'type':'tmpfs','device':'tmpfs','o':'size=64m'}}
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
        if not options.functional:
            output=cli('eval-file','/proposal-operations/blog-proposal-code-fingerprint.php')
            prefix='BLOG_PROPOSAL_CODE_FINGERPRINT_RESULT=';items=[line[len(prefix):]for line in output.splitlines()if line.startswith(prefix)]
            if len(items)!=1:raise ValueError('FINGERPRINT_RESULT_MISSING')
            fingerprint=json.loads(items[0])
            if fingerprint['codeMatchesLocalFixture'] is not True or fingerprint['installationApproved'] is not False or fingerprint['mutationsRequested'] is not False:raise ValueError('FINGERPRINT_NOT_VERIFIED')
            (ROOT/'docs/qa/blog-proposal-code-fingerprint-lab-2026-10-26.json').write_text(json.dumps(fingerprint,indent=2)+'\n')
        lifecycle=[]
        def preflight(phase,enabled,ready=True):
            proc=subprocess.run(base+['run','--rm','--no-deps','-e','BLOG_SITE_RELEASE_ENABLED='+('1'if enabled else'0'),'cli','eval-file','/release-operations/blog-proposal-preflight.php','2',phase],capture_output=True,text=True)
            lines=[l[len('BLOG_PROPOSAL_PREFLIGHT_RESULT='):]for l in proc.stdout.splitlines()if l.startswith('BLOG_PROPOSAL_PREFLIGHT_RESULT=')]
            if len(lines)!=1:raise ValueError('RELEASE_PREFLIGHT_MISSING:'+proc.stderr[:1000]+proc.stdout[:1000])
            item=json.loads(lines[0])
            if item['ready'] is not ready or (proc.returncode==0)!=ready:raise ValueError('RELEASE_PREFLIGHT_UNEXPECTED:'+json.dumps(item))
            lifecycle.append(item);print('RC2_PREFLIGHT='+json.dumps({'phase':phase,'ready':ready}),flush=True)
        def fixture(mode):
            out=cli('eval-file','/site-profile-lab/release-lifecycle.php',mode)
            lines=[l[len('SITE_RELEASE_LIFECYCLE_RESULT='):]for l in out.splitlines()if l.startswith('SITE_RELEASE_LIFECYCLE_RESULT=')]
            if len(lines)!=1:raise ValueError('LIFECYCLE_RESULT_MISSING')
            item=json.loads(lines[0])
            if item['failed']!=0 or item['passed']!=len(set(item['cases'])) or not item['passed']:raise ValueError('LIFECYCLE_CASES_INVALID')
            lifecycle.append(item);print('RC2_FIXTURE='+json.dumps(item),flush=True)
        if options.release=='lifecycle':preflight('before',False)
        if options.functional:
            if options.release:
                cli('plugin','install','/site-release.zip')
                compose('run','--rm','--no-deps','--user','0:0','--entrypoint','sh','cli','-ec',
                    'mkdir .site-tests; cp -R /site-tests/. .site-tests/; chown -R 33:33 .site-tests')
                result['archiveInstalledByWpCli']=True
            else:
                compose('run','--rm','--no-deps','--user','0:0','--entrypoint','sh','cli','-ec',
                    'mkdir wp-content/plugins/alexcuadra-editorial-blog-proposals .site-tests; cp -R /site-candidate/. wp-content/plugins/alexcuadra-editorial-blog-proposals/; cp -R /site-tests/. .site-tests/; chown -R 33:33 wp-content/plugins/alexcuadra-editorial-blog-proposals .site-tests')
            if options.release=='lifecycle':preflight('after-install',False)
            cli('plugin','activate','alexcuadra-editorial-blog-proposals')
            reports={}
            if options.release=='recovery':
                compose('run','--rm','--no-deps','--user','0:0','--entrypoint','sh','cli','-ec','chmod 700 /recovery-private; chown 33:33 /recovery-private')
                fixture('install-storage');fixture('enroll')
                output=cli('eval-file','/site-profile-lab/recovery-check.php')
                prefix='BLOG_PROPOSAL_RECOVERY_TEST_RESULT='
                items=[l[len(prefix):]for l in output.splitlines()if l.startswith(prefix)]
                if len(items)!=1:raise ValueError('RECOVERY_TEST_RECEIPT_MISSING')
                checked=json.loads(items[0])
                if checked['failed']!=0 or checked['passed']!=len(set(checked['cases'])) or checked['passed']<30:raise ValueError('RECOVERY_CASES_INCOMPLETE')
                def operator(*args,expected=True,restore_prefix=None):
                    invocation=['eval-file','/release-operations/blog-proposal-backup.php',*args]
                    if restore_prefix:
                        if restore_prefix!='recovery_cli_':raise ValueError('OWN_TEST_PREFIX_REQUIRED')
                        encoded=base64.b64encode(json.dumps(args).encode()).decode()
                        invocation=['eval',"$GLOBALS['wpdb']->prefix='recovery_cli_';$args=json_decode(base64_decode('"+encoded+"'),true);require '/release-operations/blog-proposal-backup.php';"]
                    proc=subprocess.run(base+['run','--rm','--no-deps','cli','--user=1',*invocation],capture_output=True,text=True)
                    marker='BLOG_PROPOSAL_BACKUP_RESULT='
                    records=[l[len(marker):]for l in proc.stdout.splitlines()if l.startswith(marker)]
                    if len(records)!=1:raise ValueError('RECOVERY_CLI_RECEIPT_MISSING:'+proc.stdout[:1000]+proc.stderr[:1000])
                    data=json.loads(records[0])
                    if data['ready']!=expected or (proc.returncode==0)!=expected:raise ValueError('RECOVERY_CLI_STATUS_UNEXPECTED:'+json.dumps(data))
                    return data
                exported=operator('export','/recovery-private','writers-stopped')
                verified=operator('verify','/recovery-private/'+exported['backupDirectory'],exported['manifestSha256'])
                restored=operator('restore-empty','/recovery-private/'+exported['backupDirectory'],exported['manifestSha256'],'writers-stopped',restore_prefix='recovery_cli_')
                occupied=operator('restore-empty','/recovery-private/'+exported['backupDirectory'],exported['manifestSha256'],'writers-stopped',expected=False)
                invalid=operator('export','/recovery-private','not-paused',expected=False)
                result['recoveryTests']=checked;result['operatorCli']=[exported,verified,restored,occupied,invalid]
                result['toolFileSha256']={p.name:digest(p)for p in (ROOT/'wordpress/operations/blog-proposal-rc2').glob('*.php')}
                result['testFixtureSha256']=digest(HERE/'recovery-check.php');result['releaseManifest']=release_manifest
                result['restoredApiReplayTested']=True;result['sourceTablesRestored']=False;result['realCmsChanged']=False
                (ROOT/'docs/qa/blog-proposal-recovery-2026-10-27.json').write_text(json.dumps(result,indent=2)+'\n')
                print(json.dumps({'recoveryChecks':checked['passed'],'operatorCliChecks':5,'failed':0}),flush=True)
                return
            if options.release=='lifecycle':
                preflight('after-enable',True);fixture('profile-negatives')
                fixture('install-storage');preflight('after-storage',True)
                fixture('enroll');preflight('after-owner',True)
                fixture('grant');preflight('after-scope',True)
                fixture('poison');preflight('after-scope',True,False)
                fixture('repair');preflight('after-scope',True)
                fixture('seed-and-backup');fixture('restore-and-retain')
                cli('plugin','deactivate','alexcuadra-editorial-blog-proposals')
                preflight('withdrawn',False);fixture('assert-retained')
                result['releaseLifecycle']=lifecycle;result['releaseManifest']=release_manifest
                result['candidateVersion']='0.1.0-rc.2';result['distributionReady']=False
                result['privateProposalOperationsTested']=True
                result['isolatedRestoreUsesSeparateTablePrefix']=True
                result['repositoryApiReplayAfterRestoreTested']=False
                result['sourceTablesRestored']=False
                (ROOT/'docs/qa/blog-proposal-rc2-lifecycle-2026-10-27.json').write_text(json.dumps(result,indent=2)+'\n')
                return
            for phase,marker in [('check','EDITORIAL_BLOG_PROPOSAL_WP_RESULT='),('failures','EDITORIAL_BLOG_PROPOSAL_FAULT_RESULT='),('http','EDITORIAL_BLOG_PROPOSAL_HTTP_RESULT=')]:
                out=cli('eval-file','/var/www/html/.site-tests/'+phase+'.php')
                validated=command(['python3',str(ROOT/'tests/integration/wordpress-blog-proposals/verify-report.py'),marker],input=out)
                reports[phase]=json.loads(validated.strip()[len(marker):]);print(marker+json.dumps(reports[phase]),flush=True)
            if options.release:
                out=cli('eval-file','/var/www/html/.site-tests/contracts.php')
                lines=[l[len('BLOG_PROPOSAL_RELEASE_CONTRACT_RESULT='):]for l in out.splitlines()if l.startswith('BLOG_PROPOSAL_RELEASE_CONTRACT_RESULT=')]
                if len(lines)!=1:raise ValueError('CONTRACT_RECEIPT_MISSING')
                contracts=json.loads(lines[0])
                if contracts['failed']!=0 or contracts['passed']!=len(set(contracts['cases'])):raise ValueError('CONTRACT_FAILED')
                result['releaseContracts']=contracts;result['releaseManifest']=release_manifest
                output=cli('eval-file','/site-profile-lab/production-admission-check.php')
                prefix='SITE_RELEASE_ADMISSION_RESULT='
                lines=[l[len(prefix):]for l in output.splitlines()if l.startswith(prefix)]
                if len(lines)!=1:raise ValueError('PRODUCTION_ADMISSION_RECEIPT_MISSING')
                admission=json.loads(lines[0])
                if admission['failed']!=0 or admission['passed']!=len(set(admission['cases'])):raise ValueError('PRODUCTION_ADMISSION_CHECK_FAILED')
                result['productionBranchAdmissionInSyntheticLab']=admission
            result['functional']=reports;result['proposalCrudTested']=True;result['candidateVersion']='0.1.0-rc.2'if options.release else'0.1.0-lab.site.1';result['distributionReady']=False
            result['candidateFileSha256']={p.name:digest(p)for p in candidate.iterdir()}
            result['testFileSha256']={p.name:digest(p)for p in testdir.iterdir()}
            result['candidatePolicy']=policy
            diagnostics=cli('eval',"$p=ABSPATH.'site-profile-debug.log';if(is_file($p)){echo file_get_contents($p);}")
            (work/'diagnostics.log').write_text(diagnostics)
            result['diagnostics']={'bytes':len(diagnostics.encode()),'sha256':hashlib.sha256(diagnostics.encode()).hexdigest(),'retainedPrivately':True,
                'lines':len(diagnostics.splitlines()),'fatalMarkers':diagnostics.count('PHP Fatal error')}
            (ROOT/('docs/qa/blog-proposal-rc2-functional-2026-10-27.json'if options.release else'docs/qa/blog-proposal-site-profile-functional-2026-10-26.json')).write_text(json.dumps(result,indent=2)+'\n')
            print(json.dumps({'bootstrapChecks':report['passed'],'functionalChecks':sum(r['passed']for r in reports.values()),'failed':0,'distributionReady':False}))
        else:
            (ROOT/'docs/qa/blog-proposal-site-profile-bootstrap-fingerprint-2026-10-26.json').write_text(json.dumps(result,indent=2)+'\n')
            print(json.dumps({'passed':report['passed'],'failed':0,'profileBlockReproduced':True,'newScopeGranted':False,'proposalCrudTested':False}))
    finally:compose('stop')

if __name__=='__main__':main()
