#!/usr/bin/env python3
"""Isolated filesystem tests. No database access or worker execution."""
import importlib.util
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

spec = importlib.util.spec_from_file_location('ensure_reconcile', Path(__file__).with_name('ensure-radius-reconcile.py'))
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class BootstrapTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        root = Path(self.tmp.name)
        self.scripts, self.crons, self.state, self.admin = [root / n for n in ('scripts', 'cron', 'state', 'admin')]
        self.backup = root / 'backup'
        self.counter = 0

    def ensure(self):
        self.counter += 1
        module.ensure(self.scripts, self.backup / str(self.counter), self.crons, self.state, self.admin)

    def test_fresh_and_repeat_preserve_custom_cron_and_worker(self):
        self.ensure()
        worker = self.scripts / module.WORKER
        before = worker.read_bytes()
        cron = self.crons / 'mkauth-radius-ppp-reconcile'
        custom = cron.read_text().replace('*/2', '*/3').replace('/usr/bin/php', 'API_PORT=9876 /usr/bin/php')
        cron.write_text(custom)
        self.ensure()
        self.assertEqual(worker.read_bytes(), before)
        self.assertEqual(cron.read_text(), custom)

    def test_missing_cron_and_guard_repaired(self):
        self.ensure()
        (self.crons / 'mkauth-radius-ppp-reconcile').unlink()
        (self.scripts / module.GUARD).unlink()
        self.ensure()
        self.assertTrue((self.scripts / module.GUARD).is_file())
        self.assertTrue(module.active_cron((self.crons / 'mkauth-radius-ppp-reconcile').read_text(), self.scripts / module.WORKER))

    def test_disabled_cron_not_overwritten(self):
        self.ensure()
        cron = self.crons / 'mkauth-radius-ppp-reconcile'
        cron.write_text('# disabled intentionally\n')
        with self.assertRaises(RuntimeError):
            self.ensure()
        self.assertEqual(cron.read_text(), '# disabled intentionally\n')

    def test_unknown_worker_preserved_and_failure_propagated(self):
        self.scripts.mkdir()
        worker = self.scripts / module.WORKER
        worker.write_text('<?php // custom implementation\n')
        with self.assertRaises(module.subprocess.CalledProcessError):
            self.ensure()
        self.assertEqual(worker.read_text(), '<?php // custom implementation\n')
        self.assertFalse((self.crons / 'mkauth-radius-ppp-reconcile').exists())

    def test_legacy_cron_refused(self):
        self.crons.mkdir()
        (self.crons / 'sistel-radius-ppp-reconcile').write_text('legacy')
        with self.assertRaises(RuntimeError):
            self.ensure()
        self.assertFalse((self.scripts / module.WORKER).exists())

    def test_lint_failure_does_not_install_worker_or_cron(self):
        with patch.object(module.subprocess, 'run', side_effect=module.subprocess.CalledProcessError(1, 'php')):
            with self.assertRaises(module.subprocess.CalledProcessError):
                self.ensure()
        self.assertFalse((self.scripts / module.WORKER).exists())
        self.assertFalse((self.crons / 'mkauth-radius-ppp-reconcile').exists())

    def test_cron_requires_php_and_apply(self):
        worker = self.scripts / module.WORKER
        self.assertFalse(module.active_cron('* * * * * root echo "' + str(worker) + '" --apply', worker))
        self.assertFalse(module.active_cron('* * * * * root php "' + str(worker) + '"', worker))
        self.assertFalse(module.active_cron('# */2 * * * * root php "' + str(worker) + '" --apply', worker))


if __name__ == '__main__':
    unittest.main()
