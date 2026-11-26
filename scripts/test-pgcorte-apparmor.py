import importlib.util
from pathlib import Path
import subprocess,tempfile,unittest
from unittest import mock
spec=importlib.util.spec_from_file_location('aa',Path(__file__).with_name('pgcorte_apparmor.py'));aa=importlib.util.module_from_spec(spec);spec.loader.exec_module(aa)
class AppArmorTests(unittest.TestCase):
 def fixture(self):
  t=tempfile.TemporaryDirectory();self.addCleanup(t.cleanup);base=Path(t.name);root=base/'policy';root.mkdir();(root/'local').mkdir();enabled=base/'enabled';enabled.write_text('Y');profile=root/'php';original='/usr/sbin/php-fpm7.3 {\n /etc/** r,\n}\n';profile.write_text(original)
  return base,root,enabled,profile,original
 def test_read_only_and_idempotent(self):
  b,r,e,p,original=self.fixture()
  with mock.patch.object(aa,'loaded_php_profiles',return_value={'/usr/sbin/php-fpm7.3'}),mock.patch.object(aa,'parser_path',return_value='parser'),mock.patch.object(aa.subprocess,'run',return_value=subprocess.CompletedProcess([],0,b''))as run:
   result=aa.ensure_pgcorte_apparmor(b/'backup',r,e);aa.ensure_pgcorte_apparmor(b/'backup2',r,e)
   self.assertEqual(result['access'],'read_only');self.assertEqual(p.read_text().count(aa.INCLUDE),1);self.assertEqual((r/'local/mkauth-pgcorte-read').read_bytes(),aa.RULES);self.assertEqual([c[0][0][1]for c in run.call_args_list],['-Q','-r','-Q','-r']);self.assertEqual((b/'backup/profile-0.before').read_text(),original)
 def test_parser_failure_restores_policy(self):
  b,r,e,p,original=self.fixture()
  with mock.patch.object(aa,'loaded_php_profiles',return_value={'/usr/sbin/php-fpm7.3'}),mock.patch.object(aa,'parser_path',return_value='parser'),mock.patch.object(aa.subprocess,'run',return_value=subprocess.CompletedProcess([],1,b'fixture refusal')):
   with self.assertRaisesRegex(RuntimeError,'recusou'):aa.ensure_pgcorte_apparmor(b/'backup',r,e)
  self.assertEqual(p.read_text(),original);self.assertFalse((r/'local/mkauth-pgcorte-read').exists())
 def test_unknown_profile_preserved(self):
  b,r,e,p,original=self.fixture()
  with mock.patch.object(aa,'loaded_php_profiles',return_value={'/unknown/php'}):
   with self.assertRaisesRegex(RuntimeError,'nao localizado'):aa.ensure_pgcorte_apparmor(b/'backup',r,e)
  self.assertEqual(p.read_text(),original)
 def test_disabled_no_changes(self):
  b,r,e,p,original=self.fixture();e.write_text('N')
  with mock.patch.object(aa,'loaded_php_profiles')as load:self.assertEqual(aa.ensure_pgcorte_apparmor(b/'backup',r,e)['status'],'not_enabled');load.assert_not_called()
 def test_custom_include_preserved(self):
  b,r,e,p,original=self.fixture();(r/'local/mkauth-pgcorte-read').write_text('custom')
  with mock.patch.object(aa,'loaded_php_profiles',return_value={'/usr/sbin/php-fpm7.3'}),mock.patch.object(aa,'parser_path',return_value='parser'):
   with self.assertRaisesRegex(RuntimeError,'personalizado'):aa.ensure_pgcorte_apparmor(b/'backup',r,e)
  self.assertEqual(p.read_text(),original)
 def test_nested_profiles_rejected(self):
  with self.assertRaises(RuntimeError):aa.profile_candidate('/usr/bin/php {\n /child/php {\n }\n}\n','/usr/bin/php')
if __name__=='__main__':unittest.main(verbosity=2)
