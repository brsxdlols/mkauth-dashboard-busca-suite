#!/usr/bin/env python3
"""Relay installer output, report quiet stages, retain real status and a private log."""
import argparse
import os
from pathlib import Path
import queue
import re
import signal
import shutil
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
             'reconcile': 'Reconciliador online/offline',
             'desconexao': 'Horario das desconexoes', 'sessoes': 'Sessoes administrativas', 'checagem': 'Conferencia online/offline',
             'visitas': 'Visitas', 'atendimentos': 'Atendimentos', 'offline': 'Relatorio offline',
             'receber': 'Recebimento', 'ipv6': 'IPv6', 'nfcom': 'NFCom, DICI e Trafego',
             'limpeza': 'Limpeza diaria', 'horario': 'Diagnostico de horario', 'desempenho': 'Analise de desempenho',
             'ntp': 'Correcao e verificacao NTP', 'busca': 'Validacao da Busca', 'mapa': 'Integracao do Mapa',
             'monitor': 'Monitor de trafego', 'applications': 'Menus', 'apparmor': 'Compatibilidade AppArmor'}
    return names.get(match.group(1)) if match else None


def reported_percent(line):
    """Accept checkpoints only from an identified installer phase."""
    match = re.match(r'^\[[a-z-]+\]\s+(\d{1,3})%\s', line)
    if not match or phase(line) is None:
        return None
    value = int(match.group(1))
    return value if 0 <= value <= 100 else None


def progress_line(current, percent, elapsed, failed=False, warning=False,
                  completed=False, width=80):
    """Fit one physical terminal line; elapsed time never becomes a percent."""
    width = max(1, width - 1)  # Avoid the terminal's last-column autowrap.
    label = '[erro]' if failed else ('[aviso]' if warning and completed else ('[OK]' if completed else '[...]'))
    if completed:
        percent = 100
    if percent is None or (percent == 100 and not completed):
        suffix = ' em execucao ('+duration(elapsed)+')'
        if percent == 100:
            suffix = ' validando conclusao ('+duration(elapsed)+')'
        if failed:
            suffix = ' falhou ('+duration(elapsed)+')'
    else:
        cells = 20 if width >= 73 else (10 if width >= 47 else (6 if width >= 35 else 0))
        filled = percent*cells//100
        bar = (' ['+'#'*filled+'-'*(cells-filled)+']') if cells else ''
        suffix = bar+' '+str(percent)+'%'
        if width >= 47 and not completed:
            suffix += ' ('+duration(elapsed)+')'
    available = width-len(label)-1-len(suffix)
    name = current
    if len(name) > available:
        name = name[:max(0,available-3)]+'...'
    return (label+' '+name+suffix)[:width]


def overall_checkpoint(line):
    match = re.match(r'^\[suite-progress\] (\d+)/(\d+)\s*$', line.strip())
    if not match:
        return None
    done, total = map(int, match.groups())
    return (done, total) if total > 0 and 0 <= done <= total else None


