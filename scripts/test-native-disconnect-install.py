"""Isolated files only: exercise rollback, repeat and altered-file protection."""
import argparse, hashlib, json, shutil, subprocess, tempfile
from pathlib import Path

def sha(path):return hashlib.sha256(path.read_bytes()).hexdigest()

def main():
    ap=argparse.ArgumentParser();ap.add_argument('native_original',type=Path);a=ap.parse_args()
    here=Path(__file__).resolve().parent;root=here.parent
    with tempfile.TemporaryDirectory(prefix='native-disconnect-test-') as folder:
        base=Path(folder);admin=base/'admin';shared=admin/'addons/shared';shared.mkdir(parents=True)
        libs=base/'libs';libs.mkdir();entry=libs/'freeradius.class';shutil.copy2(a.native_original,entry);before=sha(entry)
        def install(label):
            return subprocess.run(['python3',str(here/'install-native-disconnect-clock.py'),str(admin),str(base/label)],capture_output=True,text=True)
        # Missing decoder in the runtime destination must roll back the entry.
        failed=install('failure')
        assert failed.returncode!=0 and sha(entry)==before and not (shared/'native_disconnect_clock.php').exists(),failed.stdout+failed.stderr
        print('PASS runtime failure rolls back native entry and new helper')
        shutil.copy2(root/'addons/shared/native_activity_performance.php',shared/'native_activity_performance.php')
        installed=install('first');assert installed.returncode==0,installed.stdout+installed.stderr
        first=sha(entry);repeat=install('repeat');assert repeat.returncode==0 and sha(entry)==first,repeat.stdout+repeat.stderr
        meta=json.loads((base/'dados/suite-native-disconnect/manifest.json').read_text());assert sha(Path(meta['original']))==before
        print('PASS install, immutable original and repeat without source drift')
        entry.write_bytes(entry.read_bytes()+b'\n// external customization\n');custom=sha(entry)
        refused=install('modified');assert refused.returncode!=0 and sha(entry)==custom
        print('PASS externally modified native entry preserved')
    print('PASS isolated paths only; no database or router operations')

if __name__=='__main__':main()
