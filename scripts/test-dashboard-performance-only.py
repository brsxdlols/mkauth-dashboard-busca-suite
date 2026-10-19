import importlib.util
from pathlib import Path
import tempfile
import unittest

spec=importlib.util.spec_from_file_location('dashboard_patch',Path(__file__).with_name('patch-dashboard-calculations.py'))
patch=importlib.util.module_from_spec(spec);spec.loader.exec_module(patch)
class CalculationPatchTests(unittest.TestCase):
 def test_isolated_idempotent_with_backup(self):
  with tempfile.TemporaryDirectory() as folder:
   root=Path(folder);target=root/'dashboard';source=root/'donor';target.mkdir();source.mkdir()
   before={name:('legacy '+name).encode() for name in patch.BASELINES}
   saved=patch.BASELINES;patch.BASELINES={name:{patch.fingerprint(data)} for name,data in before.items()}
   try:
    for name,data in before.items():(target/name).write_bytes(data);(source/name).write_bytes(b'optimized '+data)
    (target/'index.php').write_bytes(b'CUSTOM LAYOUT');(target/'config.php').write_bytes(b'CUSTOM CONFIG')
    preview=patch.apply(target,source,root/'backup',True);self.assertEqual(preview['status'],'validated');self.assertFalse((root/'backup').exists())
    result=patch.apply(target,source,root/'backup');self.assertEqual(result['status'],'applied')
    for name,data in before.items():self.assertEqual((root/'backup'/name).read_bytes(),data)
    self.assertEqual((target/'index.php').read_bytes(),b'CUSTOM LAYOUT');self.assertEqual((target/'config.php').read_bytes(),b'CUSTOM CONFIG')
    self.assertEqual(patch.apply(target,source,root/'unused')['status'],'already_applied');self.assertFalse((root/'unused').exists())
   finally:patch.BASELINES=saved
 def test_unknown_custom_file_blocks_all_changes(self):
  with tempfile.TemporaryDirectory() as folder:
   root=Path(folder);target=root/'dashboard';source=root/'donor';target.mkdir();source.mkdir()
   for name in patch.BASELINES:(target/name).write_bytes(b'CUSTOM CALCULATION');(source/name).write_bytes(b'new calculation')
   with self.assertRaises(ValueError):patch.apply(target,source,root/'backup')
   self.assertFalse((root/'backup').exists())
   for name in patch.BASELINES:self.assertEqual((target/name).read_bytes(),b'CUSTOM CALCULATION')
if __name__=='__main__':unittest.main()
