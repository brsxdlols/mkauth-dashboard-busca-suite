import importlib.util,json,tempfile,unittest
from pathlib import Path
from unittest.mock import patch
s=importlib.util.spec_from_file_location('service',Path(__file__).with_name('time-monitor-service.py'));m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Tests(unittest.TestCase):
 def test_failure_is_terminal_and_removes_only_its_request(self):
  with tempfile.TemporaryDirectory() as d:
   queue=Path(d)/'request.json';task={'id':'a'*32,'action':'repair'};queue.write_text(json.dumps(task))
   with patch.object(m,'QUEUE',queue):m.failure(task,'fixture failure')
   result=json.loads((Path(d)/'status.json').read_text());self.assertEqual(result['repair_status'],'error');self.assertEqual(result['request_id'],task['id']);self.assertFalse(queue.exists())
 def test_unrelated_new_request_is_preserved(self):
  with tempfile.TemporaryDirectory() as d:
   queue=Path(d)/'request.json';queue.write_text(json.dumps({'id':'b'*32,'action':'check'}))
   with patch.object(m,'QUEUE',queue):m.failure({'id':'a'*32,'action':'repair'},'failure')
   self.assertEqual(json.loads(queue.read_text())['id'],'b'*32)
if __name__=='__main__':unittest.main()
