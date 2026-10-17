"""Exercise the installer's actual copy command against an existing native symlink."""
import os, subprocess, tempfile
from pathlib import Path
installer = Path(__file__).resolve().parents[1] / 'install.sh'
command = next(line.strip() for line in installer.read_text().splitlines() if 'cp -a' in line and '${SCRIPT_DIR}/addons/${addon}/.' in line)
with tempfile.TemporaryDirectory() as directory:
 root=Path(directory); source=root/'source/addons/rel_cli_offline';target=root/'admin/addons/rel_cli_offline';source.mkdir(parents=True);target.mkdir(parents=True)
 native=root/'native-auth.php';native.write_text('original native authentication')
 (target/'addons.class.php').symlink_to(native)
 (source/'addons.class.php').write_text('addon authentication loader')
 env=dict(os.environ,SCRIPT_DIR=str(root/'source'),TARGET_ADDONS_DIR=str(root/'admin/addons'),addon='rel_cli_offline')
 subprocess.run(['bash','-c','set -e; '+command],env=env,check=True)
 assert native.read_text()=='original native authentication'
 assert not (target/'addons.class.php').is_symlink()
 assert (target/'addons.class.php').read_text()=='addon authentication loader'
 subprocess.run(['bash','-c','set -e; '+command],env=env,check=True)
 assert native.read_text()=='original native authentication'
 print('PASS: install and reinstall preserve the native authentication behind an addon symlink')
