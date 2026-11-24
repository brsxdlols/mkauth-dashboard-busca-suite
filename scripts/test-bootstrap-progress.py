"""Bootstrap integration fixtures: local fake download, no network or installation."""
import errno
import fcntl
import os
from pathlib import Path
import pty
import re
import struct
import subprocess
import tarfile
import tempfile
import termios
import unittest

ROOT=Path(__file__).resolve().parent.parent

class BootstrapProgress(unittest.TestCase):
    def run_bootstrap(self, terminal=False, fetch_status=0, install_status=0, force_color=False):
        with tempfile.TemporaryDirectory(prefix='suite-bootstrap-test-') as folder:
            tmp=Path(folder);package=tmp/'package/mkauth-dashboard-busca-suite-fixture';(package/'scripts').mkdir(parents=True)
            (package/'scripts/install-progress.py').write_bytes((ROOT/'scripts/install-progress.py').read_bytes())
            (package/'install.sh').write_text("#!/bin/sh\necho '[ntp] 20% Conferindo'\nsleep .1\necho '[ntp] 85% Validando'\nexit "+str(install_status)+'\n')
            archive=tmp/'fixture.tar.gz'
            with tarfile.open(archive,'w:gz') as tar:tar.add(package,arcname=package.name)
            bindir=tmp/'bin';bindir.mkdir();curl=bindir/'curl'
            curl.write_text('''#!/bin/sh
dest=''
while [ "$#" -gt 0 ]; do
  if [ "$1" = '-o' ]; then shift; dest="$1"; fi
  shift
done
cp "$FIXTURE_ARCHIVE" "$dest"
sleep .2
exit "$FIXTURE_FETCH_STATUS"
''')
            curl.chmod(0o700)
            env=dict(os.environ,PATH=str(bindir)+':'+os.environ['PATH'],FIXTURE_ARCHIVE=str(archive),FIXTURE_FETCH_STATUS=str(fetch_status),NO_COLOR='1',COLUMNS='40')
            if force_color:env.pop('NO_COLOR',None);env['FORCE_COLOR']='1'
            else:env.pop('FORCE_COLOR',None)
            cmd=['bash',str(ROOT/'install-from-github.sh'),str(tmp/'admin'),str(tmp/'logs')]
            if terminal:
                master,slave=pty.openpty();fcntl.ioctl(slave,termios.TIOCSWINSZ,struct.pack('HHHH',24,40,0,0))
                p=subprocess.Popen(cmd,stdout=slave,stderr=slave,env=env);os.close(slave);chunks=[]
                try:
                    while True:
                        try:
                            b=os.read(master,65536)
                            if not b:break
                            chunks.append(b)
                        except OSError as e:
                            if e.errno==errno.EIO:break
                            raise
                    code=p.wait(timeout=15)
                finally:os.close(master)
                text=b''.join(chunks).decode(errors='replace')
            else:
                p=subprocess.run(cmd,stdout=subprocess.PIPE,stderr=subprocess.STDOUT,env=env,timeout=15)
                code,text=p.returncode,p.stdout.decode(errors='replace')
            return code,text

    def test_mobile_download_has_bytes_without_fake_percentage(self):
        code,text=self.run_bootstrap(terminal=True)
        self.assertEqual(code,0,text)
        self.assertRegex(text,r'Download \d+ KiB - \d+s')
        self.assertNotIn('estimado',text)
        self.assertIn('[OK] Download 100%',text)
        self.assertIn('[4/4] Instalacao remota concluida',text)
        clean=re.sub(r'\x1b\[[0-?]*[ -/]*[@-~]','',text)
        download=[x for x in re.split(r'[\r\n]',clean) if x.startswith('[...] Download')]
        self.assertTrue(all(len(x)<40 and '%' not in x for x in download),download)

    def test_download_failure_never_starts_installation(self):
        code,text=self.run_bootstrap(terminal=True,fetch_status=22)
        self.assertEqual(code,22,text)
        self.assertNotIn('[OK] Download',text)
        self.assertNotIn('[3/4]',text)

    def test_force_color_pipe_does_not_enable_cursor_animation(self):
        code,text=self.run_bootstrap(force_color=True)
        self.assertEqual(code,0,text)
        self.assertNotIn('\r',text)
        self.assertNotIn('\x1b[2K',text)

    def test_installer_failure_preserves_exit_status(self):
        code,text=self.run_bootstrap(install_status=7)
        self.assertEqual(code,7,text)
        self.assertNotIn('[4/4] Instalacao remota concluida',text)

if __name__=='__main__':unittest.main()
