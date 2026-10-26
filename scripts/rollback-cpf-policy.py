#!/usr/bin/env python3
"""Revert only this patch, refusing subsequent changes to any owned file/key."""
from pathlib import Path
import hashlib,json,shutil,subprocess
root=Path(__file__).resolve().parent
items=json.loads((root/'files.json').read_text())
for item in items:
 p=Path(item['path'])
 if not p.is_file() or p.is_symlink() or hashlib.sha256(p.read_bytes()).hexdigest()!=item['installed_sha256']:raise SystemExit('Arquivo alterado posteriormente; reversao cancelada: '+str(p))
subprocess.check_call(['php',str(root/'cpf-policy-state.php'),'restore',str(root/'database-sis_opcao.json')])
for item in reversed(items):
 p=Path(item['path'])
 if item['before']:shutil.copy2(item['before'],p)
 else:p.unlink()
print('Patch CPF revertido. Nenhum cliente ou outro valor de sis_opcao foi restaurado.')
