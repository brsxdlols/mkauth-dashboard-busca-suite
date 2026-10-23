import tempfile, subprocess, sys, importlib.util
from pathlib import Path
script=Path(__file__).with_name('install-offline-menu.py')
with tempfile.TemporaryDirectory() as folder:
 root=Path(folder);(root/'addons').mkdir();p=root/'addons/addon.js';p.write_text('// Existing addon\nadd_menu.clientes("other");\n')
 subprocess.run([sys.executable,str(script),str(root)],check=True);first=p.read_bytes();subprocess.run([sys.executable,str(script),str(root)],check=True);assert p.read_bytes()==first;assert b'Existing addon' in first;assert first.count(b'Clientes offline')==1
 p.write_text("add_menu.clientes('addons/rel_cli_offline/');\n");subprocess.run([sys.executable,str(script),str(root)],check=True);assert p.read_text().count('addons/rel_cli_offline/')==1
 p.write_text("var unrelatedMenu = `addons/rel_cli_offline/`;\n")
 subprocess.run([sys.executable,str(script),str(root)],check=True);assert 'MKA-SUITE-OFFLINE-MENU-START' in p.read_text();assert 'unrelatedMenu' in p.read_text()
 legacy=b'// a\xe7\xe3o, op\xe7\xf5es e bytes \x80\x81\r\nadd_menu.clientes("other");\r\n'
 p.write_bytes(legacy)
 subprocess.run([sys.executable,str(script),str(root)],check=True);first=p.read_bytes()
 assert first.startswith(legacy);assert first.count(b'Clientes offline')==1
 subprocess.run([sys.executable,str(script),str(root)],check=True);assert p.read_bytes()==first
 spec=importlib.util.spec_from_file_location('applications',script.with_name('reconcile-applications-menu.py'));module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
 assert module.patch(legacy) is None
 print('PASS: menu idempotent; existing entries and existing offline link preserved')
 print('PASS: Latin-1/CP1252 bytes preserved; unknown Applications menu safely skipped')
