#!/usr/bin/env python3
"""Synthetic data with hash-verified source plugin files; all raw logs remain private."""
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import tempfile
import zipfile

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[3]
HERE = Path(__file__).resolve().parent
BASE = HERE.parent / 'wordpress-mcp-proposals'
SOURCE = Path('/private/tmp/ac-wp-private-restore-_tidfs0c/source')
ARCHIVE_SHA = '13f20d924a5d161f28444fabf24adbcba40f1c7bbb8951a57c1e4a00f9541d62'


def verify_plugins(source, manifest, profile):
    expected_profile = {p['file']: {'version': p['version'], 'active': p['active']} for p in profile['plugins']}
    if manifest['profile']['plugins'] != expected_profile:
        raise ValueError('SOURCE_PLUGIN_PROFILE_DRIFT')
    expected = {name.removeprefix('wp-content/plugins/'): entry for name, entry in manifest['files'].items()
                if name.startswith('wp-content/plugins/') and entry['type'] == 'file'}
    actual = set()
    for path in source.rglob('*'):
        if path.is_symlink():
            raise ValueError('PLUGIN_SYMLINK_REJECTED')
        if not path.is_file() and not path.is_dir():
            raise ValueError('UNSUPPORTED_PLUGIN_FILE')
        if path.is_file():
            name = path.relative_to(source).as_posix()
            actual.add(name)
            if name not in expected:
                raise ValueError('UNEXPECTED_PLUGIN_FILE')
            content = path.read_bytes()
            if len(content) != expected[name]['bytes'] or hashlib.sha256(content).hexdigest() != expected[name]['sha256']:
                raise ValueError('PLUGIN_FILE_HASH_MISMATCH')
    if actual != set(expected):
        raise ValueError('MISSING_PLUGIN_FILE')
    return len(actual), sum(e['bytes'] for e in expected.values())


