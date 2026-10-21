#!/usr/bin/env python3
"""Exercise the installer hook with a fake downloaded patch; no database access."""
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]

class AdditionalBlockInstall(unittest.TestCase):
    def test_hook_and_download_failure(self):
        source = (ROOT / 'install.sh').read_text()
        hook = source.split('install_additional_block_patch() {', 1)[1].split('\ninstall_reconcile() {', 1)[0]
        function = 'install_additional_block_patch() {' + hook
        self.assertEqual(source.splitlines().count('install_additional_block_patch'), 1)
        self.assertLess(source.index('\ninstall_additional_block_patch\n'), source.index('\ninstall_reconcile\n'))
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp)
            fake = root / 'curl'
            fake.write_text('#!/bin/sh\nif [ "$FAIL_DOWNLOAD" = 1 ]; then exit 22; fi\nwhile [ "$1" != -o ]; do shift; done\nshift\nprintf \'#!/bin/sh\\nprintf "called" > "$PATCH_CALLED"\\n\' > "$1"\n')
            fake.chmod(0o755)
            env = dict(os.environ, PATH=str(root)+os.pathsep+os.environ['PATH'], PATCH_CALLED=str(root/'called'), FAIL_DOWNLOAD='0')
            command = ['bash', '-eu', '-c', function+'\ninstall_additional_block_patch']
            subprocess.run(command, env=env, check=True)
            self.assertEqual((root/'called').read_text(), 'called')
            (root/'called').unlink()
            env['FAIL_DOWNLOAD'] = '1'
            result = subprocess.run(command, env=env, check=True, capture_output=True, text=True)
            self.assertIn('nao foi possivel baixar', result.stdout)
            self.assertFalse((root/'called').exists())

if __name__ == '__main__':
    unittest.main()
