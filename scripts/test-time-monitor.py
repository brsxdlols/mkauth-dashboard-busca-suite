import importlib.util,unittest,subprocess,tempfile,os
from pathlib import Path
from unittest.mock import patch

def load(name):
 s=importlib.util.spec_from_file_location(name,Path(__file__).with_name(name+'.py'));m=importlib.util.module_from_spec(s);s.loader.exec_module(m);return m
m=load('time-monitor');installer=load('install-time-monitor')
class Tests(unittest.TestCase):
 def diagnose(self,values):
  with patch.object(m.Path,'is_file',return_value=False):return m.diagnose(lambda host:values[m.SERVERS.index(host)])
 def test_agreement_discards_outlier(self):
  d=self.diagnose([-3600,-3601,7200]);self.assertEqual(d['issue'],'clock_offset');self.assertEqual(d['offset_seconds'],-3600.5)
 def test_no_agreement_is_not_firewall_diagnosis(self):self.assertEqual(self.diagnose([0,90,180])['issue'],'unverified')
 def test_stopped_service(self):self.assertEqual(self.diagnose([0,0,0])['issue'],'service_inactive')
 def test_no_installed_service_refuses_repair(self):
  with self.assertRaisesRegex(RuntimeError,'Nenhum'):m.repair(dict(active_services=[],services=[]))
 def test_multiple_services_refuses_repair(self):
  with self.assertRaisesRegex(RuntimeError,'Mais de um'):m.repair(dict(active_services=['ntp','chrony'],services=['ntp','chrony']))
 def test_failed_one_shot_restores_daemon(self):
  calls=[]
  def command(args):
   calls.append(args)
   if '-gq' in args:raise RuntimeError('test failure')
  with patch.object(m,'configure_timezones',return_value={}),patch.object(m,'command',side_effect=command),patch.object(m.shutil,'which',return_value='/usr/sbin/ntpd'),patch.object(m.Path,'is_file',return_value=True):
   with self.assertRaises(RuntimeError):m.repair(dict(active_services=[],services=['ntpsec']))
  self.assertEqual(calls[-1],['service','ntpsec','start'])
 def test_timezone_patch_preserves_other_settings(self):
  self.assertEqual(m.patch_timezone("keep=true;date_default_timezone_set('America/Sao_Paulo');",'America/Bahia'),"keep=true;date_default_timezone_set('America/Bahia');")
  self.assertEqual(m.patch_timezone('memory_limit=128M\ndate.timezone = "America/Sao_Paulo"\n','America/Bahia',True),'memory_limit=128M\ndate.timezone = "America/Bahia"\n')
 def test_commented_ini_timezone_is_enabled(self):
  self.assertEqual(m.patch_timezone(';date.timezone =\nmemory_limit=128M\n','America/Bahia',True),'date.timezone ="America/Bahia"\nmemory_limit=128M\n')
 def test_unknown_timezone_config_is_preserved(self):
  with self.assertRaises(RuntimeError):m.patch_timezone('opaque native configuration','America/Bahia')
 def test_ambiguous_provider_refuses_timezone_changes(self):
  with patch.object(m.subprocess,'run',return_value=subprocess.CompletedProcess([],0,'AM\tUNKNOWN\n','')):
   with self.assertRaises(RuntimeError):m.provider_timezone()
 def test_timezone_file_failure_rolls_back(self):
  with tempfile.TemporaryDirectory() as d:
   root=Path(d);a=root/'configure.php';a.write_text('old');z=root/'zone';z.write_text('zone bytes');old=root/'oldzone';old.write_text('old zone bytes');local=root/'localtime';local.symlink_to(old);tz=root/'timezone';tz.write_text('old zone')
   original=m.os.replace;calls=[]
   def fail_once(src,dst):
    calls.append(dst)
    if len(calls)==2:raise OSError('fixture failure')
    return original(src,dst)
   with patch.object(m.os,'replace',side_effect=fail_once):
    with self.assertRaises(OSError):m.apply_timezone_files({a:'new'},z,'newzone',local,tz,root)
   self.assertEqual(a.read_text(),'old');self.assertTrue(local.is_symlink());self.assertEqual(os.readlink(local),str(old));self.assertEqual(z.read_text(),'zone bytes');self.assertEqual(tz.read_text(),'old zone')
 def test_cron_environment_restores_administrative_path(self):
  with patch.dict(os.environ,{'PATH':'/usr/bin:/bin'}):
   m.configure_environment();self.assertEqual(os.environ['PATH'],'/usr/sbin:/usr/bin:/sbin:/bin')
 def test_repair_skips_already_correct_server(self):
  with patch.object(m,'timezone_config_is_correct',return_value=True),patch.object(m,'command') as command:
   result=m.repair({'issue':'ok'});self.assertTrue(result['no_change']);command.assert_not_called()
 def test_loader_preserves_custom_and_is_idempotent(self):
  s=b'// custom\n';first=installer.patch(s);self.assertTrue(first.startswith(s));self.assertEqual(installer.patch(first),first)
if __name__=='__main__':unittest.main()
