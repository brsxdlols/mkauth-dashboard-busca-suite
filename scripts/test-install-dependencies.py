#!/usr/bin/env python3
"""Linux packaging fixtures; never execute a real IPv6 installer or alter MK-AUTH data."""
import os, pathlib, subprocess, tempfile, unittest
ROOT=pathlib.Path(__file__).resolve().parents[1]

class Packaging(unittest.TestCase):
    def test_existing_radius_is_not_overlaid(self):
        source=(ROOT/'install.sh').read_text()
        block=source.split('echo "[3/8] Instalando arquivos"',1)[1].split('# Grant only the web user',1)[0]
        with tempfile.TemporaryDirectory() as tmp:
            base=pathlib.Path(tmp);addons=base/'admin/addons';radius=addons/'radius';radius.mkdir(parents=True)
            (radius/'index.php').write_text('native Radius entry')
            (radius/'radius.css').write_text('native Radius stylesheet')
            (radius/'client_links.php').write_text('native client lookup')
            (base/'backup/addons').mkdir(parents=True)
            env=dict(os.environ,SCRIPT_DIR=str(ROOT),TARGET_ADMIN_DIR=str(base/'admin'),TARGET_ADDONS_DIR=str(addons),BACKUP_DIR=str(base/'backup'))
            env.pop('BASH_ENV',None)
            subprocess.run(['bash','-eu','-c',block],env=env,check=True)
            self.assertEqual((radius/'index.php').read_text(),'native Radius entry')
            self.assertEqual((radius/'radius.css').read_text(),'native Radius stylesheet')
            self.assertEqual((radius/'client_links.php').read_text(),'native client lookup')
            self.assertEqual(sorted(p.name for p in radius.iterdir()),['client_links.php','index.php','radius.css'])
            self.assertTrue((addons/'graf_faturamento/index.php').is_file())

    def test_complete_radius_bootstrap_precedes_suite_copy(self):
        source=(ROOT/'install.sh').read_text()
        self.assertLess(source.index('scripts/install-radius-dependency.sh'),source.index('echo "[3/8] Instalando arquivos"'))
        with tempfile.TemporaryDirectory() as tmp:
            base=pathlib.Path(tmp);bin_dir=base/'bin';bin_dir.mkdir();curl=bin_dir/'curl'
            curl.write_text("#!/bin/sh\nwhile [ \"$1\" != \"-o\" ]; do shift; done\nshift\ncp \"$RADIUS_FIXTURE\" \"$1\"\n")
            curl.chmod(0o755)
            bootstrap=base/'bootstrap.sh'
            required=(ROOT/'scripts/install-radius-dependency.sh').read_text().split('for file in ',1)[1].split('; do',1)[0].split()
            payload='#!/bin/sh\nset -eu\nmkdir -p "$MKAUTH_ADMIN/addons/radius/assets"\n'
            for name in required:payload+='printf "official fixture" > "$MKAUTH_ADMIN/addons/radius/'+name+'"\n'
            payload+="printf '%s' '{\"version\":\"4.5.0\",\"complete_standalone\":true,\"provider_shortcut\":true,\"current_state\":true,\"preferences\":true,\"connection_tools\":true,\"accounting_read_only\":true}' > \"$MKAUTH_ADMIN/addons/radius/package_features.json\"\n"
            bootstrap.write_text(payload)
            env=dict(os.environ,PATH=str(bin_dir)+os.pathsep+os.environ['PATH'],RADIUS_FIXTURE=str(bootstrap))
            command=['bash',str(ROOT/'scripts/install-radius-dependency.sh'),str(base/'admin'),str(base/'backup')]
            subprocess.run(command,env=env,check=True)
            self.assertEqual((base/'admin/addons/radius/index.php').read_text(),'official fixture')
            bootstrap.write_text(payload.replace('complete_standalone\":true','complete_standalone\":false'))
            self.assertNotEqual(subprocess.run(command,env=env).returncode,0)
            bootstrap.write_text('#!/bin/sh\nexit 9\n')
            self.assertNotEqual(subprocess.run(command,env=env).returncode,0)

    def test_no_radius_overlay_commands(self):
        source=(ROOT/'install.sh').read_text()
        self.assertNotIn('${SCRIPT_DIR}/addons/radius/',source)
        self.assertNotIn('scripts/patch-radius-reader.py',source)
        self.assertNotIn('scripts/install-radius-menu.py',source)
        for line in source.splitlines():
            if line.startswith('for addon in '):self.assertNotIn('radius',line.split())

    def test_receive_loader_is_idempotent(self):
        with tempfile.TemporaryDirectory() as tmp:
            base=pathlib.Path(tmp);admin=base/'admin';(admin/'scripts').mkdir(parents=True)
            target=admin/'scripts/mk-auth.js';target.write_text('/* native */')
            jquery=admin/'scripts/jquery.js';jquery.write_text('/* native jquery */')
            command=['bash',str(ROOT/'scripts/install-receive-notice.sh'),str(admin),str(base/'backup')]
            subprocess.run(command,check=True);first=target.read_bytes();subprocess.run(command,check=True)
            self.assertEqual(target.read_bytes(),first)
            self.assertEqual((base/'backup/mk-auth.js').read_text(),'/* native */')
            self.assertEqual((base/'backup/jquery.js').read_text(),'/* native jquery */')
            self.assertEqual(jquery.read_text().count('mka-receive-notice'),2)
            self.assertIn('receive_notice.js?v=20261016-1',jquery.read_text())
            self.assertTrue((admin/'addons/shared/receive_notice.js').is_file())

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

