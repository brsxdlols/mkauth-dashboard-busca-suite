#!/usr/bin/env python3
"""Install an isolated monitor from the latest verified Toolkit release, without enabling the full map."""
import hashlib, importlib.util, json, os, pathlib, re, shutil, subprocess, sys, tarfile, time, urllib.request
REPOSITORY='brsxdlols/mkauth-toolkit'

def download(url, target):
    for attempt in range(3):
        try:
            request=urllib.request.Request(url,headers={'User-Agent':'MKAuth-Suite-Monitor','Accept':'application/vnd.github+json'})
            with urllib.request.urlopen(request,timeout=45) as response,open(target,'wb') as output:shutil.copyfileobj(response,output)
            return
        except Exception:
            if attempt==2:raise
            time.sleep(1)

def release_assets(release):
    tag=release.get('tag_name','')
    if not re.fullmatch(r'v?[0-9]+(?:\.[0-9]+){2}',tag):raise ValueError('Release tag invalida.')
    assets={item['name']:item['browser_download_url'] for item in release.get('assets',[])}
    name='mkauth-geocodificacao-'+tag.lstrip('v')+'.tar.gz'
    for key in [name,name+'.sha256']:
        url=assets.get(key,'')
        if not url.startswith('https://github.com/'+REPOSITORY+'/releases/download/'+tag+'/'):raise ValueError('Pacote ou SHA-256 ausente na release.')
    return tag,assets[name],assets[name+'.sha256']

def extract_package(archive,destination):
    destination.mkdir(parents=True,exist_ok=True)
    with tarfile.open(archive,'r:gz') as package:
        members=package.getmembers()
        if sum(item.size for item in members)>100*1024*1024:raise ValueError('Pacote excede o limite esperado.')
        for item in members:
            path=pathlib.PurePosixPath(item.name)
            if path.is_absolute() or '..' in path.parts or item.issym() or item.islnk() or not(item.isdir() or item.isfile()):raise ValueError('Caminho inseguro no pacote.')
            if os.path.commonpath([str((destination/path).resolve()),str(destination.resolve())])!=str(destination.resolve()):raise ValueError('Caminho fora do pacote.')
        package.extractall(destination)
    candidates=list(destination.glob('*/addons/mapa-clientes/maps.hhvm'))
    if len(candidates)!=1:raise ValueError('Base do monitor nao encontrada no pacote.')
    return candidates[0].parent

def build_runtime(source,target,patcher):
    required=['maps.hhvm','auth.php','access_policy.php','config.hhvm','maps_clientes_api.hhvm','traffic_api.hhvm','nas_health.hhvm','lib/routeros_api.class.php']
    for name in required:
        if not(source/name).is_file():raise ValueError('Dependencia ausente: '+name)
    shutil.copytree(source,target,ignore=shutil.ignore_patterns('central-compat','monitor','menu.js','index.hhvm'))
    text=patcher.patch((target/'maps.hhvm').read_bytes().decode('utf-8'))
    # The only renderer entry is the monitor; direct map rendering is denied.
    text=text.replace('<?php',"<?php\nif(!defined('MKA_SUITE_MONITOR_RUNTIME')){http_response_code(403);exit('Acesso somente pelo monitor.');}\n$_GET['monitor']='1';",1)
    text=text.replace('<head>','<head><link rel="icon" type="image/jpeg" href="/mkfiles/logo.jpg">',1)
    text=text.replace('src="../../','src="/admin/').replace('href="../../','href="/admin/').replace("'../../","'/admin/").replace("'../geocodificacao/","'/admin/addons/geocodificacao/")
    (target/'maps.hhvm').write_bytes(text.encode('utf-8'))

