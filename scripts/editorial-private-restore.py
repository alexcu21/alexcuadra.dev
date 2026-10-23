#!/usr/bin/env python3
"""Restore a pinned private archive locally. No remote transport or source PHP execution."""
import argparse
import importlib.util
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import subprocess
import sys
import tarfile
import tempfile

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[1]
CHECKS = ROOT / 'tests/integration/wordpress-private-restore'
IMAGES = {
    'db': 'mariadb@sha256:f16d7a344413e519dff518f27bdb221388f84b0d274c97c4fb5f2cb0dc1b34fa',
    'seed': 'wordpress@sha256:c12bed9c5af9d76261221c2af3a6216a074dd80b4fc2459588b3cbf2dd8e1798',
    'cli': 'wordpress@sha256:52d8832dc8b90022c22fe963e696e4107d86f68b9b891e9624d82fec94fdc3b7',
}
spec = importlib.util.spec_from_file_location('backup', ROOT / 'scripts/editorial-private-backup.py')
backup = importlib.util.module_from_spec(spec)
spec.loader.exec_module(backup)


def prefix_for(manifest):
    names = set(manifest['profile']['schemaHashes'])
    candidates = [n[:-7] for n in names if n.endswith('options') and all(n[:-7] + s in names for s in ['posts', 'postmeta', 'users', 'usermeta'])]
    if len(candidates) != 1 or not re.fullmatch(r'[A-Za-z0-9_]+', candidates[0]):
        raise ValueError('AMBIGUOUS_TABLE_PREFIX')
    return candidates[0]


def extract_private(archive, target):
    # The complete archive has already passed the strict verifier; check paths again at extraction.
    with tarfile.open(archive, 'r:gz') as source:
        for member in source:
            path = PurePosixPath(member.name)
            if path.is_absolute() or '..' in path.parts or '\\' in member.name or not (member.isdir() or member.isfile()):
                raise ValueError('UNSAFE_EXTRACTION')
            destination = target / path.as_posix()
            if member.isdir():
                destination.mkdir(parents=True, mode=0o700, exist_ok=True)
            else:
                destination.parent.mkdir(parents=True, mode=0o700, exist_ok=True)
                with destination.open('xb') as output, source.extractfile(member) as data:
                    shutil.copyfileobj(data, output)


def configuration(prefix):
    return "<?php\n" + "\n".join([
        "define('DB_NAME', 'restore_source');", "define('DB_USER', 'restore_user');",
        "define('DB_PASSWORD', 'local-restore-only');", "define('DB_HOST', 'localhost:/socket/mysql.sock');",
        "define('DB_CHARSET', 'utf8mb4');", "define('DB_COLLATE', '');",
        "define('AC_PRIVATE_RESTORE', true);", "define('WP_ENVIRONMENT_TYPE', 'local');",
        "define('WP_HOME', 'https://content.alexcuadra.dev');", "define('WP_SITEURL', 'https://content.alexcuadra.dev');",
        "define('DISABLE_WP_CRON', true);", "define('DISALLOW_FILE_MODS', true);",
        "define('WP_HTTP_BLOCK_EXTERNAL', true);", "define('WP_CACHE', false);",
        "$table_prefix = '" + prefix + "';", "define('ABSPATH', __DIR__ . '/');",
        "require_once ABSPATH . 'wp-settings.php';", ""])


