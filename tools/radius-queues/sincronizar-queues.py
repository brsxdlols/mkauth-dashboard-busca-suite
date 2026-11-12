#!/usr/bin/env python3
"""Discover MK-AUTH NAS, compare RADIUS PPP sessions and queues, repair by CoA.

Default is audit only. Local sessions and special/custom policies are excluded.
No router configuration commands, service restarts or Disconnect-Requests.
"""
import argparse
import base64
import collections
import contextlib
import fcntl
import hashlib
import ipaddress
import json
import os
import pathlib
import re
import shutil
import subprocess
import sys
import tempfile
import time
import uuid


class Refused(Exception):
    pass


def require(test, message):
    if not test:
        raise Refused(message)


def run(argv, data=None, timeout=45, env=None):
    try:
        r = subprocess.run(argv, input=data, stdout=subprocess.PIPE,
                           stderr=subprocess.PIPE, timeout=timeout, env=env)
    except (OSError, subprocess.TimeoutExpired):
        raise Refused('Command unavailable or timed out: ' + pathlib.Path(argv[0]).name)
    require(r.returncode == 0, 'Command failed: ' + pathlib.Path(argv[0]).name)
    return r.stdout.decode('utf-8', errors='strict')


def db(action='snapshot', **kwargs):
    req = dict(action=action, **kwargs)
    out = run([os.environ.get('MKA_QUEUES_PHP', 'php'),
               str(pathlib.Path(__file__).with_name('queues-db.php'))],
              json.dumps(req).encode(), timeout=90)
    return json.loads(out)


def secret_file(path):
    p = pathlib.Path(path)
    require(p.is_file() and not p.stat().st_mode & 0o077,
            'Credential file must exist with mode 0600')
    return json.loads(p.read_text())


def number(value, duration=False):
    text = str(value).strip()
    if duration:
        m = re.fullmatch(r'(\d+(?:\.\d+)?)(ms|s)?', text)
        require(m is not None, 'Unsupported duration')
        return float(m[1]) * (0.001 if m[2] == 'ms' else 1)
    m = re.fullmatch(r'(\d+(?:\.\d+)?)([kKmMgG]?)', text)
    require(m is not None, 'Unsupported rate')
    return int(float(m[1]) * {'': 1, 'k': 1000, 'm': 1000000, 'g': 1000000000}[m[2].lower()])


def pair(value, duration=False):
    parts = str(value).split('/')
    require(len(parts) in (1, 2), 'Invalid rate pair')
    if len(parts) == 1:
        parts *= 2
    return tuple(number(x, duration) for x in parts)


def rate_from_plan(plan):
    """Standard MK-AUTH schema, kbit/s. No guessed numeric speeds."""
    fields = ('velup', 'veldown', 'maxup', 'maxdown', 'desaup', 'desadown',
              'garup', 'gardown')
    values = {}
    for key in fields:
        v = plan.get(key)
        require(v is not None and re.fullmatch(r'\d+', str(v)) is not None,
                'Nonstandard or incomplete plan rate fields')
        values[key] = int(v)
    require(values['velup'] > 0 and values['veldown'] > 0, 'Plan speed is zero')
    priority = str(plan.get('prioridade', ''))
    require(priority in list('12345678'), 'Invalid plan priority')
    times = [str(plan.get(k) or '0') for k in ('tempoup', 'tempodown')]
    require(all(re.fullmatch(r'\d+', x) for x in times), 'Invalid plan burst time')
    def p(up, down):
        return '/'.join(str(values[k]) + ('k' if values[k] else '') for k in (up, down))
    # Schema stores burst limits/thresholds even when the burst flag is unused.
    rate = '{} {} {} {}/{} {} {}'.format(
        p('velup', 'veldown'), p('maxup', 'maxdown'), p('desaup', 'desadown'),
        times[0], times[1], priority, p('garup', 'gardown'))
    desired = rate_values(rate)
    require(all(a <= b for a, b in zip(desired['limit-at'], desired['max-limit'])),
            'Minimum rate exceeds maximum')
    # Do not silently repair invalid bursts such as threshold above burst limit.
    for burst, threshold, seconds in zip(desired['burst-limit'], desired['burst-threshold'], desired['burst-time']):
        require(burst == 0 or (threshold <= burst and seconds > 0), 'Inconsistent plan burst')
    return rate


