#!/usr/bin/env python3
"""Embed the sealed package file hashes into a standalone read-only diagnostic."""
import json
from pathlib import Path

root = Path(__file__).resolve().parents[1]
manifest = json.loads((root/'artifacts/wordpress/alexcuadra-editorial-blog-read-0.1.0-rc.1.manifest.json').read_text())
template = (root/'wordpress/operations/editorial-blog-read-preflight.template.php').read_text()
entries = []
for item in manifest['files']:
    name = item['path'].split('/',1)[1]
    entries.append("    '"+name+"' => '"+item['sha256']+"',")
source = template.replace('__PACKAGE_SHA256__',manifest['sha256']).replace('/* __FILE_HASHES__ */','\n'.join(entries))
(root/'wordpress/operations/editorial-blog-read-preflight.php').write_text(source)
print('Generated standalone blog-read preflight for '+manifest['sha256'])
