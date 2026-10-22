#!/usr/bin/env python3
"""Fetch only pinned public plugin sources into a new bounded temporary cache."""
import hashlib
import io
import json
from pathlib import Path, PurePosixPath
import stat
import ssl
import urllib.request
import zipfile

CACHE = Path('/private/tmp/alexcuadra-proposals-public-plugins-v2')
PLUGINS = {
    'polylang': ('3.9.1', 'https://downloads.wordpress.org/plugin/polylang.3.9.1.zip'),
    'mcp-adapter': ('0.7.1', 'https://downloads.wordpress.org/plugin/mcp-adapter.0.7.1.zip'),
    'wp-graphql': ('2.24.2', 'https://downloads.wordpress.org/plugin/wp-graphql.2.24.2.zip'),
    'advanced-custom-fields': ('6.8.10', 'https://downloads.wordpress.org/plugin/advanced-custom-fields.6.8.10.zip'),
    'wpgraphql-acf': ('3.0.0', 'https://downloads.wordpress.org/plugin/wpgraphql-acf.3.0.0.zip'),
    'wp-graphql-polylang': ('0.7.1', 'https://codeload.github.com/valu-digital/wp-graphql-polylang/zip/refs/tags/v0.7.1'),
}


def main():
    if CACHE.exists() and any(CACHE.iterdir()):
        raise RuntimeError('CACHE_EXISTS_PRESERVE_AND_REVIEW')
    CACHE.mkdir(mode=0o700, exist_ok=True)
    downloaded = 0
    extracted = 0
    manifest = []
    for slug, (version, url) in PLUGINS.items():
        request = urllib.request.Request(url, headers={'User-Agent': 'EditorialProposalsSyntheticAcceptance/1'})
        # macOS Python framework may lack its optional CA bundle; keep certificate verification using system roots.
        trust = ssl.create_default_context(cafile='/etc/ssl/cert.pem')
        with urllib.request.urlopen(request, timeout=60, context=trust) as response:
            payload = response.read(48 * 1024**2 - downloaded + 1)
        downloaded += len(payload)
        if downloaded > 48 * 1024**2:
            raise RuntimeError('DOWNLOAD_BUDGET_EXCEEDED')
        (CACHE / (slug + '.zip')).write_bytes(payload)
        with zipfile.ZipFile(io.BytesIO(payload)) as archive:
            roots = {PurePosixPath(item.filename).parts[0] for item in archive.infolist() if item.filename}
            if len(roots) != 1:
                raise RuntimeError('SINGLE_PLUGIN_ROOT_REQUIRED')
            for item in archive.infolist():
                path = PurePosixPath(item.filename)
                if path.is_absolute() or '..' in path.parts or '\\' in item.filename or stat.S_ISLNK(item.external_attr >> 16):
                    raise RuntimeError('UNSAFE_ZIP_ENTRY')
                relative = Path(*path.parts[1:])
                destination = CACHE / 'plugins' / slug / relative
                if item.is_dir():
                    destination.mkdir(parents=True, exist_ok=True)
                else:
                    extracted += item.file_size
                    if extracted > 256 * 1024**2:
                        raise RuntimeError('EXTRACTED_BUDGET_EXCEEDED')
                    destination.parent.mkdir(parents=True, exist_ok=True)
                    destination.write_bytes(archive.read(item))
        manifest.append({'slug': slug, 'version': version, 'url': url, 'zipBytes': len(payload), 'sha256': hashlib.sha256(payload).hexdigest()})
    (CACHE / 'manifest.json').write_text(json.dumps({'format': 'public-plugin-cache-v1', 'plugins': manifest,
        'downloadedBytes': downloaded, 'extractedBytes': extracted}, indent=2) + '\n')
    print(json.dumps({'cache': str(CACHE), 'downloadedBytes': downloaded, 'extractedBytes': extracted}))


if __name__ == '__main__':
    main()