def rate_values(rate):
    fields = str(rate).split()
    require(len(fields) == 6, 'Expected complete six-field rate-limit')
    require(fields[4] in list('12345678'), 'Unsupported priority')
    return dict(zip(('max-limit', 'burst-limit', 'burst-threshold', 'burst-time',
                     'priority', 'limit-at'),
                    (pair(fields[0]), pair(fields[1]), pair(fields[2]),
                     pair(fields[3], True), (int(fields[4]),) * 2, pair(fields[5]))))


def queue_matches(queue, rate):
    expected = rate_values(rate)
    for key, value in expected.items():
        if key not in queue:
            return False
        try:
            actual = pair(queue[key], key == 'burst-time')
        except Refused:
            return False
        if actual != value:
            return False
    return True


def parse_records(text):
    """Parse RouterOS detail output, including wraps and quoted names."""
    text = re.sub(r'\x1b\[[0-?]*[ -/]*[@-~]', '', text).replace('\r', '')
    require(not re.search(r'(?im)^(?:failure:|expected |bad command|syntax error)', text),
            'RouterOS rejected read command')
    blocks = re.split(r'(?m)(?=^\s*\d+\s)', text)
    result = []
    for block in blocks:
        m = re.match(r'^\s*\d+\s+([A-Z *]*)(?=\s*\S)', block)
        if not m:
            continue
        # Remove comments before reading property names.
        block = re.sub(r';;;[^\n]*', '', block)
        item = {'flags': m[1].strip().replace(' ', '')}
        for prop in re.finditer(r'([\w.-]+)=("(?:\\.|[^"\\])*"|[^\s]+)', block):
            value = prop[2]
            if value.startswith('"'):
                value = re.sub(r'\\(["\\])', r'\1', value[1:-1])
            item[prop[1]] = value
        if 'name' in item:
            result.append(item)
    return result


def index(snapshot):
    out = dict(snapshot)
    for name, key in [('primary', 'login'), ('additional', 'username'), ('plans', 'nome')]:
        mapping = collections.defaultdict(list)
        for row in snapshot[name]:
            mapping[row[key]].append(row)
        out[name + '_by'] = mapping
    return out


def one(items, description):
    require(len(items) == 1, 'Missing or ambiguous ' + description)
    return items[0]


def normal(account):
    return (account.get('tipo') == 'pppoe' and account.get('cli_ativado') == 's'
            and account.get('bloqueado') == 'nao' and account.get('statusdown') == 'off'
            and account.get('statusturbo') == 'off' and account.get('night', 'nao') == 'nao'
            and account.get('status_corte', 'full') == 'full')


def propose(snapshot, session, queues):
    u = session['name']
    require('R' in session['flags'] and session.get('service') == 'pppoe', 'Local or non-PPPoE session')
    primary = snapshot['primary_by'].get(u, [])
    additional = snapshot['additional_by'].get(u, [])
    require(not (primary and additional), 'Overlapping primary/additional username')
    change_additional = False
    if primary:
        c = one(primary, 'primary account')
        require(normal(c), 'Blocked/inactive/turbo/reduction or nonstandard account')
        desired = c['plano']
    else:
        c = one(additional, 'additional account')
        parent = one(snapshot['primary_by'].get(c['login'], []), 'parent account')
        require(c['tipo'] == 'pppoe' and c['bloqueado'] == 'nao' and c.get('night', 'nao') == 'nao' and normal(parent),
                'Additional or parent has special state')
        desired = c['plano']
        if desired not in snapshot['plans_by']:
            m = re.fullmatch(r'(\d+)MB_FIB(?:RA|ER)', desired, re.I)
            matches = [n for n in snapshot['plans_by'] if m and re.search(
                r'ADICIONAIS_?' + re.escape(m[1]) + r'MB$', n, re.I)]
            desired = one(matches, 'legacy additional plan match')
            change_additional = True
    plan = one(snapshot['plans_by'].get(desired, []), 'current plan')
    rate = rate_from_plan(plan)
    groups = [r for r in snapshot['groups'] if r['username'] == u]
    names = {r['groupname'] for r in groups} | {desired}
    for name in names:
        require(name in snapshot['plans_by'] or re.fullmatch(r'\d+MB_FIB(?:RA|ER)', name, re.I),
                'Custom group membership')
    checks = [r for r in snapshot['group_checks'] if r['groupname'] in names]
    require(not checks, 'Custom group checks')
    replies = [r for r in snapshot['group_replies'] if r['groupname'] in names]
    require(all(r['attribute'] == 'Mikrotik-Rate-Limit' and r['op'] in ('=', ':=') for r in replies),
            'Custom group replies')
    override = [r for r in snapshot['replies'] if r['username'] == u and r['attribute'] in
                ('Mikrotik-Rate-Limit', 'Ascend-Data-Rate', 'Ascend-Xmit-Rate', 'Mikrotik-Group', 'Fall-Through')]
    require(not override, 'Individual authorization override')
    pools = [r for r in snapshot['replies'] if r['username'] == u and r['attribute'] == 'Framed-Pool']
    require(not any(re.search(r'corte|bloq|down', r['value'], re.I) for r in pools), 'Individual restricted pool')
    desired_rows = [r for r in replies if r['groupname'] == desired]
    try:
        rate_change = not desired_rows or any(rate_values(r['value']) != rate_values(rate) for r in desired_rows)
    except Refused:
        rate_change = True
    group_change = len(groups) != 1 or groups[0]['groupname'] != desired
    target = '<pppoe-' + u + '>'
    actual = [q for q in queues if q.get('name') == target or q.get('target') == target]
    require(len(actual) <= 1, 'Multiple candidate queues')
    if actual:
        require('D' in actual[0]['flags'] and not set('XI') & set(actual[0]['flags']),
                'Static, disabled or invalid queue')
        require(actual[0].get('target') == target, 'Unexpected queue target')
    queue_change = not actual or not queue_matches(actual[0], rate)
    return dict(user=u, plan=desired, rate=rate, change_groups=group_change,
                change_rate=rate_change, change_additional=change_additional,
                change_queue=queue_change, previous_queue=actual[0] if actual else None,
                session=session)


