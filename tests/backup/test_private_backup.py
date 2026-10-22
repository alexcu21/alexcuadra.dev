import contextlib
import gzip
import hashlib
import importlib.util
import io
import json
import os
from pathlib import Path
import stat
import subprocess
import tarfile
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('private_backup', ROOT / 'scripts/editorial-private-backup.py')
backup = importlib.util.module_from_spec(spec)
spec.loader.exec_module(backup)


def fixture_archive(changes=None, extra=None):
    config = b'SYNTHETIC_PRIVATE_CONFIG_MUST_NOT_BE_PRINTED'
    sql = b'CREATE TABLE synthetic_only (id int);\n'
    manifest = {'format': 'ac-private-wordpress-backup-v1',
                'files': {'wp-config.php': {'type': 'file', 'mode': 0o600, 'bytes': len(config), 'sha256': hashlib.sha256(config).hexdigest()}},
                'database': {'bytes': len(sql), 'sha256': hashlib.sha256(sql).hexdigest()}}
    if changes:
        changes(manifest)
    output = io.BytesIO()
    with tarfile.open(fileobj=output, mode='w:gz') as archive:
        for name, data, kind in [('wp-config.php', config, 'file'), ('backup', b'', 'directory'),
                                  ('backup/manifest.json', json.dumps(manifest).encode(), 'file'), ('backup/database.sql', sql, 'file')] + (extra or []):
            info = tarfile.TarInfo(name)
            info.mode = 0o700 if kind == 'directory' else 0o600
            info.type = {'file': tarfile.REGTYPE, 'directory': tarfile.DIRTYPE, 'symlink': tarfile.SYMTYPE}[kind]
            if kind == 'symlink':
                info.linkname = '/outside/private'
            info.size = len(data) if kind == 'file' else 0
            archive.addfile(info, io.BytesIO(data) if kind == 'file' else None)
    return output.getvalue()


