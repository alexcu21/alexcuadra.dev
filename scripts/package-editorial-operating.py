#!/usr/bin/env python3
"""Deterministic code-only destination candidate; no private data or installation authorization."""
import hashlib
import io
import json
from pathlib import Path
import zipfile
ROOT=Path(__file__).resolve().parents[1]
SLUG='alexcuadra-editorial-operating'
VERSION='0.1.0-rc.3'
FILES=['source-writer','mariadb-operation','polylang-source','operating-contract','operating-abilities','operating-storage','operating-audit','operating-migration','operating-export','journal-records','storage-barrier','production-admission']
def main():
    data={f'{SLUG}/{SLUG}.php':(ROOT/'wordpress/packages'/SLUG/f'{SLUG}.php').read_bytes()}
    for name in FILES:data[f'{SLUG}/includes/{name}.php']=(ROOT/'wordpress/experimental/editorial-source-adapter'/f'{name}.php').read_bytes()
    data[f'{SLUG}/README.md']=b'''# Operating acceptance candidate 0.1.0-rc.3\n\nDisabled by default. Local opt-in AC_EDITORIAL_OPERATING_LOCAL=true preserves the existing isolated lab guard. Production opt-in AC_EDITORIAL_OPERATING_SITE must equal https://content.alexcuadra.dev exactly, with neither local/experiment opt-in enabled. Closed runtime admission pins the reviewed versions, plugins/main hashes, MU/main hashes, native cache, theme and transactional tables. No grants, schema writes, exports or content edits occur on activation. Six MCP abilities register only after admission, and every controller operation repeats its guards. Independent CLI approval and operation scopes remain mandatory.\n\nProduction admission is implemented but installation is not authorized and live acceptance is not certified. The original rc.1 stays sealed. Keep all records and the private backup; no scheduled deletion/uninstall hooks. Deactivation does not undo source edits. Private exports use exclusive copies and independent verification, with no atomic-visibility claim. Existing Proposals rc.2 remains a separate required plugin.\n'''
    inventory=[{'path':p,'bytes':len(b),'sha256':hashlib.sha256(b).hexdigest()} for p,b in sorted(data.items())]
    inner={'format':'editorial-operating-package-files-v1','version':VERSION,'productionReady':False,'localOptinOnly':False,'productionAdmissionImplemented':True,'installationAuthorized':False,'files':inventory}
    data[f'{SLUG}/files.json']=(json.dumps(inner,indent=2)+'\n').encode()
    destination=ROOT/'artifacts/wordpress'/f'{SLUG}-{VERSION}.zip';destination.parent.mkdir(parents=True,exist_ok=True)
    output=io.BytesIO()
    with zipfile.ZipFile(output,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=9) as z:
        for p,b in sorted(data.items()):
            info=zipfile.ZipInfo(p,(2026,10,23,0,0,0));info.compress_type=zipfile.ZIP_DEFLATED;info.create_system=3;info.external_attr=0o100644<<16;z.writestr(info,b)
    blob=output.getvalue()
    if destination.exists() and destination.read_bytes()!=blob: raise ValueError('CANDIDATE_VERSION_ALREADY_SEALED')
    if not destination.exists(): destination.write_bytes(blob)
    manifest={'format':'editorial-operating-package-v1','version':VERSION,'productionReady':False,'localOptinOnly':False,'productionAdmissionImplemented':True,'installationAuthorized':False,'bytes':destination.stat().st_size,'sha256':hashlib.sha256(destination.read_bytes()).hexdigest(),'files':[{'path':p,'bytes':len(b),'sha256':hashlib.sha256(b).hexdigest()} for p,b in sorted(data.items())]}
    destination.with_suffix('.manifest.json').write_text(json.dumps(manifest,indent=2)+'\n')
    print(json.dumps({'package':str(destination),'bytes':manifest['bytes'],'sha256':manifest['sha256'],'productionReady':False}))
if __name__=='__main__':main()