def fingerprint(line):
    parts = line.split()
    require(len(parts) >= 3, 'Malformed SSH host key')
    return 'SHA256:' + base64.b64encode(hashlib.sha256(base64.b64decode(parts[2])).digest()).decode().rstrip('=')


class Router:
    def __init__(self, nas, args, credentials):
        self.nas = nas
        self.host = str(ipaddress.IPv4Address(nas['nasname']))
        self.port = int(nas.get('portassh') or 22)
        require(1 <= self.port <= 65535, 'Invalid SSH port')
        auth = credentials.get(self.host, {})
        self.user = auth.get('username', args.ssh_user)
        self.password = auth.get('password', nas.get('senha') or '')
        require(re.fullmatch(r'[A-Za-z0-9_.-]+', self.user), 'Unsupported SSH username')
        require(self.password, 'NAS has no usable SSH password; provide credential override')
        self.coa_port = None
        # Existing OpenSSH trust is reused; explicit pins bootstrap new routers.
        self.known = pathlib.Path(args.known_hosts)
        self.pin = dict(x.split('=', 1) for x in args.host_key).get(self.host)
        scan = run(['ssh-keyscan', '-T', '5', '-p', str(self.port), self.host], timeout=10)
        self.keys = [line for line in scan.splitlines() if line and not line.startswith('#')]
        require(self.keys, 'No SSH host key received')
        if self.pin:
            self.keys = [line for line in self.keys if fingerprint(line) == self.pin]
            require(self.keys, 'SSH host-key fingerprint mismatch')
            self.trust = '\n'.join(self.keys) + '\n'
        else:
            query = self.host if self.port == 22 else '[{}]:{}'.format(self.host, self.port)
            require(self.known.is_file(), 'Unknown SSH host; fingerprint(s): ' + ', '.join(fingerprint(x) for x in self.keys))
            trusted = run(['ssh-keygen', '-F', query, '-f', str(self.known)])
            require(any(k.split()[1:] == t.split()[1:] for k in self.keys
                        for t in trusted.splitlines() if t and not t.startswith('#')),
                    'Unknown or changed SSH host; fingerprint(s): ' + ', '.join(fingerprint(x) for x in self.keys))
            self.trust = self.known.read_text()

    def command(self, command):
        with tempfile.TemporaryDirectory(prefix='mka-queues-ssh-') as temp:
            root = pathlib.Path(temp)
            pw = root / 'password'; known = root / 'known_hosts'
            pw.write_text(self.password); pw.chmod(0o600)
            known.write_text(self.trust); known.chmod(0o600)
            argv = ['sshpass', '-f', str(pw), 'ssh', '-T', '-p', str(self.port),
                    '-o', 'StrictHostKeyChecking=yes', '-o', 'UserKnownHostsFile=' + str(known),
                    '-o', 'ConnectTimeout=8', '-o', 'LogLevel=ERROR',
                    '-o', 'HostKeyAlgorithms=+ssh-rsa',
                    self.user + '@' + self.host, command]
            return run(argv, timeout=50)

    def read(self):
        a = self.command('/ppp active print detail without-paging')
        q = self.command('/queue simple print detail without-paging')
        require('Flags:' in a and 'Flags:' in q and 'not enough permissions' not in a + q,
                'Cannot read complete PPP/queue inventory')
        active = parse_records(a)
        queues = parse_records(q)
        return active, queues

    def incoming(self):
        text = self.command('/radius incoming print')
        require(re.search(r'accept:\s*yes', text), 'NAS does not accept CoA')
        m = re.search(r'port:\s*(\d+)', text)
        require(m is not None, 'Cannot read CoA port')
        self.coa_port = int(m[1])


