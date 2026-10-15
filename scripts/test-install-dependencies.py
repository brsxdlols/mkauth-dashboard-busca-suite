#!/usr/bin/env python3
"""Linux packaging fixtures; never execute a real IPv6 installer or alter MK-AUTH data."""
import os, pathlib, subprocess, tempfile, unittest
ROOT=pathlib.Path(__file__).resolve().parents[1]

class Packaging(unittest.TestCase):
    def test_existing_radius_keeps_native_entry_and_gets_modal_css(self):
        source=(ROOT/'install.sh').read_text()
        block=source.split('echo "[3/8] Instalando arquivos"',1)[1].split('# Grant only the web user',1)[0]
        with tempfile.TemporaryDirectory() as tmp:
            base=pathlib.Path(tmp);addons=base/'admin/addons';radius=addons/'radius';radius.mkdir(parents=True)
            (radius/'index.php').write_text('native Radius entry')
            (radius/'radius.css').write_text('native Radius stylesheet')
            (base/'backup/addons').mkdir(parents=True)
            env=dict(os.environ,SCRIPT_DIR=str(ROOT),TARGET_ADDONS_DIR=str(addons),BACKUP_DIR=str(base/'backup'))
            subprocess.run(['bash','-eu','-c',block],env=env,check=True)
            self.assertEqual((radius/'index.php').read_text(),'native Radius entry')
            self.assertEqual((radius/'radius.css').read_text(),'native Radius stylesheet')
            self.assertEqual((radius/'suite-live.css').read_bytes(),(ROOT/'addons/radius/radius.css').read_bytes())
            self.assertTrue((addons/'graf_faturamento/index.php').is_file())

    def test_receive_loader_is_idempotent(self):
        with tempfile.TemporaryDirectory() as tmp:
            base=pathlib.Path(tmp);admin=base/'admin';(admin/'scripts').mkdir(parents=True)
            target=admin/'scripts/mk-auth.js';target.write_text('/* native */')
            command=['bash',str(ROOT/'scripts/install-receive-notice.sh'),str(admin),str(base/'backup')]
            subprocess.run(command,check=True);first=target.read_bytes();subprocess.run(command,check=True)
            self.assertEqual(target.read_bytes(),first)
            self.assertEqual((base/'backup/mk-auth.js').read_text(),'/* native */')

    def test_ipv6_official_installer_downloads_before_execution(self):
        with tempfile.TemporaryDirectory() as tmp:
            base=pathlib.Path(tmp);bin_dir=base/'bin';bin_dir.mkdir();curl=bin_dir/'curl'
            curl.write_text('#!/bin/sh\nwhile [ "$1" != "-o" ]; do shift; done\nshift\nprintf "#!/bin/sh\\nexit 0\\n" > "$1"\n')
            curl.chmod(0o755)
            env=dict(os.environ,PATH=str(bin_dir)+os.pathsep+os.environ['PATH'])
            command=['bash',str(ROOT/'scripts/install-ipv6-dependency.sh'),'/opt/mk-auth/admin',str(base/'backup')]
            subprocess.run(command,env=env,check=True)
            self.assertTrue((base/'backup/install.log').is_file())
            curl.write_text('#!/bin/sh\nexit 22\n');curl.chmod(0o755)
            self.assertNotEqual(subprocess.run(command,env=env).returncode,0)

if __name__=='__main__':unittest.main()
