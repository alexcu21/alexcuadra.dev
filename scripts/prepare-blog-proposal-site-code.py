#!/usr/bin/env python3
"""Prepare verified code/resources only, never source SQL/config or a hosting connection."""
import argparse
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import tarfile
import tempfile
import zipfile

ROOT=Path(__file__).resolve().parents[1]
ARCHIVE_SHA='13f20d924a5d161f28444fabf24adbcba40f1c7bbb8951a57c1e4a00f9541d62'
ARCHIVE_BYTES=247734036
PREFIXES=('wp-content/plugins/','wp-content/themes/','wp-content/mu-plugins/')
SEALED={
 'alexcuadra-editorial-blog-read/alexcuadra-editorial-blog-read.php':'alexcuadra-editorial-blog-read-0.1.0-rc.1',
 'alexcuadra-editorial-operating/alexcuadra-editorial-operating.php':'alexcuadra-editorial-operating-0.1.0-rc.3',
 'alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php':'alexcuadra-editorial-proposals-0.1.0-rc.2',
}

def sha(path):
    digest=hashlib.sha256()
    with path.open('rb') as stream:
        for chunk in iter(lambda:stream.read(1024*1024),b''):digest.update(chunk)
    return digest.hexdigest()

def safe_name(name):
    p=PurePosixPath(name)
    if p.is_absolute() or '..' in p.parts or '\\' in name:raise ValueError('UNSAFE_CODE_PATH')
    return p

def version(data):
    header=data[:8192].decode('utf-8',errors='replace')
    found=re.search(r'^[ \t/*#@]*Version:\s*([^\r\n]+)',header,re.M|re.I)
    if not found:raise ValueError('PLUGIN_VERSION_UNAVAILABLE')
    return found[1].strip()