def same_session(a, b):
    return (a.get('name') == b.get('name') and 'R' in b.get('flags', '') and
            a.get('session-id', '').lower() == b.get('session-id', '').lower() and
            a.get('address') == b.get('address'))


def accounting(router, session):
    rows = db('accounting', user=session['name'], nas=router.host)
    row = one(rows, 'open accounting session')
    require(row['acctsessionid'].lower() == re.sub(r'^0x', '', session['session-id'].lower()),
            'Accounting session ID differs from router')
    require(row['framedipaddress'] == session['address'], 'Accounting IP differs from router')
    return row


def send_coa(router, session, rate, directory):
    row = accounting(router, session)
    user = session['name']
    require(not any(c in user + row['acctsessionid'] + rate for c in '\r\n"\\'),
            'Unsupported attribute characters')
    request = ('User-Name = "{}"\nAcct-Session-Id = "{}"\nFramed-IP-Address = {}\n'
               'Mikrotik-Rate-Limit = "{}"\n').format(user, row['acctsessionid'], row['framedipaddress'], rate)
    with tempfile.NamedTemporaryFile(mode='w') as f:
        os.chmod(f.name, 0o600); f.write(router.nas['secret']); f.flush()
        result = run(['radclient', '-F', '-r', '1', '-t', '3', '-S', f.name,
                      router.host + ':' + str(router.coa_port), 'coa'], request.encode(), timeout=8)
    # Store only a summary. radclient output can contain subscriber attributes.
    require('CoA-ACK' in result, 'CoA not acknowledged; session not disconnected')
    log = directory / 'coa-ack.json'
    log.write_text(json.dumps(dict(user=user, nas=router.host,
                                  session=session['session-id'], acknowledged=True), indent=2))
    log.chmod(0o600)


def secure_dir(root):
    path = pathlib.Path(root) / (time.strftime('%Y%m%d_%H%M%S') + '-sync-' + uuid.uuid4().hex[:8])
    path.mkdir(mode=0o700, parents=True)
    return path


def public_change(change):
    return {k: change[k] for k in ('user', 'plan', 'rate', 'change_groups', 'change_rate',
                                  'change_additional', 'change_queue')}


