#!/usr/bin/env python3
import importlib.util, os, stat, sys, tempfile
from pathlib import Path
spec=importlib.util.spec_from_file_location('permissions',sys.argv[1]);module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
with tempfile.TemporaryDirectory(prefix='mka-permission-test-') as folder:
    base=Path(folder);root=base/'addons';root.mkdir(mode=0o700)
    for name in ['dashboard','shared','busca_inteligente','third-party']:(root/name).mkdir(mode=0o700)
    (root/'shared').chmod(0o666)
    tmp=root/'busca_inteligente'/'tmp';tmp.mkdir(mode=0o700)
    private=root/'third-party'/'private';private.write_text('untouched');private.chmod(0o600)
    external=base/'external';external.write_text('untouched');external.chmod(0o600)
    (root/'shared'/'outside').symlink_to(external)
    page=root/'dashboard'/'index.php';page.write_text('<?php');page.chmod(0o600)
    previous=lambda p:(p.stat().st_uid,p.stat().st_gid,stat.S_IMODE(p.stat().st_mode))
    before={str(p):previous(p) for p in [root,root/'dashboard',tmp,page,private,external]}
    module.apply(root,base/'backup');assert stat.S_IMODE(root.stat().st_mode)==0o755;assert stat.S_IMODE((root/'shared').stat().st_mode)==0o755;assert stat.S_IMODE(tmp.stat().st_mode)==0o770
    assert previous(private)==before[str(private)];assert previous(external)==before[str(external)]
    module.apply(root,base/'second');assert not (base/'second').exists()
    module.restore(root,base/'backup'/'addon-permissions.json')
    for path,value in before.items():assert previous(Path(path))==value
print('PASS old 0700/0666 directories, web ownership, private tmp, unrelated addons, symlinks, idempotence and rollback')
