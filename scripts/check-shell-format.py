#!/usr/bin/env python3
"""Reject shell artifacts that Linux cannot execute after downloading a release."""
from pathlib import Path
import sys
root=Path(sys.argv[1]) if len(sys.argv)>1 else Path(__file__).resolve().parents[1]
errors=[]
for path in root.rglob('*.sh'):
    if '.git' in path.parts:continue
    data=path.read_bytes()
    if b'\r' in data or data.startswith(b'\xef\xbb\xbf'):
        errors.append(str(path.relative_to(root)))
if errors:
    print('ERROR: shell files must use LF without BOM: '+', '.join(errors),file=sys.stderr)
    sys.exit(1)
print('OK: shell scripts use Linux line endings')
