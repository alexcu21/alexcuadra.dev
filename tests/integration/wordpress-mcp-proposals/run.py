#!/usr/bin/env python3
"""Offline synthetic CLI acceptance. Requires cached images and a retained synthetic seed."""
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import zipfile

ROOT = Path(__file__).resolve().parents[3]
HERE = Path(__file__).resolve().parent
PROJECT = 'alexcuadra-editorial-proposals-acceptance'
COMPOSE = ['docker', 'compose', '-f', str(HERE / 'compose.yaml'), '--profile', 'tools']
SEEDS = {name + '_wp-data': name for name in ['alexcuadra-editorial-application-lab', 'alexcuadra-editorial-graphql-lab']}


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
    seed = os.environ.get('EDITORIAL_PROPOSALS_SEED_VOLUME', next(iter(SEEDS)))
    if seed not in SEEDS:
        raise RuntimeError('KNOWN_SYNTHETIC_SEED_REQUIRED')
    volume = json.loads(run(['docker', 'volume', 'inspect', seed]))[0]
    if (volume.get('Labels') or {}).get('com.docker.compose.project') != SEEDS[seed]:
        raise RuntimeError('SYNTHETIC_SEED_LABEL_MISMATCH')
    # Inspect retained tmpfs contents before allocating the acceptance project.
    seed_image = 'wordpress:7.1.3-php8.2-apache@sha256:7cb0b86fdd78760c741db2fde3ed7a10084029164009e2c41b09de32f54607e8'
    seed_check = 'for p in polylang mcp-adapter wp-graphql advanced-custom-fields wpgraphql-acf wp-graphql-polylang; do test -d "/seed/wp-content/plugins/$p" || { echo "MISSING_SYNTHETIC_SEED_PLUGIN:$p" >&2; exit 1; }; done'
    run(['docker', 'run', '--rm', '--pull', 'never', '--network', 'none', '--mount', 'type=volume,src=' + seed + ',dst=/seed,readonly', seed_image, 'sh', '-c', seed_check])
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
              'localFreeBeforeBytes': free, 'seedVolume': seed, 'results': [],
              'realCmsBackupVerified': False, 'httpAcceptance': False, 'graphqlAcceptance': False}
    started = False
    try:
        started = True
        compose('up', '-d', '--wait', '--pull', 'never', 'wordpress')
        compose('run', '--rm', '--no-deps', '--pull', 'never', 'seed')
        cli('core', 'install', '--url=https://content.alexcuadra.dev', '--title=SyntheticProposalsAcceptance',
            '--admin_user=acceptance_admin', '--admin_password=synthetic-acceptance-only', '--admin_email=admin@example.test', '--skip-email')
        versions = {'polylang': '3.9.0', 'mcp-adapter': '0.7.1', 'wp-graphql': '2.24.2',
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
        print('PROPOSALS_ACCEPTANCE_RESULT=' + json.dumps(report), flush=True)
    finally:
        if started:
            # No down -v, pruning, touching seeds or older labs.
            compose('stop', 'wordpress', 'db')


if __name__ == '__main__':
    main()
