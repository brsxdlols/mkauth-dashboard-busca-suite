#!/usr/bin/env python3
import copy
import importlib.util
import pathlib
import json
import tempfile
import sys
import io
import contextlib
import unittest
from unittest.mock import patch

spec = importlib.util.spec_from_file_location('sync', pathlib.Path(__file__).with_name('sincronizar-queues.py'))
sync = importlib.util.module_from_spec(spec)
spec.loader.exec_module(sync)


def plan(name='100MB_FIBRA', up='105000', down='105000'):
    return dict(nome=name, velup=up, veldown=down, maxup='0', maxdown='0',
                desaup='0', desadown='0', garup='0', gardown='0',
                tempoup=None, tempodown=None, prioridade='8')


def fixture():
    return dict(nas=[], primary=[dict(login='cliente@exemplo', tipo='pppoe',
        plano='100MB_FIBRA', cli_ativado='s', bloqueado='nao', statusdown='off', statusturbo='off')],
        additional=[], plans=[plan()], groups=[dict(username='cliente@exemplo',
        groupname='100MB_FIBRA', priority='1', login='cliente@exemplo')], replies=[],
        group_replies=[dict(id='1', groupname='100MB_FIBRA', attribute='Mikrotik-Rate-Limit',
                           op='=', value='105000k/105000k 0/0 0/0 0/0 8 0/0')], group_checks=[])


SESSION = dict(name='cliente@exemplo', flags='R', service='pppoe', address='192.0.2.10', **{'session-id': '0x01234567'})
QUEUE = dict(name='<pppoe-cliente@exemplo>', target='<pppoe-cliente@exemplo>', flags='D',
             **{'max-limit': '105M/105M', 'burst-limit': '0/0', 'burst-threshold': '0/0',
                'burst-time': '0s/0s', 'priority': '8/8', 'limit-at': '0/0'})


