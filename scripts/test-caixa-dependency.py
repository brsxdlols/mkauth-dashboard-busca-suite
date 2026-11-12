#!/usr/bin/env python3
"""Exercise dependency routing without a database or production addon."""
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]


class CaixaDependency(unittest.TestCase):
    def test_official_arguments_preservation_and_failure(self):
        with tempfile.TemporaryDirectory() as tmp:
            base = Path(tmp)
            admin = base / 'mk-auth/admin'
            addon = admin / 'addons/rel_caixa'
            addon.mkdir(parents=True)
            (addon / 'config.php').write_text('existing config')
            (addon / 'model.php').write_text('existing private model')
            bin_dir = base / 'bin'
            bin_dir.mkdir()
            curl = bin_dir / 'curl'
            curl.write_text('#!/bin/sh\nwhile [ "$1" != "-o" ]; do shift; done\nshift\ncp "$CAIXA_FIXTURE" "$1"\n')
            curl.chmod(0o755)
            fixture = base / 'official.sh'
            fixture.write_text('''#!/bin/sh
set -eu
[ "$1" = --ref ] && [ "$2" = 65e625ef8bdd8a1047d85955b8fe2199aaad5199 ]
[ "$3" = --root ] && [ "$5" = --no-menu ]
for file in index.php rollback.sh; do printf 'official' > "$4/admin/addons/rel_caixa/$file"; done
''')
            env = dict(os.environ, PATH=str(bin_dir)+os.pathsep+os.environ['PATH'], CAIXA_FIXTURE=str(fixture))
            command = ['bash', str(ROOT/'scripts/install-caixa-dependency.sh'), str(admin), str(base/'backup')]
            subprocess.run(command, env=env, check=True)
            self.assertEqual((addon/'config.php').read_text(), 'existing config')
            self.assertEqual((addon/'model.php').read_text(), 'existing private model')
            fixture.write_text('#!/bin/sh\nexit 9\n')
            self.assertNotEqual(subprocess.run(command, env=env).returncode, 0)
            self.assertEqual((addon/'config.php').read_text(), 'existing config')

    def test_suite_uses_official_installer_without_legacy_copy(self):
        source = (ROOT/'install.sh').read_text()
        self.assertNotIn('for addon in rel_clientes rel_caixa', source)
        self.assertIn('scripts/install-caixa-dependency.sh', source)


if __name__ == '__main__':
    unittest.main()
