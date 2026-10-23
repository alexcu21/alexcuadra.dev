import hashlib
import importlib.util
import json
from pathlib import Path
import subprocess
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('hosting', ROOT / 'tests/integration/wordpress-proposals-profile/hosting.py')
hosting = importlib.util.module_from_spec(spec)
spec.loader.exec_module(hosting)


class HostingComponentTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.source = Path(self.temp.name)
        theme = self.source / 'wp-content/themes/synthetic'
        theme.mkdir(parents=True)
        (theme / 'style.css').write_text('/*\nTheme Name: Synthetic\nVersion: 1.2.3\n*/\n')
        (self.source / 'wp-content/mu-plugins').mkdir()
        (self.source / 'backup').mkdir()
        sql = b'-- synthetic source metadata only\n'
        (self.source / 'backup/database.sql').write_bytes(sql)
        self.values = {'stylesheet': 'synthetic', 'template': 'synthetic'}
        self.manifest = {'database': {'bytes': len(sql), 'sha256': hashlib.sha256(sql).hexdigest()},
                         'profile': {'schemaHashes': {('safe_' + suffix): '' for suffix in ['options', 'posts', 'postmeta', 'users', 'usermeta']}}, 'files': {}}

    def test_theme_identity_is_only_slug_and_version(self):
        self.assertEqual(hosting.theme_metadata(self.source, self.values),
                         {'stylesheet': 'synthetic', 'stylesheetVersion': '1.2.3', 'template': 'synthetic', 'templateVersion': '1.2.3'})

    def test_paths_nulls_and_non_string_theme_values_are_rejected(self):
        for value in ['../outside', '/absolute', 'bad/name', None, 1]:
            with self.subTest(value=value), self.assertRaises(ValueError):
                hosting.theme_metadata(self.source, {**self.values, 'stylesheet': value})

    def test_missing_theme_is_rejected(self):
        with self.assertRaisesRegex(ValueError, 'SOURCE_THEME_MISSING'):
            hosting.theme_metadata(self.source, {**self.values, 'stylesheet': 'missing'})

    def test_symlink_header_is_rejected(self):
        header = self.source / 'wp-content/themes/synthetic/style.css'
        header.rename(header.with_suffix('.saved'))
        header.symlink_to(header.with_suffix('.saved'))
        with self.assertRaisesRegex(ValueError, 'SOURCE_THEME_MISSING'):
            hosting.theme_metadata(self.source, self.values)

    def test_sql_hash_failure_happens_before_docker(self):
        (self.source / 'backup/database.sql').write_bytes(b'changed')
        with self.assertRaisesRegex(ValueError, 'SOURCE_SQL_HASH_MISMATCH'):
            hosting.derive(self.source, self.manifest, lambda *args, **kwargs: self.fail('Docker must not run'), self.source)

    def test_metadata_container_is_networkless_has_no_host_mount_and_always_stops(self):
        calls = []
        def run(args, **kwargs):
            calls.append(args)
            return json.dumps(self.values) if '-e' in args and args[-2] == '-e' else ''
        with patch.object(hosting.subprocess, 'run', return_value=subprocess.CompletedProcess([], 0)):
            result = hosting.derive(self.source, self.manifest, run, self.source)
        command = next(args for args in calls if args[:2] == ['docker', 'run'])
        self.assertEqual(command[command.index('--network') + 1], 'none')
        self.assertNotIn('--mount', command)
        self.assertNotIn('-v', command)
        self.assertNotIn('-p', command)
        self.assertIn('--skip-networking', command)
        self.assertEqual(calls[-1][:2], ['docker', 'stop'])
        self.assertFalse(result['sourceConfigurationExecuted'])

    def test_metadata_query_failure_still_stops_its_container(self):
        calls = []
        def run(args, **kwargs):
            calls.append(args)
            if '-e' in args and args[-2] == '-e':
                raise RuntimeError('synthetic query failure')
            return ''
        with patch.object(hosting.subprocess, 'run', return_value=subprocess.CompletedProcess([], 0)):
            with self.assertRaises(RuntimeError):
                hosting.derive(self.source, self.manifest, run, self.source)
        self.assertEqual(calls[-1][:2], ['docker', 'stop'])


if __name__ == '__main__':
    unittest.main()
