from pathlib import Path
import importlib.util,json
root=Path(__file__).resolve().parent.parent
spec=importlib.util.spec_from_file_location('clock',root/'scripts/server-timezone-worker.py');clock=importlib.util.module_from_spec(spec);spec.loader.exec_module(clock)
catalog=json.loads((root/'addons/shared/server_timezones.json').read_text(encoding='utf-8'))
def check(city,state,zone):assert clock.resolve({'cidade':city,'estado':state},catalog)==zone
check('Maceió','AL','America/Maceio');check('JARU','RO','America/Porto_Velho');check('Goiânia','Goiás','America/Sao_Paulo');check('Manaus','AM','America/Manaus');check('Tabatinga','Amazonas','America/Eirunepe');check('Fernando de Noronha','PE','America/Noronha');check('','AM',None);check('','XX',None)
assert len(catalog['states'])==27
print('Timezone detection passed: 27 UFs, accents, western Amazonas, Noronha and invalid/missing data.')

clock.ensure_safe_transition(-10800,"-03:00",True)
clock.ensure_safe_transition(-14400,"-03:00",False)
try:
 clock.ensure_safe_transition(-14400,"-03:00",True)
except ValueError:
 pass
else:
 raise AssertionError("An active RADIUS must block an unsafe UTC change")
print("Active RADIUS transition guard passed.")
