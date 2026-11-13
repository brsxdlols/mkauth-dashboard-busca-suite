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


def panel(current, stage_seconds, total_seconds, count, finished=None):
    """Append-only ASCII panel: safe through pipes, mobile terminals and logs."""
    title = 'INSTALACAO MK-AUTH | '+(finished or 'EM ANDAMENTO')
    lines = [title, 'Etapa atual: '+current,
             'Tempo nesta etapa: '+duration(stage_seconds),
             'Tempo total: '+duration(total_seconds),
             'Fases observadas: '+str(count)]
    if not finished:
        lines += ['Referencia anterior: 2m36s (nao e prazo garantido)',
                  'Tempo restante: varia conforme servidor e downloads',
                  '>>> Executando | aguarde a conclusao das validacoes']
    width = 66
    border = '+'+'-'*(width+2)+'+'
    output = [border]
    for line in lines:
        for start in range(0, len(line), width):
            output.append('| '+line[start:start+width].ljust(width)+' |')
    return '\n'.join(output+[border])


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--log-dir', required=True)
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
        def show_panel(now, finished=None):
            message = panel(current, now-changed, now-started, len(observed), finished)
            print(message, flush=True)
            log.write(message+'\n')
            log.flush()
        show_panel(started)
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
                        message = '[progresso] Executando: %s | etapa %s | total %s | aguardando nova saida; processo ativo' % (current, duration(now-changed), duration(now-started))
                        print(message, flush=True)
                        log.write(message+'\n')
                        log.flush()
                        show_panel(now)
                    continue
                if line is None:
                    break
                now = time.monotonic()
                next_phase = phase(line)
                if next_phase and next_phase != current:
                    timings.append((current, now-changed))
                    current, changed = next_phase, now
                    observed.add(current)
                    show_panel(now)
                last_output = now
                print(line, end='', flush=True)
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
        show_panel(time.monotonic(), 'CONCLUIDA' if status == 0 else 'FALHOU')
        elapsed = duration(time.monotonic()-started)
        summary = ('[OK] Instalacao concluida em '+elapsed if status == 0 else '[erro] Instalacao falhou apos '+elapsed+' (codigo '+str(status)+')')
        print(summary, flush=True)
        log.write(summary+'\n')
        for name, seconds in sorted(timings, key=lambda row: row[1], reverse=True)[:5]:
            if seconds >= 1:
                message = '[tempo] '+name+': '+duration(seconds)
                print(message, flush=True)
                log.write(message+'\n')
        print('[progresso] Log completo: '+logfile, flush=True)
        return status if status >= 0 else 128-status


if __name__ == '__main__':
    raise SystemExit(main())
