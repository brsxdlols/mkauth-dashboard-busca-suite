#!/usr/bin/env python3
"""Allow the PHP pools serving admin/addons to write private diagnostic state."""
from pathlib import Path
import argparse, shutil, subprocess, re, json

RULES = """# MK-AUTH suite: private diagnostic files only; no execution permission.
/var/lib/mkauth-suite-performance/ rw,
owner /var/lib/mkauth-suite-performance/*.json rwk,
owner /var/lib/mkauth-suite-performance/*.runs rwk,
/var/lib/mkauth-suite-performance/optimization.lock rwk,
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
    if not re.search(r"/opt/mk-auth/hhvm/[0-9-]+/bin/php-(admin|central)\s*\{", source):
        raise ValueError("Perfil PHP nativo nao reconhecido.")
    # Replace the initial admin-only hook if it was installed while diagnosing.
    source = source.replace("local/mkauth-suite-php-admin", "local/mkauth-suite-performance")
    if "local/mkauth-suite-performance" in source:
        return source
    end = re.search(r"\n}\s*$", source)
    if not end:
        raise ValueError("Fim do perfil AppArmor nao reconhecido.")
    return source[:end.start()] + "\n" + INCLUDE + source[end.start():]

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("backup")
    args = parser.parse_args()
    backup = Path(args.backup)
    root = Path("/etc/apparmor.d")
    enabled = Path("/sys/module/apparmor/parameters/enabled")
    loader = shutil.which("apparmor_parser")
    if not (root / "sistema.php-admin").is_file() or not loader or not enabled.is_file() or enabled.read_text().strip() != "Y":
        print("[desempenho] AppArmor PHP administrativo inativo ou ausente; nenhuma alteracao.")
        return
    backup.mkdir(parents=True, exist_ok=True)
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
    print("[desempenho] AppArmor: arquivos privados do diagnostico autorizados nos processos " + ", ".join(pools) + ".")

if __name__ == "__main__":
    main()
