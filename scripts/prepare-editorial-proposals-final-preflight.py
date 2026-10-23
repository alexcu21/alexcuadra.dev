#!/usr/bin/env python3
"""Build a self-contained read-only command for Alex. Never invokes SSH."""
import base64
import hashlib
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SOURCE = Path('/private/tmp/ac-wp-private-restore-_tidfs0c/source')


def tree_digest(files, prefix, root_only=False):
    selected = {name[len(prefix):]: entry for name, entry in files.items()
                if name.startswith(prefix) and entry['type'] == 'file'
                and (not root_only or '/' not in name[len(prefix):])}
    digest = hashlib.sha256()
    for name, entry in sorted(selected.items()):
        digest.update(name.encode() + b'\0' + entry['sha256'].encode('ascii') + b'\n')
    return {'files': len(selected), 'bytes': sum(entry['bytes'] for entry in selected.values()), 'sha256': digest.hexdigest()}


def build():
    provenance = json.loads((SOURCE.parent / 'result.json').read_text())
    if provenance['archiveSha256'] != '13f20d924a5d161f28444fabf24adbcba40f1c7bbb8951a57c1e4a00f9541d62' or not provenance['restorationVerified']:
        raise ValueError('VERIFIED_BACKUP_REQUIRED')
    manifest = json.loads((SOURCE / 'backup/manifest.json').read_text())
    observed = json.loads((ROOT / 'docs/qa/MCP_BACKUP_PROFILE_2026-10-22.json').read_text())
    hosting = json.loads((ROOT / 'docs/qa/MCP_PROPOSALS_HOSTING_COMPONENTS_RESULT_2026-10-22.json').read_text())['hostingComponents']
    expected = {key: observed[key] for key in ['wordpress', 'php', 'databaseServer']}
    expected.update(plugins={p['file']: {'version': p['version'], 'active': p['active']} for p in observed['plugins']},
                    stylesheet=hosting['stylesheet'], template=hosting['template'], themeVersion=hosting['stylesheetVersion'])
    expected['trees'] = {name: tree_digest(manifest['files'], 'wp-content/' + name + '/') for name in ['plugins', 'mu-plugins', 'themes']}
    expected['trees']['content-root'] = tree_digest(manifest['files'], 'wp-content/', True)
    encoded = base64.b64encode(json.dumps(expected, separators=(',', ':')).encode()).decode()
    helper = (ROOT / 'wordpress/operations/editorial-proposals-final-preflight.php').read_text().replace('@@EXPECTED_PROFILE@@', encoded)
    script = '#!/bin/sh\n# Read-only operator check; Alex runs SSH. No scratch/export/grants/DDL.\nset -eu\ncd "$HOME/domains/alexcuadra.dev/public_html/content"\ntest -f wp-load.php\nprintf "%s\\n" EDITORIAL_PROPOSALS_FINAL_PREFLIGHT_V1\nwp --path=. eval-file - <<\'AC_PROPOSALS_PREFLIGHT_PHP\'\n' + helper + '\nAC_PROPOSALS_PREFLIGHT_PHP\nprintf "%s\\n" END_EDITORIAL_PROPOSALS_FINAL_PREFLIGHT_V1\n'
    destination = ROOT / 'wordpress/operations/editorial-proposals-final-preflight.sh'
    destination.write_text(script)
    baseline = ROOT / 'docs/qa/MCP_PROPOSALS_FINAL_PREFLIGHT_EXPECTED_2026-10-22.json'
    baseline.write_text(json.dumps(expected, indent=2) + '\n')
    print(json.dumps({'script': str(destination), 'scriptBytes': len(script.encode()), 'createsRemoteFiles': False, 'executesSsh': False}))


if __name__ == '__main__':
    build()
