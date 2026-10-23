#!/usr/bin/env python3
"""Fresh synthetic schema acceptance. No SSH, source backup or external networking."""
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[3]
HERE = Path(__file__).resolve().parent
spec = importlib.util.spec_from_file_location('restore', ROOT / 'scripts/editorial-private-restore.py')
restore = importlib.util.module_from_spec(spec)
spec.loader.exec_module(restore)


def main():
    if shutil.disk_usage('/private/tmp').free < 8 * 1024**3 or os.environ.get('DOCKER_HOST'):
        raise RuntimeError('LOCAL_SOCKET_AND_8_GIB_REQUIRED')
    def run(args):
        result = subprocess.run(args, capture_output=True, text=True)
        if result.returncode:
            raise RuntimeError(result.stdout[-5000:] + result.stderr[-5000:])
        return result.stdout
    endpoint = json.loads(run(['docker', 'context', 'inspect']))[0]['Endpoints']['docker']['Host']
    if not endpoint.startswith('unix://'):
        raise RuntimeError('LOCAL_DOCKER_REQUIRED')
    for image in restore.IMAGES.values():
        run(['docker', 'image', 'inspect', image])
    work = Path(tempfile.mkdtemp(prefix='ac-proposals-schema-', dir='/private/tmp'))
    control = work / 'control'
    control.mkdir(mode=0o700)
    config = restore.configuration('schema_').replace("define('ABSPATH'", "define('AC_SCHEMA_TEST', true);\ndefine('AC_EDITORIAL_PROPOSALS_SITE', 'https://content.alexcuadra.dev');\ndefine('ABSPATH'")
    config = config.replace("define('ABSPATH', __DIR__ . '/');", "if (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }")
    (control / 'wp-config.php').write_text(config)
    settings = restore.compose(control)
    settings['services']['cli']['volumes'] += [str(HERE) + ':/schema:ro', str(ROOT / 'wordpress/plugins/alexcuadra-editorial-proposals') + ':/candidate:ro', str(ROOT / 'tests/operations') + ':/operations:ro']
    (work / 'compose.json').write_text(json.dumps(settings))
    project = work.name.replace('_', '-')
    base = ['docker', 'compose', '-p', project, '-f', str(work / 'compose.json')]
    cli = base + ['exec', '-T', 'cli', 'wp', '--allow-root']
    try:
        run(base + ['up', '-d', '--wait', 'db'])
        run(base + ['up', '-d', 'cli'])
        run(base + ['run', '--rm', '--no-deps', 'seed'])
        run(cli + ['core', 'install', '--url=https://content.alexcuadra.dev', '--title=SyntheticSchemaAcceptance', '--admin_user=schema_admin', '--admin_password=synthetic-only-schema', '--admin_email=admin@example.invalid', '--skip-email'])
        output = run(cli + ['--user=schema_admin', 'eval-file', '/schema/check.php'])
        report = json.loads(next(line.split('=', 1)[1] for line in output.splitlines() if line.startswith('PROPOSALS_SCHEMA_RESULT=')))
        reports = []
        for case in ['enabled', 'no-optin', 'wrong-optin', 'wrong-home', 'wrong-site', 'multisite', 'lab-active', 'no-storage', 'no-cli']:
            # Standalone fixture uses relative source paths; stage its original directory layout in /tmp.
            command = "mkdir -p /tmp/boundary/tests/operations /tmp/boundary/wordpress/plugins; cp /operations/editorial-proposals-candidate.php /tmp/boundary/tests/operations/; cp -R /candidate /tmp/boundary/wordpress/plugins/alexcuadra-editorial-proposals; php /tmp/boundary/tests/operations/editorial-proposals-candidate.php " + case
            reports.append(json.loads(run(base + ['exec', '-T', 'cli', 'sh', '-ec', command])))
        report.update(boundaryChecks=sum(r['checks'] for r in reports), boundaryScenarios=len(reports), networkMode='none', newImagesDownloaded=False, privateBackupUsed=False, liveCmsChanged=False)
    finally:
        run(base + ['stop', '--timeout', '10'])
    report.update(containersStopped=True)
    (work / 'result.json').write_text(json.dumps(report, indent=2) + '\n')
    print('PROPOSALS_SCHEMA_RESULT=' + json.dumps(report))
    print('SYNTHETIC_WORK=' + str(work))


if __name__ == '__main__':
    main()