class Decisions(unittest.TestCase):
    def propose(self, data=None, session=None, queues=None):
        return sync.propose(sync.index(data or fixture()), session or SESSION,
                            [QUEUE] if queues is None else queues)

    def test_healthy_is_noop_despite_equivalent_units(self):
        r = self.propose()
        self.assertFalse(any(r[k] for k in ('change_groups', 'change_rate', 'change_queue')))

    def test_missing_queue_detected(self):
        self.assertTrue(self.propose(queues=[])['change_queue'])

    def test_changed_speed_in_plan_changes_attribute_and_session(self):
        s = fixture(); s['plans'][0]['veldown'] = '210000'
        r = self.propose(s)
        self.assertTrue(r['change_rate']); self.assertTrue(r['change_queue'])
        self.assertTrue(r['rate'].startswith('105000k/210000k'))

    def test_changed_plan_replaces_stale_valid_group(self):
        s = fixture();s['plans'].append(plan('200MB_FIBRA', '110000', '210000'))
        s['primary'][0]['plano'] = '200MB_FIBRA'
        r = self.propose(s)
        self.assertTrue(r['change_groups']);self.assertEqual(r['plan'], '200MB_FIBRA')

    def test_legacy_group_and_duplicates_reconciled(self):
        s = fixture();s['groups'][0]['groupname'] = '70MB_FIBRA';s['groups'] *= 2
        self.assertTrue(self.propose(s)['change_groups'])

    def test_local_session_never_changed(self):
        s = dict(SESSION, flags='')
        with self.assertRaises(sync.Refused):self.propose(session=s)

    def test_block_turbo_down_inactive_never_changed(self):
        for k, v in [('bloqueado','sim'),('statusturbo','on'),('statusdown','on'),('cli_ativado','n'),('night','sim'),('status_corte','bloq')]:
            s = fixture();s['primary'][0][k] = v
            with self.subTest(k=k), self.assertRaises(sync.Refused):self.propose(s)

    def test_static_queue_not_overwritten(self):
        with self.assertRaises(sync.Refused):self.propose(queues=[dict(QUEUE, flags='')])

    def test_custom_checks_not_discarded(self):
        s=fixture();s['group_checks']=[dict(groupname='100MB_FIBRA',attribute='NAS-IP-Address')]
        with self.assertRaises(sync.Refused):self.propose(s)

    def test_custom_group_reply_not_discarded(self):
        s=fixture();s['group_replies'].append(dict(groupname='100MB_FIBRA',attribute='Filter-Id',op='=',value='special'))
        with self.assertRaises(sync.Refused):self.propose(s)

    def test_user_rate_override_preserved(self):
        s=fixture();s['replies']=[dict(username='cliente@exemplo',attribute='Mikrotik-Rate-Limit')]
        with self.assertRaises(sync.Refused):self.propose(s)

    def test_restricted_pool_preserved(self):
        s=fixture();s['replies']=[dict(username='cliente@exemplo',attribute='Framed-Pool',value='pgcorte')]
        with self.assertRaises(sync.Refused):self.propose(s)

    def test_unknown_custom_membership_preserved(self):
        s=fixture();s['groups'][0]['groupname']='CUSTOM_VIP'
        with self.assertRaises(sync.Refused):self.propose(s)

    def test_additional_legacy_match_is_unique_and_parent_protected(self):
        s=fixture();s['primary'][0]['login']='titular@exemplo'
        s['additional']=[dict(username='cliente@exemplo',login='titular@exemplo',tipo='pppoe',plano='70MB_FIBER',bloqueado='nao')]
        s['groups'][0]['groupname']='70MB_FIBER';s['plans'].append(plan('PLANO_PARA_ADICIONAIS70MB','75000','75000'))
        self.assertTrue(self.propose(s)['change_additional'])
        s['primary'][0]['bloqueado']='sim'
        with self.assertRaises(sync.Refused):self.propose(s)

    def test_ambiguous_legacy_match_refused(self):
        s=fixture();s['primary'][0]['login']='titular@exemplo'
        s['additional']=[dict(username='cliente@exemplo',login='titular@exemplo',tipo='pppoe',plano='70MB_FIBRA',bloqueado='nao')]
        s['plans'] += [plan('PLANO_PARA_ADICIONAIS70MB'),plan('OUTRO_ADICIONAIS70MB')]
        with self.assertRaises(sync.Refused):self.propose(s)

    def test_burst_and_guarantee_are_compared(self):
        q=dict(QUEUE);q['limit-at']='1M/1M'
        self.assertTrue(self.propose(queues=[q])['change_queue'])

    def test_inconsistent_plan_not_pushed(self):
        s=fixture();s['plans'][0]['garup']='500000'
        with self.assertRaises(sync.Refused):self.propose(s)

    def test_router_parser_preserves_radius_local_and_quotes(self):
        text='''Flags: R - radius
 0 R name="cliente@exemplo" service=pppoe address=192.0.2.10
     uptime=1h session-id=0x01234567

 1   ;;; local comment
     name="local@exemplo" service=pppoe address=192.0.2.11 session-id=0x01234568
'''
        r=sync.parse_records(text)
        self.assertEqual(len(r),2);self.assertEqual(r[0]['flags'],'R');self.assertEqual(r[1]['flags'],'')

    def test_ack_alone_not_queue_verification(self):
        q=dict(QUEUE);q['max-limit']='100M/100M'
        self.assertFalse(sync.queue_matches(q, sync.rate_from_plan(plan())))

    def test_accounting_id_and_ip_must_match(self):
        router=type('Router',(),dict(host='192.0.2.1'))()
        with patch.object(sync,'db',return_value=[dict(acctsessionid='wrong',framedipaddress='192.0.2.10')]):
            with self.assertRaises(sync.Refused):sync.accounting(router,SESSION)

    def test_discovery_reads_two_nas_and_excludes_local_without_apply(self):
        data=fixture();data['nas']=[dict(nasname='192.0.2.1',sshativo='sim'),dict(nasname='192.0.2.2',sshativo='sim')]
        class Router:
            def __init__(self,nas,*args):pass
            def read(self):return [dict(SESSION),dict(SESSION,name='local@exemplo',flags='')],[dict(QUEUE)]
        with tempfile.TemporaryDirectory() as temp:
            report=pathlib.Path(temp)/'report.json'
            with contextlib.redirect_stdout(io.StringIO()),patch.object(sync,'db',return_value=data),patch.object(sync,'Router',Router),patch.object(sync.shutil,'which',return_value='/fixture'),patch.object(sys,'argv',['sync','--backups='+temp,'--report='+str(report)]):
                self.assertEqual(sync.main(),0)
            result=json.loads(report.read_text())
            self.assertEqual(len(result['routers']),2);self.assertFalse(result['changes'])
            self.assertEqual(len(result['skipped']),2)

    def test_blocked_session_is_skip_even_in_apply_mode(self):
        data=fixture();data['primary'][0]['bloqueado']='sim';data['nas']=[dict(nasname='192.0.2.1',sshativo='sim')]
        class Router:
            def __init__(self,nas,*args):pass
            def read(self):return [dict(SESSION)],[dict(QUEUE)]
        with tempfile.TemporaryDirectory() as temp:
            report=pathlib.Path(temp)/'report.json'
            with contextlib.redirect_stdout(io.StringIO()),patch.object(sync,'db',return_value=data),patch.object(sync,'Router',Router),patch.object(sync.shutil,'which',return_value='/fixture'),patch.object(sync,'perform') as apply,patch.object(sys,'argv',['sync','--apply','--backups='+temp,'--report='+str(report)]):
                self.assertEqual(sync.main(),0);apply.assert_not_called()
            self.assertFalse(json.loads(report.read_text())['errors'])

    def test_session_rollback_to_unlimited_and_changed_session_refused(self):
        data=fixture();data['nas']=[dict(nasname='192.0.2.1')]
        q=dict(QUEUE);s=dict(SESSION)
        class Router:
            def __init__(self,nas,*args):self.host='192.0.2.1'
            def read(self):return [s],[q]
            def incoming(self):pass
        with tempfile.TemporaryDirectory() as temp:
            p=pathlib.Path(temp)/'before.json'
            p.write_text(json.dumps(dict(nas='192.0.2.1',change=dict(user=SESSION['name'],change_queue=True,session=SESSION,rate=sync.rate_from_plan(plan()),previous_queue=None))))
            args=type('Args',(),dict(backups=temp))()
            def coa(router,session,rate,directory):
                self.assertEqual(rate,'0/0');q['max-limit']='0/0'
            with patch.object(sync,'db',return_value=data),patch.object(sync,'Router',Router),patch.object(sync,'send_coa',coa):
                self.assertTrue(sync.rollback_session(str(p),args,{})['restored'])
                s['session-id']='0x99999999'
                with self.assertRaises(sync.Refused):sync.rollback_session(str(p),args,{})


if __name__=='__main__':unittest.main()
