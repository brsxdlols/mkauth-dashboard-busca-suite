"""Root-only ownership fixtures; no production addons are touched."""
import importlib.util
import os
from pathlib import Path
import pwd
import stat
import tempfile
import unittest

spec=importlib.util.spec_from_file_location('addon_permissions',Path(__file__).with_name('fix-addon-permissions.py'));module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)


@unittest.skipUnless(os.geteuid()==0,'Ownership tests require root in an isolated directory')
class FinalPermissions(unittest.TestCase):
    def test_all_addons_root_files_private_paths_repeat_and_restore(self):
        with tempfile.TemporaryDirectory(prefix='all-addon-permissions-')as directory:
            base=Path(directory);root=base/'addons';root.mkdir(mode=0o700)
            for name in ['dashboard','shared','foreign-addon','radius']:(root/name).mkdir(mode=0o700)
            (root/'foreign-addon').chmod(0o666)
            page=root/'foreign-addon'/'index.php';page.write_text('<?php // fixture');page.chmod(0o600)
            loader=root/'addon.js';loader.write_text('// fixture');loader.chmod(0o640)
            tmp=root/'dashboard'/'tmp';tmp.mkdir(mode=0o700)
            private=root/'foreign-addon'/'Backups';private.mkdir(mode=0o700);secret=private/'private.sql';secret.write_text('fixture only');secret.chmod(0o600)
            external=base/'outside';external.mkdir(mode=0o700);outside=external/'file';outside.write_text('unchanged');outside.chmod(0o600)
            (root/'foreign-addon'/'linked').symlink_to(external,target_is_directory=True)
            (root/'external-addon').symlink_to(external,target_is_directory=True)
            def metadata(path):
                info=path.stat();return info.st_uid,info.st_gid,stat.S_IMODE(info.st_mode)
            paths=[root,page,loader,tmp,private,secret,outside,root/'foreign-addon']
            before={p:metadata(p)for p in paths}
            module.apply(root,base/'backup',all_addons=True)
            account=pwd.getpwnam('www-data')
            for p in [page,loader,root/'foreign-addon']:
                self.assertEqual(metadata(p)[:2],(account.pw_uid,account.pw_gid))
            self.assertEqual(metadata(root/'foreign-addon')[2],0o755)
            self.assertEqual(metadata(page)[2],0o600);self.assertEqual(metadata(loader)[2],0o640)
            self.assertEqual(metadata(tmp)[2],0o770)
            for p in [private,secret,outside]:self.assertEqual(metadata(p),before[p])
            self.assertTrue((root/'external-addon').is_symlink())
            module.apply(root,base/'repeat',all_addons=True);self.assertFalse((base/'repeat').exists())
            module.restore(root,base/'backup'/'addon-permissions.json')
            for p in paths:self.assertEqual(metadata(p),before[p])

    def test_private_paths_also_preserved_during_initial_suite_pass(self):
        with tempfile.TemporaryDirectory()as directory:
            base=Path(directory);root=base/'addons';private=root/'dashboard'/'private';private.mkdir(parents=True,mode=0o700)
            info=private.stat();module.apply(root,base/'backup')
            now=private.stat();self.assertEqual((now.st_uid,now.st_gid,stat.S_IMODE(now.st_mode)),(info.st_uid,info.st_gid,stat.S_IMODE(info.st_mode)))


if __name__=='__main__':unittest.main(verbosity=2)
