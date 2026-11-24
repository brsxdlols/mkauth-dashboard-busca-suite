#!/usr/bin/env python3
"""Install the stock-aware customer equipment widget and native contract variable.
Native originals are preserved outside the web root. No existing allocation is migrated.
"""
import argparse, hashlib, json, os, re, shutil, subprocess, tempfile
from pathlib import Path

START=b'/* MKA-CLIENT-EQUIPMENT-START */'; END=b'/* MKA-CLIENT-EQUIPMENT-END */'
BLOCK=START+b"\n;(function(){if(!/\\/cliente_(alt|ins)\\.(hhvm|php)$/.test(location.pathname))return;var l=document.createElement('link');l.rel='stylesheet';l.href='/admin/addons/client_equipment/equipment.css?v=20261123-5';document.head.appendChild(l);var s=document.createElement('script');s.src='/admin/addons/client_equipment/equipment.js?v=20261123-5';document.head.appendChild(s);})();\n"+END+b'\n'
def patch_loader(raw):
    return re.sub(re.escape(START)+b'.*?'+re.escape(END)+b'\r?\n?',b'',raw,flags=re.S).rstrip(b'\r\n')+b'\n'+BLOCK
def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
def php_string(s):return "'"+str(s).replace('\\','\\\\').replace("'","\\'")+"'"
def command(args):return subprocess.check_output(args,stderr=subprocess.STDOUT).decode('utf8',errors='replace')
def atomic(path,data):
    path.parent.mkdir(parents=True,exist_ok=True)
    fd,tmp=tempfile.mkstemp(prefix='.equipment-',dir=str(path.parent));os.close(fd)
    try:
        Path(tmp).write_bytes(data);os.chmod(tmp,0o644)
        if path.suffix in ('.php','.hhvm'):command(['php','-l',tmp])
        os.replace(tmp,path)
    finally:
        if os.path.exists(tmp):os.unlink(tmp)
def main():
    ap=argparse.ArgumentParser();ap.add_argument('admin',type=Path);ap.add_argument('backup',type=Path);ap.add_argument('--dry-run',action='store_true');a=ap.parse_args()
    root=Path(__file__).resolve().parent.parent;source=root/'addons/client_equipment';target=a.admin/'addons/client_equipment';entry=a.admin/'gera_contrato.hhvm';manifest=target/'suite-manifest.json';storage=Path('/var/lib/mkauth-suite-equipment')
    a.backup.mkdir(parents=True,exist_ok=True);os.chmod(a.backup,0o700)
    if entry.is_symlink() or target.is_symlink() or manifest.is_symlink():raise RuntimeError('Link simbólico preservado; instalação interrompida.')
    previous=json.loads(manifest.read_text()) if manifest.exists() else {}
    if b'mka-suite-equipment-contract-v1' in entry.read_bytes():
        if digest(entry)!=previous.get('installed_sha256'):raise RuntimeError('Hook do contrato alterado por outro módulo.')
        original=Path(previous['original'])
        if digest(original)!=previous.get('source_sha256'):raise RuntimeError('Original nativo alterado.')
    else:original=entry
    # Decode only the vendor codec and validate the exact authenticated insertion anchor.
    helper="<?php define('MKA_EQUIPMENT_CODEC_TEST',true);require "+php_string(source/'contract_runtime.php')+";$p=$argv[1];eval('?>'.mka_activity_decoder_source(file_get_contents($p),$p));mka_equipment_patch_contract($mkaActivityDecoded);echo 'compatible';"
    check=a.backup/'compatibility.php';check.write_bytes(helper.encode())
    if command(['php',str(check),str(original)]).strip()!='compatible':raise RuntimeError('Contrato nativo incompatível.')
    # Stock debits and allocations must be transactional; never silently use MyISAM.
    schema="<?php require "+php_string(root/'scripts/installer-database.php')+";require "+php_string(source/'model.php')+";$db=mka_installer_database_connect();$r=$db->query(\"SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('sis_cliente','sis_produto','sis_estoque','sis_prodcliente','sis_prodhistorico')\");if($r->num_rows!==5)throw new RuntimeException('Tabelas nativas ausentes');while($t=$r->fetch_assoc())if(!in_array(strtolower($t['ENGINE']),array('innodb','tokudb'),true))throw new RuntimeException('Estoque sem suporte a transações');"
    if not a.dry_run:schema+="mka_equipment_schema($db);"
    schema+="echo 'compatible';";schemaFile=a.backup/'schema.php';schemaFile.write_bytes(schema.encode())
    if command(['php',str(schemaFile)]).strip()!='compatible':raise RuntimeError('Estoque incompatível.')
    if a.dry_run:print('[equipment] native contract and transactional stock compatible');return
    storage.mkdir(parents=True,exist_ok=True);os.chmod(storage,0o755);saved=storage/('gera_contrato-'+digest(original)+'.hhvm')
    if not saved.exists():shutil.copy2(original,saved);os.chmod(saved,0o444)
    if digest(saved)!=digest(original):raise RuntimeError('Backup do contrato divergente.')
    wrapper=('<?php\n/* mka-suite-equipment-contract-v1 */\n$mkaEquipmentOriginal='+php_string(saved)+';\nrequire '+php_string(target/'contract_runtime.php')+';\n').encode()
    changes={}
    for p in source.iterdir():
        if p.is_file() and p.name!='suite-manifest.json':changes[target/p.name]=p.read_bytes()
    changes[entry]=wrapper
    for rel in ['addons/addon.js','scripts/mk-auth.js']:
        p=a.admin/rel
        if p.is_symlink():raise RuntimeError('Loader simbólico preservado.')
        if p.exists():changes[p]=patch_loader(p.read_bytes())
    if a.admin/'addons/addon.js' not in changes:changes[a.admin/'addons/addon.js']=patch_loader(b'')
    meta={'original':str(saved),'source_sha256':digest(saved),'installed_sha256':hashlib.sha256(wrapper).hexdigest()}
    changes[manifest]=json.dumps(meta,indent=2).encode();rollback=[]
    for i,(p,data) in enumerate(changes.items()):
        if p.is_symlink():raise RuntimeError('Destino simbólico preservado.')
        old=a.backup/(str(i)+'.before') if p.exists() else None
        if old:shutil.copy2(p,old)
        rollback.append({'path':str(p),'before':str(old) if old else None})
    (a.backup/'files.json').write_text(json.dumps(rollback,indent=2));applied=[]
    try:
        for p,data in changes.items():atomic(p,data);applied.append(p)
    except Exception:
        for r in reversed(rollback):
            p=Path(r['path'])
            if p not in applied:continue
            if r['before']:atomic(p,Path(r['before']).read_bytes())
            else:p.unlink()
        raise
    print('[equipment] installed; backup: '+str(a.backup)+'; existing stock and customers unchanged')
if __name__=='__main__':main()
