#!/usr/bin/env python3
import os
import importlib.util
from pathlib import Path
import subprocess
import signal
import re
import sys
import tempfile
import time
import unittest

RUNNER = Path(__file__).with_name('install-progress.py')
spec = importlib.util.spec_from_file_location('install_progress', RUNNER)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class Progress(unittest.TestCase):
    def run_fixture(self, code, terminal=False, columns=None):
        with tempfile.TemporaryDirectory() as tmp:
            env=dict(os.environ)
            if columns is not None:env.update(COLUMNS=str(columns),NO_COLOR='1')
            result = subprocess.run([sys.executable, str(RUNNER)]+(['--terminal'] if terminal else [])+['--log-dir', tmp,
                                     '--interval', '0.05', '--', sys.executable, '-u', '-c', code],
                                    stdout=subprocess.PIPE, stderr=subprocess.STDOUT, universal_newlines=True, timeout=10,env=env)
            logs = list(Path(tmp).glob('install-run-*.log'))
            self.assertEqual(len(logs), 1)
            self.assertEqual(logs[0].stat().st_mode & 0o777, 0o600)
            return result, logs[0].read_text()

    def test_quiet_phase_reports_elapsed_and_keeps_output(self):
        result, log = self.run_fixture("import time; print('[radius] Instalando'); time.sleep(.2); print('ultimo texto sem quebra',end='')")
        self.assertEqual(result.returncode, 0)
        self.assertIn('Log RADIUS [####################] 100%', result.stdout)
        self.assertIn('ultimo texto sem quebra', log)
        self.assertIn('[OK] Instalacao concluida', result.stdout)

    def test_compact_progress_keeps_details_only_in_log(self):
        result, log = self.run_fixture("print('[caixa] Instalando'); print('detalhe interno')")
        self.assertIn('[OK] Caixa [####################] 100%', result.stdout)
        self.assertNotIn('detalhe interno', result.stdout)
        self.assertIn('detalhe interno', log)
        self.assertNotIn('+----', result.stdout)

    def test_unknown_progress_waits_for_real_completion(self):
        result,log=self.run_fixture("import time; print('[caixa] Instalando'); time.sleep(.8); raise SystemExit(7)",terminal=True)
        self.assertIn('Caixa em execucao',result.stdout)
        self.assertNotIn('estimado',result.stdout)
        self.assertNotIn('[OK] Caixa',result.stdout)
        self.assertNotIn('Caixa [',result.stdout)
        self.assertEqual(result.returncode,7)

    def test_reported_checkpoints_are_real_monotonic_and_not_time_estimates(self):
        code="import time; print('[ntp] 20% Conferindo'); print('[ntp] 40% Backup'); print('[ntp] 40% Duplicado'); print('[ntp] 30% Atrasado'); time.sleep(1.1); print('[ntp] 65% Sincronizando'); print('[ntp] 85% Validando')"
        result,log=self.run_fixture(code)
        values=[int(x) for x in re.findall(r'Correcao e verificacao NTP \[[#-]+\] (\d+)%',result.stdout)]
        unique=[v for i,v in enumerate(values) if i==0 or v!=values[i-1]]
        self.assertEqual(unique,[20,40,65,85,100])
        self.assertEqual(values,sorted(values))
        self.assertNotIn('estimado',result.stdout)
        self.assertIn('[ntp] 30% Atrasado',log)

    def test_reported_hundred_waits_for_successful_exit(self):
        result,log=self.run_fixture("print('[ntp] 20% Inicio'); print('[ntp] 100% Declarado pelo filho'); raise SystemExit(7)")
        self.assertNotIn('NTP [####################] 100%',result.stdout)
        self.assertIn('validando conclusao',result.stdout)
        self.assertNotIn('[OK] Correcao',result.stdout)
        self.assertEqual(result.returncode,7)

    def test_report_parser_accepts_only_known_valid_phase_checkpoints(self):
        self.assertEqual(module.reported_percent('[ntp] 65% Sincronizando'),65)
        for line in ['detalhe 50%','[erro] 20% erro','[ntp] 101% invalido','[unknown] 40% etapa','[ntp] -1% invalido']:
            self.assertIsNone(module.reported_percent(line))

    def test_mobile_render_frames_fit_terminal_width(self):
        result,log=self.run_fixture("import time; print('[ntp] 20% Conferindo'); print('[ntp] 40% Fusos'); time.sleep(.3); print('[nfcom] Instalando'); time.sleep(.3)",terminal=True,columns=40)
        clean=re.sub(r'\x1b\[[0-?]*[ -/]*[@-~]','',result.stdout)
        frames=[x for x in clean.splitlines() if re.match(r'^\[(?:\.\.\.|OK|aviso|erro)\]',x) and 'Instalacao concluida' not in x]
        self.assertTrue(frames)
        self.assertTrue(all(len(x)<=39 for x in frames),frames)

    def test_quiet_terminal_does_not_redraw_same_percent_five_times_a_second(self):
        result,log=self.run_fixture("import time; print('[nfcom] Instalando'); time.sleep(1.2)",terminal=True,columns=80)
        clean=re.sub(r'\x1b\[[0-?]*[ -/]*[@-~]','',result.stdout)
        frames=[x for x in clean.splitlines() if 'NFCom, DICI e Trafego' in x]
        self.assertLessEqual(len(frames),3,frames)
        self.assertNotIn('estimado',clean)

    def test_formatting_does_not_turn_waiting_time_into_percentage(self):
        for width in [32,40,60,80,120]:
            for elapsed in [0,1,60,3600]:
                line=module.progress_line('Correcao e verificacao NTP',None,elapsed,width=width)
                self.assertLess(len(line),width)
                self.assertNotIn('%',line)
                line=module.progress_line('Correcao e verificacao NTP',65,elapsed,width=width)
                self.assertLess(len(line),width)
                self.assertIn('65%',line)

    def test_failure_is_not_reported_as_success(self):
        result, log = self.run_fixture("import sys; print('[erro] fixture'); sys.exit(7)")
        self.assertEqual(result.returncode, 7)
        self.assertIn('[erro] Instalacao falhou', log)
        self.assertNotIn('[OK] Instalacao concluida', result.stdout)

    def test_traceback_with_zero_exit_is_not_success(self):
        result, log = self.run_fixture("print('[apparmor] Verificando'); print('Traceback (most recent call last):'); print('ValueError: fixture'); print('[busca] Continuando')")
        self.assertEqual(result.returncode, 1)
        self.assertIn('ValueError: fixture', result.stdout)
        self.assertIn('[erro] Compatibilidade AppArmor', result.stdout)
        self.assertNotIn('[OK] Compatibilidade AppArmor', result.stdout)
        self.assertNotIn('[OK] Instalacao concluida', result.stdout)

    def test_explicit_optional_warning_is_reported_separately(self):
        result, log = self.run_fixture("print('[limpeza] Instalada'); print('[apparmor] Verificando'); print('[aviso] Perfil preservado'); print('[busca] Validando')")
        self.assertEqual(result.returncode, 0)
        self.assertIn('[OK] Limpeza diaria', result.stdout)
        self.assertIn('[aviso] Compatibilidade AppArmor', result.stdout)
        self.assertIn('concluida com avisos', result.stdout)
        self.assertNotIn('[OK] Compatibilidade AppArmor', result.stdout)

    def test_complete_message_is_at_end_of_installer(self):
        source = RUNNER.parent.parent.joinpath('install.sh').read_text()
        self.assertGreater(source.index('[OK] Todas as etapas da suite concluidas'), source.index('scripts/install-invoice-notes.php'))

    def test_cancellation_terminates_child(self):
        with tempfile.TemporaryDirectory() as tmp:
            marker = str(Path(tmp)/'should-not-run')
            ready = Path(tmp)/'ready'
            child = "import time,pathlib; pathlib.Path("+repr(str(ready))+").write_text('ready'); time.sleep(2); pathlib.Path("+repr(marker)+").write_text('unexpected')"
            process = subprocess.Popen([sys.executable, str(RUNNER), '--log-dir', tmp,
                                        '--', sys.executable, '-u', '-c', child],
                                       stdout=subprocess.PIPE, stderr=subprocess.STDOUT, universal_newlines=True)
            deadline = time.monotonic()+5
            while not ready.exists():
                if process.poll() is not None or time.monotonic() > deadline:
                    self.fail('Runner exited before starting child')
                time.sleep(.02)
            process.send_signal(signal.SIGTERM)
            process.communicate(timeout=5)
            self.assertEqual(process.returncode, 130)
            time.sleep(2.1)
            self.assertFalse(Path(marker).exists())


if __name__ == '__main__':
    unittest.main()
