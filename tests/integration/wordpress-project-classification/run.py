#!/usr/bin/env python3
"""Fresh synthetic WordPress, internal Docker network, no shared CMS or host ports."""
from pathlib import Path
import hashlib,json,os,re,subprocess,tempfile
ROOT=Path(__file__).resolve().parents[3];HERE=Path(__file__).resolve().parent
PROJECT=os.environ.get('PROJECT_CLASSIFICATION_LAB_PROJECT','alexcuadra-project-classification-20261027')
def run(args):
 r=subprocess.run(args,capture_output=True,text=True)
 if r.returncode:raise RuntimeError('LAB_COMMAND_FAILED:'+r.stdout[:3000]+r.stderr[:3000])
 return r.stdout

def main():
 os.umask(0o077)
 if not re.fullmatch(r'alexcuadra-project-classification-[a-z0-9-]+',PROJECT)or os.environ.get('DOCKER_HOST'):raise ValueError('OWN_LOCAL_PROJECT_REQUIRED')
 # Only use already-cached, hash-pinned dependencies; no download in this runner.
 for name,sha in {'polylang.3.9.1.zip':'b09aa8b3d92a882b1d5e24ca2c729981e8ac07d4403f78d4a6853899b20f9266','mcp-adapter.0.7.1.zip':'719d81aa1250615acfd8256f0acc8c13f1712f1ae398c3d5883c8b166024fe9b'}.items():
  if hashlib.sha256((ROOT/'node_modules/.cache/editorial-blog-read-lab'/name).read_bytes()).hexdigest()!=sha:raise ValueError('DEPENDENCY_DRIFT')
 cache=ROOT/'node_modules/.cache/editorial-project-classification';cache.mkdir(exist_ok=True,parents=True)
 work=Path(tempfile.mkdtemp(prefix='controls-',dir=cache))
 env={'WORDPRESS_CONFIG_EXTRA':"define('WP_ENVIRONMENT_TYPE','local');define('AC_PROJECT_CLASSIFICATION_LAB',true);define('WP_DEBUG_DISPLAY',false);define('DISABLE_WP_CRON',true);define('WP_HTTP_BLOCK_EXTERNAL',true);define('WP_ACCESSIBLE_HOSTS','wordpress');"}
 volumes=[str(HERE)+':/classification-lab:ro',str(ROOT/'wordpress/experimental/editorial-project-classification')+':/project-classification:ro']
 settings={'services':{n:{'environment':env,'volumes':volumes}for n in ['wordpress','cli']}}
 override=work/'compose.json';override.write_text(json.dumps(settings))
 base=['docker','compose','-p',PROJECT,'-f',str(ROOT/'tests/integration/wordpress-blog-read/compose.yaml'),'-f',str(override)]
 def compose(*args):return run(base+list(args))
 def cli(*args):return compose('run','--rm','--no-deps','cli',*args)
 try:
  compose('up','-d','--wait','wordpress')
  probe=subprocess.run(base+['run','--rm','--no-deps','cli','core','is-installed'],capture_output=True)
  if probe.returncode==0:raise ValueError('FRESH_DATABASE_REQUIRED')
  cli('core','install','--url=http://wordpress','--title=SyntheticClassification','--admin_user=lab_admin','--admin_password=synthetic-classification-only','--admin_email=admin@example.test','--skip-email')
  compose('run','--rm','--no-deps','--entrypoint','sh','cli','-ec','mkdir -p wp-content/mu-plugins; cp /classification-lab/fixture.php wp-content/mu-plugins/project-classification-lab.php')
  for name,version in [('polylang','3.9.1'),('mcp-adapter','0.7.1')]:
   cli('plugin','install','/lab-assets/'+name+'.'+version+'.zip','--activate')
  reports={}
  for phase,marker in [('check','PROJECT_CLASSIFICATION_RESULT='),('http','PROJECT_CLASSIFICATION_HTTP_RESULT=')]:
   out=cli('eval-file','/classification-lab/'+phase+'.php');lines=[l[len(marker):]for l in out.splitlines()if l.startswith(marker)]
   if len(lines)!=1:raise ValueError('REPORT_MISSING')
   r=json.loads(lines[0])
   if r['failed']!=0 or r['passed']!=len(set(r['cases']))or not r['syntheticScopeRemoved']:raise ValueError('REPORT_INCOMPLETE')
   if phase=='http'and(not r['httpTransportVerified']or not r['syntheticCredentialsRevoked']):raise ValueError('HTTP_CLEANUP_OR_TRANSPORT_FAILED')
   reports[phase]=r;print(json.dumps({'phase':phase,'passed':r['passed'],'failed':0}),flush=True)
  output={'format':'project-classification-lab-v1','reports':reports,'realCmsChanged':False,'realPermissionsChanged':False,'sourceFileSha256':{str(p.relative_to(ROOT)):hashlib.sha256(p.read_bytes()).hexdigest()for p in [ROOT/'wordpress/experimental/editorial-project-classification/reader.php',HERE/'fixture.php',HERE/'check.php',HERE/'http.php',HERE/'run.py']},'project':PROJECT,'hostPorts':False}
  (ROOT/'docs/qa/project-classification-reader-2026-10-27.json').write_text(json.dumps(output,indent=2)+'\n')
 finally:compose('stop')
if __name__=='__main__':main()
