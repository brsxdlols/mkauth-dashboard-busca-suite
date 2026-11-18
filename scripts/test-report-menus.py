import subprocess,sys,tempfile
from pathlib import Path
with tempfile.TemporaryDirectory() as folder:
 root=Path(folder);p=root/'addons/addon.js';p.parent.mkdir();original=b'// existing custom menu\n';p.write_bytes(original)
 command=[sys.executable,str(Path(__file__).with_name('install-report-menus.py')),folder]
 subprocess.run(command,check=True);first=p.read_bytes();subprocess.run(command,check=True);assert p.read_bytes()==first;assert first.startswith(original)
 for target in [b'/rel_clientes/',b'/graf_faturamento/graf_det.php',b'/busca_inteligente/',b'/rel_plano/',b'/busca_inteligente/score.php',b'/rel_bloqueados/']:
  assert first.count(target+b"'")==1,target
 assert first.count(b'add_menu.clientes(')==5
 assert first.count(b'add_menu.financeiro(')==1
 print('PASS report menus: six links, correct categories, custom entries preserved, idempotent')
