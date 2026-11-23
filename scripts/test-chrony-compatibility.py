import ast,re,unittest
from pathlib import Path
# Test compatibility transformation without touching host services or files.
source=Path(__file__).with_name('time-monitor.py').read_text()
function=next(n for n in ast.parse(source).body if isinstance(n,ast.FunctionDef) and n.name=='compatible_chrony_config')
namespace={'re':re}
exec(compile(ast.Module(body=[function],type_ignores=[]),'compatibility-test','exec'),namespace)
fix=namespace['compatible_chrony_config']
class CompatibilityTests(unittest.TestCase):
 def test_known_ntpbr_sources_on_old_version(self):
  for host in ['a.st1.ntp.br','b.st1.ntp.br','c.st1.ntp.br','d.st1.ntp.br','gps.ntp.br']:
   line='server '+host+' iburst nts\n'
   self.assertEqual(fix(line,'chronyd version 3.4'),line.replace(' nts',''))
 def test_preserves_other_sources_and_comments(self):
  text='# server a.st1.ntp.br iburst nts\nserver custom.example iburst nts\nmakestep 1 3\n'
  self.assertEqual(fix(text,'version 3.4'),text)
 def test_modern_version_preserved(self):
  text='server gps.ntp.br iburst nts\n'
  self.assertEqual(fix(text,'version 4.6'),text)
 def test_unknown_version_preserved(self):
  text='server gps.ntp.br iburst nts\n'
  self.assertEqual(fix(text,'unrecognized'),text)
 def test_idempotent(self):
  text='server gps.ntp.br iburst nts\n'
  result=fix(text,'version 3.4')
  self.assertEqual(fix(result,'version 3.4'),result)
if __name__=='__main__':unittest.main()
