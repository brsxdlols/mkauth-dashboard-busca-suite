#!/usr/bin/env python3
"""Move only the NFCOM creation shortcut from Provider to Finance."""
import re,sys
from pathlib import Path
p=Path(sys.argv[1])/'addons/addon.js'
if not p.exists():raise SystemExit(0)
if p.is_symlink():raise ValueError('addon.js is a symlink; preserved')
old=p.read_bytes();parts=[]
for line in old.splitlines(keepends=True):
 if b'addons/gerador_nf_dici/' in line and b'add_menu.provedor' in line:
  line=line.replace(b'add_menu.provedor',b'add_menu.financeiro')
 parts.append(line)
new=b''.join(parts)
if new!=old:p.write_bytes(new)
print('NFCOM: atalho de criacao direcionado ao Financeiro; DICI e Trafego preservados.')
