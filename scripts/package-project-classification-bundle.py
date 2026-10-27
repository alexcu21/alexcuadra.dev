#!/usr/bin/env python3
"""Versioned reader/profile bundle. Never rewrites installed/sealed rc.2 sources."""
from pathlib import Path
import hashlib,io,json,re,zipfile
ROOT=Path(__file__).resolve().parents[1]
OLD_SHA='7e5bc2b3213a8497dc47426b4565bbf528400d69f4e4f3e57f40f6119d4d6def'
BLOG='alexcuadra-editorial-blog-proposals';READER='alexcuadra-editorial-project-classification'
def digest(data):return hashlib.sha256(data).hexdigest()
def package(slug,version,files,extra):
 folder=ROOT/'wordpress/candidates'/f'{slug}-{version}'
 if folder.is_symlink():raise ValueError('SYMLINK')
 folder.mkdir(parents=True,exist_ok=True)
 if any(p.is_symlink()for p in folder.iterdir()):raise ValueError('SYMLINK')
 if set(p.name for p in folder.iterdir())-set(files):raise ValueError('EXTRA_FILES')
 buffer=io.BytesIO();entries=[]
 with zipfile.ZipFile(buffer,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=9)as z:
  for name in sorted(files):
   data=files[name];(folder/name).write_bytes(data)
   info=zipfile.ZipInfo(slug+'/'+name,(2026,10,27,0,0,0));info.compress_type=zipfile.ZIP_DEFLATED;info.external_attr=0o100644<<16;z.writestr(info,data)
   entries.append({'path':info.filename,'bytes':len(data),'sha256':digest(data)})
 data=buffer.getvalue();stem=ROOT/'artifacts/wordpress'/f'{slug}-{version}'
 manifest={'format':'editorial-coordinated-component-v1','version':version,'slug':slug,'sha256':digest(data),'zipBytes':len(data),'files':entries,
  'installationApproved':False,'capabilityGrantApproved':False,'sourceWriterIncluded':False,'automaticMigration':False,**extra}
 Path(str(stem)+'.zip').write_bytes(data);Path(str(stem)+'.manifest.json').write_text(json.dumps(manifest,indent=2)+'\n');return manifest

