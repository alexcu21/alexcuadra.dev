#!/usr/bin/env python3
"""Build rc.2 from the sealed rc.1 plus the reviewed destination entry fingerprints."""
from pathlib import Path
import hashlib,io,json,zipfile
ROOT=Path(__file__).resolve().parents[1]
SLUG='alexcuadra-editorial-blog-proposals';VERSION='0.1.0-rc.2'
def digest(data):return hashlib.sha256(data).hexdigest()
def build():
    old=ROOT/'artifacts/wordpress'/f'{SLUG}-0.1.0-rc.1.zip'
    if digest(old.read_bytes())!='ca33ebd4b3a874a939b8d18dbf42c2cea324325b26716f01208cd1f85154635a':raise ValueError('SEALED_RC1_DRIFT')
    receipt=ROOT/'docs/qa/blog-proposal-code-fingerprint-destination-2026-10-26.json'
    result=json.loads(receipt.read_text())['result']
    if not all(result['checks'].values()) or not result['codeMatchesLocalFixture'] or result['laboratoryFixtureIgnored']:raise ValueError('DESTINATION_FINGERPRINT_REQUIRED')
    with zipfile.ZipFile(old)as z:files={n.split('/',1)[1]:z.read(n)for n in z.namelist()}
    main=SLUG+'.php';files[main]=files[main].replace(b'Version: 0.1.0-rc.1',b'Version: 0.1.0-rc.2')
    policy={'plugins':result['plugins'],'muPluginSha256':result['muPluginSha256'],'theme':result['theme'],
        'selfMainSha256':digest(files[main]),'fixtureMuSha256':digest((ROOT/'tests/integration/wordpress-blog-proposals-site-profile/guard.php').read_bytes())}
    admission=files['admission.php'].decode();start=admission.index('function site_admitted(): bool {');end=admission.index('/** Metadata only.',start)
    admission=admission[:start]+'''function laboratory_environment(): bool {
    return wp_get_environment_type()==='local' && defined('DB_HOST') && DB_HOST==='db'
        && getenv('BLOG_SITE_PROFILE_LAB')==='verified-code-only'
        && home_url()==='https://content.alexcuadra.dev' && site_url()===home_url();
}
function site_admitted(): bool {
    if(is_multisite()){return false;}
    $home=rtrim(home_url(),'/');$site=rtrim(site_url(),'/');
    $lab=laboratory_environment() && defined('AC_EDITORIAL_BLOG_PROPOSALS_LAB') && AC_EDITORIAL_BLOG_PROPOSALS_LAB===true;
    $live=defined('AC_EDITORIAL_BLOG_PROPOSALS_SITE') && AC_EDITORIAL_BLOG_PROPOSALS_SITE==='https://content.alexcuadra.dev'
        && $home===AC_EDITORIAL_BLOG_PROPOSALS_SITE && $site===$home && !laboratory_environment();
    return $lab||$live;
}
'''+admission[end:]
    oldlocal="$local=wp_get_environment_type()==='local'&&home_url()==='http://wordpress'&&site_url()==='http://wordpress'&&defined('DB_HOST')&&DB_HOST==='db';"
    assert admission.count(oldlocal)==1;admission=admission.replace(oldlocal,'$local=laboratory_environment();')
    admission=admission.replace("$local=wp_get_environment_type()==='local'&&home_url()==='http://wordpress';","$local=laboratory_environment();")
    marker="    $checks=['singleSite'"
    idx=admission.index(marker)
    override='''    // Exact destination entry fingerprints, independent of enabling the new scope.
    $policy=json_decode('__SITE_POLICY__',true,32,JSON_THROW_ON_ERROR);
    $self='alexcuadra-editorial-blog-proposals/alexcuadra-editorial-blog-proposals.php';
    $actual=$plugins;$selfVersion=$actual[$self]??null;unset($actual[$self]);ksort($actual);
    $wanted=[];$mainReady=true;
    foreach($policy['plugins']as$name=>$entry){
        $wanted[$name]=$entry['version'];$path=WP_PLUGIN_DIR.'/'.$name;$real=realpath($path);
        $mainReady=$mainReady&&$real&&str_starts_with($real,realpath(WP_PLUGIN_DIR).DIRECTORY_SEPARATOR)
            &&is_file($real)&&!is_link($path)&&hash_file('sha256',$real)===$entry['mainSha256'];
    }
    ksort($wanted);$versions=$actual===$wanted&&$mainReady;
    if($selfVersion!==null){$versions=$versions&&$selfVersion==='0.1.0-rc.2'&&hash_file('sha256',WP_PLUGIN_DIR.'/'.$self)===$policy['selfMainSha256'];}
    $expectedMu=$policy['muPluginSha256'];
    if($local){$expectedMu['000-blog-site-guard.php']=$policy['fixtureMuSha256'];}
    ksort($expectedMu);ksort($muHashes);$muReady=$muHashes===$expectedMu;
    $themeReady=$themeInfo===$policy['theme'];
'''.replace('__SITE_POLICY__',json.dumps(policy,separators=(',',':')))
    admission=admission[:idx]+override+admission[idx:];files['admission.php']=admission.encode()
    files['README.md']=('''# Private post proposals 0.1.0-rc.2

Candidate for the measured content.alexcuadra.dev entry profile. No source writer,
automatic schema migration, owner enrollment, permission grant or deletion.
Activation only registers five private plan abilities when site/profile are admitted.
Main-file fingerprints do not verify complete plugin trees. PHP 8.5 branch tested
on 8.5.11; destination reports 8.5.12. Installation remains pending review.
Keep rc.1 sealed; do not install the laboratory experiment.
''').encode()
    folder=ROOT/'wordpress/candidates'/f'{SLUG}-{VERSION}';folder.mkdir(parents=True,exist_ok=True)
    for name,data in files.items():(folder/name).write_bytes(data)
    if sorted(p.name for p in folder.iterdir())!=sorted(files):raise ValueError('UNEXPECTED_CANDIDATE_FILES')
    buf=io.BytesIO();entries=[]
    with zipfile.ZipFile(buf,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=9)as z:
        for name in sorted(files):
            info=zipfile.ZipInfo(SLUG+'/'+name,(2026,10,27,0,0,0));info.compress_type=zipfile.ZIP_DEFLATED;info.external_attr=0o100644<<16
            z.writestr(info,files[name]);entries.append({'path':info.filename,'bytes':len(files[name]),'sha256':digest(files[name])})
    data=buf.getvalue();sha=digest(data);stem=ROOT/'artifacts/wordpress'/f'{SLUG}-{VERSION}'
    Path(str(stem)+'.zip').write_bytes(data)
    manifest={'format':'editorial-blog-proposals-review-v2','version':VERSION,'decision':'hold-pending-release-lifecycle-review',
        'targetSite':'https://content.alexcuadra.dev','plannedUserId':2,'capability':'alexcuadra_prepare_post_changes',
        'installationApproved':False,'capabilityGrantApproved':False,'sourceWriterIncluded':False,'automaticMigration':False,
        'basePackageSha256':digest(old.read_bytes()),'destinationReceiptSha256':digest(receipt.read_bytes()),'sha256':sha,'zipBytes':len(data),'files':entries}
    Path(str(stem)+'.manifest.json').write_text(json.dumps(manifest,indent=2)+'\n')
    ops=ROOT/'wordpress/operations/blog-proposal-rc2';ops.mkdir(exist_ok=True)
    (ops/'blog-proposal-admission.php').write_text(admission.replace('namespace AlexCuadra\\EditorialBlogProposals;','namespace AlexCuadra\\BlogProposalAdmission;'))
    text=(ROOT/'wordpress/operations/blog-proposal-preflight.template.php').read_text()
    text=text.replace('__PACKAGE_SHA256__',sha).replace('__FILE_HASHES_JSON__',json.dumps({e['path'].split('/',1)[1]:e['sha256']for e in entries},separators=(',',':')))
    text=text.replace("$local=wp_get_environment_type()==='local'&&$home==='http://wordpress'&&$site===$home&&defined('DB_HOST')&&DB_HOST==='db';","$local=A\\laboratory_environment();")
    (ops/'blog-proposal-preflight.php').write_text(text)
    print(json.dumps({'version':VERSION,'sha256':sha,'bytes':len(data),'installationApproved':False}))
if __name__=='__main__':build()
