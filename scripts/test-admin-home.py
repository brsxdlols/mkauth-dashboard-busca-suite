import importlib.util,re,unittest
from pathlib import Path
spec=importlib.util.spec_from_file_location('home',Path(__file__).with_name('install-admin-header.py'));home=importlib.util.module_from_spec(spec);spec.loader.exec_module(home)
class HomeTests(unittest.TestCase):
 def test_home_entry_scope_and_preservation(self):
  existing=b'RewriteEngine On\nRewriteRule ^login.php /admin/login.hhvm [L]\n# custom settings\n';after=home.patch_home(existing)
  self.assertTrue(after.endswith(existing));self.assertEqual(home.patch_home(after),after);self.assertEqual(after.count(home.HOME_START),1);rule=re.search(rb'RewriteRule (\S+)',after).group(1).decode()
  for path in ('','index.php','index.hhvm'):self.assertIsNotNone(re.fullmatch(rule,path))
  for path in ('login.php','logout.hhvm','cfg.php','addons/dashboard/','clientes.hhvm'):self.assertIsNone(re.fullmatch(rule,path))
  self.assertIn(b'RewriteCond %{REQUEST_METHOD} ^(?:GET|HEAD)$',after)
 def test_logos_direct_dashboard(self):
  source=(Path(__file__).parents[1]/'addons/shared/admin_header.js').read_text(encoding='utf-8');self.assertIn("link.href='/admin/addons/dashboard/'",source);self.assertNotIn("link.href='/admin/'",source)
if __name__=='__main__':unittest.main(verbosity=2)
