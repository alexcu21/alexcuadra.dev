import importlib.util
import io
import json
from pathlib import Path
import tarfile
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('restore', ROOT / 'scripts/editorial-private-restore.py')
restore = importlib.util.module_from_spec(spec)
spec.loader.exec_module(restore)


class PrivateRestoreTests(unittest.TestCase):
    def test_every_service_has_no_network_or_published_ports(self):
        config = restore.compose(Path('/private/tmp/test-control'))
        for service in config['services'].values():
            self.assertEqual(service['network_mode'], 'none')
            self.assertEqual(service['pull_policy'], 'never')
            self.assertNotIn('ports', service)
            self.assertFalse(any('/source' in mount for mount in service['volumes']))
        self.assertIn('--skip-networking', config['services']['db']['command'])
        self.assertTrue(all(v['driver_opts']['type'] == 'tmpfs' for v in config['volumes'].values()))

    def test_control_and_checks_are_read_only(self):
        for name in ['seed', 'cli']:
            mounts = restore.compose(Path('/private/tmp/test-control'))['services'][name]['volumes']
            self.assertTrue(all(m.endswith(':ro') for m in mounts if '/control:' in m or '/checks:' in m))

    def test_prefix_must_be_unique_and_safe(self):
        def manifest(prefix):
            return {'profile': {'schemaHashes': {prefix + s: 'x' for s in ['options', 'posts', 'postmeta', 'users', 'usermeta']}}}
        self.assertEqual(restore.prefix_for(manifest('test_')), 'test_')
        with self.assertRaises(ValueError):
            restore.prefix_for(manifest("bad';"))
        merged = manifest('one_')
        merged['profile']['schemaHashes'].update(manifest('two_')['profile']['schemaHashes'])
        with self.assertRaises(ValueError):
            restore.prefix_for(merged)

    def test_fresh_configuration_is_local_and_blocks_background_work(self):
        config = restore.configuration('test_')
        for required in ['localhost:/socket/mysql.sock', 'local-restore-only', "'DISABLE_WP_CRON', true", "'DISALLOW_FILE_MODS', true", "'WP_HTTP_BLOCK_EXTERNAL', true", "'WP_ENVIRONMENT_TYPE', 'local'"]:
            self.assertIn(required, config)

    def test_extraction_rejects_traversal_and_links(self):
        for name, kind in [('../escape', tarfile.REGTYPE), ('/escape', tarfile.REGTYPE), ('link', tarfile.SYMTYPE), ('hard', tarfile.LNKTYPE)]:
            with self.subTest(name=name), tempfile.TemporaryDirectory() as folder:
                folder = Path(folder)
                archive = folder / 'test.tar.gz'
                with tarfile.open(archive, 'w:gz') as handle:
                    entry = tarfile.TarInfo(name)
                    entry.type = kind
                    entry.linkname = '/outside'
                    handle.addfile(entry)
                with self.assertRaises(ValueError):
                    restore.extract_private(archive, folder / 'out')

    def test_extraction_does_not_execute_or_overwrite_source_files(self):
        with tempfile.TemporaryDirectory() as folder:
            folder = Path(folder)
            archive = folder / 'test.tar.gz'
            content = b'<?php throw new Exception("must never run");'
            with tarfile.open(archive, 'w:gz') as handle:
                entry = tarfile.TarInfo('wp-config.php')
                entry.size = len(content)
                handle.addfile(entry, io.BytesIO(content))
            restore.extract_private(archive, folder / 'out')
            self.assertEqual((folder / 'out/wp-config.php').read_bytes(), content)
            with self.assertRaises(FileExistsError):
                restore.extract_private(archive, folder / 'out')


if __name__ == '__main__':
    unittest.main()
