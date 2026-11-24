#!/usr/bin/env python3
"""Allow the PHP pools serving admin/addons to write private diagnostic state."""
from pathlib import Path
import argparse, shutil, subprocess, re, json

RULES = """# MK-AUTH suite: private diagnostic files only; no execution permission.
/var/lib/mkauth-suite-performance/ rw,
owner /var/lib/mkauth-suite-performance/*.json rwk,
owner /var/lib/mkauth-suite-performance/*.runs rwk,
/var/lib/mkauth-suite-performance/optimization.lock rwk,
/opt/mk-auth/dados/suite-time-monitor/ rw,
/opt/mk-auth/dados/suite-time-monitor/status.json r,
/opt/mk-auth/dados/suite-time-monitor/queue.lock rwk,
owner /opt/mk-auth/dados/suite-time-monitor/.request-*.tmp rw,
owner /opt/mk-auth/dados/suite-time-monitor/request.json rw,
"""
INCLUDE = "  #include if exists <local/mkauth-suite-performance>"

def diagnostic_pools(apache):
    pools = {"admin"}
    block = re.search(r'<Directory\s+[\"]?/opt/mk-auth/admin/addons[\"]?\s*>(.*?)</Directory>', apache, re.S)
    if block:
        handler = re.search(r'/run/php-(admin|central)\.sock', block.group(1))
        if not handler:
            raise ValueError("Processo PHP dos addons nao reconhecido.")
        pools.add(handler.group(1))
    return sorted(pools)

def patched_profile(source):
    if not re.search(r"/opt/mk-auth/hhvm/[0-9-]+/bin/php-(admin|central)(?:\s+flags=\([a-z_, \t]+\))?\s*\{", source):
        raise ValueError("Perfil PHP nativo nao reconhecido.")
    # Replace the initial admin-only hook if it was installed while diagnosing.
    source = source.replace("local/mkauth-suite-php-admin", "local/mkauth-suite-performance")
    if "local/mkauth-suite-performance" in source:
        return source
    end = re.search(r"\n}\s*$", source)
    if not end:
        raise ValueError("Fim do perfil AppArmor nao reconhecido.")
    return source[:end.start()] + "\n" + INCLUDE + source[end.start():]

def find_parser(backup):
    loader=shutil.which('apparmor_parser',path='/usr/sbin:/usr/bin:/sbin:/bin')
    if loader:return loader
    # Some MK-AUTH images keep active kernel profiles but omit the userspace parser.
    # Extract only the distro package into the private backup; do not enable/reload
    # the global AppArmor service or replace unrelated profiles.
    apt=shutil.which('apt-get',path='/usr/bin:/bin');deb=shutil.which('dpkg-deb',path='/usr/bin:/bin')
    if not apt or not deb:raise RuntimeError('AppArmor ativo sem parser; apt-get/dpkg-deb indisponiveis.')
    package=backup/'parser-package';package.mkdir(mode=0o700,exist_ok=True)
    print('[apparmor] Parser ausente; obtendo ferramenta do repositorio Debian para validar somente os perfis PHP.',flush=True)
    result=subprocess.run([apt,'download','apparmor'],cwd=str(package),capture_output=True,text=True,timeout=120)
    (package/'download.log').write_text(result.stdout+result.stderr)
    (package/'download.log').chmod(0o600)
    files=list(package.glob('apparmor_*.deb'))
    if result.returncode or len(files)!=1:raise RuntimeError('Nao foi possivel obter apparmor_parser. Politica nativa preservada; consulte os repositorios APT e download.log no backup.')
    subprocess.run([deb,'--extract',str(files[0]),str(package/'extracted')],check=True,timeout=30)
    candidates=[p for p in (package/'extracted').rglob('apparmor_parser') if p.is_file() and not p.is_symlink()]
    if len(candidates)!=1:raise RuntimeError('Parser nao encontrado no pacote Debian.')
    return str(candidates[0])

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("backup")
    args = parser.parse_args()
    print("[apparmor] Verificando perfil PHP do diagnostico.")
    backup = Path(args.backup)
    root = Path("/etc/apparmor.d")
    enabled = Path("/sys/module/apparmor/parameters/enabled")
    if not (root / "sistema.php-admin").is_file() or not enabled.is_file() or enabled.read_text().strip() != "Y":
        print("[apparmor] AppArmor PHP administrativo inativo ou ausente; nenhuma alteracao.")
        return
    backup.mkdir(parents=True, exist_ok=True)
    backup.chmod(0o700)
    loader = find_parser(backup)
    apache = Path("/etc/apache2/conf-enabled/admin.conf")
    pools = diagnostic_pools(apache.read_text() if apache.is_file() else "")
    originals = {}
    candidates = {}
    for pool in pools:
        profile = root / ("sistema.php-" + pool)
        if not profile.is_file():
            raise ValueError("Perfil PHP dos addons indisponivel: " + pool)
        originals[profile] = profile.read_text()
        candidates[profile] = patched_profile(originals[profile])
        shutil.copy2(profile, backup / ("php-" + pool + "-apparmor-before"))
    local = root / "local/mkauth-suite-performance"
    previous = local.read_bytes() if local.exists() else None
    if previous is not None:
        (backup / "php-admin-apparmor-local-before").write_bytes(previous)
    local.parent.mkdir(exist_ok=True)
    try:
        local.write_text(RULES)
        local.chmod(0o644)
        for profile, candidate in candidates.items():
            profile.write_text(candidate)
        for profile in originals:
            # Validate all profiles before updating the running policy.
            result = subprocess.run([loader, "-Q", "-T", str(profile)], capture_output=True, text=True)
            if result.returncode:
                raise RuntimeError("AppArmor recusou o perfil; alteracao revertida.")
        for profile in originals:
            result = subprocess.run([loader, "-r", "-T", str(profile)], capture_output=True, text=True)
            if result.returncode:
                raise RuntimeError("AppArmor recusou a recarga; alteracao revertida.")
    except Exception:
        for profile, source in originals.items():
            profile.write_text(source)
        if previous is None:
            if local.exists():
                local.unlink()
        else:
            local.write_bytes(previous)
        for profile in originals:
            subprocess.run([loader, "-r", "-T", str(profile)], capture_output=True)
        raise
    (backup / "performance-apparmor.json").write_text(json.dumps({"status": "applied", "profiles": [str(p) for p in originals], "include": str(local)}, indent=2))
    print("[apparmor] AppArmor: arquivos privados do diagnostico autorizados nos processos " + ", ".join(pools) + ".")

if __name__ == "__main__":
    try:
        main()
    except (ValueError, RuntimeError, OSError, subprocess.SubprocessError) as error:
        print("[aviso] AppArmor preservado: " + str(error))
        raise SystemExit(1)
