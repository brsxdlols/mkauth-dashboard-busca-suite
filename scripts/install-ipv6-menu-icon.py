#!/usr/bin/env python3
"""Style the existing official IPv4/IPv6 shortcut without adding duplicate links."""
import json,sys
from pathlib import Path
p=Path(sys.argv[1])/'addons/addon.js'
if not p.exists():raise SystemExit('IPv4/IPv6: addon.js ausente; preservado.')
if p.is_symlink():raise ValueError('addon.js is a symlink; preserved')
source=p.read_bytes();out=[]
icon='<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" style="vertical-align:-2px;margin-right:6px" aria-hidden="true"><circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18M5 7h14M5 17h14"/></svg>'
line='add_menu.conexoes(JSON.stringify('+json.dumps(dict(plink='/admin/addons/ipv6/ipv6.php',ptext=icon+'Painel IPv4/IPv6'),ensure_ascii=True)+'));'
for old in source.splitlines(keepends=True):
 if b'add_menu.conexoes' in old and b'addons/ipv6/ipv6.php' in old:out.append(line.encode()+b'\n')
 else:out.append(old)
new=b''.join(out)
if new!=source:p.write_bytes(new)
