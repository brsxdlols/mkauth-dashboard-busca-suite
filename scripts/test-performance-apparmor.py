from pathlib import Path
import importlib.util
import unittest

spec = importlib.util.spec_from_file_location("performance_apparmor", Path(__file__).with_name("install-performance-apparmor.py"))
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)

class PerformancePolicyTests(unittest.TestCase):
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

    def test_initial_admin_hook_migrates(self):
        old = '/opt/mk-auth/hhvm/8-0-30/bin/php-admin {\n #include if exists <local/mkauth-suite-php-admin>\n}\n'
        patched = module.patched_profile(old)
        self.assertNotIn("local/mkauth-suite-php-admin", patched)
        self.assertEqual(patched.count("local/mkauth-suite-performance"), 1)

if __name__ == "__main__":
    unittest.main()
