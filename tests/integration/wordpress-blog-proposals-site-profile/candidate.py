"""Derive a LAB-only candidate, leaving all sealed release bytes untouched."""
import hashlib
import json
from pathlib import Path
import zipfile

ROOT=Path(__file__).resolve().parents[3]
HERE=Path(__file__).resolve().parent

def prepare(work,live,inventory):
    slug='alexcuadra-editorial-blog-proposals'
    original=ROOT/'artifacts/wordpress'/f'{slug}-0.1.0-rc.1.zip'
    if hashlib.sha256(original.read_bytes()).hexdigest()!='ca33ebd4b3a874a939b8d18dbf42c2cea324325b26716f01208cd1f85154635a':raise ValueError('RC1_DRIFT')
    manifest=json.loads(original.with_suffix('.manifest.json').read_text())
    candidate=work/'candidate';candidate.mkdir()
    with zipfile.ZipFile(original)as archive:
        if sorted(archive.namelist())!=sorted(e['path']for e in manifest['files']):raise ValueError('RC1_CONTENT_DRIFT')
        for entry in manifest['files']:
            data=archive.read(entry['path'])
            if hashlib.sha256(data).hexdigest()!=entry['sha256']:raise ValueError('RC1_ENTRY_DRIFT')
            (candidate/entry['path'].split('/',1)[1]).write_bytes(data)
    main=candidate/(slug+'.php');text=main.read_text();assert text.count('Version: 0.1.0-rc.1')==1
    main.write_text(text.replace('Version: 0.1.0-rc.1','Version: 0.1.0-lab.site.1'))
    admission=candidate/'admission.php';text=admission.read_text()
    start=text.index('function site_admitted(): bool {');end=text.index('/** Metadata only.',start)
    text=text[:start]+'''function site_admitted(): bool {
    return defined('AC_BLOG_PROPOSALS_SITE_PROFILE_LAB') && AC_BLOG_PROPOSALS_SITE_PROFILE_LAB===true
        && wp_get_environment_type()==='local' && defined('DB_HOST') && DB_HOST==='db' && !is_multisite()
        && home_url()==='https://content.alexcuadra.dev' && site_url()===home_url();
}
'''+text[end:]
    text=text.replace('function profile_report(): array {','function minimal_profile_report(): array {')
    old="$local=wp_get_environment_type()==='local'&&home_url()==='http://wordpress';"
    assert text.count(old)==1;text=text.replace(old,'$local=site_admitted();')
    text+='\nrequire_once __DIR__.\'/site-admission.php\';\n';admission.write_text(text)
    (candidate/'site-admission.php').write_bytes((HERE/'site-admission.php').read_bytes())
    policy={'activePlugins':live['activePlugins'],'muPluginSha256':live['muPluginSha256'],'theme':live['theme'],
        'mainSha256':{name:entry['mainSha256']for name,entry in inventory['plugins'].items()},
        'fixtureMuSha256':hashlib.sha256((HERE/'guard.php').read_bytes()).hexdigest(),
        'selfMainSha256':hashlib.sha256(main.read_bytes()).hexdigest()}
    (candidate/'site-profile.json').write_text(json.dumps(policy,indent=2)+'\n')
    tests=work/'tests';tests.mkdir()
    for name in ['check.php','worker.php','failure-worker.php','failures.php','http-client.php','http.php']:
        text=(ROOT/'tests/integration/wordpress-blog-proposals'/name).read_text()
        text=text.replace('AlexCuadra\\EditorialBlogProposalLab','AlexCuadra\\EditorialBlogProposals').replace('ac_blog_lab_','ac_blog_proposal_')
        text=text.replace('/blog-proposals/','/var/www/html/wp-content/plugins/'+slug+'/').replace('/proposal-lab/','/var/www/html/.site-tests/')
        text=text.replace('alexcuadra-lab/','alexcuadra-blog/').replace('wordpress_lab_guard','wordpress_guard')
        if name=='check.php':
            start=text.index('function workers(');end=text.index('wp_set_current_user(1);',start)
            text=text[:start]+"require_once __DIR__.'/process-support.php';\n"+text[end:]
        if name in ['worker.php','failure-worker.php']:
            text=text.replace('echo "BLOG_PROPOSAL_WORKER_READY', 'while(ob_get_level()>0){ob_end_flush();}echo "BLOG_PROPOSAL_WORKER_READY')
            text=text.replace('echo "PROPOSAL_FAILURE_READY', 'while(ob_get_level()>0){ob_end_flush();}echo "PROPOSAL_FAILURE_READY')
        if name=='failures.php':
            start=text.index('$startWorker=');end=text.index('try{\n    wp_set_current_user(1);',start)
            text=text[:start]+'''require_once __DIR__.'/process-support.php';
$startWorker=static function($owner,$key,$mode,$source){
    $job=site_process_start([PHP_BINARY,'/usr/local/bin/wp','--quiet','eval-file',__DIR__.'/failure-worker.php',(string)$owner,$key,$mode,(string)$source],'PROPOSAL_FAILURE_READY');
    site_process_go($job);return $job;
};
'''+text[end:]
            start=text.index('        [$process,$pipes]=$startWorker(');end=text.index('        $check($mode.',start)
            text=text[:start]+'''        $job=$startWorker($owner,$key,$mode,$source);$result=site_process_result($job,'PROPOSAL_FAILURE_RESULT=');
'''+text[end:]
            start=text.index('        [$process,$pipes]=$startWorker(');end=text.index('        // A new owned operation',start)
            text=text[:start]+'''        $job=$startWorker($owner,$key,$mode,$source);site_process_marker($job,$marker);site_process_abort($job);
'''+text[end:]
        (tests/name).write_text(text)
    (tests/'process-support.php').write_bytes((HERE/'process-support.php').read_bytes())
    return candidate,tests,policy
