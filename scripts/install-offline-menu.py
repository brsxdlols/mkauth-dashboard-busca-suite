#!/usr/bin/env python3
"""Install one owned Clients shortcut without changing other addon entries."""
import re
import sys
from pathlib import Path

path = Path(sys.argv[1]) / 'addons' / 'addon.js'
if path.is_symlink():raise ValueError('addon.js e link simbolico; preservado.')
original = path.read_bytes() if path.exists() else b''
start, end = b'/* MKA-SUITE-OFFLINE-MENU-START */', b'/* MKA-SUITE-OFFLINE-MENU-END */'
source = re.sub(re.escape(start) + rb'.*?' + re.escape(end) + rb'\r?\n?', b'', original, flags=re.S)
block = b'''/* MKA-SUITE-OFFLINE-MENU-START */
add_menu.clientes('{"plink": "' + window.location.origin + '/admin/addons/rel_cli_offline/", "ptext": "&#128268;&nbsp; <b>Clientes offline</b>"}');
/* MKA-SUITE-OFFLINE-MENU-END */
'''
# A link in a custom ADDONS menu is not a Clients menu shortcut.
if not re.search(rb'add_menu\.clientes\s*\([^;]*addons/rel_cli_offline(?:/|[\x27\x22`])', source):
    source = source + (b'\n' if source and not source.endswith(b'\n') else b'') + block
path.parent.mkdir(parents=True, exist_ok=True)
if source != original:path.write_bytes(source)
