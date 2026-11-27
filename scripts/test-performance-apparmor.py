from pathlib import Path
import importlib.util
import unittest
import tempfile
from unittest import mock
from types import SimpleNamespace
import tempfile
from unittest.mock import patch

spec = importlib.util.spec_from_file_location("performance_apparmor", Path(__file__).with_name("install-performance-apparmor.py"))
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)

class PerformancePolicyTests(unittest.TestCase):
    def test_existing_parser_does_not_download_or_reload(self):
        with patch.object(module.shutil,'which',return_value='/usr/sbin/apparmor_parser'),patch.object(module.subprocess,'run') as run:
            self.assertEqual(module.find_parser(Path('/unused')),'/usr/sbin/apparmor_parser');run.assert_not_called()

    def test_missing_package_is_reported_without_changing_profiles(self):
        with tempfile.TemporaryDirectory() as d:
            result=type('Result',(),{'returncode':100,'stdout':'','stderr':'no candidate'})()
            with patch.object(module.shutil,'which',side_effect=[None,'/usr/bin/apt-get','/usr/bin/dpkg-deb']),patch.object(module.subprocess,'run',return_value=result) as run:
                with self.assertRaisesRegex(RuntimeError,'Politica nativa preservada'):module.find_parser(Path(d))
                self.assertEqual(run.call_count,1);self.assertTrue((Path(d)/'parser-package/download.log').is_file())

    def test_native_addon_routing(self):
        apache = '<Directory /opt/mk-auth/admin/addons>\nSetHandler "proxy:unix:/run/php-central.sock|fcgi://127.0.0.1/"\n</Directory>'
        self.assertEqual(module.diagnostic_pools(apache), ["admin", "central"])
        self.assertEqual(module.diagnostic_pools(apache.replace("php-central", "php-admin")), ["admin"])
        with self.assertRaises(ValueError):
            module.diagnostic_pools(apache.replace("php-central", "php-api"))

    def test_existing_security_rules_preserved(self):
        for pool in ["admin", "central"]:
            original = '/opt/mk-auth/hhvm/8-0-30/bin/php-' + pool + ' {\n audit deny network inet,\n /var/** r,\n}\n'
            patched = module.patched_profile(original)
            self.assertEqual(patched.replace("\n" + module.INCLUDE, ""), original)
            self.assertEqual(module.patched_profile(patched), patched)
        self.assertNotIn("network", module.RULES)
        self.assertNotIn(" ix", module.RULES)
        self.assertIn("owner /var/lib/mkauth-suite-performance/*.json rwk,", module.RULES)

    def test_native_attach_disconnected_flag_is_preserved(self):
        for pool in ["admin", "central"]:
            original = '/opt/mk-auth/hhvm/8-0-30/bin/php-' + pool + ' flags=(attach_disconnected) {\n audit deny network inet,\n /opt/mk-auth/** mrwlkix,\n}\n'
            patched = module.patched_profile(original)
            self.assertEqual(patched.replace("\n" + module.INCLUDE, ""), original)
            self.assertEqual(module.patched_profile(patched), patched)

    def test_unrecognized_profile_is_rejected(self):
        with self.assertRaises(ValueError):
            module.patched_profile('/usr/bin/unrelated {\n}\n')

    def test_initial_admin_hook_migrates(self):
        old = '/opt/mk-auth/hhvm/8-0-30/bin/php-admin {\n #include if exists <local/mkauth-suite-php-admin>\n}\n'
        patched = module.patched_profile(old)
        self.assertNotIn("local/mkauth-suite-php-admin", patched)
        self.assertEqual(patched.count("local/mkauth-suite-performance"), 1)

class SecurityFsTests(unittest.TestCase):
    def fixture(self, root):
        security=root/'security';security.mkdir()
        mounts=root/'mounts';mounts.write_text('')
        filesystems=root/'filesystems';filesystems.write_text('nodev securityfs\n')
        backup=root/'backup';backup.mkdir()
        return security,mounts,filesystems,backup

    def test_mount_once_and_repeat_without_reload_or_global_service(self):
        with tempfile.TemporaryDirectory() as directory:
            security,mounts,filesystems,backup=self.fixture(Path(directory))
            def mount(command, **kwargs):
                self.assertEqual(command,['/bin/mount','-t','securityfs','securityfs',str(security)])
                (security/'apparmor').mkdir();(security/'apparmor/.load').touch()
                return SimpleNamespace(returncode=0,stdout='',stderr='')
            with mock.patch.object(module.shutil,'which',return_value='/bin/mount'),mock.patch.object(module.subprocess,'run',side_effect=mount) as run:
                module.ensure_securityfs(backup,security,mounts,filesystems)
                module.ensure_securityfs(backup,security,mounts,filesystems)
                self.assertEqual(run.call_count,1)

    def test_denied_mount_is_reported_and_recorded(self):
        with tempfile.TemporaryDirectory() as directory:
            security,mounts,filesystems,backup=self.fixture(Path(directory))
            with mock.patch.object(module.shutil,'which',return_value='/bin/mount'),mock.patch.object(module.subprocess,'run',return_value=SimpleNamespace(returncode=32,stdout='',stderr='permission denied')):
                with self.assertRaises(RuntimeError):module.ensure_securityfs(backup,security,mounts,filesystems)
            self.assertIn('permission denied',(backup/'securityfs-mount.log').read_text())

    def test_existing_mount_without_apparmor_is_never_replaced(self):
        with tempfile.TemporaryDirectory() as directory:
            security,mounts,filesystems,backup=self.fixture(Path(directory))
            mounts.write_text('securityfs '+str(security)+' securityfs rw 0 0\n')
            with mock.patch.object(module.subprocess,'run') as run:
                with self.assertRaises(RuntimeError):module.ensure_securityfs(backup,security,mounts,filesystems)
                run.assert_not_called()

    def test_kernel_without_securityfs_is_preserved(self):
        with tempfile.TemporaryDirectory() as directory:
            security,mounts,filesystems,backup=self.fixture(Path(directory));filesystems.write_text('nodev proc\n')
            with mock.patch.object(module.subprocess,'run') as run:
                with self.assertRaises(RuntimeError):module.ensure_securityfs(backup,security,mounts,filesystems)
                run.assert_not_called()

if __name__ == "__main__":
    unittest.main()