def compose(control):
    common = {'network_mode': 'none', 'pull_policy': 'never', 'logging': {'driver': 'none'}}
    return {'services': {
        'db': {**common, 'image': IMAGES['db'], 'environment': {
            'MARIADB_ROOT_PASSWORD': 'local-private-restore-root', 'MARIADB_DATABASE': 'restore_source',
            'MARIADB_USER': 'restore_user', 'MARIADB_PASSWORD': 'local-restore-only'},
            'command': ['--skip-networking', '--socket=/socket/mysql.sock', '--pid-file=/socket/mysql.pid'],
            'volumes': ['database:/var/lib/mysql', 'socket:/socket'],
            # Do not mistake the entrypoint's temporary initialization server for the final daemon.
            'healthcheck': {'test': ['CMD-SHELL', 'test "$(cat /socket/mysql.pid 2>/dev/null)" = 1 && mariadb-admin --socket=/socket/mysql.sock -uroot -plocal-private-restore-root ping'], 'interval': '2s', 'timeout': '2s', 'retries': 60}},
        'seed': {**common, 'image': IMAGES['seed'], 'entrypoint': ['/bin/sh', '-ec'],
            'command': ['cp -a /usr/src/wordpress/. /var/www/html/; cp /control/wp-config.php /var/www/html/wp-config.php; mkdir -p /var/www/html/wp-content/mu-plugins; cp /checks/guard.php /var/www/html/wp-content/mu-plugins/private-restore-guard.php'],
            'volumes': ['runtime:/var/www/html', str(control) + ':/control:ro', str(CHECKS) + ':/checks:ro']},
        'cli': {**common, 'image': IMAGES['cli'], 'user': '0:0', 'entrypoint': ['sleep'], 'command': ['infinity'],
            'volumes': ['runtime:/var/www/html', 'socket:/socket', str(control) + ':/control:ro', str(CHECKS) + ':/checks:ro']},
        }, 'volumes': {name: {'driver_opts': {'type': 'tmpfs', 'device': 'tmpfs', 'o': 'size=' + size}} for name, size in [('database', '768m'), ('runtime', '512m'), ('socket', '8m')]}}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--archive', type=Path, required=True)
    parser.add_argument('--sha256', required=True)
    parser.add_argument('--dry-run', action='store_true')
    args = parser.parse_args()
    os.umask(0o077)
    verified = backup.verify_archive(args.archive)
    if verified['archiveSha256'] != args.sha256:
        raise ValueError('PINNED_ARCHIVE_MISMATCH')
    free = shutil.disk_usage('/private/tmp').free
    if free < 8 * 1024**3:
        raise ValueError('AT_LEAST_8_GIB_FREE_REQUIRED')
    if args.dry_run:
        print(json.dumps({'verified': verified, 'freeBytes': free, 'createsFiles': False, 'startsContainers': False}))
        return
    work = Path(tempfile.mkdtemp(prefix='ac-wp-private-restore-', dir='/private/tmp'))
    print('PRIVATE_RESTORE_WORK=' + str(work), flush=True)
    log = (work / 'private-process.log').open('wb')
    def run(command, *, data=None):
        result = subprocess.run(command, input=data, stdout=subprocess.PIPE, stderr=log)
        if result.returncode:
            log.write(result.stdout)
            log.flush()
            raise RuntimeError('LOCAL_RESTORE_STAGE_FAILED_SEE_PRIVATE_LOG')
        return result.stdout
    project = 'ac-private-' + work.name.rsplit('-', 1)[-1]
    base = ['docker', 'compose', '-p', project, '-f', str(work / 'compose.json')]
    started = False
    try:
        endpoint = json.loads(run(['docker', 'context', 'inspect']))[0]['Endpoints']['docker']['Host']
        if os.environ.get('DOCKER_HOST') or not endpoint.startswith('unix://'):
            raise ValueError('LOCAL_DOCKER_SOCKET_REQUIRED')
        for image in IMAGES.values():
            run(['docker', 'image', 'inspect', image])
        private_archive = work / 'wordpress.tar.gz'
        with args.archive.open('rb') as original, private_archive.open('xb') as snapshot:
            shutil.copyfileobj(original, snapshot)
        if backup.verify_archive(private_archive)['archiveSha256'] != args.sha256:
            raise ValueError('PRIVATE_ARCHIVE_SNAPSHOT_MISMATCH')
        source = work / 'source'
        source.mkdir(mode=0o700)
        extract_private(private_archive, source)
        # Hash every extracted file as well, catching modification between verification and extraction.
        manifest = json.loads((source / 'backup/manifest.json').read_text())
        for name, entry in manifest['files'].items():
            if entry['type'] == 'file':
                with (source / name).open('rb') as handle:
                    size, digest = backup.digest_stream(handle)
                if (size, digest) != (entry['bytes'], entry['sha256']):
                    raise ValueError('EXTRACTED_FILE_MISMATCH')
        if backup.verify_archive(args.archive)['archiveSha256'] != args.sha256:
            raise ValueError('ARCHIVE_CHANGED')
        control = work / 'control'
        control.mkdir(mode=0o700)
        (control / 'manifest.json').write_text(json.dumps(manifest))
        (control / 'wp-config.php').write_text(configuration(prefix_for(manifest)))
        (work / 'compose.json').write_text(json.dumps(compose(control)))
        started = True
        run(base + ['up', '-d', '--wait', 'db'])
        # Keep the tmpfs runtime mounted while the seed container exits.
        run(base + ['up', '-d', 'cli'])
        run(base + ['run', '--rm', '--no-deps', 'seed'])
        ids = run(base + ['ps', '-q']).decode().split()
        inspections = json.loads(run(['docker', 'inspect', *ids]))
        if len(inspections) != 2 or any(c['HostConfig']['NetworkMode'] != 'none' or c['HostConfig']['PortBindings'] for c in inspections):
            raise ValueError('CONTAINER_ISOLATION_FAILED')
        sql = (source / 'backup/database.sql').read_bytes()
        import hashlib
        if len(sql) != manifest['database']['bytes'] or hashlib.sha256(sql).hexdigest() != verified['databaseSha256']:
            raise ValueError('EXTRACTED_SQL_MISMATCH')
        db = base + ['exec', '-T', 'db']
        client = ['mariadb', '--protocol=socket', '--socket=/socket/mysql.sock', '--binary-mode', '--local-infile=0', '-uroot', '-plocal-private-restore-root']
        run(db + client + ['restore_source'], data=sql)
        def inspect(database):
            state = json.loads(run(base + ['exec', '-T', 'cli', 'php', '/checks/inspect.php', database]))
            (work / (database + '-inspection.json')).write_text(json.dumps(state))
            return state
        first = inspect('restore_source')
        run(db + client, data=b"CREATE DATABASE restore_roundtrip; GRANT ALL ON restore_roundtrip.* TO 'restore_user'@'%';")
        run(db + ['bash', '-o', 'pipefail', '-c', 'mariadb-dump --socket=/socket/mysql.sock -uroot -plocal-private-restore-root --single-transaction --quick --skip-lock-tables --hex-blob --routines --events --triggers --no-tablespaces restore_source | mariadb --socket=/socket/mysql.sock -uroot -plocal-private-restore-root --binary-mode --local-infile=0 restore_roundtrip'])
        second = inspect('restore_roundtrip')
        for key in ['schemas', 'checksums', 'rows']:
            if first[key] != second[key]:
                raise ValueError('SQL_ROUNDTRIP_MISMATCH')
        output = run(base + ['exec', '-T', 'cli', 'wp', '--allow-root', '--url=https://content.alexcuadra.dev', '--skip-plugins', '--skip-themes', 'eval-file', '/checks/bootstrap.php']).decode()
        boot = json.loads(next(line.split('=', 1)[1] for line in output.splitlines() if line.startswith('PRIVATE_CORE_BOOTSTRAP=')))
        after = inspect('restore_source')
        bootstrap_unchanged = all(first[key] == after[key] for key in ['schemas', 'checksums', 'rows'])
        result = {'format': 'editorial-private-restoration-v1', 'archiveSha256': args.sha256,
            'fileHashesVerified': True, 'tableCount': first['tableCount'], 'tableHealthVerified': True,
            'sourceSchemaHashesMatched': True, 'localSqlRoundtripMatched': True, 'bootstrapDatabaseUnchanged': bootstrap_unchanged,
            'bootstrapChangedTableCount': sum(first['checksums'][name] != after['checksums'][name] for name in first['checksums']),
            'databaseServer': first['databaseServer'], 'bootstrap': boot, 'networkMode': 'none', 'portsPublished': False,
            'restorationVerified': True, 'applicationProfileAccepted': False, 'sshExecuted': False,
            'newImagesDownloaded': False, 'archiveRetained': True, 'privateWork': str(work)}
        (work / 'result.json').write_text(json.dumps(result, indent=2) + '\n')
    finally:
        if started:
            run(base + ['stop', '--timeout', '10'])
        log.close()
    result['containersStopped'] = True
    (work / 'result.json').write_text(json.dumps(result, indent=2) + '\n')
    print('EDITORIAL_PRIVATE_RESTORE_RESULT=' + json.dumps(result), flush=True)


if __name__ == '__main__':
    try:
        main()
    except Exception as error:
        reason = str(error) if re.fullmatch(r'[A-Z_]+', str(error)) else type(error).__name__
        print('PRIVATE_RESTORE_FAILED: ' + reason + '; inspect the private work log locally; no private contents printed.', file=sys.stderr)
        sys.exit(1)
