import hashlib
import importlib.util
import os
from pathlib import Path
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('source_profile', ROOT / 'tests/integration/wordpress-proposals-profile/run.py')
profile_runner = importlib.util.module_from_spec(spec)
spec.loader.exec_module(profile_runner)


class SourcePluginProfileTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.source = Path(self.temp.name)
        self.file = self.source / 'synthetic/main.php'
        self.file.parent.mkdir()
        self.file.write_bytes(b'<?php // synthetic, never executed')
        self.profile = {'plugins': [{'file': 'synthetic/main.php', 'version': '1.0', 'active': True}]}
        self.manifest = {'profile': {'plugins': {'synthetic/main.php': {'version': '1.0', 'active': True}}},
                         'files': {'wp-content/plugins/synthetic/main.php': {'type': 'file', 'bytes': self.file.stat().st_size,
                             'sha256': hashlib.sha256(self.file.read_bytes()).hexdigest()}}}

    def verify(self):
        return profile_runner.verify_plugins(self.source, self.manifest, self.profile)

    def test_exact_file_bytes_and_profile_are_accepted_without_execution(self):
        self.assertEqual(self.verify(), (1, self.file.stat().st_size))

    def test_changed_bytes_are_rejected(self):
        self.file.write_bytes(b'changed')
        with self.assertRaisesRegex(ValueError, 'HASH_MISMATCH'):
            self.verify()

    def test_extra_file_is_rejected(self):
        (self.source / 'unexpected.php').write_bytes(b'extra')
        with self.assertRaisesRegex(ValueError, 'UNEXPECTED_PLUGIN_FILE'):
            self.verify()

    def test_missing_file_is_rejected(self):
        self.file.unlink()
        with self.assertRaisesRegex(ValueError, 'MISSING_PLUGIN_FILE'):
            self.verify()

    def test_symlink_is_rejected(self):
        (self.source / 'linked.php').symlink_to(self.file)
        with self.assertRaisesRegex(ValueError, 'SYMLINK_REJECTED'):
            self.verify()

    def test_special_file_is_rejected(self):
        os.mkfifo(self.source / 'unexpected-pipe')
        with self.assertRaisesRegex(ValueError, 'UNSUPPORTED_PLUGIN_FILE'):
            self.verify()

    def test_activation_state_or_version_drift_is_rejected(self):
        for key, value in [('active', False), ('version', '2.0')]:
            with self.subTest(key=key):
                before = self.profile['plugins'][0][key]
                self.profile['plugins'][0][key] = value
                with self.assertRaisesRegex(ValueError, 'PROFILE_DRIFT'):
                    self.verify()
                self.profile['plugins'][0][key] = before


if __name__ == '__main__':
    unittest.main()
