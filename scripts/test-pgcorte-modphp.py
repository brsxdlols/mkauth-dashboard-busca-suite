import importlib.util,tempfile,unittest
from pathlib import Path
from unittest import mock
spec=importlib.util.spec_from_file_location('modphp',Path(__file__).with_name('pgcorte_modphp.py'));mod=importlib.util.module_from_spec(spec);spec.loader.exec_module(mod)
class ModPhpTests(unittest.TestCase):
 def fixture(self):
  t=tempfile.TemporaryDirectory();self.addCleanup(t.cleanup);b=Path(t.name)
  for n in ('admin','mkt','mods'):(b/n).mkdir()
  (b/'mods/php.conf').write_text('SetHandler application/x-httpd-php');(b/'hook.php').write_text('<?php');(b/'admin/.htaccess').write_text('RewriteEngine On\n')
  return b
 def test_scoped_idempotent(self):
  b=self.fixture()
  with mock.patch.object(mod,'HELPER',str(b/'hook.php')),mock.patch.object(mod,'probe_admin')as probe:
   mod.configure_modphp(b/'backup',b/'admin',b/'mkt',b/'mods');before=(b/'admin/.htaccess').read_bytes();mod.configure_modphp(b/'backup2',b/'admin',b/'mkt',b/'mods')
  self.assertEqual(before,(b/'admin/.htaccess').read_bytes());self.assertEqual(before.count(mod.START),1);self.assertIn(b'^ramal_det\\.hhvm$',before);self.assertNotIn(b'SetEnv',before);self.assertEqual(probe.call_count,2)
 def test_http_error_rolls_back(self):
  b=self.fixture();before=(b/'admin/.htaccess').read_bytes()
  with mock.patch.object(mod,'HELPER',str(b/'hook.php')),mock.patch.object(mod,'probe_admin',side_effect=RuntimeError('PHP refused')):
   with self.assertRaises(RuntimeError):mod.configure_modphp(b/'backup',b/'admin',b/'mkt',b/'mods')
  self.assertEqual((b/'admin/.htaccess').read_bytes(),before);self.assertFalse((b/'mkt/.htaccess').exists())
 def test_no_modphp_no_changes(self):
  b=self.fixture();(b/'mods/php.conf').unlink()
  self.assertEqual(mod.configure_modphp(b/'backup',b/'admin',b/'mkt',b/'mods')['status'],'not_used')
 def test_custom_scope_preserved(self):
  b=self.fixture();text='<Files "ramal_det.hhvm">\nphp_value auto_prepend_file custom.php\n</Files>\n';(b/'admin/.htaccess').write_text(text)
  with mock.patch.object(mod,'HELPER',str(b/'hook.php')):
   with self.assertRaisesRegex(RuntimeError,'personalizado'):mod.configure_modphp(b/'backup',b/'admin',b/'mkt',b/'mods')
  self.assertEqual((b/'admin/.htaccess').read_text(),text)
if __name__=='__main__':unittest.main(verbosity=2)
