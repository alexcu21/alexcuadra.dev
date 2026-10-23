"""Read theme options in a temporary SQL-only container, never by executing source PHP."""
import hashlib
import json
from pathlib import Path
import re
import subprocess
import time

IMAGE = 'mariadb@sha256:f16d7a344413e519dff518f27bdb221388f84b0d274c97c4fb5f2cb0dc1b34fa'


def theme_metadata(source, values):
    result = {}
    for key in ['stylesheet', 'template']:
        slug = values.get(key)
        if not isinstance(slug, str) or not re.fullmatch(r'[A-Za-z0-9_-]+', slug):
            raise ValueError('INVALID_THEME_SLUG')
        header = source / 'wp-content/themes' / slug / 'style.css'
        if header.is_symlink() or not header.is_file():
            raise ValueError('SOURCE_THEME_MISSING')
        match = re.search(r'^\s*Version:\s*([^\r\n]+)', header.read_text(errors='strict')[:8192], re.M)
        if not match or not re.fullmatch(r'[A-Za-z0-9_.-]+', match[1].strip()):
            raise ValueError('INVALID_THEME_VERSION')
        result[key] = slug
        result[key + 'Version'] = match[1].strip()
    return result


def derive(source, manifest, run, work):
    sql = (source / 'backup/database.sql').read_bytes()
    if len(sql) != manifest['database']['bytes'] or hashlib.sha256(sql).hexdigest() != manifest['database']['sha256']:
        raise ValueError('SOURCE_SQL_HASH_MISMATCH')
    tables = set(manifest['profile']['schemaHashes'])
    prefixes = [n[:-7] for n in tables if n.endswith('options') and all(n[:-7] + suffix in tables for suffix in ['posts', 'postmeta', 'users', 'usermeta'])]
    if len(prefixes) != 1 or not re.fullmatch(r'[A-Za-z0-9_]+', prefixes[0]):
        raise ValueError('AMBIGUOUS_SOURCE_PREFIX')
    # An isolated daemon has no PHP, no host bind mounts, no ports and no network.
    name = work.name.replace('_', '-') + '-metadata'
    client = ['mariadb', '--socket=/socket/mysql.sock', '--binary-mode', '--local-infile=0', '-uroot', '-plocal-metadata-only']
    started = False
    try:
        run(['docker', 'image', 'inspect', IMAGE])
        started = True
        run(['docker', 'run', '-d', '--pull', 'never', '--name', name, '--network', 'none', '--log-driver', 'none',
             '--tmpfs', '/var/lib/mysql:rw,size=512m', '--tmpfs', '/socket:rw,size=8m',
             '-e', 'MARIADB_ROOT_PASSWORD=local-metadata-only', '-e', 'MARIADB_DATABASE=restore_source',
             IMAGE, '--skip-networking', '--socket=/socket/mysql.sock', '--pid-file=/socket/mysql.pid'])
        for _ in range(60):
            probe = subprocess.run(['docker', 'exec', name, 'sh', '-ec', 'test "$(cat /socket/mysql.pid 2>/dev/null)" = 1 && mariadb --socket=/socket/mysql.sock -uroot -plocal-metadata-only -e "SELECT 1" restore_source'], capture_output=True)
            if probe.returncode == 0:
                break
            time.sleep(2)
        else:
            raise ValueError('METADATA_DATABASE_NOT_READY')
        run(['docker', 'exec', '-i', name, *client, 'restore_source'], input=sql)
        table = prefixes[0] + 'options'
        query = "SELECT JSON_OBJECT('stylesheet',(SELECT option_value FROM `" + table + "` WHERE option_name='stylesheet'),'template',(SELECT option_value FROM `" + table + "` WHERE option_name='template'));"
        values = json.loads(run(['docker', 'exec', name, *client, '--batch', '--skip-column-names', 'restore_source', '-e', query]))
        result = theme_metadata(source, values)
        result['muPlugins'] = [{'file': p.name, 'sha256': hashlib.sha256(p.read_bytes()).hexdigest()} for p in sorted((source / 'wp-content/mu-plugins').glob('*.php'))]
        dropins = ['advanced-cache.php', 'db.php', 'db-error.php', 'install.php', 'maintenance.php', 'object-cache.php', 'php-error.php', 'fatal-error-handler.php', 'sunrise.php']
        present = [name for name in dropins if 'wp-content/' + name in manifest['files']]
        if present:
            raise ValueError('SOURCE_DROPINS_REQUIRE_SEPARATE_REVIEW')
        result.update(sourceDropins=[], sourceSqlOnlyUsedForThemeMetadata=True, sourceConfigurationExecuted=False)
        return result
    finally:
        if started:
            run(['docker', 'stop', '--time', '10', name])
