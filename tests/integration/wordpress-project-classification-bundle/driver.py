"""Full measured code-profile bundle exercises; only synthetic data and own resources."""
from pathlib import Path
import base64,hashlib,json,subprocess
ROOT=Path(__file__).resolve().parents[3]
def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
def execute(mode,compose,cli,base,work,result):
 compose('run','--rm','--no-deps','--user','0:0','--entrypoint','sh','cli','-ec','chmod 700 /bundle-private; chown 33:33 /bundle-private')
 fixtures=[];preflights=[];operators=[];regressions={}
 def one(out,marker):
  records=[l[len(marker):]for l in out.splitlines()if l.startswith(marker)]
  if len(records)!=1:raise ValueError('BUNDLE_RECEIPT_MISSING:'+out[:1500])
  return json.loads(records[0])
 def fixture(name,*args):
  r=one(cli('eval-file','/bundle-lab/fixture.php',name,*args),'CLASSIFICATION_BUNDLE_FIXTURE_RESULT=')
  if r['failed']!=0 or r['passed']!=len(set(r['cases']))or r['passed']<=0:raise ValueError('BUNDLE_FIXTURE_FAILED')
  fixtures.append(r);print(json.dumps({'fixture':name,'passed':r['passed']}),flush=True);return r
 def preflight(phase,history=None,ready=True):
  args=[phase]+([history]if history else[])
  p=subprocess.run(base+['run','--rm','--no-deps','cli','eval-file','/bundle-operations/preflight.php','--use-include',*args],capture_output=True,text=True)
  r=one(p.stdout+p.stderr,'PROJECT_CLASSIFICATION_UPGRADE_RESULT=')
  if r['ready']is not ready or(p.returncode==0)!=ready:raise ValueError('BUNDLE_PREFLIGHT_FAILED:'+json.dumps(r))
  preflights.append(r);print(json.dumps({'phase':phase,'ready':r['ready']}),flush=True);return r
 def operator(location,*args,ready=True,restore_prefix=None):
  invocation=['eval-file',location+'/blog-proposal-backup.php',*args]
  if restore_prefix:
   if restore_prefix!='bundle_cli_restore_':raise ValueError('OWN_RESTORE_PREFIX_REQUIRED')
   payload=base64.b64encode(json.dumps(args).encode()).decode();invocation=['eval',"$GLOBALS['wpdb']->prefix='bundle_cli_restore_';$args=json_decode(base64_decode('"+payload+"'),true);require '"+location+"/blog-proposal-backup.php';"]
  p=subprocess.run(base+['run','--rm','--no-deps','cli','--user=1',*invocation],capture_output=True,text=True)
  r=one(p.stdout,'BLOG_PROPOSAL_BACKUP_RESULT=')
  if r['ready']is not ready or(p.returncode==0)!=ready:raise ValueError('BUNDLE_OPERATOR_FAILED:'+json.dumps(r))
  operators.append(r);return r
 def upgrade():
  cli('plugin','deactivate','alexcuadra-editorial-blog-proposals');cli('plugin','install','/bundle-proposals.zip','--force');cli('plugin','activate','alexcuadra-editorial-blog-proposals')
 def classifier():
  cli('plugin','install','/bundle-classification.zip');cli('plugin','activate','alexcuadra-editorial-project-classification')
 fixture('configure')
 if mode=='bundle-upgrade':
  fixture('seed');before=preflight('before');history=before['historySha256'];fixture('pause')
  exported=operator('/release-operations','export','/bundle-private','writers-stopped');backup='/bundle-private/'+exported['backupDirectory'];sha=exported['manifestSha256']
  operator('/release-operations','verify',backup,sha)
  upgrade();preflight('proposal-upgraded',history)
  cli('plugin','install','/bundle-classification.zip');preflight('classifier-installed',history)
  cli('plugin','activate','alexcuadra-editorial-project-classification');preflight('classifier-enabled',history)
  preflight('classifier-enabled','0'*64,ready=False);fixture('profile-negatives');fixture('resume');preflight('after-scope',history);fixture('preserve');fixture('readers')
  fixture('pause');operator('/bundle-operations','verify',backup,sha);fixture('restore-old-backup',backup,sha)
  newer=operator('/bundle-operations','export','/bundle-private','writers-stopped');operator('/bundle-operations','verify','/bundle-private/'+newer['backupDirectory'],newer['manifestSha256'])
  operator('/bundle-operations','restore-empty',backup,sha,'writers-stopped',ready=False)
  cli('plugin','deactivate','alexcuadra-editorial-project-classification');preflight('classifier-installed',history)
  cli('plugin','deactivate','alexcuadra-editorial-blog-proposals');cli('plugin','install','/site-release.zip','--force');cli('plugin','activate','alexcuadra-editorial-blog-proposals')
  fixture('resume');preflight('rolled-back',history);fixture('preserve')
 elif mode in ['bundle-regression','bundle-recovery']:
  upgrade();classifier()
  testdir=work/'tests'
  if mode=='bundle-regression':
   for phase in ['check','http']:
    p=ROOT/'tests/integration/wordpress-project-classification'/f'{phase}.php'
    t=p.read_text().replace('AlexCuadra\\ProjectClassificationLab','AlexCuadra\\EditorialProjectClassification').replace("home_url() !== 'http://wordpress'","home_url() !== 'https://content.alexcuadra.dev'")
    (testdir/f'classification-{phase}.php').write_text(t)
   compose('run','--rm','--no-deps','--user','0:0','--entrypoint','sh','cli','-ec','cp /site-tests/classification-*.php .site-tests/; chown 33:33 .site-tests/classification-*.php')
   stages=[('classification-check','PROJECT_CLASSIFICATION_RESULT='),('classification-http','PROJECT_CLASSIFICATION_HTTP_RESULT='),('check','EDITORIAL_BLOG_PROPOSAL_WP_RESULT='),('failures','EDITORIAL_BLOG_PROPOSAL_FAULT_RESULT='),('http','EDITORIAL_BLOG_PROPOSAL_HTTP_RESULT='),('contracts','BLOG_PROPOSAL_RELEASE_CONTRACT_RESULT=')]
  else:
   cli('eval-file','/site-profile-lab/release-lifecycle.php','install-storage');cli('eval-file','/site-profile-lab/release-lifecycle.php','enroll')
   text=(ROOT/'tests/integration/wordpress-blog-proposals-site-profile/recovery-check.php').read_text().replace("'/release-operations/recovery.php'","'/bundle-operations/recovery.php'").replace("'/recovery-private'","'/bundle-private'")
   (testdir/'recovery-check.php').write_text(text)
   compose('run','--rm','--no-deps','--user','0:0','--entrypoint','sh','cli','-ec','cp /site-tests/recovery-check.php .site-tests/; chown 33:33 .site-tests/recovery-check.php')
   stages=[('recovery-check','BLOG_PROPOSAL_RECOVERY_TEST_RESULT=')]
  for name,marker in stages:
   out=cli('eval-file','/var/www/html/.site-tests/'+name+'.php');r=one(out,marker)
   if r['failed']!=0 or r['passed']!=len(set(r['cases']))or r['passed']<=0:raise ValueError('BUNDLE_REGRESSION_FAILED')
   if name=='classification-http'and(not r['httpTransportVerified']or not r['syntheticCredentialsRevoked']or not r['syntheticScopeRemoved']):raise ValueError('CLASSIFICATION_HTTP_NOT_CLEAN')
   if name=='http'and(not r['httpTransportVerified']or not r['syntheticCredentialsRevoked']or not r['syntheticCapabilitiesRemoved']):raise ValueError('PROPOSAL_HTTP_NOT_CLEAN')
   regressions[name]=r;print(json.dumps({'regression':name,'passed':r['passed']}),flush=True)
  if mode=='bundle-recovery':
   exp=operator('/bundle-operations','export','/bundle-private','writers-stopped');backup='/bundle-private/'+exp['backupDirectory'];sha=exp['manifestSha256']
   operator('/bundle-operations','verify',backup,sha);operator('/bundle-operations','restore-empty',backup,sha,'writers-stopped',restore_prefix='bundle_cli_restore_');operator('/bundle-operations','restore-empty',backup,sha,'writers-stopped',ready=False)
 else:raise ValueError('UNKNOWN_BUNDLE_MODE')
 result.update({'format':'project-classification-full-profile-bundle-v1','mode':mode,'fixtures':fixtures,'upgradePreflights':preflights,'operatorReceipts':operators,'regressions':regressions,'realCmsChanged':False,'realProposalImported':False,'syntheticProposalOneVersionOne':mode=='bundle-upgrade','sourceSqlImported':False,'installationApproved':False,'bundleManifestSha256':digest(ROOT/'artifacts/wordpress/project-classification-bundle-0.1.0-rc.1.manifest.json')})
 result['sourceFileSha256']={str(p.relative_to(ROOT)):digest(p)for p in [Path(__file__),ROOT/'tests/integration/wordpress-project-classification-bundle/fixture.php',ROOT/'tests/integration/wordpress-blog-proposals-site-profile/run.py',ROOT/'scripts/package-project-classification-bundle.py']}
 result['operatorFileSha256']={p.name:digest(p)for p in (ROOT/'wordpress/operations/project-classification-upgrade').glob('*.php')}
 result['adaptedTestFileSha256']={p.name:digest(p)for p in (work/'tests').glob('*.php')}
 path=ROOT/'docs/qa'/f'project-classification-{mode}-2026-10-27.json';path.write_text(json.dumps(result,indent=2)+'\n')
 print(json.dumps({'mode':mode,'fixtureChecks':sum(x['passed']for x in fixtures),'regressionChecks':sum(x['passed']for x in regressions.values()),'preflights':len(preflights),'operators':len(operators),'failed':0}),flush=True)