def perform(router, proposal, args):
    # Re-read live session and DB immediately before making any change.
    active, queues = router.read()
    session = one([a for a in active if a.get('name') == proposal['user']], 'current PPP session')
    require(same_session(proposal['session'], session), 'Session changed after audit')
    snapshot = index(db())
    current = propose(snapshot, session, queues)
    require(public_change(current) == public_change(proposal), 'Plan or queue changed after audit; audit again')
    if current['change_queue']:
        router.incoming()
        accounting(router, session)
        require(router.nas.get('secret'), 'No NAS RADIUS secret')
    directory = secure_dir(args.backups)
    (directory / 'before.json').write_text(json.dumps(dict(nas=router.host, change=current), indent=2))
    (directory / 'before.json').chmod(0o600)
    if any(current[k] for k in ('change_groups', 'change_rate', 'change_additional')):
        guard = db('guard', user=current['user'], plan=current['plan'])
        # Guard itself was read after snapshot; compare authoritative policy before applying.
        require(guard['plan'] == snapshot['plans_by'][current['plan']], 'Plan changed before transaction')
        expect = {'primary': snapshot['primary_by'].get(current['user'], []),
                  'additional': snapshot['additional_by'].get(current['user'], [])}
        expect['parent'] = snapshot['primary_by'].get(expect['additional'][0]['login'], []) if len(expect['additional']) == 1 else []
        expect['groups'] = [r for r in snapshot['groups'] if r['username'] == current['user']]
        names = {r['groupname'] for r in expect['groups']} | {current['plan']}
        expect['group_replies'] = [r for r in snapshot['group_replies'] if r['groupname'] in names]
        expect['group_checks'] = [r for r in snapshot['group_checks'] if r['groupname'] in names]
        expect['replies'] = [r for r in snapshot['replies'] if r['username'] == current['user']]
        for key, expected in expect.items():
            require(guard[key] == expected, 'Account changed before transaction')
        result = db('apply', user=current['user'], plan=current['plan'], rate=current['rate'],
                    change_groups=current['change_groups'], change_rate=current['change_rate'],
                    change_additional=current['change_additional'], guard=guard)
        (directory / 'db-result.json').write_text(json.dumps(result))
    if current['change_queue']:
        try:
            send_coa(router, session, current['rate'], directory)
        except Refused as error:
            raise Refused(str(error) + '; database may already be updated; backup: ' + str(directory))
        time.sleep(0.3)
        after, after_queues = router.read()
        new_session = one([a for a in after if a.get('name') == current['user']], 'session after CoA')
        require(same_session(session, new_session), 'Session changed during verification; inspect manually')
        verified = propose(index(db()), new_session, after_queues)
        require(not verified['change_queue'], 'CoA acknowledged but queue does not match; backup: ' + str(directory))
    return dict(**public_change(current), verified=True, backup=str(directory))


