#!/usr/bin/env python3
import os
from pathlib import Path
import subprocess
import signal
import sys
import tempfile
import time
import unittest

RUNNER = Path(__file__).with_name('install-progress.py')


class Progress(unittest.TestCase):
    def run_fixture(self, code):
        with tempfile.TemporaryDirectory() as tmp:
            result = subprocess.run([sys.executable, str(RUNNER), '--log-dir', tmp,
                                     '--interval', '0.05', '--', sys.executable, '-u', '-c', code],
                                    stdout=subprocess.PIPE, stderr=subprocess.STDOUT, universal_newlines=True, timeout=10)
            logs = list(Path(tmp).glob('install-run-*.log'))
            self.assertEqual(len(logs), 1)
            self.assertEqual(logs[0].stat().st_mode & 0o777, 0o600)
            return result, logs[0].read_text()

    def test_quiet_phase_reports_elapsed_and_keeps_output(self):
        result, log = self.run_fixture("import time; print('[radius] Instalando'); time.sleep(.2); print('ultimo texto sem quebra',end='')")
        self.assertEqual(result.returncode, 0)
        self.assertIn('Executando: Log RADIUS', result.stdout)
        self.assertIn('ultimo texto sem quebra', log)
        self.assertIn('[OK] Instalacao concluida', result.stdout)

    def test_visual_panel_has_actual_timers_and_no_fake_percentage(self):
        result, log = self.run_fixture("print('[caixa] Instalando')")
        self.assertIn('INSTALACAO MK-AUTH | EM ANDAMENTO', result.stdout)
        self.assertIn('Etapa atual: Caixa', log)
        self.assertIn('Tempo total:', log)
        self.assertIn('INSTALACAO MK-AUTH | CONCLUIDA', log)
        self.assertNotIn('100%', log)

    def test_failure_is_not_reported_as_success(self):
        result, log = self.run_fixture("import sys; print('[erro] fixture'); sys.exit(7)")
        self.assertEqual(result.returncode, 7)
        self.assertIn('[erro] Instalacao falhou', log)
        self.assertNotIn('[OK] Instalacao concluida', result.stdout)

    def test_complete_message_is_at_end_of_installer(self):
        source = RUNNER.parent.parent.joinpath('install.sh').read_text()
        self.assertGreater(source.index('[OK] Todas as etapas da suite concluidas'), source.index('scripts/install-invoice-notes.php'))

    def test_cancellation_terminates_child(self):
        with tempfile.TemporaryDirectory() as tmp:
            marker = str(Path(tmp)/'should-not-run')
            child = "import time,pathlib; print('child-started',flush=True); time.sleep(2); pathlib.Path("+repr(marker)+").write_text('unexpected')"
            process = subprocess.Popen([sys.executable, str(RUNNER), '--log-dir', tmp,
                                        '--', sys.executable, '-u', '-c', child],
                                       stdout=subprocess.PIPE, stderr=subprocess.STDOUT, universal_newlines=True)
            while 'child-started' not in process.stdout.readline():
                if process.poll() is not None:
                    self.fail('Runner exited before starting child')
            process.send_signal(signal.SIGTERM)
            process.communicate(timeout=5)
            self.assertEqual(process.returncode, 130)
            time.sleep(2.1)
            self.assertFalse(Path(marker).exists())


if __name__ == '__main__':
    unittest.main()
