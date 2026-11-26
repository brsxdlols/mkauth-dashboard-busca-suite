#!/usr/bin/env python3
"""Download, verify and run the upstream PGCORTE patch; never execute router scripts."""
import argparse
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import stat
import subprocess
import tempfile
import urllib.request
import zipfile

VERSION = '1.3.1'
ARCHIVE_NAME = 'mkauth-pgcorte-dual-v1.3.1.zip'
ZIP_URL = 'https://raw.githubusercontent.com/brsxdlols/mkauth-ipv6-addon/main/patches/pgcorte-dual/' + ARCHIVE_NAME
CHECKSUM_URL = ZIP_URL + '.sha256'
PACKAGE_NAME = 'mkauth-pgcorte-dual'
MAX_ARCHIVE = 16 * 1024 * 1024
MAX_CONTENTS = 64 * 1024 * 1024


def private_directory(path):
    if path.is_symlink() or any(p.is_symlink() for p in path.parents):
        raise RuntimeError('Diretorio simbolico recusado: ' + str(path))
    path.mkdir(parents=True, exist_ok=True)
    path.chmod(0o700)


def download(url, destination, limit):
    request = urllib.request.Request(url, headers={'Cache-Control': 'no-cache', 'User-Agent': 'MKAuth-Dashboard-PGCORTE/' + VERSION})
    with urllib.request.urlopen(request, timeout=60) as response, destination.open('wb') as output:
        total = 0
        while True:
            block = response.read(65536)
            if not block:
                break
            total += len(block)
            if total > limit:
                raise RuntimeError('Download excedeu o limite: ' + url)
            output.write(block)
    destination.chmod(0o600)


def verify_checksum(archive, checksum):
    lines = checksum.read_text(encoding='utf-8').strip().splitlines()
    if len(lines) != 1:
        raise RuntimeError('Formato de SHA256 invalido.')
    match = re.fullmatch(r'([a-fA-F0-9]{64})(?:\s+\*?([^\s]+))?', lines[0])
    if not match or (match.group(2) and match.group(2) != ARCHIVE_NAME):
        raise RuntimeError('SHA256 nao corresponde ao nome do pacote esperado.')
    digest = hashlib.sha256(archive.read_bytes()).hexdigest()
    if digest != match.group(1).lower():
        raise RuntimeError('SHA256 divergente; pacote nao extraido nem executado.')
    return digest


def extract_verified(archive, staging):
    # Called only after verify_checksum. Reject paths that could escape the package.
    seen = set()
    with zipfile.ZipFile(archive) as package:
        members = package.infolist()
        if len(members) > 1024 or sum(m.file_size for m in members) > MAX_CONTENTS:
            raise RuntimeError('Pacote excedeu os limites de extracao.')
        for member in members:
            path = PurePosixPath(member.filename)
            mode = member.external_attr >> 16
            if ('\\' in member.filename or path.is_absolute() or '..' in path.parts
                    or not path.parts or path.parts[0] != PACKAGE_NAME
                    or member.filename in seen or stat.S_ISLNK(mode)
                    or member.flag_bits & 1):
                raise RuntimeError('Entrada ZIP recusada: ' + member.filename)
            seen.add(member.filename)
        if package.testzip() is not None:
            raise RuntimeError('Arquivo ZIP corrompido.')
        package.extractall(staging)
    folder = staging / PACKAGE_NAME
    for name in ('install.sh', 'patch.py', 'native_pgcorte.py', 'SHA256SUMS'):
        if not (folder / name).is_file():
            raise RuntimeError('Pacote incompleto: ' + name)
    return folder


def publish_package(source, target):
    if target.is_symlink() or any(p.is_symlink() for p in target.parents):
        raise RuntimeError('Destino simbolico recusado.')
    target.mkdir(parents=True, exist_ok=True)
    target.chmod(0o700)
    # Preserve files not provided by the archive. The upstream package owns its backups.
    for item in sorted(source.rglob('*')):
        destination = target / item.relative_to(source)
        if destination.is_symlink() or any(p.is_symlink() for p in destination.parents):
            raise RuntimeError('Destino simbolico recusado: ' + str(destination))
        if item.is_dir():
            destination.mkdir(parents=True, exist_ok=True)
            destination.chmod(0o700)
        else:
            destination.parent.mkdir(parents=True, exist_ok=True)
            descriptor, temporary = tempfile.mkstemp(prefix='.pgcorte-', dir=str(destination.parent))
            try:
                with os.fdopen(descriptor, 'wb') as output, item.open('rb') as original:
                    shutil.copyfileobj(original, output)
                os.chmod(temporary, 0o700 if item.suffix == '.sh' else 0o600)
                os.replace(temporary, destination)
            finally:
                if os.path.exists(temporary):
                    os.unlink(temporary)


