#!/usr/bin/env python3
"""Deterministic, closed-by-default blog-read release candidate; no deployment."""
import hashlib
import io
import json
from pathlib import Path
import zipfile

ROOT = Path(__file__).resolve().parents[1]
SLUG = 'alexcuadra-editorial-blog-read'
VERSION = '0.1.0-rc.1'
SOURCE = ROOT / 'wordpress/packages' / SLUG
LAB = ROOT / 'wordpress/experimental/editorial-blog-read' / (SLUG + '.php')
FILES = [SLUG + '.php', 'reader.php', 'README.md']
HEADER = "<?php\nnamespace AlexCuadra\\EditorialBlogRead;\nif (!defined('ABSPATH')) { exit; }\n"


def build():
    lab = LAB.read_text()
    expected = HEADER + lab[lab.index("const CAPABILITY ="):]
    if (SOURCE / 'reader.php').read_text() != expected:
        raise ValueError('READER_DIFFERS_FROM_TESTED_LAB')
    if sorted(p.name for p in SOURCE.iterdir()) != sorted(FILES):
        raise ValueError('UNEXPECTED_PACKAGE_FILES')
    output = io.BytesIO()
    entries = []
    with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name in FILES:
            path = SOURCE / name
            if path.is_symlink():
                raise ValueError('SYMLINK_REJECTED')
            data = path.read_bytes()
            info = zipfile.ZipInfo(SLUG + '/' + name, (2026, 10, 26, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, data)
            entries.append({'path': info.filename, 'bytes': len(data), 'sha256': hashlib.sha256(data).hexdigest()})
    payload = output.getvalue()
    with zipfile.ZipFile(io.BytesIO(payload)) as archive:
        assert archive.testzip() is None
        assert archive.namelist() == [e['path'] for e in entries]
    manifest = {'format': 'editorial-blog-read-review-v1', 'version': VERSION,
        'targetSite': 'https://content.alexcuadra.dev', 'decision': 'hold', 'installationApproved': False,
        'capabilityGrantApproved': False, 'sourceWritesIncluded': False,
        'capability': 'alexcuadra_read_public_posts', 'plannedUserId': 2,
        'labSourceSha256': hashlib.sha256(LAB.read_bytes()).hexdigest(),
        'sha256': hashlib.sha256(payload).hexdigest(), 'zipBytes': len(payload), 'files': entries}
    folder = ROOT / 'artifacts/wordpress'
    folder.mkdir(parents=True, exist_ok=True)
    stem = SLUG + '-' + VERSION
    (folder / (stem + '.zip')).write_bytes(payload)
    (folder / (stem + '.manifest.json')).write_text(json.dumps(manifest, indent=2) + '\n')
    print(json.dumps({'version': VERSION, 'sha256': manifest['sha256'], 'bytes': len(payload)}))


if __name__ == '__main__':
    build()
