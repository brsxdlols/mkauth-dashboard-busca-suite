import importlib.util
from pathlib import Path
import unittest

spec=importlib.util.spec_from_file_location('map_patch',Path(__file__).with_name('patch-map-traffic.py'))
module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)

class MonitorPatchTests(unittest.TestCase):
    def test_existing_map_preserved(self):
        source='provider configuration stays here\n/* suite-map-traffic-v2 */\n</head>\nfunction setupMonitorSearch(){}\nfunction setMonitorMode(on){}\nfunction renderTraffic(box,data){}\n  function pollTraffic(login){}\n  function startTraffic(login){}\n  function trafficDots(box,values,max,kind,g){}\n'
        result=module.patch(source)
        self.assertEqual(module.patch(result),result)
        self.assertTrue(result.startswith('provider configuration stays here'))
        self.assertEqual(result.count('suite-map-monitor-v3'),1)
        self.assertIn('mkaMapMonitor.stop()',result)
        self.assertIn('mkaMapMonitor.sync(box)',result)

    def test_unrecognized_map_rejected(self):
        with self.assertRaises(ValueError):module.patch('different monitor implementation')

if __name__=='__main__':unittest.main()
