import importlib.util
import json
import os
from pathlib import Path
import stat
import tempfile
import unittest
from unittest import mock

spec=importlib.util.spec_from_file_location('unblock_cron',Path(__file__).with_name('configure-unblock-cron.py'));module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)

NATIVE=b'## MK-AUTH\n# CONTROLE LIBERAR CLIENTE\n*/10 *\t* * *\troot\t/opt/mk-auth/scripts/liberar.php\n\n*/5 * * * * root /opt/mk-auth/scripts/desbloq_obs.php\n'


class UnblockCron(unittest.TestCase):
    def test_only_known_jobs_preserving_other_fields_comments_and_bytes(self):
        data=NATIVE+b'*/15 * * * * root /opt/mk-auth/scripts/other.php\n# */20 * * * * root /opt/mk-auth/scripts/liberar.php\nPATH=/usr/bin:/bin\n'
        output,changes=module.patch(data)
        self.assertEqual(output,data.replace(b'*/10 *\t',b'*/1 *\t').replace(b'*/5 * *',b'*/1 * *'))
        self.assertEqual(len(changes),2)

    def test_interpreter_crlf_and_inline_comment(self):
        data='*/10 * * * * root /usr/bin/php -q -f /opt/mk-auth/scripts/liberar.php # liberaÃ§Ã£o\r\n'.encode()
        output,changes=module.patch(data);self.assertEqual(output,b'*/1'+data[len(b'*/10'):]);self.assertEqual(len(changes),1)

    def test_existing_every_minute_needs_no_change(self):
        for minute in (b'*',b'*/1'):
            data=minute+b' * * * * root /opt/mk-auth/scripts/liberar.php\n'
            output,changes=module.patch(data);self.assertEqual(output,data);self.assertFalse(changes)

    def test_native_desbloqueio_variant_changes_only_minutes(self):
        data=NATIVE.replace(b'liberar.php',b'desbloqueio.php')
        output,changes=module.patch(data)
        self.assertEqual(output,data.replace(b'*/10',b'*/1').replace(b'*/5',b'*/1'))
        self.assertEqual(len(changes),2)
        again,changes=module.patch(output)
        self.assertEqual(again,output)
        self.assertFalse(changes)
        with self.assertRaises(RuntimeError):
            module.patch(data.replace(b'/opt/mk-auth/scripts/desbloqueio.php',b'/custom/desbloqueio.php'))

    def test_unknown_custom_or_partial_jobs_refused(self):
        fixtures=[NATIVE.replace(b'*/10',b'0,10'),NATIVE.replace(b'root',b'apache'),b'*/10 * * * * root /unknown/liberar.php\n',b'*/10 * * * * root echo /opt/mk-auth/scripts/liberar.php\n',b'*/10 * * * * root /opt/mk-auth/scripts/desbloq_obs.php\n']
        for data in fixtures:
            with self.subTest(data=data),self.assertRaises(RuntimeError):module.patch(data)

    @unittest.skipUnless(os.geteuid()==0,'Metadata preservation fixture requires root')
    def test_apply_backup_restart_repeat_and_failure_rollback(self):
        with tempfile.TemporaryDirectory(prefix='unblock-cron-fixture-')as directory:
            root=Path(directory);cron=root/'desbloqueio';cron.write_bytes(NATIVE);cron.chmod(0o640)
            with mock.patch.object(module,'restart_cron')as restart:
                self.assertEqual(module.apply(root/'backup',cron),'changed');restart.assert_called_once()
                self.assertEqual(module.apply(root/'repeat',cron),'unchanged');restart.assert_called_once()
            self.assertEqual(stat.S_IMODE(cron.stat().st_mode),0o640)
            self.assertEqual((root/'backup/desbloqueio.before').read_bytes(),NATIVE)
            self.assertEqual(stat.S_IMODE((root/'backup/desbloqueio.before').stat().st_mode),0o600)
            self.assertTrue(json.loads((root/'backup/cron.json').read_text())['completed'])
            cron.write_bytes(NATIVE)
            with mock.patch.object(module,'restart_cron',side_effect=RuntimeError('fixture service failure')),self.assertRaises(RuntimeError):module.apply(root/'failure',cron)
            self.assertEqual(cron.read_bytes(),NATIVE)
            self.assertFalse(json.loads((root/'failure/cron.json').read_text())['completed'])

    def test_missing_file_is_not_created(self):
        with tempfile.TemporaryDirectory()as directory,mock.patch.object(module,'restart_cron')as restart:
            root=Path(directory);self.assertEqual(module.apply(root/'backup',root/'missing'),'absent')
            self.assertFalse((root/'missing').exists());restart.assert_not_called()

    def test_symlink_preserved(self):
        with tempfile.TemporaryDirectory()as directory:
            root=Path(directory);target=root/'native';target.write_bytes(NATIVE);link=root/'desbloqueio';link.symlink_to(target)
            with self.assertRaises(RuntimeError):module.apply(root/'backup',link)
            self.assertEqual(target.read_bytes(),NATIVE)


if __name__=='__main__':unittest.main(verbosity=2)
