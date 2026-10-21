#!/usr/bin/env python3
"""Patch the verified shell-based reader and add suite tools without replacing the full addon."""
import shutil,sys
from pathlib import Path
MARKER='// MKA-SUITE-NATIVE-LOG-READER'
def patch_reader(text):
    if MARKER in text:return text
    base=text.index('function radius_read_logs(');start=text.index('    if (is_readable($logPath)) {',base);end=text.index('    $counts = array(',start)
    block=text[start:end]
    if "shell_exec($command)" not in block or "'tail -n '" not in block or '$result === null' not in block:raise ValueError('Leitor Radius personalizado preservado; revisão necessária.')
    replacement='''    // MKA-SUITE-NATIVE-LOG-READER
    try {
        require_once __DIR__.'/../shared/radius_log_reader.php';
        $logLines=mka_radius_tail($logPath,$linesLimit);
    } catch (Throwable $e) {
        $logError='Não foi possível ler o arquivo de log do FreeRADIUS.';
    }

'''
    return text[:start]+replacement+text[end:]
def patch_index(text):
    if '/shared/connection_settings.js' in text:return text.replace('connection_settings.js?v=20261020-3','connection_settings.js?v=20261021-1').replace('connection_settings.js?v=20261020-2','connection_settings.js?v=20261021-1').replace('connection_settings.css?v=20261020-1','connection_settings.css?v=20261020-2').replace('connection_settings.js?v=20261020-1','connection_settings.js?v=20261021-1')
    if '</body>' not in text:raise ValueError('Página Radius sem ponto de integração conhecido.')
    assets='<link rel="stylesheet" href="../shared/connection_settings.css?v=20261020-2">\n<script src="../shared/connection_settings.js?v=20261021-1"></script>\n'
    return text.replace('</body>',assets+'</body>',1)
def install(admin,backup):
    root=Path(admin)/'addons/radius';backup=Path(backup);backup.mkdir(parents=True,exist_ok=True)
    changes=[]
    for name,patch in [('radius_lib.php',patch_reader),('index.php',patch_index)]:
        path=root/name
        if path.is_symlink():raise ValueError('Link simbólico preservado: '+str(path))
        old=path.read_text(encoding='utf-8');new=patch(old)
        if new!=old:changes.append((path,new))
    for path,new in changes:
        shutil.copy2(path,backup/path.name);path.write_bytes(new.encode('utf-8'))
    print('Radius: leitor e opções IP/MAC integrados ('+str(len(changes))+' arquivo(s)).')
if __name__=='__main__':install(sys.argv[1],sys.argv[2])
