#!/usr/bin/env python3
"""Maintain only the suite report shortcuts; preserve custom menus."""
import re,sys
from pathlib import Path
p=Path(sys.argv[1])/'addons/addon.js'
if p.is_symlink():raise ValueError('addon.js is a symlink; preserved')
s=p.read_bytes() if p.exists() else b''
s=re.sub(rb'/\* MKA-SUITE-REPORT-MENUS-START \*/.*?/\* MKA-SUITE-REPORT-MENUS-END \*/\r?\n?',b'',s,flags=re.S)
block=b'/* MKA-SUITE-REPORT-MENUS-START */\n'
for menu,url,label in [('clientes','/admin/addons/rel_clientes/','Ativa&ccedil;&otilde;es e Cancelamentos'),('financeiro','/admin/addons/graf_faturamento/graf_det.php','Faturamento detalhado'),('clientes','/admin/addons/busca_inteligente/','Busca inteligente'),('clientes','/admin/addons/rel_plano/','Planos de internet'),('clientes','/admin/addons/busca_inteligente/score.php','Score de clientes'),('clientes','/admin/addons/rel_bloqueados/','Clientes bloqueados')]:
    if not re.search(rb'add_menu\.'+menu.encode()+rb'\s*\([^;]*'+re.escape(url.encode()),s):
        block+=("add_menu."+menu+"(JSON.stringify({plink:window.location.origin+'"+url+"',ptext:'"+label+"'}));\n").encode()
block+=b'/* MKA-SUITE-REPORT-MENUS-END */\n'
p.parent.mkdir(parents=True,exist_ok=True)
p.write_bytes(s+(b'\n' if s and not s.endswith(b'\n') else b'')+block)
