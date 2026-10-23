#!/usr/bin/env python3
"""Menu installation preserves arbitrary legacy script bytes and native entries."""
import importlib.util
from pathlib import Path
import tempfile
import unittest

spec=importlib.util.spec_from_file_location('radius_menu',Path(__file__).with_name('install-radius-menu.py'))
menu=importlib.util.module_from_spec(spec);spec.loader.exec_module(menu)

class RadiusMenuEncoding(unittest.TestCase):
    def test_preserve_legacy_and_utf8_bytes(self):
        for original in [b'// a\xe7\xe3o e op\xe7\xf5es\r\nvar legacy="\x80\x81";\r\n',
                         '// Ação e opções\n'.encode('utf-8'),b'\xef\xbb\xbf// UTF8 BOM\n']:
            with self.subTest(original=original):
                changed=menu.patch_bytes(original)
                self.assertTrue(changed.startswith(original))
                self.assertEqual(menu.patch_bytes(changed),changed)
                # Updating an owned block must also preserve following scripts.
                tail=b'\r\n// extens\xe3o posterior\r\n'
                self.assertTrue(menu.patch_bytes(changed+tail).endswith(tail))

    def test_install_mixed_encodings_backup_and_repeat(self):
        with tempfile.TemporaryDirectory() as temp:
            root=Path(temp);admin=root/'admin';backup=root/'backup'
            custom=admin/'addons/addon.js';native=admin/'scripts/mk-auth.js'
            custom.parent.mkdir(parents=True);native.parent.mkdir(parents=True)
            original_custom=b'// cliente: a\xe7\xe3o\r\nadd_menu.opcoes("{}");\r\n'
            original_native='// Administração nativa\n'.encode('utf-8')
            custom.write_bytes(original_custom);native.write_bytes(original_native)
            custom.chmod(0o640)
            menu.install(admin,backup)
            self.assertEqual((backup/'addons_addon.js').read_bytes(),original_custom)
            self.assertEqual((backup/'scripts_mk-auth.js').read_bytes(),original_native)
            self.assertEqual(custom.stat().st_mode & 0o777,0o640)
            first=(custom.read_bytes(),native.read_bytes())
            menu.install(admin,backup)
            self.assertEqual((custom.read_bytes(),native.read_bytes()),first)

if __name__=='__main__':unittest.main()
