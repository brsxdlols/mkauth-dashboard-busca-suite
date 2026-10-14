import importlib.util,io,json,pathlib,tarfile,tempfile,unittest
from unittest import mock
root=pathlib.Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location('installer',root/'scripts/install-map-monitor.py');m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)
class MonitorInstallTests(unittest.TestCase):
 def setUp(self):self.tmp=tempfile.TemporaryDirectory(dir=root.parent/'work');self.path=pathlib.Path(self.tmp.name)
 def tearDown(self):self.tmp.cleanup()
 def test_release_pair(self):
  base='https://github.com/brsxdlols/mkauth-toolkit/releases/download/v2.14.29/';name='mkauth-geocodificacao-2.14.29.tar.gz'
  release={'tag_name':'v2.14.29','assets':[{'name':n,'browser_download_url':base+n} for n in [name,name+'.sha256']]}
  self.assertEqual(m.release_assets(release)[0],'v2.14.29');release['assets'][0]['browser_download_url']='https://other.test/x'
  with self.assertRaises(ValueError):m.release_assets(release)
 def test_reject_archive_escape(self):
  archive=self.path/'bad.tgz'
  with tarfile.open(archive,'w:gz') as t:
   info=tarfile.TarInfo('../outside.php');info.size=1;t.addfile(info,io.BytesIO(b'x'))
  with self.assertRaises(ValueError):m.extract_package(archive,self.path/'extract')
 def test_reject_archive_link(self):
  archive=self.path/'link.tgz'
  with tarfile.open(archive,'w:gz') as t:
   info=tarfile.TarInfo('package/link');info.type=tarfile.SYMTYPE;info.linkname='/etc';t.addfile(info)
  with self.assertRaises(ValueError):m.extract_package(archive,self.path/'extract')
 def test_monitor_only_preserves_existing_map_and_config(self):
  source=self.path/'source';source.mkdir()
  for name in ['maps.hhvm','auth.php','access_policy.php','config.hhvm','maps_clientes_api.hhvm','traffic_api.hhvm','nas_health.hhvm','lib/routeros_api.class.php','index.hhvm','menu.js']:
   file=source/name;file.parent.mkdir(exist_ok=True);file.write_text('<?php\n// base')
  admin=self.path/'admin';addon=admin/'addons/mapa-clientes';addon.mkdir(parents=True);(addon/'maps.hhvm').write_text('existing full map');(addon/'config.hhvm').write_text('local database config')
  backup=self.path/'backup';backup.mkdir()
  patcher=mock.Mock();patcher.patch.side_effect=lambda s:s
  with mock.patch.object(m.subprocess,'run'):m.install_runtime(source,admin,backup,patcher)
  self.assertFalse((addon/'index.hhvm').exists());self.assertFalse((addon/'index.php').exists());self.assertEqual((addon/'maps.hhvm').read_text(),'existing full map')
  self.assertEqual((addon/'monitor/runtime/config.hhvm').read_text(),'local database config')
  self.assertIn('MKA_SUITE_MONITOR_RUNTIME',(addon/'monitor/runtime/maps.hhvm').read_text());self.assertIn('suite-map-monitor-entry-v2',(addon/'monitor/index.php').read_text())
  self.assertFalse((addon/'monitor/runtime/index.hhvm').exists())
  with mock.patch.object(m.subprocess,'run'):m.install_runtime(source,admin,self.path/'second-backup',patcher)
if __name__=='__main__':unittest.main()
