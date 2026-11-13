#!/usr/bin/env python3
"""Destructive housekeeping regression tests confined to TemporaryDirectory."""
import importlib.util
import json
import os
from pathlib import Path
import sqlite3
import tempfile
import time
import unittest


def module(name):
    spec = importlib.util.spec_from_file_location(name.replace('-', '_'), Path(__file__).with_name(name+'.py'))
    result = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(result)
    return result


worker = module('cleanup-worker')
installer = module('install-cleanup')


class Cleanup(unittest.TestCase):
    def fixture(self, root):
        directory = root/'opt/mk-auth/bckp'
        directory.mkdir(parents=True)
        database = root/'opt/mk-auth/dados/backup.db'
        database.parent.mkdir()
        db = sqlite3.connect(str(database))
        db.execute('CREATE TABLE backup(uuid TEXT,arquivo TEXT,tipo TEXT,data TEXT,descricao TEXT)')
        for i in range(18):
            name = 'auto'+str(i)
            db.execute('INSERT INTO backup VALUES (?,?,?,?,?)', (name, name, 'maz', '2020-01-%02d 01:00:00' % (i+1), 'backup automatico'))
            path = directory/(name+'.maz')
            path.write_text('archive')
            os.utime(str(path), (time.time()-20*86400,)*2)
        db.execute('INSERT INTO backup VALUES (?,?,?,?,?)', ('manual', 'manual', 'maz', '2019-01-01', 'backup enviado'))
        (directory/'manual.maz').write_text('manual')
        db.commit()
        db.close()
        return directory, database

    def test_dry_run_then_retention_preserves_manual_and_newest(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            directory, database = self.fixture(root)
            report = {}
            worker.backup_retention(root, 15, 7, False, report)
            self.assertEqual(report['backup_candidates'], 3)
            self.assertEqual(len(list(directory.glob('*.maz'))), 19)
            before = database.stat().st_mode & 0o777
            worker.backup_retention(root, 15, 7, True, report)
            self.assertEqual(report['backup_entries_removed'], 3)
            self.assertTrue((directory/'manual.maz').exists())
            self.assertTrue((directory/'auto17.maz').exists())
            self.assertFalse((directory/'auto0.maz').exists())
            self.assertEqual(database.stat().st_mode & 0o777, before)
            db = sqlite3.connect(str(database))
            self.assertEqual(db.execute('SELECT COUNT(*) FROM backup').fetchone()[0], 16)
            db.close()

    def test_transaction_failure_restores_archives(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            directory, database = self.fixture(root)
            db = sqlite3.connect(str(database))
            db.execute("CREATE TRIGGER fail BEFORE DELETE ON backup BEGIN SELECT RAISE(ABORT,'fixture'); END")
            db.close()
            with self.assertRaises(sqlite3.IntegrityError):
                worker.backup_retention(root, 15, 7, True, {})
            self.assertEqual(len(list(directory.glob('*.maz'))), 19)
            self.assertEqual(len(list(directory.glob('.suite-cleanup-*'))), 0)

    def test_paths_manual_orphans_and_recent_files_are_preserved(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            directory, database = self.fixture(root)
            db = sqlite3.connect(str(database))
            db.execute("UPDATE backup SET arquivo='../../outside' WHERE uuid='auto0'")
            db.execute("INSERT INTO backup VALUES ('missing','missing','maz','2010-01-01','manual')")
            db.commit()
            db.close()
            os.utime(str(directory/'auto1.maz'), None)
            (directory/'auto2.maz').unlink()
            outside = root/'outside'
            outside.write_text('sentinel')
            (directory/'auto2.maz').symlink_to(outside)
            worker.backup_retention(root, 15, 7, True, {})
            self.assertEqual(outside.read_text(), 'sentinel')
            self.assertTrue((directory/'auto1.maz').exists())
            db = sqlite3.connect(str(database))
            self.assertEqual(db.execute("SELECT COUNT(*) FROM backup WHERE uuid='missing'").fetchone()[0], 1)
            db.close()

    def test_temp_scope_excludes_php_sessions_and_other_apps(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            for name in ['var/tmp/sess_123', 'tmp/other-app', 'tmp/mkauth-old', 'opt/mk-auth/tmp_sql/old']:
                p = root/name
                p.parent.mkdir(parents=True, exist_ok=True)
                p.write_text('fixture')
                os.utime(str(p), (time.time()-20*86400,)*2)
            report = {}
            worker.temporary_files(root, 7, True, report)
            self.assertEqual(report['temporary_removed'], 2)
            self.assertTrue((root/'var/tmp/sess_123').exists())
            self.assertTrue((root/'tmp/other-app').exists())

    def test_installer_backup_and_rollback_without_initial_cleanup(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)/'root'
            root.mkdir()
            directory, database = self.fixture(root)
            cron = root/'etc/cron.d/mkauth-limpeza'
            cron.parent.mkdir(parents=True)
            cron.write_text('old cron')
            config = root/'etc/mkauth-suite-cleanup.json'
            config.write_text('{"keep_backups":40,"temporary_days":10,"docker":false}')
            backup = Path(tmp)/'backup'
            installer.install(root, backup)
            self.assertIn('30 7', cron.read_text())
            self.assertEqual(json.loads(config.read_text())['keep_backups'], 40)
            self.assertEqual(len(list(directory.glob('*.maz'))), 19)
            installer.restore(root, backup)
            self.assertEqual(cron.read_text(), 'old cron')
            self.assertFalse((root/'usr/local/lib/mkauth-suite/cleanup.py').exists())

    def test_open_temporary_file_is_not_deleted(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            path = root/'tmp/mkauth-open'
            path.parent.mkdir()
            path.write_text('open')
            with path.open() as stream:
                os.utime(str(path), (time.time()-20*86400,)*2)
                worker.temporary_files(root, 7, True, {})
                self.assertTrue(path.exists())


if __name__ == '__main__':
    unittest.main()
