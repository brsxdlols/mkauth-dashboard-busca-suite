"""Exercise the installed SQL guard predicate without connecting to MK-AUTH.

SQLite evaluates the extracted expression; this does not validate MariaDB
trigger installation or the native mk-bot workflow.
"""
from pathlib import Path
import re
import sqlite3

source = Path(__file__).with_name('install-manual-block-guard.sh').read_text()
predicate = re.search(r"IF (OLD.bloqueado=.*?) THEN", source, re.S).group(1)
action_predicate = re.search(r"IF (v_last_action=.*?) THEN", source, re.S).group(1)
predicate = '(' + predicate + ') AND (' + action_predicate.replace('v_last_action', ':action') + ')'
predicate = predicate.replace('OLD.bloqueado', ':blocked')
predicate = predicate.replace('NEW.observacao', ':observation')
db = sqlite3.connect(':memory:')
cases = [
    ('grant trust in same update', 'sim', 'bloqueio', 'sim', 0),
    ('native removes expired observation', 'sim', 'bloqueio', 'nao', 1),
    ('manual block without observation', 'sim', 'bloqueio', 'nao', 1),
    ('missing observation', 'sim', 'bloqueio', None, 1),
    ('manual unlock', 'sim', 'desbloqueio', 'nao', 0),
    ('no manual decision', 'sim', None, 'nao', 0),
    ('already free', 'nao', 'bloqueio', 'nao', 0),
    ('free in observation', 'nao', 'bloqueio', 'sim', 0),
]
for name, blocked, action, observation, expected in cases:
    result = db.execute('SELECT ' + predicate, dict(blocked=blocked, action=action, observation=observation)).fetchone()[0]
    assert bool(result) == bool(expected), (name, result, expected)
    print('PASS', name)
