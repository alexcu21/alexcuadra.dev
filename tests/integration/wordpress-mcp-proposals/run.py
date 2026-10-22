#!/usr/bin/env python3
"""Offline synthetic CLI acceptance with verified pinned public plugin cache."""
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import zipfile

ROOT = Path(__file__).resolve().parents[3]
HERE = Path(__file__).resolve().parent
PROJECT = 'alexcuadra-editorial-proposals-acceptance-v2'
COMPOSE = ['docker', 'compose', '-p', PROJECT, '-f', str(HERE / 'compose.yaml'), '-f', str(HERE / 'compose.public-cache.yaml'), '--profile', 'tools']
CACHE = Path('/private/tmp/alexcuadra-proposals-public-plugins-v2')


def run(args, **kwargs):
    result = subprocess.run(args, cwd=ROOT, text=True, capture_output=True, **kwargs)
    if result.returncode:
        raise RuntimeError('Command failed: ' + ' '.join(args[:7]) + '\n' + result.stdout[-4000:] + result.stderr[-4000:])
    return result.stdout


def compose(*args):
    return run(COMPOSE + list(args))


def cli(*args, restored=False, shell=False):
    command = ['run', '--rm', '--no-deps', '--pull', 'never']
    if restored:
        command += ['-e', 'WORDPRESS_DB_NAME=wordpress_restore']
    if shell:
        command += ['--entrypoint', 'sh']
    return compose(*(command + ['cli'] + list(args)))


def record(output, marker):
    matches = [json.loads(line[len(marker):]) for line in output.splitlines() if line.startswith(marker)]
    if len(matches) != 1:
        raise RuntimeError('Missing or duplicate result marker: ' + marker)
    report = matches[0]
    if report.get('failed', 0) != 0 or report.get('passed', 0) < 1:
        raise RuntimeError('Acceptance case failed: ' + json.dumps(report))
    return report


