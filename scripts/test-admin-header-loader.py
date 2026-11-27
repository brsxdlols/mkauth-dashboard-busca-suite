import importlib.util,tempfile,subprocess,sys
from pathlib import Path
spec=importlib.util.spec_from_file_location('header',Path(__file__).with_name('install-admin-header.py'));module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
with tempfile.TemporaryDirectory(prefix='header-loader-fixture-')as tmp:
 root=Path(tmp);admin=root/'admin';(admin/'addons').mkdir(parents=True);(admin/'scripts').mkdir();(admin/'addons/dashboard').mkdir();(admin/'addons/dashboard/index.php').write_text('<?php')
 original=b'// custom caf\xe9\n';(admin/'addons/addon.js').write_bytes(original);(admin/'scripts/mk-auth.js').write_bytes(b'// native\n')
 args=[sys.executable,str(Path(__file__).with_name('install-admin-header.py')),str(admin),str(root/'backup')]
 subprocess.run(args,check=True);first=(admin/'addons/addon.js').read_bytes();args[-1]=str(root/'repeat');subprocess.run(args,check=True)
 assert first==(admin/'addons/addon.js').read_bytes()and original in first and first.count(module.START)==1
 external=root/'external';external.write_bytes(b'untouched');(admin/'scripts/mk-auth.js').unlink();(admin/'scripts/mk-auth.js').symlink_to(external)
 args[-1]=str(root/'fail');assert subprocess.run(args,stdout=subprocess.PIPE,stderr=subprocess.PIPE).returncode!=0
 assert external.read_bytes()==b'untouched'and (admin/'addons/addon.js').read_bytes()==first
 print('PASS native loader byte preservation, idempotent installation and symbolic-target rejection')

