#!/usr/bin/env python3
"""Deterministic code-only local candidate; no production opt-in or private data."""
import hashlib
import io
import json
from pathlib import Path
import zipfile
ROOT=Path(__file__).resolve().parents[1]
SLUG='alexcuadra-editorial-operating'
VERSION='0.1.0-rc.1'
FILES=['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities','operating-storage','operating-audit','operating-migration','operating-export','journal-records','storage-barrier']
def main():
    data={f'{SLUG}/{SLUG}.php':(ROOT/'wordpress/packages'/SLUG/f'{SLUG}.php').read_bytes()}
    for name in FILES:data[f'{SLUG}/includes/{name}.php']=(ROOT/'wordpress/experimental/editorial-source-adapter'/f'{name}.php').read_bytes()
    data[f'{SLUG}/README.md']=b'''# Local operating candidate 0.1.0-rc.1\n\nDisabled by default. AC_EDITORIAL_OPERATING_LOCAL=true only permits the previously admitted local experiment; it does not admit a production site. Loading/activation grants no capabilities and performs no DDL, exports or editorial writes. Six MCP abilities register only under explicit local opt-in. CLI controller methods retain their own operator checks. No activation/uninstall or scheduled deletion hooks.\n\nThis package is a code-only acceptance candidate, not authorized for installation in the shared CMS. Production admission, remote read-only preflight and deployment/rollback instructions are still pending. Keep the existing backup and all operational records. Deactivation does not undo approved source edits or delete data. Existing rc.2 remains a separate required plugin.\n'''
    inventory=[{'path':p,'bytes':len(b),'sha256':hashlib.sha256(b).hexdigest()} for p,b in sorted(data.items())]
    inner={'format':'editorial-operating-package-files-v1','version':VERSION,'productionReady':False,'localOptinOnly':True,'files':inventory}
    data[f'{SLUG}/files.json']=(json.dumps(inner,indent=2)+'\n').encode()
    destination=ROOT/'artifacts/wordpress'/f'{SLUG}-{VERSION}.zip';destination.parent.mkdir(parents=True,exist_ok=True)
    output=io.BytesIO()
    with zipfile.ZipFile(output,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=9) as z:
        for p,b in sorted(data.items()):
            info=zipfile.ZipInfo(p,(2026,10,23,0,0,0));info.compress_type=zipfile.ZIP_DEFLATED;info.create_system=3;info.external_attr=0o100644<<16;z.writestr(info,b)
    blob=output.getvalue()
    if destination.exists() and destination.read_bytes()!=blob: raise ValueError('CANDIDATE_VERSION_ALREADY_SEALED')
    if not destination.exists(): destination.write_bytes(blob)
    manifest={'format':'editorial-operating-package-v1','version':VERSION,'productionReady':False,'localOptinOnly':True,'bytes':destination.stat().st_size,'sha256':hashlib.sha256(destination.read_bytes()).hexdigest(),'files':[{'path':p,'bytes':len(b),'sha256':hashlib.sha256(b).hexdigest()} for p,b in sorted(data.items())]}
    destination.with_suffix('.manifest.json').write_text(json.dumps(manifest,indent=2)+'\n')
    print(json.dumps({'package':str(destination),'bytes':manifest['bytes'],'sha256':manifest['sha256'],'productionReady':False}))
if __name__=='__main__':main()