def main():
    # Refuse remote Docker endpoints before contacting any daemon (in particular ssh contexts).
    endpoint_override = os.environ.get('DOCKER_HOST', '')
    if endpoint_override and not endpoint_override.startswith('unix://'):
        raise RuntimeError('LOCAL_DOCKER_SOCKET_REQUIRED')
    context = json.loads(run(['docker', 'context', 'inspect']))[0]
    if not endpoint_override and not context['Endpoints']['docker']['Host'].startswith('unix://'):
        raise RuntimeError('LOCAL_DOCKER_CONTEXT_REQUIRED')
    free = shutil.disk_usage(ROOT).free
    if free < 8 * 1024**3:
        raise RuntimeError('AT_LEAST_8_GIB_LOCAL_FREE_REQUIRED')
    cache_manifest = json.loads((CACHE / 'manifest.json').read_text())
    expected = {'polylang': '3.9.1', 'mcp-adapter': '0.7.1', 'wp-graphql': '2.24.2',
                'advanced-custom-fields': '6.8.10', 'wpgraphql-acf': '3.0.0', 'wp-graphql-polylang': '0.7.1'}
    if {p['slug']: p['version'] for p in cache_manifest['plugins']} != expected:
        raise RuntimeError('CACHE_PROFILE_MISMATCH')
    for plugin in cache_manifest['plugins']:
        slug = plugin['slug']
        archive = CACHE / (slug + '.zip')
        if hashlib.sha256(archive.read_bytes()).hexdigest() != plugin['sha256']:
            raise RuntimeError('PUBLIC_PACKAGE_HASH_MISMATCH')
        expected_files = set()
        with zipfile.ZipFile(archive) as package:
            for item in package.infolist():
                if item.is_dir():
                    continue
                relative = Path(*Path(item.filename).parts[1:])
                source = CACHE / 'plugins' / slug / relative
                if source.is_symlink() or source.read_bytes() != package.read(item):
                    raise RuntimeError('PUBLIC_EXTRACTED_BYTES_MISMATCH')
                expected_files.add(relative.as_posix())
        actual = {p.relative_to(CACHE / 'plugins' / slug).as_posix() for p in (CACHE / 'plugins' / slug).rglob('*') if p.is_file()}
        if actual != expected_files:
            raise RuntimeError('UNEXPECTED_PUBLIC_CACHE_FILE')
    # Require a fresh acceptance project; never reset/reuse another test's data.
    old = run(['docker', 'ps', '-aq', '--filter', 'label=com.docker.compose.project=' + PROJECT]).strip()
    if old:
        raise RuntimeError('ACCEPTANCE_CONTAINERS_ALREADY_EXIST_PRESERVE_AND_REVIEW')
    old_volumes = run(['docker', 'volume', 'ls', '-q', '--filter', 'label=com.docker.compose.project=' + PROJECT]).strip()
    if old_volumes:
        raise RuntimeError('ACCEPTANCE_VOLUMES_ALREADY_EXIST_PRESERVE_AND_REVIEW')
    config = json.loads(compose('config', '--format', 'json'))
    if not config['networks']['default'].get('internal'):
        raise RuntimeError('INTERNAL_NETWORK_REQUIRED')
    for service in config['services'].values():
        if service.get('ports'):
            raise RuntimeError('HOST_PORTS_FORBIDDEN')
        run(['docker', 'image', 'inspect', service['image']])
    archive = ROOT / 'artifacts/wordpress/alexcuadra-editorial-proposals-0.1.0-rc.1.zip'
    manifest = json.loads(archive.with_suffix('.manifest.json').read_text())
    if hashlib.sha256(archive.read_bytes()).hexdigest() != manifest['sha256']:
        raise RuntimeError('ZIP_HASH_MISMATCH')
    with zipfile.ZipFile(archive) as package:
        if package.namelist() != [entry['path'] for entry in manifest['files']]:
            raise RuntimeError('ZIP_ENTRIES_MISMATCH')
        for entry in manifest['files']:
            source = ROOT / 'wordpress/plugins' / entry['path']
            content = source.read_bytes()
            if package.read(entry['path']) != content or hashlib.sha256(content).hexdigest() != entry['sha256']:
                raise RuntimeError('SOURCE_ZIP_BYTES_MISMATCH')
    report = {'format': 'proposals-synthetic-cli-acceptance-v1', 'candidateSha256': manifest['sha256'],
              'localFreeBeforeBytes': free, 'publicPluginCache': cache_manifest, 'results': [],
              'realCmsBackupVerified': False, 'httpAcceptance': False, 'graphqlAcceptance': False}
    started = False
    try:
        started = True
        compose('up', '-d', '--wait', '--pull', 'never', 'wordpress')
        compose('run', '--rm', '--no-deps', '--pull', 'never', 'seed')
        cli('core', 'install', '--url=https://content.alexcuadra.dev', '--title=SyntheticProposalsAcceptance',
            '--admin_user=acceptance_admin', '--admin_password=synthetic-acceptance-only', '--admin_email=admin@example.test', '--skip-email')
        versions = {'polylang': '3.9.1', 'mcp-adapter': '0.7.1', 'wp-graphql': '2.24.2',
                    'advanced-custom-fields': '6.8.10', 'wpgraphql-acf': '3.0.0', 'wp-graphql-polylang': '0.7.1'}
        for plugin, version in versions.items():
            if cli('plugin', 'get', plugin, '--field=version').strip() != version:
                raise RuntimeError('PLUGIN_VERSION_MISMATCH_' + plugin)
        cli('plugin', 'activate', *versions.keys(), 'alexcuadra-editorial-proposals')
        cli('--user=acceptance_admin', 'eval', '$r=AlexCuadra\\EditorialProposals\\install_storage(); if ($r !== true) { WP_CLI::error("MIGRATION_FAILED"); }')
        # Reuse existing scenarios by adapting tests only, never the candidate or its guard.
        for name, marker in [('check', 'EDITORIAL_CHANGES_RESULT='), ('history', 'EDITORIAL_CHANGES_HISTORY_RESULT='), ('quotas', 'EDITORIAL_CHANGES_QUOTAS_RESULT=')]:
            output = cli('-c', 'php /acceptance/adapt-tests.php && wp eval-file /tmp/proposals-' + name + '.php', shell=True)
            report['results'].append(record(output, marker))
        report['results'].append(record(cli('eval-file', '/acceptance/restore.php', 'before'), 'PROPOSALS_RESTORE_RESULT='))
        backup = '''set -eu
mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --skip-comments --hex-blob wordpress > /archive/proposals.sql
sha256sum /archive/proposals.sql
wc -c < /archive/proposals.sql
mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -e "CREATE DATABASE wordpress_restore; GRANT ALL ON wordpress_restore.* TO 'wordpress'@'%';"
mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" wordpress_restore < /archive/proposals.sql
'''
        report['syntheticArchiveMetadata'] = compose('exec', '-T', 'db', 'sh', '-c', backup).splitlines()
        report['results'].append(record(cli('eval-file', '/acceptance/restore.php', 'after', restored=True), 'PROPOSALS_RESTORE_RESULT='))
        cli('eval', '$u=get_user_by("login","archive_owner"); $u->remove_cap("alexcuadra_prepare_project_changes"); $u->remove_cap("alexcuadra_read_public_projects");')
        report['passed'] = sum(item['passed'] for item in report['results'])
        report['localFreeAfterBytes'] = shutil.disk_usage(ROOT).free
        (Path('/private/tmp') / (PROJECT + '-result.json')).write_text(json.dumps(report, indent=2) + '\n')
        print('PROPOSALS_ACCEPTANCE_RESULT=' + json.dumps(report), flush=True)
    finally:
        if started:
            # No down -v, pruning, touching seeds or older labs.
            compose('stop', 'wordpress', 'db')


if __name__ == '__main__':
    main()