class PrivateBackupTests(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix='ac-private-backup-test-', dir='/private/tmp')
        self.folder = Path(self.temporary.name)

    def tearDown(self):
        self.temporary.cleanup()

    def write(self, content):
        path = self.folder / 'fixture.tar.gz'
        path.write_bytes(content)
        return path

    def test_valid_archive_checks_data_without_printing_or_extracting(self):
        path = self.write(fixture_archive())
        with patch.object(backup.subprocess, 'Popen', side_effect=AssertionError('SSH_FORBIDDEN')):
            result = backup.verify_archive(path)
        self.assertTrue(result['fileHashesVerified'])
        self.assertFalse(result['restorationVerified'])
        self.assertNotIn('SYNTHETIC_PRIVATE_CONFIG', json.dumps(result))
        self.assertEqual(list(self.folder.iterdir()), [path])

    def test_file_hash_tampering_rejected(self):
        path = self.write(fixture_archive(lambda m: m['files']['wp-config.php'].update(sha256='0' * 64)))
        with self.assertRaisesRegex(ValueError, 'FILE_MANIFEST'):
            backup.verify_archive(path)

    def test_sql_hash_tampering_rejected(self):
        path = self.write(fixture_archive(lambda m: m['database'].update(sha256='0' * 64)))
        with self.assertRaisesRegex(ValueError, 'DATABASE_HASH'):
            backup.verify_archive(path)

    def test_extra_file_rejected(self):
        path = self.write(fixture_archive(extra=[('unexpected.php', b'not expected', 'file')]))
        with self.assertRaisesRegex(ValueError, 'FILE_MANIFEST'):
            backup.verify_archive(path)

    def test_traversal_rejected_without_extraction(self):
        path = self.write(fixture_archive(extra=[('../outside', b'bad', 'file')]))
        with self.assertRaisesRegex(ValueError, 'UNSAFE'):
            backup.verify_archive(path)
        self.assertFalse((self.folder.parent / 'outside').exists())

    def test_absolute_path_rejected(self):
        path = self.write(fixture_archive(extra=[('/outside', b'bad', 'file')]))
        with self.assertRaisesRegex(ValueError, 'UNSAFE'):
            backup.verify_archive(path)

    def test_symlink_rejected(self):
        path = self.write(fixture_archive(extra=[('link', b'', 'symlink')]))
        with self.assertRaisesRegex(ValueError, 'UNSUPPORTED'):
            backup.verify_archive(path)

    def test_duplicate_member_rejected(self):
        path = self.write(fixture_archive(extra=[('wp-config.php', b'bad', 'file')]))
        with self.assertRaisesRegex(ValueError, 'DUPLICATE'):
            backup.verify_archive(path)

    def test_special_permission_bits_rejected(self):
        output = io.BytesIO()
        with tarfile.open(fileobj=io.BytesIO(fixture_archive()), mode='r:gz') as source, tarfile.open(fileobj=output, mode='w:gz') as target:
            for member in source:
                if member.name == 'wp-config.php':
                    member.mode |= 0o4000
                target.addfile(member, source.extractfile(member) if member.isfile() else None)
        path = self.write(output.getvalue())
        with self.assertRaisesRegex(ValueError, 'ARCHIVE_MODE'):
            backup.verify_archive(path)

    def test_truncated_gzip_rejected(self):
        path = self.write(fixture_archive()[:-8])
        with self.assertRaises((EOFError, OSError)):
            backup.verify_archive(path)

    def test_expansion_budget_enforced(self):
        path = self.write(fixture_archive())
        with patch.object(backup, 'MAX_EXPANDED', 32), self.assertRaisesRegex(ValueError, 'EXPANDED_BUDGET'):
            backup.verify_archive(path)

    def test_repository_and_symlink_destinations_rejected(self):
        with self.assertRaisesRegex(ValueError, 'OUTSIDE_REPOSITORY'):
            backup.private_destination(ROOT / 'private')
        link = self.folder / 'link'
        link.symlink_to(self.folder, target_is_directory=True)
        with self.assertRaisesRegex(ValueError, 'SYMLINK'):
            backup.private_destination(link / 'child')

    def test_public_existing_destination_rejected(self):
        path = self.folder / 'public'
        path.mkdir(mode=0o755)
        path.chmod(0o755)
        with self.assertRaisesRegex(ValueError, 'MUST_BE_PRIVATE'):
            backup.private_destination(path)

    def test_payload_contains_no_placeholders_and_dry_run_does_not_create(self):
        value = backup.remote_payload()
        self.assertNotIn(b'@@', value)
        self.assertIn(b'8.5.11', __import__('base64').b64decode(value.split(b"base64_decode('", 1)[1].split(b"'", 1)[0]))
        path = self.folder / 'new-private-destination'
        backup.private_destination(path)
        self.assertFalse(path.exists())

    def transport(self, payload, status=0, marker=True):
        class Fake:
            def __init__(self, args, **kwargs):
                if args != backup.SSH:
                    raise AssertionError('UNEXPECTED_TRANSPORT')
                self.stdout = io.BytesIO(payload)
                kwargs['stderr'].write(b'EDITORIAL_PRIVATE_BACKUP_STREAM_COMPLETE\n' if marker else b'no marker\n')
            def wait(self, **kwargs): return status
            def poll(self): return status
        return Fake

    def test_mock_transfer_private_modes_and_safe_summary(self):
        target = self.folder / 'backups'
        original_umask = os.umask(0o077)
        try:
            output = io.StringIO()
            with patch.object(backup.subprocess, 'Popen', self.transport(fixture_archive())), contextlib.redirect_stdout(output):
                backup.download(target)
            self.assertNotIn('SYNTHETIC_PRIVATE_CONFIG', output.getvalue())
            summaries = list(target.glob('*/verification.json'))
            self.assertEqual(len(summaries), 1)
            summary = json.loads(summaries[0].read_text())
            for file in summaries[0].parent.iterdir():
                self.assertEqual(stat.S_IMODE(file.stat().st_mode), 0o600)
            self.assertEqual(stat.S_IMODE(summaries[0].parent.stat().st_mode), 0o700)
            self.assertTrue(Path(summary['archive']).exists())
            self.assertFalse(list(target.glob('*/*.incomplete')))
        finally:
            os.umask(original_umask)

    def test_transport_failure_keeps_only_incomplete(self):
        target = self.folder / 'failure'
        original_umask = os.umask(0o077)
        try:
            with patch.object(backup.subprocess, 'Popen', self.transport(fixture_archive(), status=1)), contextlib.redirect_stdout(io.StringIO()), self.assertRaisesRegex(RuntimeError, 'SSH_BACKUP_FAILED'):
                backup.download(target)
            self.assertEqual(len(list(target.glob('*/*.incomplete'))), 1)
            self.assertFalse(list(target.glob('*/wordpress.tar.gz')))
        finally:
            os.umask(original_umask)

    def test_completion_marker_required(self):
        original_umask = os.umask(0o077)
        try:
            with patch.object(backup.subprocess, 'Popen', self.transport(fixture_archive(), marker=False)), contextlib.redirect_stdout(io.StringIO()), self.assertRaisesRegex(ValueError, 'MARKER_MISSING'):
                backup.download(self.folder / 'missing-marker')
        finally:
            os.umask(original_umask)

    def test_php_client_escaping(self):
        helper = (ROOT / 'wordpress/operations/editorial-private-backup.php').read_text().split('$work = realpath($workArg);', 1)[0]
        expression = r'''
+$value="a\\b\"' #;\n\r\t";
+if (ac_backup_option($value)!=='"a\\\\b\\"\' #;\\n\\r\\t"') { exit(1); }
+try { ac_backup_option("bad\0value"); exit(2); } catch (RuntimeException $e) {}
+echo "CLIENT_ESCAPING_OK\n";
+'''.replace('\n+', '\n')
        result = subprocess.run(['php'], input=helper + expression, text=True, capture_output=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn('CLIENT_ESCAPING_OK', result.stdout)

    def test_php_inventory_rejects_symlink_and_detects_file_changes(self):
        root = self.folder / 'source'
        (root / 'wp-content/plugins').mkdir(parents=True)
        (root / 'wp-config.php').write_text('SYNTHETIC_CONFIG')
        outside = self.folder / 'outside.txt'
        outside.write_text('SYNTHETIC_OUTSIDE_MUST_NOT_BE_READ')
        helper = (ROOT / 'wordpress/operations/editorial-private-backup.php').read_text().split('$work = realpath($workArg);', 1)[0]
        code = helper + '''
$root=getenv('AC_BACKUP_TEST_ROOT');
$first=ac_backup_inventory($root);
file_put_contents($root.'/wp-config.php','CHANGED_SYNTHETIC_CONFIG');
clearstatcache();
if(ac_backup_inventory($root)===$first){exit(1);}
symlink(getenv('AC_BACKUP_TEST_OUTSIDE'),$root.'/wp-content/plugins/link');
try { ac_backup_inventory($root); exit(2); } catch (RuntimeException $e) {}
echo "INVENTORY_BOUNDARIES_OK";
'''.replace('\n+', '\n')
        result = subprocess.run(['php'], input=code, text=True, capture_output=True,
                                env=dict(os.environ, AC_BACKUP_TEST_ROOT=str(root), AC_BACKUP_TEST_OUTSIDE=str(outside)))
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout, 'INVENTORY_BOUNDARIES_OK')

    def test_php_prepare_private_client_config_with_synthetic_wp_profile(self):
        task_home = self.folder / 'account'
        work = task_home / '.ac-editorial-backup.fixture'
        (work / 'backup').mkdir(parents=True, mode=0o700)
        work.chmod(0o700)
        root = self.folder / 'wordpress'
        (root / 'wp-admin/includes').mkdir(parents=True)
        (root / 'wp-admin/includes/plugin.php').write_text('<?php\n')
        (root / 'wp-content/plugins').mkdir(parents=True)
        (root / 'wp-config.php').write_text('SYNTHETIC_CONFIG_ONLY')
        php_version = subprocess.check_output(['php', '-r', 'echo PHP_VERSION;'], text=True)
        profile = {'wordpress': '7.1.3', 'php': php_version, 'databaseServer': 'SYNTHETIC_DB', 'plugins': {}}
        source = (ROOT / 'wordpress/operations/editorial-private-backup.php').read_text()
        source = source.replace("getenv('HOME')", "getenv('AC_BACKUP_TEST_HOME')").replace('@@EXPECTED_PROFILE@@', __import__('base64').b64encode(json.dumps(profile).encode()).decode())
        helper = self.folder / 'prepared-helper.php'
        helper.write_text(source)
        bootstrap = '''<?php
define('WP_CLI',true); define('ARRAY_A','ARRAY_A'); define('ABSPATH',getenv('AC_BACKUP_TEST_ROOT').'/');
define('DB_HOST','synthetic.local'); define('DB_NAME','synthetic_database');
define('DB_USER','synthetic_user'); define('DB_PASSWORD',"synthetic\\\\quoted\\\" #;\\npass");
function home_url(){return 'https://content.alexcuadra.dev';} function is_multisite(){return false;}
function get_bloginfo($key){return '7.1.3';} function get_plugins(){return [];} function is_plugin_active($file){return false;}
class FakeDb {
 public $last_error='';
 function get_results($query,$type){return [['table_type'=>'BASE TABLE','engine'=>'InnoDB','table_count'=>'37']];}
 function get_var($query){return 'SYNTHETIC_DB';}
 function get_col($query){return array_map(fn($n)=>'synthetic_'.$n,range(1,37));}
 function get_row($query,$type){return ['Create Table'=>'CREATE TABLE synthetic (id int) AUTO_INCREMENT=1'];}
}
$wpdb=new FakeDb(); $args=['prepare',getenv('AC_BACKUP_TEST_WORK')];
require getenv('AC_BACKUP_TEST_HELPER');
'''.replace('\n+', '\n')
        result = subprocess.run(['php'], input=bootstrap, text=True, capture_output=True,
                                env=dict(os.environ, AC_BACKUP_TEST_HOME=str(task_home), AC_BACKUP_TEST_ROOT=str(root),
                                         AC_BACKUP_TEST_WORK=str(work), AC_BACKUP_TEST_HELPER=str(helper)))
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout, '')
        self.assertEqual(stat.S_IMODE((work / 'client.cnf').stat().st_mode), 0o600)
        config = (work / 'client.cnf').read_text()
        self.assertIn('password="synthetic\\\\quoted\\" #;\\npass"', config)
        private_manifest = json.loads((work / 'backup/manifest.json').read_text())
        self.assertNotIn('password', json.dumps(private_manifest).lower())
        self.assertIn('wp-config.php', private_manifest['files'])

    def test_php_sql_collector_and_private_mode(self):
        task_home = self.folder / 'synthetic-account'
        work = task_home / '.ac-editorial-backup.fixture'
        (work / 'backup').mkdir(parents=True, mode=0o700)
        work.chmod(0o700)
        helper = self.folder / 'helper.php'
        source = (ROOT / 'wordpress/operations/editorial-private-backup.php').read_text().replace("getenv('HOME')", "getenv('AC_BACKUP_TEST_HOME')")
        helper.write_text(source)
        result = subprocess.run(['php', str(helper), 'collect-sql', str(work)], input=b'SYNTHETIC SQL\n',
                                env=dict(os.environ, AC_BACKUP_TEST_HOME=str(task_home)), capture_output=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual((work / 'backup/database.sql').read_bytes(), b'SYNTHETIC SQL\n')
        self.assertEqual(stat.S_IMODE((work / 'backup/database.sql').stat().st_mode), 0o600)


if __name__ == '__main__':
    unittest.main()