def install(log_directory, patch_root=Path('/opt/mkauth-patches')):
    if os.geteuid() != 0:
        print('[erro] PGCORTE requer execucao como root; patch nao concluido.')
        return 1
    private_directory(log_directory)
    descriptor, log_name = tempfile.mkstemp(prefix='pgcorte-1.3.1-', suffix='.log', dir=str(log_directory))
    os.chmod(log_name, 0o600)
    report = {'version': VERSION, 'completed': False, 'steps': [], 'log': log_name}
    status = 1
    with os.fdopen(descriptor, 'w', encoding='utf-8', buffering=1) as log:
        try:
            private_directory(patch_root)
            import fcntl
            if (patch_root / '.pgcorte-dual.lock').is_symlink():
                raise RuntimeError('Lock simbolico recusado.')
            with (patch_root / '.pgcorte-dual.lock').open('a') as lock:
                os.chmod(str(patch_root / '.pgcorte-dual.lock'), 0o600)
                fcntl.flock(lock.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
                print('[pgcorte] Baixando pacote ' + VERSION + ' e SHA256.')
                with tempfile.TemporaryDirectory(prefix='.pgcorte-download-', dir=str(patch_root)) as temporary:
                    staging = Path(temporary)
                    archive, checksum = staging / ARCHIVE_NAME, staging / (ARCHIVE_NAME + '.sha256')
                    download(ZIP_URL, archive, MAX_ARCHIVE)
                    download(CHECKSUM_URL, checksum, 4096)
                    report['sha256'] = verify_checksum(archive, checksum)
                    log.write('SHA256 verificado: ' + report['sha256'] + '\n')
                    folder = extract_verified(archive, staging)
                    target = patch_root / PACKAGE_NAME
                    publish_package(folder, target)
                commands = [
                    ('instalacao', ['sh', str(target / 'install.sh')]),
                    ('RADIUS', ['python3', 'patch.py', 'check']),
                    ('PGCORTE nativo', ['python3', 'native_pgcorte.py', 'check']),
                    ('integracao administrativa', ['python3', 'native_pgcorte.py', 'check', '--admin']),
                ]
                for label, command in commands:
                    print('[pgcorte] Executando ' + label + '.')
                    log.write('\n=== ' + label + ': ' + json.dumps(command) + ' ===\n')
                    log.flush()
                    result = subprocess.run(command, cwd=str(target), stdout=log, stderr=subprocess.STDOUT)
                    report['steps'].append({'component': label, 'code': result.returncode})
                    log.write('Codigo: ' + str(result.returncode) + '\n')
                failed = [s for s in report['steps'] if s['code'] != 0]
                pending = any(s['code'] == 4 and s['component'] in ('instalacao', 'RADIUS') for s in failed)
                report['activation_pending'] = pending
                report['completed'] = not failed
                status = 0 if not failed else (4 if pending and all(s['code'] == 4 and s['component'] in ('instalacao', 'RADIUS') for s in failed) else 1)
        except Exception as error:
            report['error'] = str(error)
            log.write('Falha: ' + str(error) + '\n')
            status = 1
    report['exit_code'] = status
    report_path = Path(log_name).with_suffix('.json')
    report_path.write_text(json.dumps(report, indent=2, ensure_ascii=False), encoding='utf-8')
    report_path.chmod(0o600)
    if status == 0:
        print('[pgcorte] Patch ' + VERSION + ' instalado e aprovado nas tres verificacoes.')
        print('[pgcorte] A integracao visual pode levar ate 5 minutos para aparecer no PHP.')
    else:
        detail = ' Ativacao RADIUS pendente (codigo 4).' if report.get('activation_pending') else ''
        failures = [s['component'] + '=' + str(s['code']) for s in report['steps'] if s['code'] != 0]
        if failures:
            detail += ' Etapas: ' + ', '.join(failures) + '.'
        print('[erro] Patch PGCORTE nao concluido.' + detail + ' Consulte o log: ' + log_name)
    print('[pgcorte] Log completo: ' + log_name)
    return status


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('log_directory', type=Path)
    args = parser.parse_args()
    try:
        code = install(args.log_directory)
    except Exception as error:
        print('[erro] Patch PGCORTE nao concluido: ' + str(error), flush=True)
        code = 1
    raise SystemExit(code)
