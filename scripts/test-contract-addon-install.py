import os,subprocess,tempfile,unittest
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
class ContractInstallerTests(unittest.TestCase):
 def run_fixture(self,status=0,download_fail=False):
  with tempfile.TemporaryDirectory()as folder:
   base=Path(folder);bin=base/'bin';bin.mkdir()
   (bin/'id').write_text('#!/bin/sh\necho 0\n');(bin/'curl').write_text('#!/bin/sh\n'+('exit 9\n'if download_fail else 'while [ "$#" -gt 0 ]; do if [ "$1" = "-o" ]; then shift; target=$1; fi; shift; done\nprintf \'#!/bin/sh\\nprintf "fixture official installer\\\\n"\\nexit '+str(status)+'\\n\' > "$target"\n'))
   for p in bin.iterdir():p.chmod(0o755)
   env=dict(os.environ,PATH=str(bin)+os.pathsep+os.environ['PATH']);r=subprocess.run(['sh',str(ROOT/'scripts/install-contract-addon.sh'),str(base/'backup')],env=env,stdout=subprocess.PIPE,stderr=subprocess.STDOUT)
   return r.returncode,r.stdout.decode()
 def test_success(self):
  code,out=self.run_fixture();self.assertEqual(code,0);self.assertIn('fixture official installer',out)
 def test_upstream_failure_propagates(self):
  code,out=self.run_fixture(37);self.assertEqual(code,37);self.assertNotIn('Addon oficial atualizado',out)
 def test_download_failure(self):self.assertNotEqual(self.run_fixture(download_fail=True)[0],0)
 def test_common_update_flow(self):
  source=(ROOT/'install.sh').read_text();hook='sh "${SCRIPT_DIR}/scripts/install-contract-addon.sh" "${BACKUP_DIR}/contracts-addon"';self.assertEqual(source.count(hook),1);self.assertNotIn('||',source.split(hook)[1].splitlines()[0]);self.assertLess(source.index(hook),source.index('scripts/install-admin-header.py'))
if __name__=='__main__':unittest.main(verbosity=2)
