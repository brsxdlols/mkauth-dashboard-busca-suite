import importlib.util,unittest
from pathlib import Path
s=importlib.util.spec_from_file_location('mobile',Path(__file__).with_name('install-admin-mobile.py'));m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Test(unittest.TestCase):
 def test_custom_native_bytes_preserved(self):
  original=b'// custom menu latin1 \xff\n';self.assertTrue(m.patch(original).startswith(original))
 def test_idempotent_loader(self):
  first=m.patch(b'// existing\n');self.assertEqual(m.patch(first),first);self.assertEqual(first.count(m.START),1)
if __name__=='__main__':unittest.main()
