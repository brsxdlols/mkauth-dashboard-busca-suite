"""Permit PHP to read PGCORTE helpers before enabling auto_prepend_file."""
import importlib.util
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile

INCLUDE = '  #include <local/mkauth-pgcorte-read>'
RULES = b'# MK-AUTH PGCORTE: read-only helper access; no write or execution.\n/usr/local/share/mkauth-pgcorte-dual/ r,\n/usr/local/share/mkauth-pgcorte-dual/** r,\n'
HEADER = re.compile(r'(?m)^[ \t]*(/[^\s{}]+)(?:[ \t]+flags=\([^)]*\))?[ \t]*\{[ \t]*$')


def loaded_php_profiles(registry=Path('/sys/kernel/security/apparmor/profiles'), proc=Path('/proc')):
    labels, readable = set(), False
    try:
        data = registry.read_text();readable = True
        labels.update(line.rsplit(' (', 1)[0] for line in data.splitlines() if 'php' in line.lower())
    except OSError:
        for entry in proc.glob('[0-9]*/attr/current'):
            try:
                text = entry.read_text().strip();readable = True
                if 'php' in text.lower() and text.endswith(('(enforce)', '(complain)')):
                    labels.add(text.rsplit(' (', 1)[0])
            except OSError:
                continue
    if not readable:
        raise RuntimeError('Nao foi possivel conferir os perfis PHP ativos do AppArmor.')
    return labels


def profile_candidate(source, label):
    headers = HEADER.findall(source)
    if headers != [label]:
        raise RuntimeError('Perfil PHP com estrutura desconhecida; preservado: ' + label)
    if 'local/mkauth-pgcorte-read' in source:
        return source
    end = re.search(r'\n}[ \t]*\n?\Z', source)
    if not end:
        raise RuntimeError('Fim do perfil PHP desconhecido: ' + label)
    return source[:end.start()] + '\n' + INCLUDE + source[end.start():]


def parser_path(backup):
    parser = shutil.which('apparmor_parser', path='/usr/sbin:/usr/bin:/sbin:/bin')
    if parser:
        return parser
    source = Path(__file__).with_name('install-performance-apparmor.py')
    spec = importlib.util.spec_from_file_location('pgcorte_parser_support', source)
    module = importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
    return module.find_parser(backup)


def write_config(path, data):
    if path.is_symlink() or any(parent.is_symlink() for parent in path.parents):
        raise RuntimeError('Configuracao AppArmor simbolica recusada.')
    previous = path.stat() if path.exists() else None
    descriptor, temporary = tempfile.mkstemp(prefix='.pgcorte-aa-', dir=str(path.parent))
    try:
        with os.fdopen(descriptor, 'wb') as output:
            output.write(data)
        if previous:
            os.chown(temporary, previous.st_uid, previous.st_gid)
        os.chmod(temporary, previous.st_mode & 0o777 if previous else 0o644)
        os.replace(temporary, path)
    finally:
        if os.path.exists(temporary):os.unlink(temporary)


def ensure_pgcorte_apparmor(backup, root=Path('/etc/apparmor.d'), enabled=Path('/sys/module/apparmor/parameters/enabled')):
    if not enabled.is_file() or enabled.read_text().strip() != 'Y':
        return {'status': 'not_enabled'}
    labels = loaded_php_profiles()
    if not labels:
        return {'status': 'php_not_confined'}
    selected = {}
    for profile in root.iterdir():
        if not profile.is_file() or profile.is_symlink():continue
        try:
            source = profile.read_text(encoding='utf-8')
        except UnicodeError:
            continue
        for label in labels:
            if label in HEADER.findall(source):
                selected[profile] = (source, profile_candidate(source, label))
    found = set(label for source, _ in selected.values() for label in HEADER.findall(source))
    if labels - found:
        raise RuntimeError('Perfil PHP ativo nao localizado/reconhecido; PGCORTE nao sera ativado: ' + ', '.join(sorted(labels - found)))
    backup.mkdir(parents=True, exist_ok=True);backup.chmod(0o700)
    parser = parser_path(backup)
    local = root/'local/mkauth-pgcorte-read'
    if local.is_symlink() or any(p.is_symlink() for p in local.parents):
        raise RuntimeError('Include AppArmor simbolico preservado.')
    prior = local.read_bytes() if local.exists() else None
    # This unique include is package-owned; do not overwrite unrelated policy.
    if prior is not None and prior != RULES:
        raise RuntimeError('Include PGCORTE personalizado; politica preservada.')
    for index, (profile, (before, _)) in enumerate(selected.items()):
        saved = backup/('profile-'+str(index)+'.before');saved.write_bytes(profile.read_bytes());saved.chmod(0o600)
    if prior is not None:
        saved = backup/'local.before';saved.write_bytes(prior);saved.chmod(0o600)
    local.parent.mkdir(exist_ok=True)
    applied = False
    try:
        write_config(local, RULES)
        for profile, (_, after) in selected.items():write_config(profile, after.encode())
        for flag in ('-Q', '-r'):
            for profile in selected:
                result = subprocess.run([parser, flag, '-T', str(profile)], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
                if result.returncode:
                    raise RuntimeError('AppArmor recusou validacao/recarga: ' + result.stdout.decode(errors='replace').strip())
        applied = True
    finally:
        if not applied:
            for profile, (before, _) in selected.items():write_config(profile, before.encode())
            if prior is None:
                if local.exists():local.unlink()
            else:write_config(local, prior)
            for profile in selected:
                subprocess.run([parser, '-r', '-T', str(profile)], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    report = {'status': 'read_allowed', 'profiles': [str(p) for p in selected], 'include': str(local), 'access': 'read_only'}
    target = backup/'apparmor.json';target.write_text(json.dumps(report, indent=2));target.chmod(0o600)
    return report
