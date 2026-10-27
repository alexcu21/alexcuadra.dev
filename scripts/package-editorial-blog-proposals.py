#!/usr/bin/env python3
"""Reviewable deterministic candidate, explicit derivation from tested laboratory code."""
import hashlib
import io
import json
from pathlib import Path
import zipfile

ROOT=Path(__file__).resolve().parents[1]
SLUG='alexcuadra-editorial-blog-proposals'
VERSION='0.1.0-rc.1'
LAB=ROOT/'wordpress/experimental/editorial-blog-proposals'
SOURCE=ROOT/'wordpress/packages'/SLUG
DERIVED=['planner.php','wordpress-source.php','wordpress-repository.php','connection-fence.php']
AUTHORED=[SLUG+'.php','admission.php','abilities.php','README.md']

def derive(name):
    text=(LAB/name).read_text().replace('AlexCuadra\\EditorialBlogProposalLab','AlexCuadra\\EditorialBlogProposals')
    text=text.replace('wordpress_lab_guard','wordpress_guard').replace('ac_blog_lab_','ac_blog_proposal_')
    if name=='wordpress-source.php':
        start=text.index('function wordpress_guard(): void {')
        end=text.index('function wordpress_actor()',start)
        text=text[:start]+'''function wordpress_guard(): void {
    if(!site_admitted()||!profile_report()['ready']){reject('destination_or_profile');}
}
'''+text[end:]
    if name=='wordpress-repository.php':
        start=text.index('    private function tablesReady(): void {')
        end=text.index('    /** DDL',start)
        text=text[:start]+'''    private function tablesReady(): void {
        if(!schema_report($this->db)['ready']){reject('storage_not_ready');}
    }
'''+text[end:]
    return text

def build():
    if SOURCE.is_symlink() or LAB.is_symlink():raise ValueError('SYMLINK_REJECTED')
    for name in AUTHORED+DERIVED:
        if (SOURCE/name).is_symlink():raise ValueError('SYMLINK_REJECTED')
    for name in DERIVED:
        if (LAB/name).is_symlink():raise ValueError('SYMLINK_REJECTED')
    for name in DERIVED:(SOURCE/name).write_text(derive(name))
    files=AUTHORED+DERIVED
    if sorted(p.name for p in SOURCE.iterdir())!=sorted(files):raise ValueError('UNEXPECTED_PACKAGE_FILES')
    output=io.BytesIO();entries=[]
    with zipfile.ZipFile(output,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=9) as archive:
        for name in files:
            path=SOURCE/name
            if path.is_symlink():raise ValueError('SYMLINK_REJECTED')
            data=path.read_bytes();info=zipfile.ZipInfo(SLUG+'/'+name,(2026,10,26,0,0,0))
            info.compress_type=zipfile.ZIP_DEFLATED;info.external_attr=0o100644<<16
            archive.writestr(info,data);entries.append({'path':info.filename,'bytes':len(data),'sha256':hashlib.sha256(data).hexdigest()})
    payload=output.getvalue()
    with zipfile.ZipFile(io.BytesIO(payload)) as archive:assert archive.testzip() is None
    manifest={'format':'editorial-blog-proposals-review-v1','version':VERSION,'decision':'hold-pending-destination-profile',
        'targetSite':'https://content.alexcuadra.dev','plannedUserId':2,'capability':'alexcuadra_prepare_post_changes',
        'installationApproved':False,'capabilityGrantApproved':False,'sourceWriterIncluded':False,'automaticMigration':False,
        'labSourceSha256':{n:hashlib.sha256((LAB/n).read_bytes()).hexdigest() for n in DERIVED},
        'derivation':['namespace','guard-name-and-site-profile-admission','dedicated-table-prefix','closed-schema-admission'],
        'sha256':hashlib.sha256(payload).hexdigest(),'zipBytes':len(payload),'files':entries}
    stem=ROOT/'artifacts/wordpress'/(SLUG+'-'+VERSION)
    Path(str(stem)+'.zip').write_bytes(payload);Path(str(stem)+'.manifest.json').write_text(json.dumps(manifest,indent=2)+'\n')
    # Standalone operator helper uses the identical read-only implementation with an isolated namespace.
    helper=(SOURCE/'admission.php').read_text().replace('namespace AlexCuadra\\EditorialBlogProposals;','namespace AlexCuadra\\BlogProposalAdmission;')
    (ROOT/'wordpress/operations/blog-proposal-admission.php').write_text(helper)
    template=(ROOT/'wordpress/operations/blog-proposal-preflight.template.php').read_text()
    hashes={e['path'].split('/',1)[1]:e['sha256'] for e in entries}
    encoded=json.dumps(hashes,separators=(',',':'))
    result=template.replace('__PACKAGE_SHA256__',manifest['sha256']).replace('__FILE_HASHES_JSON__',encoded)
    (ROOT/'wordpress/operations/blog-proposal-preflight.php').write_text(result)
    print(json.dumps({'version':VERSION,'sha256':manifest['sha256'],'bytes':len(payload)}))

if __name__=='__main__':build()
