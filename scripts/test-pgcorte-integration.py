"""Isolated fake packages only. No real patch, RADIUS, database or router changes."""
import contextlib
import hashlib
import importlib.util
import io
import json
import os
from pathlib import Path
import stat
import subprocess
import tempfile
import types
import unittest
from unittest import mock
import zipfile

ROOT = Path(__file__).resolve().parent.parent
spec = importlib.util.spec_from_file_location('pgcorte_installer', ROOT / 'scripts/install-pgcorte-dual.py')
installer = importlib.util.module_from_spec(spec)
spec.loader.exec_module(installer)


class PatchIntegrationTests(unittest.TestCase):
    def package(self, extra=None):
        buffer = io.BytesIO()
        with zipfile.ZipFile(buffer, 'w') as archive:
            for name, text in {'install.sh': '#!/bin/sh\nexit 0\n', 'patch.py': '# fixture', 'native_pgcorte.py': '# fixture', 'SHA256SUMS': '# fixture'}.items():
                archive.writestr(installer.PACKAGE_NAME + '/' + name, text)
            for name, data in (extra or {}).items():
                archive.writestr(name, data)
        return buffer.getvalue()

    def run_patch(self, codes=(0, 0, 0, 0), corrupt=False, failed_download=False, package=None, repeat=False):
        payload = package or self.package()
        digest = hashlib.sha256(payload).hexdigest()
        calls, downloads = [], []
        with tempfile.TemporaryDirectory(prefix='pgcorte-integration-fixture-') as folder:
            base = Path(folder)
            def download(url, target, limit):
                downloads.append(url)
                if failed_download:
                    raise OSError('fixture download failure')
                target.write_bytes(payload if url == installer.ZIP_URL else ((('0' * 64 if corrupt else digest) + '  ' + installer.ARCHIVE_NAME + '\n').encode()))
            def command(args, **kwargs):
                calls.append((args, kwargs['cwd']))
                kwargs['stdout'].write('fixture output ' + str(len(calls)) + '\n')
                return subprocess.CompletedProcess(args, codes[(len(calls) - 1) % 4])
            fake_lock = types.SimpleNamespace(LOCK_EX=1, LOCK_NB=2, flock=lambda *args: None)
            output = io.StringIO()
            with mock.patch.object(installer.os, 'geteuid', return_value=0, create=True), mock.patch.object(installer, 'download', side_effect=download), mock.patch.object(installer.subprocess, 'run', side_effect=command), mock.patch.dict('sys.modules', {'fcntl': fake_lock}), contextlib.redirect_stdout(output):
                code = installer.install(base / 'logs', base / 'patches')
                if repeat:
                    marker = base / 'patches' / installer.PACKAGE_NAME / 'local-note.txt'
                    marker.write_text('preserve me')
                    code = installer.install(base / 'logs', base / 'patches')
                    self.assertEqual(marker.read_text(), 'preserve me')
            reports = [json.loads(p.read_text()) for p in (base / 'logs').glob('*.json')]
            logs = [p.read_text() for p in (base / 'logs').glob('*.log')]
            if os.name == 'posix':
                for p in (base / 'logs').iterdir():
                    self.assertEqual(stat.S_IMODE(p.stat().st_mode), 0o600)
            return code, calls, downloads, reports, logs, output.getvalue()

    def test_all_four_commands_and_checksum(self):
        code, calls, downloads, reports, logs, output = self.run_patch()
        self.assertEqual(code, 0)
        self.assertEqual(downloads, [installer.ZIP_URL, installer.CHECKSUM_URL])
        self.assertEqual(calls[0][0][0], 'sh')
        self.assertTrue(calls[0][0][1].endswith('/mkauth-pgcorte-dual/install.sh') or calls[0][0][1].endswith('\\mkauth-pgcorte-dual\\install.sh'))
        self.assertEqual([x[0] for x in calls[1:]], [['python3', 'patch.py', 'check'], ['python3', 'native_pgcorte.py', 'check'], ['python3', 'native_pgcorte.py', 'check', '--admin']])
        self.assertEqual(len(set(x[1] for x in calls)), 1)
        self.assertTrue(reports[0]['completed'])
        self.assertIn('5 minutos', output)
        self.assertIn('fixture output 4', logs[0])

    def test_mismatch_never_extracts_or_runs(self):
        with mock.patch.object(installer, 'extract_verified', side_effect=AssertionError('extracted before verification')):
            code, calls, _, reports, _, output = self.run_patch(corrupt=True)
        self.assertEqual(code, 1)
        self.assertFalse(calls)
        self.assertFalse(reports[0]['completed'])
        self.assertIn('nao concluido', output)

    def test_each_nonzero_prevents_success_and_all_checks_still_run(self):
        for index in range(4):
            for value in (1, 2, 4):
                with self.subTest(index=index, value=value):
                    codes = [0] * 4
                    codes[index] = value
                    code, calls, _, reports, _, output = self.run_patch(codes)
                    self.assertNotEqual(code, 0)
                    self.assertEqual(len(calls), 4)
                    self.assertFalse(reports[0]['completed'])
                    self.assertNotIn('aprovado nas tres', output)
                    if value == 4 and index in (0, 1):
                        self.assertEqual(code, 4)
                        self.assertIn('Ativacao RADIUS pendente', output)

    def test_network_failure_does_not_run_stale_package(self):
        code, calls, _, reports, logs, _ = self.run_patch(failed_download=True)
        self.assertEqual(code, 1)
        self.assertFalse(calls)
        self.assertIn('fixture download failure', logs[0])

    def test_traversal_and_symlink_rejected(self):
        for name in ('../outside', installer.PACKAGE_NAME + '/../outside', '/absolute', 'another-package/install.sh'):
            with self.subTest(name=name):
                code, calls, _, _, _, _ = self.run_patch(package=self.package({name: 'bad'}))
                self.assertEqual(code, 1)
                self.assertFalse(calls)
        buffer = io.BytesIO(self.package())
        with zipfile.ZipFile(buffer, 'a') as archive:
            info = zipfile.ZipInfo(installer.PACKAGE_NAME + '/link')
            info.create_system = 3
            info.external_attr = (stat.S_IFLNK | 0o777) << 16
            archive.writestr(info, '/etc/fixture')
        self.assertEqual(self.run_patch(package=buffer.getvalue())[0], 1)

    def test_updates_reapply_and_preserve_extra_files(self):
        code, calls, downloads, reports, _, _ = self.run_patch(repeat=True)
        self.assertEqual(code, 0)
        self.assertEqual(len(calls), 8)
        self.assertEqual(len(downloads), 4)
        self.assertEqual(len(reports), 2)

    def test_nonroot_refused(self):
        with tempfile.TemporaryDirectory() as folder, mock.patch.object(installer.os, 'geteuid', return_value=1000, create=True), mock.patch.object(installer, 'download') as download, contextlib.redirect_stdout(io.StringIO()):
            self.assertEqual(installer.install(Path(folder) / 'logs', Path(folder) / 'patches'), 1)
            download.assert_not_called()

    @unittest.skipUnless(os.name == 'posix', 'Real shell subprocess fixture requires Linux')
    def test_real_children_and_installer_exit_propagation(self):
        for failure in (None, 'install', 'radius', 'native', 'admin'):
            with self.subTest(failure=failure), tempfile.TemporaryDirectory(prefix='pgcorte-real-children-') as folder:
                base = Path(folder)
                buffer = io.BytesIO()
                values = {'install': 0, 'radius': 0, 'native': 0, 'admin': 0}
                if failure:
                    values[failure] = 4 if failure == 'radius' else 7
                contents = {
                    'install.sh': '#!/bin/sh\nprintf "fixture install output\\n"\nexit ' + str(values['install']) + '\n',
                    'patch.py': 'import sys\nprint("fixture radius output")\nsys.exit(' + str(values['radius']) + ')\n',
                    'native_pgcorte.py': 'import sys\nprint("fixture native/admin output")\nsys.exit(' + str(values['admin']) + ' if "--admin" in sys.argv else ' + str(values['native']) + ')\n',
                    'SHA256SUMS': '# fixture\n',
                }
                with zipfile.ZipFile(buffer, 'w') as package:
                    for name, data in contents.items():
                        package.writestr(installer.PACKAGE_NAME + '/' + name, data)
                payload = buffer.getvalue()
                def download(url, target, limit):
                    target.write_bytes(payload if url == installer.ZIP_URL else (hashlib.sha256(payload).hexdigest() + '  ' + installer.ARCHIVE_NAME).encode())
                with mock.patch.object(installer, 'download', side_effect=download), mock.patch.object(installer.os, 'geteuid', return_value=0), contextlib.redirect_stdout(io.StringIO()):
                    code = installer.install(base / 'logs', base / 'patches')
                report = json.loads(next((base / 'logs').glob('*.json')).read_text())
                self.assertEqual(len(report['steps']), 4)
                self.assertEqual(code, 0 if failure is None else (4 if failure == 'radius' else 1))
                self.assertEqual(report['completed'], failure is None)
                self.assertIn('fixture install output', next((base / 'logs').glob('*.log')).read_text())

    def test_mandatory_step_in_common_install_update_flow(self):
        source = (ROOT / 'install.sh').read_text()
        hook = 'python3 -u "${SCRIPT_DIR}/scripts/install-pgcorte-dual.py" "${BACKUP_DIR}/pgcorte-dual"'
        self.assertEqual(source.count(hook), 1)
        self.assertNotIn('||', source.split(hook, 1)[1].splitlines()[0])
        self.assertLess(source.index('install_reconcile\n'), source.index(hook))
        self.assertLess(source.index('install-native-web-sessions.py'), source.index(hook))
        self.assertLess(source.index(hook), source.index('[OK] Todas as etapas da suite concluidas'))
        self.assertIn('[suite-progress] 19/19', source)


if __name__ == '__main__':
    unittest.main(verbosity=2)
