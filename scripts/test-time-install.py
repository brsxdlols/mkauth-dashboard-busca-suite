import importlib.util,unittest
from pathlib import Path
from unittest.mock import patch
s=importlib.util.spec_from_file_location('monitor',Path(__file__).with_name('time-monitor.py'));m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Tests(unittest.TestCase):
 def test_no_reference_preserves_clock(self):
  with patch.object(m,'repair') as repair,patch.object(m,'write_status'):
   with self.assertRaises(SystemExit) as e:m.repair_install({'reference_count':1})
   self.assertEqual(e.exception.code,2);repair.assert_not_called()
 def test_repair_then_verify(self):
  with patch.object(m,'repair',return_value={'no_change':True}) as repair,patch.object(m,'diagnose',return_value={'issue':'ok'}),patch.object(m,'write_status') as write:
   m.repair_install({'reference_count':3});repair.assert_called_once();self.assertTrue(write.call_args[0][0]['no_change'])
 def test_unconfirmed_is_not_success(self):
  with patch.object(m,'repair',return_value={}),patch.object(m,'diagnose',return_value={'issue':'service_inactive'}),patch.object(m,'write_status'):
   with self.assertRaises(SystemExit):m.repair_install({'reference_count':3})
if __name__=='__main__':unittest.main()