def rollback_session(path, args, credentials):
    b = json.loads(pathlib.Path(path).read_text())
    change = b['change']; old = change['session']
    require(change['change_queue'], 'Backup did not change a live queue')
    snapshot = index(db())
    nas = one([n for n in snapshot['nas'] if n['nasname'] == b['nas']], 'NAS registration')
    router = Router(nas, args, credentials)
    active, queues = router.read()
    live = one([a for a in active if a['name'] == change['user']], 'session to restore')
    require(same_session(old, live), 'Original session is no longer active; rollback refused')
    # Reuse policy exclusions to avoid lifting a newly applied block/turbo/down.
    propose(snapshot, live, queues)
    target = '<pppoe-' + change['user'] + '>'
    now = one([q for q in queues if q.get('name') == target and q.get('target') == target], 'current queue')
    require('D' in now['flags'] and queue_matches(now, change['rate']), 'Queue changed since correction; rollback refused')
    previous = change['previous_queue']
    if previous is None:
        rate = '0/0'
    else:
        priority = pair(previous['priority'])
        require(priority[0] == priority[1], 'Cannot restore asymmetric priority by RADIUS')
        rate = ' '.join(previous[k] for k in ('max-limit', 'burst-limit', 'burst-threshold'))
        durations = pair(previous['burst-time'], True)
        rate += ' {}/{} {} {}'.format(durations[0], durations[1], priority[0], previous['limit-at'])
    router.incoming()
    directory = secure_dir(args.backups)
    (directory / 'rollback-source.json').write_text(json.dumps(b, indent=2))
    send_coa(router, live, rate, directory)
    after, after_queues = router.read()
    same = one([a for a in after if a['name'] == change['user']], 'restored session')
    require(same_session(live, same), 'Session changed during rollback verification')
    new = [q for q in after_queues if q.get('name') == target and q.get('target') == target]
    if previous is None:
        require(not new or (len(new) == 1 and pair(new[0].get('max-limit', 'invalid')) == (0, 0)),
                'Rollback ACK received; unlimited state not verified')
    else:
        require(len(new) == 1 and queue_matches(new[0], rate), 'Previous queue rates not restored')
    return dict(restored=True, user=change['user'], backup=str(directory))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--apply', action='store_true', help='Apply discovered corrections; default audit only')
    parser.add_argument('--nas', action='append', default=[], help='Restrict discovery to this NAS IPv4; repeatable')
    parser.add_argument('--user', action='append', default=[], help='Restrict to this RADIUS username; repeatable')
    parser.add_argument('--ssh-user', default='mkauth')
    parser.add_argument('--credentials', help='0600 JSON: NAS IP -> username/password SSH overrides')
    parser.add_argument('--known-hosts', default='/root/.ssh/known_hosts')
    parser.add_argument('--host-key', action='append', default=[], metavar='IP=SHA256:PIN')
    parser.add_argument('--backups', default='/opt/mk-auth/backups/codex-queues')
    parser.add_argument('--report', help='Save private JSON report here, mode 0600')
    parser.add_argument('--rollback-db', help='Explicitly restore a directed database backup; no CoA sent')
    parser.add_argument('--rollback-session', help='Restore prior rates for the same live session from before.json')
    args = parser.parse_args()
    os.umask(0o077)
    lock_path = pathlib.Path(args.backups) / '.sync.lock'
    lock_path.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
    with lock_path.open('a') as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            raise Refused('Another queue synchronization is running')
        if args.rollback_db:
            require(args.apply, 'Database rollback requires --apply')
            print(json.dumps(db('rollback', file=args.rollback_db))); return 0
        for binary in ('ssh', 'sshpass', 'ssh-keyscan', 'ssh-keygen') + (('radclient',) if args.apply else ()):
            require(shutil.which(binary), 'Missing dependency: ' + binary)
        credentials = secret_file(args.credentials) if args.credentials else {}
        if args.rollback_session:
            require(args.apply, 'Session rollback requires --apply')
            print(json.dumps(rollback_session(args.rollback_session, args, credentials))); return 0
        snapshot = index(db())
        report = dict(mode='apply' if args.apply else 'audit', routers=[], changes=[], skipped=[], errors=[])
        seen = set()
        registered = collections.Counter(n['nasname'] for n in snapshot['nas'])
        for nas in snapshot['nas']:
            host = nas['nasname']
            if args.nas and host not in args.nas:
                continue
            if nas.get('sshativo') != 'sim':
                report['skipped'].append(dict(nas=host, reason='SSH disabled in NAS registration')); continue
            if registered[host] != 1:
                if host not in seen:
                    report['errors'].append(dict(nas=host, reason='Duplicate NAS registration'))
                seen.add(host); continue
            if host in seen:
                report['errors'].append(dict(nas=host, reason='Duplicate NAS registration')); continue
            seen.add(host)
            try:
                router = Router(nas, args, credentials)
                active, queues = router.read()
                report['routers'].append(dict(nas=host, active=len(active),
                    radius=sum('R' in a['flags'] for a in active), queues=len(queues)))
                usernames = collections.Counter(a['name'] for a in active)
                for session in active:
                    user = session['name']
                    if args.user and user not in args.user:
                        continue
                    if 'R' not in session['flags']:
                        report['skipped'].append(dict(nas=host, user=user, reason='Local session')); continue
                    try:
                        require(usernames[user] == 1, 'Multiple active sessions for username')
                        change = propose(snapshot, session, queues)
                        if not any(change[k] for k in ('change_groups', 'change_rate', 'change_additional', 'change_queue')):
                            continue
                        result = public_change(change)
                        if args.apply:
                            try:
                                result = perform(router, change, args)
                            except Refused as error:
                                report['errors'].append(dict(nas=host, user=user, reason=str(error)))
                                break
                            # Prior changes can update shared plan attributes; refresh before next user.
                            snapshot = index(db())
                        report['changes'].append(dict(nas=host, **result))
                    except Refused as error:
                        report['skipped'].append(dict(nas=host, user=user, reason=str(error)))
            except (Refused, ValueError, KeyError, UnicodeError) as error:
                # Do not print transport exceptions or secrets from command output.
                reason = str(error) if isinstance(error, Refused) else 'Unsupported NAS/schema/RouterOS response'
                report['errors'].append(dict(nas=host, reason=reason))
        if args.nas:
            for host in set(args.nas) - seen:
                report['errors'].append(dict(nas=host, reason='NAS not found or SSH disabled'))
        text = json.dumps(report, indent=2, ensure_ascii=True)
        if args.report:
            p = pathlib.Path(args.report)
            with p.open('w') as output:
                os.chmod(str(p), 0o600); output.write(text + '\n')
        print(text)
        return 2 if report['errors'] else 0


if __name__ == '__main__':
    try:
        sys.exit(main())
    except (Refused, ValueError, KeyError, json.JSONDecodeError, OSError):
        print('Synchronization stopped: check dependencies, schema, permissions or configuration. No disconnect was sent.', file=sys.stderr)
        sys.exit(1)