def main():
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--archive',type=Path,required=True);args=parser.parse_args()
    os.umask(0o077)
    if args.archive.is_symlink() or args.archive.stat().st_size!=ARCHIVE_BYTES or sha(args.archive)!=ARCHIVE_SHA:raise ValueError('ARCHIVE_IDENTITY_MISMATCH')
    if shutil.disk_usage(ROOT).free<2*1024**3:raise ValueError('INSUFFICIENT_LOCAL_SPACE')
    live=json.loads((ROOT/'docs/qa/blog-proposal-destination-before-2026-10-26.json').read_text())['result']['profile']
    with tarfile.open(args.archive,'r:gz') as archive:manifest=json.load(archive.extractfile('backup/manifest.json'))
    expected={n:e for n,e in manifest['files'].items() if n.startswith(PREFIXES) and e['type']=='file'}
    if not expected or len(expected)>30000 or sum(e['bytes'] for e in expected.values())>512*1024**2:raise ValueError('CODE_BUDGET')
    cache=ROOT/'node_modules/.cache/editorial-blog-proposals-site-profile';cache.mkdir(parents=True,exist_ok=True,mode=0o700)
    target=Path(tempfile.mkdtemp(prefix='verified-code-',dir=cache));seen=set()
    # Stream across the archive; extract only three code/resource roots, never backup/ or config.
    with tarfile.open(args.archive,'r|gz') as archive:
        for member in archive:
            name=safe_name(member.name).as_posix()
            if not name.startswith(PREFIXES):continue
            if member.isdir():continue
            if not member.isfile() or name not in expected or name in seen:raise ValueError('UNEXPECTED_CODE_MEMBER')
            e=expected[name]
            if member.size!=e['bytes']:raise ValueError('CODE_SIZE_DRIFT')
            path=target/name;path.parent.mkdir(parents=True,exist_ok=True,mode=0o700)
            digest=hashlib.sha256()
            with path.open('xb') as output,archive.extractfile(member) as data:
                for chunk in iter(lambda:data.read(1024*1024),b''):digest.update(chunk);output.write(chunk)
            if digest.hexdigest()!=e['sha256']:raise ValueError('CODE_HASH_DRIFT')
            seen.add(name)
    if seen!=set(expected) or (target/'backup').exists() or (target/'wp-config.php').exists():raise ValueError('EXTRACTION_INCOMPLETE_OR_PRIVATE_DATA')
    plugins={}
    for plugin,wanted in live['activePlugins'].items():
        if plugin in SEALED:
            stem=ROOT/'artifacts/wordpress'/SEALED[plugin];meta=json.loads(Path(str(stem)+'.manifest.json').read_text());zpath=Path(str(stem)+'.zip')
            if sha(zpath)!=meta['sha256']:raise ValueError('SEALED_ZIP_DRIFT')
            with zipfile.ZipFile(zpath) as archive:
                if sorted(archive.namelist())!=sorted(e['path'] for e in meta['files']):raise ValueError('SEALED_ZIP_EXTRA_FILES')
                for e in meta['files']:
                    safe_name(e['path']);data=archive.read(e['path'])
                    if len(data)!=e['bytes'] or hashlib.sha256(data).hexdigest()!=e['sha256']:raise ValueError('SEALED_ENTRY_DRIFT')
                mainfile=archive.read(plugin)
            origin={'kind':'sealed-zip','artifact':zpath.name,'sha256':meta['sha256']}
        else:
            path=target/'wp-content/plugins'/plugin
            if not path.is_file():raise ValueError('LIVE_PLUGIN_SOURCE_MISSING')
            mainfile=path.read_bytes();origin={'kind':'verified-backup-code','archiveSha256':ARCHIVE_SHA}
        actual=version(mainfile)
        if actual!=wanted:raise ValueError('LIVE_PLUGIN_VERSION_DRIFT:'+plugin)
        plugins[plugin]={'reportedVersion':wanted,'availableVersion':actual,'mainSha256':hashlib.sha256(mainfile).hexdigest(),'origin':origin,'liveMainHashProvided':False}
    mu={}
    for name,wanted in live['muPluginSha256'].items():
        actual=sha(target/'wp-content/mu-plugins'/name)
        if actual!=wanted:raise ValueError('LIVE_MU_HASH_DRIFT')
        mu[name]={'sha256':actual,'matchesLiveReceipt':True}
    theme=live['theme'];folder=target/'wp-content/themes'/theme['stylesheet']
    if version((folder/'style.css').read_bytes())!=theme['version']:raise ValueError('THEME_VERSION_DRIFT')
    for name,key in [('style.css','styleSha256'),('functions.php','functionsSha256')]:
        if sha(folder/name)!=theme[key]:raise ValueError('LIVE_THEME_ENTRY_DRIFT')
    result={'format':'blog-proposal-site-code-inventory-v1','sourceArchiveSha256':ARCHIVE_SHA,
        'sourceArchiveBytes':ARCHIVE_BYTES,'verifiedCodeFiles':len(seen),'verifiedCodeBytes':sum(e['bytes'] for e in expected.values()),
        'activePluginCount':len(plugins),'plugins':plugins,'muPlugins':mu,'theme':theme,'themeEntryHashesMatchLive':True,
        'sourceSqlExtracted':False,'sourceConfigurationExtracted':False,'sourceDataImported':False,'sourcePhpExecuted':False,
        'hostingAccessed':False,'candidateChanged':False,'destinationAccepted':False,
        'limits':['Plugin versions match code candidates; the live receipt does not provide main-file or whole-tree hashes.',
            'MU and two theme entry hashes match exactly; whole live theme integrity is not certified.',
            'Compatibility has not been tested with this full profile. No allowlist or new package admission is implied.']}
    (target/'code-provenance.json').write_text(json.dumps(result,indent=2)+'\n')
    # Pointer stays in ignored private cache, not in public evidence.
    (cache/'current.json').write_text(json.dumps({'directory':str(target),'archiveSha256':ARCHIVE_SHA})+'\n')
    output=ROOT/'docs/qa/blog-proposal-site-code-inventory-2026-10-26.json';output.write_text(json.dumps(result,indent=2)+'\n')
    print(json.dumps({k:result[k] for k in ['verifiedCodeFiles','verifiedCodeBytes','activePluginCount','themeEntryHashesMatchLive','sourceSqlExtracted','sourcePhpExecuted','destinationAccepted']}))

if __name__=='__main__':main()
