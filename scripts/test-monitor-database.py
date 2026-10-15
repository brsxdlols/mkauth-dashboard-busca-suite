import importlib.util,pathlib,subprocess,unittest
from unittest import mock
root=pathlib.Path(__file__).resolve().parent
spec=importlib.util.spec_from_file_location('patcher',root/'patch-map-traffic.py');m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)
class MonitorDatabase(unittest.TestCase):
 def test_socket_fallback(self):
  failure=subprocess.CalledProcessError(1,['mysql'],stderr='ERROR 2002 (HY000): refused')
  with mock.patch.object(m.subprocess,'run',side_effect=[failure,subprocess.CompletedProcess([],0,stdout='1\t00\n')]) as run,mock.patch('time.sleep'):
   output,args=m.monitor_mysql_read({},'mkradius','SELECT fixture')
   self.assertEqual(output,'1\t00\n');self.assertEqual(args[args.index('-h')+1],'localhost');self.assertEqual(run.call_count,2)
 def test_no_remote_redirection(self):
  failure=subprocess.CalledProcessError(1,['mysql'],stderr='ERROR 2003 (HY000): refused')
  with mock.patch.object(m.subprocess,'run',side_effect=failure) as run,mock.patch('time.sleep'):
   with self.assertRaises(subprocess.CalledProcessError):m.monitor_mysql_read({'MYSQL_HOST':'remote.example'},'mkradius','SELECT fixture')
   self.assertEqual(run.call_count,3)
   self.assertTrue(all(call.args[0][call.args[0].index('-h')+1]=='remote.example' for call in run.call_args_list))
 def test_no_retry_for_auth_or_sql_error(self):
  for code in [1045,1146]:
   failure=subprocess.CalledProcessError(1,['mysql'],stderr='ERROR '+str(code)+' (HY000): fixture')
   with mock.patch.object(m.subprocess,'run',side_effect=failure) as run:
    with self.assertRaises(subprocess.CalledProcessError):m.monitor_mysql_read({},'mkradius','SELECT fixture')
    self.assertEqual(run.call_count,1)
if __name__=='__main__':unittest.main()