def install_runtime(source,admin,backup,patcher):
    route=admin/'addons/mapa-clientes/monitor';entry=route/'index.php'
    if entry.is_file() and 'suite-map-monitor-entry-v' not in entry.read_text(encoding='utf-8'):raise ValueError('Rota de monitor pertence a outra instalacao; preservada.')
    staged=backup/'runtime-staged';build_runtime(source,staged,patcher)
    config=route/'runtime/config.hhvm'
    if not config.is_file():config=admin/'addons/mapa-clientes/config.hhvm'
    if config.is_file():shutil.copy2(config,staged/'config.hhvm')
    for file in staged.rglob('*'):
        if file.is_file() and file.suffix in ('.php','.hhvm'):subprocess.run(['php','-l',str(file)],check=True,stdout=subprocess.DEVNULL)
    template=pathlib.Path(__file__).with_name('standalone-monitor-entry.php')
    subprocess.run(['php','-l',str(template)],check=True,stdout=subprocess.DEVNULL)
    route.mkdir(parents=True,exist_ok=True)
    current=route/'runtime'
    if current.exists():shutil.move(str(current),str(backup/'runtime-before'))
    if entry.exists():shutil.copy2(entry,backup/'monitor-entry-before.php')
    shutil.move(str(staged),str(current))
    shutil.copy2(template,entry)
    for file in current.rglob('*'):
        os.chmod(file,0o755 if file.is_dir() else 0o644)
        if file.is_file():os.utime(file,None)
    os.chmod(entry,0o644);os.utime(entry,None)

def repair_geocode_loader(admin,backup):
    files=[(admin/'addons/geocodificacao/geocodificacao.js','geocodificacao-js-before-paths.js'),(admin/'scripts/mk-auth.js','mk-auth-js-before-geocode-path.js')]
    for target,name in files:
        if not target.is_file():continue
        original=target.read_bytes()
        if name.startswith('geocodificacao-'):
            updated=re.sub(rb'([\"\'])addons/geocodificacao/',rb'\1/admin/addons/geocodificacao/',original)
            updated=re.sub(rb'([\"\'])conf_mapas\.hhvm',rb'\1/admin/conf_mapas.hhvm',updated)
        else:
            updated=re.sub(rb'(s\.src\s*=\s*[\"\'])addons/geocodificacao/',rb'\1/admin/addons/geocodificacao/',original)
            updated=re.sub(rb'(geocodificacao\.js\?v=[^\"\'&]+)(?![^\"\']*suite_paths=)',rb'\1&suite_paths=2',updated)
        if original==updated:continue
        shutil.copy2(target,backup/name)
        staged=target.with_name(target.name+'.suite-monitor-new')
        shutil.copy2(target,staged);staged.write_bytes(updated)
        if hasattr(os,'chown'):
            stat=target.stat();os.chown(staged,stat.st_uid,stat.st_gid)
        os.utime(staged,None);os.replace(staged,target)
        print('[monitor] Caminhos absolutos da geocodificacao corrigidos: '+target.name)

def main():
    admin=pathlib.Path(sys.argv[1]);backup=pathlib.Path(sys.argv[2]);backup.mkdir(parents=True,exist_ok=True);os.chmod(backup,0o700)
    metadata=backup/'release.json';download('https://api.github.com/repos/'+REPOSITORY+'/releases/latest',metadata)
    tag,url,digest_url=release_assets(json.loads(metadata.read_text(encoding='utf-8')))
    archive=backup/'toolkit.tar.gz';digest=backup/'toolkit.sha256';download(url,archive);download(digest_url,digest)
    expected=digest.read_text().split()[0];actual=hashlib.sha256(archive.read_bytes()).hexdigest()
    if not re.fullmatch('[0-9a-fA-F]{64}',expected) or expected.lower()!=actual:raise ValueError('SHA-256 invalido; instalacao cancelada.')
    source=extract_package(archive,backup/'package')
    spec=importlib.util.spec_from_file_location('map_patch',pathlib.Path(__file__).with_name('patch-map-traffic.py'));patcher=importlib.util.module_from_spec(spec);spec.loader.exec_module(patcher)
    install_runtime(source,admin,backup,patcher)
    patcher.migrate_monitor_links(backup)
    repair_geocode_loader(admin,backup)
    (backup/'installed.json').write_text(json.dumps({'repository':REPOSITORY,'release':tag,'sha256':actual,'mode':'monitor-only'}),encoding='utf-8')
    print('[monitor] Toolkit '+tag+' verificado por SHA-256. Monitor instalado sem liberar o Mapa completo.')
if __name__=='__main__':
    try:main()
    except Exception as error:print('[aviso] Monitor nao atualizado: '+str(error),file=sys.stderr);sys.exit(1)
