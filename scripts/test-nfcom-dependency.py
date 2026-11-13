#!/usr/bin/env python3
"""Isolated installer routing tests. Never invoke a production fiscal installer."""
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]


class FiscalDependency(unittest.TestCase):
    def test_download_validate_execute_and_failures(self):
        with tempfile.TemporaryDirectory() as tmp:
            base = Path(tmp)
            bins = base / 'bin'
            bins.mkdir()
            curl = bins / 'curl'
            curl.write_text('''#!/bin/sh
set -eu
while [ "$1" != "-o" ]; do shift; done
shift
cp "$FISCAL_FIXTURE" "$1"
''')
            curl.chmod(0o755)
            fixture = base / 'fixture.sh'
            # Exercise CRLF normalization too. This fixture only writes a test marker.
            fixture.write_bytes(b'#!/bin/sh\r\nprintf installed > "$FISCAL_MARKER"\r\n')
            marker = base / 'executed'
            env = dict(os.environ, PATH=str(bins)+os.pathsep+os.environ['PATH'],
                       FISCAL_FIXTURE=str(fixture), FISCAL_MARKER=str(marker))
            command = ['bash', str(ROOT/'scripts/install-nfcom-dependency.sh'),
                       '/opt/mk-auth/admin', str(base/'backup')]
            subprocess.run(command, env=env, check=True)
            self.assertEqual(marker.read_text(), 'installed')
            self.assertNotIn(b'\r', (base/'backup/install-all.sh').read_bytes())
            marker.unlink()
            fixture.write_text('#!/bin/sh\nexit 9\n')
            self.assertNotEqual(subprocess.run(command, env=env).returncode, 0)
            self.assertFalse(marker.exists())
            fixture.write_text('#!/bin/sh\nif\n')
            self.assertNotEqual(subprocess.run(command, env=env).returncode, 0)
            self.assertFalse(marker.exists())
            curl.write_text('#!/bin/sh\nexit 22\n')
            self.assertNotEqual(subprocess.run(command, env=env).returncode, 0)
            self.assertFalse(marker.exists())

    def test_alternative_root_never_runs_fixed_root_installer(self):
        with tempfile.TemporaryDirectory() as tmp:
            backup = Path(tmp)/'not-created'
            subprocess.run(['bash', str(ROOT/'scripts/install-nfcom-dependency.sh'),
                            str(Path(tmp)/'admin'), str(backup)], check=True)
            self.assertFalse(backup.exists())

    def test_suite_calls_bundle_before_completion(self):
        source = (ROOT/'install.sh').read_text()
        self.assertEqual(source.count('scripts/install-nfcom-dependency.sh'), 1)
        self.assertLess(source.index('scripts/install-nfcom-dependency.sh'),
                        source.index('echo "[OK] Todas as etapas da suite concluidas"'))


if __name__ == '__main__':
    unittest.main()
