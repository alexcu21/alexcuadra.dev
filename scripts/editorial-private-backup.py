#!/usr/bin/env python3
"""Alex executes this coordinator. The agent must never invoke its SSH mode."""
import argparse
import base64
from datetime import datetime, timezone
import gzip
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import secrets
import shutil
import stat
import subprocess
import tarfile
import tempfile

ROOT = Path(__file__).resolve().parents[1]
PROFILE = ROOT / 'docs/qa/MCP_BACKUP_PROFILE_2026-10-22.json'
MAX_ARCHIVE = 2 * 1024**3
MAX_EXPANDED = 3 * 1024**3
SSH = ['ssh', '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=yes', '-o', 'ConnectTimeout=10',
       '-p', '65002', 'u776991923@194.164.64.104', 'bash -s']


def digest_stream(stream):
    digest = hashlib.sha256()
    size = 0
    while chunk := stream.read(1024**2):
        size += len(chunk)
        digest.update(chunk)
    return size, digest.hexdigest()


def remote_payload():
    observed = json.loads(PROFILE.read_text())
    expected = {k: observed[k] for k in ['wordpress', 'php', 'databaseServer']}
    expected['plugins'] = {p['file']: {'version': p['version'], 'active': p['active']} for p in sorted(observed['plugins'], key=lambda p: p['file'])}
    encoded = base64.b64encode(json.dumps(expected, separators=(',', ':')).encode()).decode()
    helper = (ROOT / 'wordpress/operations/editorial-private-backup.php').read_text().replace('@@EXPECTED_PROFILE@@', encoded)
    script = (ROOT / 'wordpress/operations/editorial-private-backup.sh').read_text().replace('@@PHP_HELPER@@', helper)
    if '@@' in script or '\nAC_BACKUP_HELPER\n' in helper:
        raise ValueError('INVALID_REMOTE_TEMPLATE')
    return script.encode()


def verify_archive(path):
    path = Path(path)
    if path.is_symlink() or not path.is_file() or path.stat().st_size > MAX_ARCHIVE:
        raise ValueError('INVALID_OR_OVERSIZED_ARCHIVE')
    # Consume the entire gzip stream to verify CRC/truncation, including its trailer.
    expanded = 0
    with gzip.open(path, 'rb') as stream:
        while chunk := stream.read(1024**2):
            expanded += len(chunk)
            if expanded > MAX_EXPANDED:
                raise ValueError('EXPANDED_BUDGET_EXCEEDED')
    actual = {}
    manifest = None
    logical_size = 0
    with tarfile.open(path, 'r|gz') as archive:
        for member in archive:
            parts = PurePosixPath(member.name)
            key = parts.as_posix()
            if parts.is_absolute() or '..' in parts.parts or '\\' in member.name or key in actual or key in ['', '.']:
                raise ValueError('UNSAFE_OR_DUPLICATE_MEMBER')
            if len(actual) >= 100000 or not (member.isdir() or member.isfile()):
                raise ValueError('UNSUPPORTED_ARCHIVE_MEMBER')
            logical_size += member.size
            if logical_size > MAX_EXPANDED or member.size < 0:
                raise ValueError('LOGICAL_ARCHIVE_BUDGET_EXCEEDED')
            if member.mode & 0o7000:
                raise ValueError('UNSUPPORTED_ARCHIVE_MODE')
            entry = {'type': 'directory' if member.isdir() else 'file', 'mode': member.mode & 0o777}
            if member.isfile():
                stream = archive.extractfile(member)
                if key == 'backup/manifest.json':
                    if member.size > 16 * 1024**2:
                        raise ValueError('MANIFEST_TOO_LARGE')
                    data = stream.read()
                    manifest = json.loads(data)
                    entry.update(bytes=len(data), sha256=hashlib.sha256(data).hexdigest())
                else:
                    size, digest = digest_stream(stream)
                    if size != member.size:
                        raise ValueError('TRUNCATED_MEMBER')
                    entry.update(bytes=size, sha256=digest)
            actual[key] = entry
    if not isinstance(manifest, dict) or manifest.get('format') != 'ac-private-wordpress-backup-v1':
        raise ValueError('BACKUP_MANIFEST_REQUIRED')
    expected = dict(manifest['files'])
    sql = actual.pop('backup/database.sql', None)
    actual.pop('backup/manifest.json', None)
    directory = actual.pop('backup', None)
    if directory is None or directory['type'] != 'directory' or sql is None or sql['type'] != 'file':
        raise ValueError('DATABASE_AND_METADATA_REQUIRED')
    if {'bytes': sql['bytes'], 'sha256': sql['sha256']} != manifest['database'] or sql['bytes'] < 1:
        raise ValueError('DATABASE_HASH_MISMATCH')
    if actual != expected or 'wp-config.php' not in actual:
        raise ValueError('FILE_MANIFEST_MISMATCH')
    with path.open('rb') as stream:
        size, digest = digest_stream(stream)
    return {'format': 'editorial-private-backup-verification-v1', 'archiveBytes': size, 'archiveSha256': digest,
            'expandedBytes': expanded, 'databaseBytes': sql['bytes'], 'databaseSha256': sql['sha256'],
            'selectedEntries': len(actual), 'fileHashesVerified': True, 'restorationVerified': False}


