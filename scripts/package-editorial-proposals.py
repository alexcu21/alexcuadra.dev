#!/usr/bin/env python3
"""Build a deterministic review candidate from an explicit source allowlist."""
import hashlib
import io
import json
from pathlib import Path
import zipfile

ROOT = Path(__file__).resolve().parents[1]
SLUG = 'alexcuadra-editorial-proposals'
VERSION = '0.1.0-rc.2'
SOURCE = ROOT / 'wordpress/plugins' / SLUG
OUTPUT = ROOT / 'artifacts/wordpress'
FILES = ['alexcuadra-editorial-proposals.php', 'storage.php', 'README.md']


def build():
    output = io.BytesIO()
    entries = []
    with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name in FILES:
            source = SOURCE / name
            if source.is_symlink():
                raise ValueError('Symlink rejected: ' + name)
            data = source.read_bytes()
            entry = zipfile.ZipInfo(SLUG + '/' + name, (2026, 10, 22, 0, 0, 0))
            entry.compress_type = zipfile.ZIP_DEFLATED
            entry.external_attr = 0o100644 << 16
            archive.writestr(entry, data)
            entries.append({'path': entry.filename, 'bytes': len(data), 'sha256': hashlib.sha256(data).hexdigest()})
    payload = output.getvalue()
    with zipfile.ZipFile(io.BytesIO(payload)) as archive:
        assert archive.testzip() is None
        assert archive.namelist() == [e['path'] for e in entries]
        for entry in entries:
            assert hashlib.sha256(archive.read(entry['path'])).hexdigest() == entry['sha256']
    manifest = {'format': 'editorial-proposals-review-candidate-v1', 'version': VERSION,
                'targetSite': 'https://content.alexcuadra.dev', 'decision': 'hold',
                'installationApproved': False, 'sourceWritesIncluded': False,
                'zipBytes': len(payload), 'sha256': hashlib.sha256(payload).hexdigest(), 'files': entries}
    OUTPUT.mkdir(parents=True, exist_ok=True)
    stem = SLUG + '-' + VERSION
    (OUTPUT / (stem + '.zip')).write_bytes(payload)
    (OUTPUT / (stem + '.manifest.json')).write_text(json.dumps(manifest, indent=2) + '\n')
    print(json.dumps(manifest))


if __name__ == '__main__':
    build()
