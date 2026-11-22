import importlib.util,unittest,subprocess
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
  with patch.object(m,'command',side_effect=command),patch.object(m.shutil,'which',return_value='/usr/sbin/ntpd'),patch.object(m.Path,'is_file',return_value=True):
   with self.assertRaises(RuntimeError):m.repair(dict(active_services=[],services=['ntpsec']))
  self.assertEqual(calls[-1],['service','ntpsec','start'])
 def test_loader_preserves_custom_and_is_idempotent(self):
  s=b'// custom\n';first=installer.patch(s);self.assertTrue(first.startswith(s));self.assertEqual(installer.patch(first),first)
if __name__=='__main__':unittest.main()