def private_destination(value, create=False):
    path = Path(value).expanduser()
    if not path.is_absolute():
        raise ValueError('ABSOLUTE_DESTINATION_REQUIRED')
    for parent in [path, *path.parents]:
        if parent.is_symlink():
            raise ValueError('DESTINATION_SYMLINK_REQUIRES_REVIEW')
    resolved = path.resolve()
    if resolved == ROOT or ROOT in resolved.parents:
        raise ValueError('BACKUP_MUST_BE_OUTSIDE_REPOSITORY')
    if resolved.parts[:2] == ('/', 'Volumes'):
        if len(resolved.parts) < 3 or not os.path.ismount(Path(*resolved.parts[:3])):
            raise ValueError('EXTERNAL_VOLUME_NOT_MOUNTED')
    nearest = resolved
    while not nearest.exists():
        nearest = nearest.parent
    if shutil.disk_usage(nearest).free < 8 * 1024**3:
        raise ValueError('AT_LEAST_8_GIB_ON_DESTINATION_REQUIRED')
    if resolved.exists() and (not resolved.is_dir() or resolved.stat().st_uid != os.getuid() or stat.S_IMODE(resolved.stat().st_mode) & 0o077):
        raise ValueError('EXISTING_DESTINATION_MUST_BE_PRIVATE_AND_OWNED')
    if create:
        resolved.mkdir(parents=True, mode=0o700, exist_ok=True)
        if stat.S_IMODE(resolved.stat().st_mode) != 0o700 or resolved.stat().st_uid != os.getuid():
            raise ValueError('DESTINATION_CANNOT_ENFORCE_PRIVATE_PERMISSIONS')
    return resolved


def download(destination):
    os.umask(0o077)
    base = private_destination(destination, create=True)
    folder = base / (datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ') + '-' + secrets.token_hex(4))
    folder.mkdir(mode=0o700)
    if stat.S_IMODE(folder.stat().st_mode) != 0o700 or folder.stat().st_uid != os.getuid():
        raise ValueError('DESTINATION_CANNOT_ENFORCE_PRIVATE_PERMISSIONS')
    incomplete = folder / 'wordpress.tar.gz.incomplete'
    payload = remote_payload()
    process = None
    try:
        with tempfile.TemporaryFile() as commands, incomplete.open('xb') as output, (folder / 'transfer.stderr.log').open('xb') as errors:
            if any(stat.S_IMODE(os.fstat(handle.fileno()).st_mode) != 0o600 for handle in [output, errors]):
                raise ValueError('FILESYSTEM_CANNOT_ENFORCE_PRIVATE_FILE_MODES')
            commands.write(payload)
            commands.seek(0)
            process = subprocess.Popen(SSH, stdin=commands, stdout=subprocess.PIPE, stderr=errors)
            total = 0
            while chunk := process.stdout.read(1024**2):
                total += len(chunk)
                if total > MAX_ARCHIVE:
                    raise ValueError('COMPRESSED_BUDGET_EXCEEDED')
                output.write(chunk)
            process.stdout.close()
            if process.wait() != 0:
                raise RuntimeError('SSH_BACKUP_FAILED_SEE_PRIVATE_LOG')
        log = (folder / 'transfer.stderr.log').read_bytes()
        if b'EDITORIAL_PRIVATE_BACKUP_STREAM_COMPLETE' not in log:
            raise ValueError('REMOTE_COMPLETION_MARKER_MISSING')
        summary = verify_archive(incomplete)
        # Never overwrite an older backup. Only rename our unique incomplete file after verification.
        final = folder / 'wordpress.tar.gz'
        if final.exists():
            raise ValueError('FINAL_BACKUP_ALREADY_EXISTS')
        incomplete.rename(final)
        summary['archive'] = str(final)
        (folder / 'verification.json').write_text(json.dumps(summary, indent=2) + '\n')
        print('EDITORIAL_PRIVATE_BACKUP_RESULT=' + json.dumps(summary))
    except BaseException:
        if process is not None and process.poll() is None:
            process.terminate()
            try:
                process.wait(timeout=10)
            except subprocess.TimeoutExpired:
                process.kill()
                process.wait()
        print('EDITORIAL_PRIVATE_BACKUP_INCOMPLETE=' + str(folder), flush=True)
        raise


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--destination', default=str(Path.home() / 'PrivateBackups/alexcuadra.dev'))
    mode = parser.add_mutually_exclusive_group()
    mode.add_argument('--dry-run', action='store_true')
    mode.add_argument('--verify', type=Path)
    args = parser.parse_args()
    if args.verify is not None:
        print('EDITORIAL_PRIVATE_BACKUP_RESULT=' + json.dumps(verify_archive(args.verify)))
    elif args.dry_run:
        print(json.dumps({'destination': str(private_destination(args.destination)), 'remotePayloadBytes': len(remote_payload()),
                          'sshExecuted': False, 'filesCreated': False, 'compressedBudgetBytes': MAX_ARCHIVE,
                          'expandedBudgetBytes': MAX_EXPANDED, 'containsPrivateDataAndConfig': True}))
    else:
        download(args.destination)


if __name__ == '__main__':
    main()
