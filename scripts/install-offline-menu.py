#!/usr/bin/env python3
"""Install one owned Clients shortcut without changing other addon entries."""
import re
import sys
from pathlib import Path

path = Path(sys.argv[1]) / 'addons' / 'addon.js'
source = path.read_bytes().decode('utf-8') if path.exists() else ''
start, end = '/* MKA-SUITE-OFFLINE-MENU-START */', '/* MKA-SUITE-OFFLINE-MENU-END */'
source = re.sub(re.escape(start) + r'.*?' + re.escape(end) + r'\r?\n?', '', source, flags=re.S)
block = '''/* MKA-SUITE-OFFLINE-MENU-START */
add_menu.clientes('{"plink": "' + window.location.origin + '/admin/addons/rel_cli_offline/", "ptext": "<b>Clientes offline</b>"}');
/* MKA-SUITE-OFFLINE-MENU-END */
'''
# A link in a custom ADDONS menu is not a Clients menu shortcut.
if not re.search(r'add_menu\.clientes\s*\([^;]*addons/rel_cli_offline(?:/|[\x27\x22`])', source):
    source = source.rstrip() + '\n' + block
path.parent.mkdir(parents=True, exist_ok=True)
path.write_bytes(source.encode('utf-8'))