def overall_line(current, percent, elapsed, width=80):
    width = max(1, width-1)
    cells = 20 if width >= 90 else (10 if width >= 60 else 6)
    filled = percent*cells//100
    prefix = '[geral] ['+'#'*filled+'-'*(cells-filled)+'] '+str(percent)+'% | '+duration(elapsed)+' | '
    return (prefix+current)[:width]


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
    interval = min(0.2, max(0.05, args.interval))
    refresh_interval = 1 if args.terminal else max(1, args.interval)
    started = changed = last_output = time.monotonic()
    current = 'Preparando instalacao'
    timings = []
    observed = set()
    phase_error = phase_warning = False
    error_phases = set()
    warning_phases = set()
    traceback_lines = 0
    current_percent = None
    last_frame = None
    last_draw = started
    overall_percent = 0
    overall_total = None
    frame_visible = False
    print('[instalacao] Total previsto: 18 etapas de instalacao de pacotes e validacoes.', flush=True)
    print('[instalacao] Tempo estimado: 3 a 6 minutos; pode variar conforme o servidor e a conexao.', flush=True)
    print('[progresso] Log completo: '+logfile, flush=True)
    with os.fdopen(descriptor, 'w', encoding='utf-8') as log:
        color = (args.terminal or os.environ.get('FORCE_COLOR') == '1') and not os.environ.get('NO_COLOR')
        def clear_frame():
            nonlocal frame_visible
            if args.terminal and frame_visible:
                print('\r\033[2K', end='', flush=True)
                frame_visible = False
        def progress(percent=None, failed=False, warning=False, completed=False, announce=False):
            nonlocal last_frame, last_draw, frame_visible
            width = shutil.get_terminal_size(fallback=(80,24)).columns if args.terminal else 200
            message = progress_line(current, percent if completed else None, time.monotonic()-changed,
                                    failed, warning, completed,
                                    width)
            general = overall_line(current, overall_percent, time.monotonic()-started, width)
            frame = message
            if frame == last_frame:
                return
            if not args.terminal and not (announce or completed or failed):
                return
            last_frame, last_draw = frame, time.monotonic()
            if args.terminal:
                clear_frame()
                prefix = '\033[31m' if failed else ('\033[33m' if warning and completed else ('\033[32m' if completed else ''))
                print((prefix if color else '')+message+('\033[0m' if color else ''),
                      end='\n' if failed or completed else '', flush=True)
                frame_visible = not (failed or completed)
            else:
                print(message, flush=True)
        progress(announce=True)
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
                    if not phase_error and now-last_draw >= refresh_interval:
                        progress(current_percent, announce=True)
                    continue
                if line is None:
                    break
                now = time.monotonic()
                checkpoint = overall_checkpoint(line)
                if checkpoint:
                    done, total = checkpoint
                    if overall_total is None:
                        overall_total = total
                    if total == overall_total:
                        overall_percent = max(overall_percent, min(99, done*100//total))
                    log.write(line); log.flush()
                    progress(current_percent, announce=True)
                    continue
                next_phase = phase(line)
                if next_phase and next_phase != current:
                    if phase_error: error_phases.add(current)
                    elif phase_warning: warning_phases.add(current)
                    progress(current_percent, failed=phase_error, warning=phase_warning, completed=not phase_error)
                    timings.append((current, now-changed))
                    current, changed = next_phase, now
                    phase_error = phase_warning = False
                    current_percent = None
                    last_frame = None
                    traceback_lines = 0
                    observed.add(current)
                    progress(announce=True)
                last_output = now
                is_error = bool(re.search(r'\[erro\]|\[error\]|fatal error|parse error|traceback|^error:|^erro:', line, re.I))
                is_warning = '[aviso]' in line.lower()
                if is_error: phase_error = True
                if 'Traceback (most recent call last):' in line: traceback_lines = 30
                trace_detail = traceback_lines > 0 and bool(re.match(r'^\s+File |^[\w.]+(?:Error|Exception|Exit|Interrupt):', line))
                if is_warning:
                    phase_warning = True
                    phase_error = False  # The caller explicitly classified a handled/optional failure.
                reported = reported_percent(line)
                advanced = reported is not None and (current_percent is None or reported > current_percent)
                if advanced:
                    current_percent = reported
                if is_error or is_warning or trace_detail:
                    clear_frame()
                    print(('\033[31m' if color and is_error else ('\033[33m' if color and is_warning else ''))+line.rstrip()+('\033[0m' if color else ''), flush=True)
                    last_frame = None
                elif advanced and not phase_error:
                    # Detail stays in the private log; the visible bar uses the
                    # same reported checkpoint instead of a second time estimate.
                    progress(current_percent, announce=True)
                if traceback_lines: traceback_lines -= 1
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
                clear_frame()
                print('[erro] Instalacao interrompida pelo operador. Log: '+logfile, flush=True)
            except BrokenPipeError:
                pass
            return 130
        timings.append((current, time.monotonic()-changed))
        if phase_error: error_phases.add(current)
        elif phase_warning: warning_phases.add(current)
        if status == 0 and error_phases: status = 1
        if status == 0:
            overall_percent = 100
        progress(current_percent, failed=status != 0 or phase_error, warning=phase_warning,
                 completed=status == 0 and not phase_error)
        elapsed = duration(time.monotonic()-started)
        success = ('[aviso] Instalacao concluida com avisos em ' if warning_phases else '[OK] Instalacao concluida em ') + elapsed
        summary = (success if status == 0 else '[erro] Instalacao falhou apos '+elapsed+' (codigo '+str(status)+')')
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

