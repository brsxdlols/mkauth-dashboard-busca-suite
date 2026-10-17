import tempfile, subprocess
from pathlib import Path
script=Path(__file__).with_name('install-offline-menu.py')
with tempfile.TemporaryDirectory() as folder:
 root=Path(folder);(root/'addons').mkdir();p=root/'addons/addon.js';p.write_text('// Existing addon\nadd_menu.clientes("other");\n')
 subprocess.run(['python',str(script),str(root)],check=True);first=p.read_bytes();subprocess.run(['python',str(script),str(root)],check=True);assert p.read_bytes()==first;assert b'Existing addon' in first;assert first.count(b'ptext:')==1
 p.write_text("add_menu.clientes('addons/rel_cli_offline/');\n");subprocess.run(['python',str(script),str(root)],check=True);assert p.read_text().count('addons/rel_cli_offline/')==1
 print('PASS: menu idempotent; existing entries and existing offline link preserved')