def build():
 source=ROOT/'wordpress/experimental/editorial-project-classification/reader.php'
 receipt=json.loads((ROOT/'docs/qa/project-classification-reader-2026-10-27.json').read_text())
 if digest(source.read_bytes())!=receipt['sourceFileSha256'][str(source.relative_to(ROOT))]:raise ValueError('CLASSIFICATION_SOURCE_DRIFT')
 text=source.read_text().replace('AlexCuadra\\ProjectClassificationLab','AlexCuadra\\EditorialProjectClassification')
 start=text.index('function laboratory(): bool {');end=text.index('const CAPABILITY',start)
 text=text[:start]+'''function site_admitted(): bool {
 if(!defined('ABSPATH')||is_multisite()){return false;}
 $home=rtrim(home_url(),'/');$site=rtrim(site_url(),'/');
 $lab=defined('AC_PROJECT_CLASSIFICATION_LAB')&&AC_PROJECT_CLASSIFICATION_LAB===true
  &&wp_get_environment_type()==='local'&&defined('DB_HOST')&&DB_HOST==='db'
  &&in_array($home,['http://wordpress','https://content.alexcuadra.dev'],true)&&$site===$home;
 $live=defined('AC_EDITORIAL_PROJECT_CLASSIFICATION_SITE')&&AC_EDITORIAL_PROJECT_CLASSIFICATION_SITE==='https://content.alexcuadra.dev'
  &&$home===AC_EDITORIAL_PROJECT_CLASSIFICATION_SITE&&$site===$home;
 return $lab||$live;
}
if(!site_admitted()){return;}
'''+text[end:]
 text=text.replace('return laboratory()&&','return site_admitted()&&')
 main=f'''<?php
/**
 * Plugin Name: Alex Cuadra Editorial Project Classification
 * Description: Scoped public project-owned category and technology metadata.
 * Version: 0.1.0-rc.1
 * Requires at least: 7.1.3
 * Requires PHP: 8.5
 * License: GPL-2.0-or-later
 */
namespace AlexCuadra\\EditorialProjectClassification;
if(!defined('ABSPATH')){{exit;}}
require_once __DIR__.'/reader.php';
'''
 reader=package(READER,'0.1.0-rc.1',{READER+'.php':main.encode(),'reader.php':text.encode(),'README.md':b'''# Public project classification rc.1
Two scoped public metadata abilities; existing public-project read capability.
No term registration, assignment, migration, permission grants or source writer.
Requires exact site opt-in; local fixtures require explicit local flag/DB/URL.
Coordinated installation with blog proposal profile rc.3; do not activate beside rc.2.
'''},{'scope':'alexcuadra_read_public_projects','experimentalReaderSha256':digest(source.read_bytes())})
 archive=ROOT/'artifacts/wordpress'/f'{BLOG}-0.1.0-rc.2.zip'
 if digest(archive.read_bytes())!=OLD_SHA:raise ValueError('SEALED_RC2_DRIFT')
 with zipfile.ZipFile(archive)as z:files={name.split('/',1)[1]:z.read(name)for name in z.namelist()}
 files[BLOG+'.php']=files[BLOG+'.php'].replace(b'Version: 0.1.0-rc.2',b'Version: 0.1.0-rc.3')
 admission=files['admission.php'].decode();match=re.search(r"\$policy=json_decode\('(.*?)',true,32,JSON_THROW_ON_ERROR\);",admission)
 if not match:raise ValueError('PROFILE_POLICY_NOT_FOUND')
 policy=json.loads(match[1]);policy['selfMainSha256']=digest(files[BLOG+'.php'])
 policy['classificationFilesSha256']={e['path'].split('/',1)[1]:e['sha256']for e in reader['files']}
 admission=admission[:match.start(1)]+json.dumps(policy,separators=(',',':'))+admission[match.end(1):]
 old="$actual=$plugins;$selfVersion=$actual[$self]??null;unset($actual[$self]);ksort($actual);"
 new="$actual=$plugins;$selfVersion=$actual[$self]??null;unset($actual[$self]);\n    $classifier='alexcuadra-editorial-project-classification/alexcuadra-editorial-project-classification.php';\n    $classifierVersion=$actual[$classifier]??null;unset($actual[$classifier]);ksort($actual);"
 assert admission.count(old)==1;admission=admission.replace(old,new).replace("$selfVersion==='0.1.0-rc.2'","$selfVersion==='0.1.0-rc.3'")
 needle="    $expectedMu=$policy['muPluginSha256'];"
 admission=admission.replace(needle,'''    // Only the exact tested reader may be active during/after the coordinated transition.
    if($classifierVersion!==null){
        $dir=WP_PLUGIN_DIR.'/alexcuadra-editorial-project-classification';
        $names=is_dir($dir)?array_values(array_diff(scandir($dir),['.','..'])):[];sort($names);
        $wantedNames=array_keys($policy['classificationFilesSha256']);sort($wantedNames);
        $versions=$versions&&$classifierVersion==='0.1.0-rc.1'&&!is_link($dir)&&$names===$wantedNames;
        foreach($policy['classificationFilesSha256']as$name=>$hash){$file=$dir.'/'.$name;$versions=$versions&&is_file($file)&&!is_link($file)&&hash_file('sha256',$file)===$hash;}
    }
'''+needle)
 files['admission.php']=admission.encode();files['README.md']=b'''# Private post proposals 0.1.0-rc.3
Profile-only upgrade from sealed rc.2. Business files, DTOs and tables unchanged.
Admits base measured profile, plus the optional exact project classification rc.1.
No schema migration, role/scope assignment, history deletion or public source writer.
Requires non-empty-history upgrade preflight; never rerun empty initial acceptance.
'''
 blog=package(BLOG,'0.1.0-rc.3',files,{'basePackageSha256':OLD_SHA,'classificationReaderSha256':reader['sha256'],'businessFilesUnchanged':['planner.php','wordpress-source.php','wordpress-repository.php','connection-fence.php','abilities.php']})
 ops=ROOT/'wordpress/operations/project-classification-upgrade';ops.mkdir(exist_ok=True)
 hashes={e['path'].split('/',1)[1]:e['sha256']for e in blog['files']}
 recovery=(ROOT/'wordpress/operations/blog-proposal-rc2/recovery.php').read_text()
 recovery=recovery.replace("const PACKAGE_SHA='"+OLD_SHA+"';","const PACKAGE_SHA='"+blog['sha256']+"';\nconst READABLE_PACKAGES=[PACKAGE_SHA,'"+OLD_SHA+"'];")
 recovery=re.sub(r"\$hashes=json_decode\('(.*?)',true,32,JSON_THROW_ON_ERROR\);",lambda m:"$hashes=json_decode('"+json.dumps(hashes,separators=(',',':'))+"',true,32,JSON_THROW_ON_ERROR);",recovery)
 recovery=recovery.replace("$m['packageSha256']!==PACKAGE_SHA","!in_array($m['packageSha256'],READABLE_PACKAGES,true)")
 (ops/'recovery.php').write_text(recovery);(ops/'blog-proposal-backup.php').write_bytes((ROOT/'wordpress/operations/blog-proposal-rc2/blog-proposal-backup.php').read_bytes())
 old_manifest=json.loads(archive.with_suffix('.manifest.json').read_text());old_manifest['slug']=BLOG
 bundle={'format':'project-classification-coordinated-bundle-v1','targetSite':'https://content.alexcuadra.dev','installationApproved':False,'sourceWriterIncluded':False,
  'requiresPrivateWriterPause':True,'preservesNonEmptyHistory':True,'components':{'oldProposals':old_manifest,'newProposals':blog,'classification':reader},
  'recoveryFiles':{p.name:digest(p.read_bytes())for p in ops.iterdir()if p.name in ['recovery.php','blog-proposal-backup.php']}}
 template=ROOT/'wordpress/operations/project-classification-upgrade-preflight.template.php'
 if template.exists():
  out=template.read_text().replace('__BUNDLE_POLICY_JSON__',json.dumps(bundle,separators=(',',':')))
  (ops/'preflight.php').write_text(out);bundle['preflightSha256']=digest(out.encode())
 (ROOT/'artifacts/wordpress/project-classification-bundle-0.1.0-rc.1.manifest.json').write_text(json.dumps(bundle,indent=2)+'\n')
 print(json.dumps({k:{'version':v['version'],'sha256':v['sha256'],'bytes':v['zipBytes']}for k,v in [('classification',reader),('proposals',blog)]}))
if __name__=='__main__':build()