def main():
    os.umask(0o077)
    free = shutil.disk_usage('/private/tmp').free
    if free < 8 * 1024**3 or os.environ.get('DOCKER_HOST'):
        raise ValueError('LOCAL_SOCKET_AND_8_GIB_REQUIRED')
    # Pin provenance to the successful restoration, without mounting its SQL or configuration.
    provenance = json.loads((SOURCE.parent / 'result.json').read_text())
    if provenance['archiveSha256'] != ARCHIVE_SHA or provenance['restorationVerified'] is not True:
        raise ValueError('VERIFIED_RESTORATION_REQUIRED')
    profile = json.loads((ROOT / 'docs/qa/MCP_BACKUP_PROFILE_2026-10-22.json').read_text())
    manifest = json.loads((SOURCE / 'backup/manifest.json').read_text())
    count, size = verify_plugins(SOURCE / 'wp-content/plugins', manifest, profile)
    package = ROOT / 'artifacts/wordpress/alexcuadra-editorial-proposals-0.1.0-rc.2.zip'
    package_manifest = json.loads(package.with_suffix('.manifest.json').read_text())
    if hashlib.sha256(package.read_bytes()).hexdigest() != package_manifest['sha256']:
        raise ValueError('CANDIDATE_HASH_MISMATCH')
    with zipfile.ZipFile(package) as archive:
        if archive.testzip() is not None or archive.namelist() != [e['path'] for e in package_manifest['files']]:
            raise ValueError('CANDIDATE_ZIP_INVALID')
        for entry in package_manifest['files']:
            content = (ROOT / 'wordpress/plugins' / entry['path']).read_bytes()
            if archive.read(entry['path']) != content or hashlib.sha256(content).hexdigest() != entry['sha256']:
                raise ValueError('CANDIDATE_SOURCE_DRIFT')
    work = Path(tempfile.mkdtemp(prefix='ac-proposals-profile-', dir='/private/tmp'))
    print('PRIVATE_PROFILE_WORK=' + str(work), flush=True)
    log = (work / 'private-process.log').open('wb')
    stage = 'local-context'
    def run(args, **kwargs):
        completed = subprocess.run(args, cwd=ROOT, stdout=subprocess.PIPE, stderr=log, **kwargs)
        if completed.returncode:
            log.write(completed.stdout); log.flush()
            raise RuntimeError('PROFILE_STAGE_FAILED_' + stage.upper().replace('-', '_'))
        return completed.stdout.decode()
    endpoint = json.loads(run(['docker', 'context', 'inspect']))[0]['Endpoints']['docker']['Host']
    if not endpoint.startswith('unix://'):
        raise ValueError('LOCAL_DOCKER_REQUIRED')
    settings = json.loads(run(['docker', 'compose', '-f', str(BASE / 'compose.yaml'), '--profile', 'tools', 'config', '--format', 'json']))
    settings.pop('name', None)
    settings['volumes'].pop('seed-data')
    for volume in settings['volumes'].values():
        volume.pop('name', None)
    for network in settings['networks'].values():
        network.pop('name', None)
        if not network.get('internal'):
            raise ValueError('INTERNAL_NETWORK_REQUIRED')
    for name, service in settings['services'].items():
        if service.get('ports'):
            raise ValueError('HOST_PORTS_FORBIDDEN')
        run(['docker', 'image', 'inspect', service['image']])
        service['logging'] = {'driver': 'none'}
        service['pull_policy'] = 'never'
        service['volumes'].append({'type': 'bind', 'source': str(HERE), 'target': '/profile', 'read_only': True})
        if name in ['wordpress', 'cli']:
            service['environment'].update(AC_PROPOSALS_TRANSPORT='synthetic-http-v1', AC_PROPOSALS_PROFILE='backup-2026-10-22')
            service['volumes'].append({'type': 'bind', 'source': str(ROOT / 'docs/qa/MCP_BACKUP_PROFILE_2026-10-22.json'), 'target': '/recorded-profile.json', 'read_only': True})
    seed = settings['services']['seed']
    seed['volumes'] = [v for v in seed['volumes'] if v['target'] != '/seed']
    seed['volumes'].append({'type': 'bind', 'source': str(SOURCE / 'wp-content/plugins'), 'target': '/source-plugins', 'read_only': True})
    seed['entrypoint'] = ['sh', '/profile/seed.sh']
    config = work / 'compose.json'
    config.write_text(json.dumps(settings))
    project = work.name.replace('_', '-')
    compose = ['docker', 'compose', '-p', project, '-f', str(config), '--profile', 'tools']
    def cli(*args, shell=False):
        command = compose + ['run', '--rm', '--no-deps', '--pull', 'never']
        if shell:
            command += ['--entrypoint', 'sh']
        return run(command + ['cli', *args])
    def record(output, marker):
        lines = [json.loads(line[len(marker):]) for line in output.splitlines() if line.startswith(marker)]
        if len(lines) != 1 or lines[0].get('failed', 0) != 0:
            raise ValueError('MISSING_OR_FAILED_RESULT')
        return lines[0]
    results = []
    started = False
    try:
        stage = 'startup'; started = True
        run(compose + ['up', '-d', '--wait', '--pull', 'never', 'wordpress'])
        stage = 'seed'
        run(compose + ['run', '--rm', '--no-deps', '--pull', 'never', 'seed'])
        stage = 'core-install'
        cli('core', 'install', '--url=https://content.alexcuadra.dev', '--title=SyntheticSourcePlugins', '--admin_user=acceptance_admin', '--admin_password=synthetic-profile-only', '--admin_email=admin@example.invalid', '--skip-email')
        for plugin in profile['plugins']:
            if plugin['active']:
                stage = 'activate-' + plugin['file'].split('/')[0]
                cli('plugin', 'activate', plugin['file'])
        stage = 'activate-candidate'
        cli('plugin', 'activate', 'alexcuadra-editorial-proposals')
        stage = 'migration'
        cli('--user=acceptance_admin', 'eval', '$r=AlexCuadra\\EditorialProposals\\install_storage(); if ($r !== true) { WP_CLI::error("MIGRATION_FAILED"); }')
        stage = 'profile-before'
        actual = record(cli('eval-file', '/profile/assert-profile.php'), 'PROPOSALS_PROFILE_RESULT=')
        cli('eval', '$s=get_option("graphql_general_settings",[]); $s["restrict_endpoint_to_logged_in_users"]="on"; update_option("graphql_general_settings",$s);')
        for name, marker in [('check', 'EDITORIAL_CHANGES_RESULT='), ('history', 'EDITORIAL_CHANGES_HISTORY_RESULT='), ('quotas', 'EDITORIAL_CHANGES_QUOTAS_RESULT=')]:
            stage = name
            # CLI suites select their adapter mode explicitly; only the test adapter is affected.
            output = cli('-c', 'AC_PROPOSALS_TRANSPORT= php /acceptance/adapt-tests.php && wp eval-file /tmp/proposals-' + name + '.php', shell=True)
            results.append(record(output, marker))
        stage = 'http'
        results.append(record(cli('-c', 'php /acceptance/adapt-tests.php && cp /tmp/proposals-http-barrier.php wp-content/mu-plugins/proposals-http-barrier.php && wp eval-file /tmp/proposals-http.php', shell=True), 'EDITORIAL_CHANGES_HTTP_RESULT='))
        stage = 'graphql'
        results.append(record(cli('-c', 'php /acceptance/adapt-tests.php && wp eval-file /tmp/proposals-stack.php', shell=True), 'EDITORIAL_CHANGES_STACK_RESULT='))
        stage = 'profile-after'
        final_profile = record(cli('eval-file', '/profile/assert-profile.php'), 'PROPOSALS_PROFILE_RESULT=')
        if actual != final_profile:
            raise ValueError('PLUGIN_PROFILE_CHANGED_DURING_TESTS')
        if not results[-2].get('independentHttpConcurrencyVerified') or not results[-1].get('graphqlPrivacyVerified'):
            raise ValueError('TRANSPORT_ACCEPTANCE_INCOMPLETE')
        allowed = {'passed', 'failed', 'cases', 'environment', 'wordpress', 'php', 'polylang', 'versions',
                   'httpTransportVerified', 'independentHttpConcurrencyVerified', 'raceProofs', 'graphqlPrivacyVerified', 'sourceSnapshotPreserved'}
        report = {'format': 'proposals-source-plugin-profile-v1', 'candidateSha256': package_manifest['sha256'],
            'sourceArchiveSha256': ARCHIVE_SHA, 'verifiedPluginFiles': count, 'verifiedPluginBytes': size,
            'profile': actual, 'results': [{k: v for k, v in item.items() if k in allowed} for item in results],
            'passed': sum(item['passed'] for item in results), 'externalNetworkBlocked': True,
            'hostPortsPublished': False, 'sourceDatabaseImported': False, 'sourceConfigurationExecuted': False,
            'sourceMuThemesDropinsExecuted': False, 'fullHostingEnvironmentAccepted': False,
            'sshExecuted': False, 'newImagesDownloaded': False, 'liveCmsChanged': False,
            'localFreeBeforeBytes': free, 'localFreeAfterBytes': shutil.disk_usage(ROOT).free}
        (work / 'result.json').write_text(json.dumps(report, indent=2) + '\n')
    finally:
        if started:
            stage = 'stop'
            run(compose + ['stop', '--timeout', '10', 'wordpress', 'db'])
        log.close()
    report['containersStopped'] = True
    (work / 'result.json').write_text(json.dumps(report, indent=2) + '\n')
    print('PROPOSALS_SOURCE_PROFILE_RESULT=' + json.dumps(report))


if __name__ == '__main__':
    try:
        main()
    except Exception as error:
        reason = str(error) if re.fullmatch(r'[A-Z_0-9]+', str(error)) else type(error).__name__
        print('PRIVATE_PROFILE_FAILED=' + reason, file=sys.stderr)
        sys.exit(1)
