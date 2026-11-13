#!/usr/bin/env python3
"""Relay installer output, report quiet stages, retain real status and a private log."""
import argparse
import os
from pathlib import Path
import queue
import re
import signal
import subprocess
import tempfile
import threading
import time


def duration(seconds):
    seconds = int(seconds)
    return '%dm%02ds' % (seconds//60, seconds%60)


def phase(line):
    match = re.match(r'^\[(?:\d+/\d+|etapa \d+)\]\s*(.+)', line)
    if match:
        return match.group(1).strip()
    match = re.match(r'^\[([a-z-]+)\]', line)
    names = {'radius': 'Log RADIUS', 'caixa': 'Caixa', 'cpf': 'CPF/CNPJ',
             'reconcile': 'Reconciliador online/offline', 'checagem': 'Conferencia online/offline',
             'visitas': 'Visitas', 'atendimentos': 'Atendimentos', 'offline': 'Relatorio offline',
             'receber': 'Recebimento', 'ipv6': 'IPv6', 'nfcom': 'NFCom, DICI e Trafego',
             'limpeza': 'Limpeza diaria', 'desempenho': 'Analise de desempenho',
             'busca': 'Validacao da Busca', 'mapa': 'Integracao do Mapa',
             'monitor': 'Monitor de trafego', 'applications': 'Menus'}
    return names.get(match.group(1)) if match else None


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--log-dir', required=True)
    parser.add_argument('--terminal', action='store_true')
    parser.add_argument('--interval', type=float, default=10)
    parser.add_argument('command', nargs=argparse.REMAINDER)
    args = parser.parse_args()
    command = args.command[1:] if args.command[:1] == ['--'] else args.command
    if not command:
        parser.error('Comando ausente')
    logdir = Path(args.log_dir)
    logdir.mkdir(parents=True, exist_ok=True)
    descriptor, logfile = tempfile.mkstemp(prefix='install-run-', suffix='.log', dir=str(logdir))
    os.chmod(logfile, 0o600)
    interval = max(0.05, args.interval)
    started = changed = last_output = time.monotonic()
    current = 'Preparando instalacao'
    timings = []
    observed = set()
    print('[progresso] Log completo: '+logfile, flush=True)
    with os.fdopen(descriptor, 'w', encoding='utf-8') as log:
        color = (args.terminal or os.environ.get('FORCE_COLOR') == '1') and not os.environ.get('NO_COLOR')
        def progress(percent, failed=False):
            label = '[erro]' if failed else ('[OK]' if percent == 100 else '[...]')
            message = '%s %s: %d%%' % (label, current, percent)
            if args.terminal:
                prefix = '\033[31m' if failed else ('\033[32m' if percent == 100 else '')
                print('\r\033[2K'+(prefix if color else '')+message+('\033[0m' if color else ''),
                      end='\n' if failed or percent == 100 else '', flush=True)
            elif failed or percent == 100:
                print(message, flush=True)
        progress(0)
        process = subprocess.Popen(command, stdout=subprocess.PIPE, stderr=subprocess.STDOUT,
                                   universal_newlines=True, errors='replace', start_new_session=True)
        def terminate(signum, frame):
            raise SystemExit(130)
        signal.signal(signal.SIGTERM, terminate)
        messages = queue.Queue()

        def read():
            try:
                for line in process.stdout:
                    messages.put(line)
            finally:
                messages.put(None)

        threading.Thread(target=read, daemon=True).start()
        try:
            while True:
                try:
                    line = messages.get(timeout=interval)
                except queue.Empty:
                    now = time.monotonic()
                    if now-last_output >= interval:
                        progress(0)
                    continue
                if line is None:
                    break
                now = time.monotonic()
                next_phase = phase(line)
                if next_phase and next_phase != current:
                    progress(100)
                    timings.append((current, now-changed))
                    current, changed = next_phase, now
                    observed.add(current)
                    progress(0)
                last_output = now
                if re.search(r'\[erro\]|\[error\]|fatal error|parse error|traceback|^error:|^erro:', line, re.I):
                    print(('\033[31m' if color else '')+line.rstrip()+('\033[0m' if color else ''), flush=True)
                log.write(line)
                log.flush()
            status = process.wait()
        except (KeyboardInterrupt, SystemExit, BrokenPipeError):
            try:
                os.killpg(process.pid, signal.SIGTERM)
            except ProcessLookupError:
                pass
            try:
                process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                os.killpg(process.pid, signal.SIGKILL)
                process.wait()
            try:
                print('[erro] Instalacao interrompida pelo operador. Log: '+logfile, flush=True)
            except BrokenPipeError:
                pass
            return 130
        timings.append((current, time.monotonic()-changed))
        progress(100 if status == 0 else 0, failed=status != 0)
        elapsed = duration(time.monotonic()-started)
        summary = ('[OK] Instalacao concluida em '+elapsed if status == 0 else '[erro] Instalacao falhou apos '+elapsed+' (codigo '+str(status)+')')
        print(('\033[32m' if status == 0 and color else ('\033[31m' if color else ''))+summary+('\033[0m' if color else ''), flush=True)
        log.write(summary+'\n')
        for name, seconds in sorted(timings, key=lambda row: row[1], reverse=True)[:5]:
            if seconds >= 1:
                message = '[tempo] '+name+': '+duration(seconds)
                log.write(message+'\n')
        print('[progresso] Log completo: '+logfile, flush=True)
        return status if status >= 0 else 128-status


if __name__ == '__main__':
    raise SystemExit(main())
