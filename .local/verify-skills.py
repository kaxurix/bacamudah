from pathlib import Path
import hashlib
import json
import re
from urllib.parse import unquote

root = Path(__file__).resolve().parent.parent
entries = json.loads((root / '.agent-skills/manifest.json').read_text(encoding='utf-8'))
checked = 0
broken = []
for entry in entries:
    directory = root / entry['destination']
    assert (directory / 'SKILL.md').is_file(), entry['name']
    for relative, digest in entry['files'].items():
        file = directory / relative
        assert hashlib.sha256(file.read_bytes()).hexdigest() == digest, str(file)
        checked += 1
        if file.suffix != '.md':
            continue
        prose = file.read_text(encoding='utf-8')
        # References inside code examples describe files a future skill may create.
        lines = []
        fence = None
        for line in prose.splitlines():
            marker = re.match(r'^\s*(`{3,}|~{3,})', line)
            if marker:
                token = marker.group(1)
                if fence is None:
                    fence = token
                elif token[0] == fence[0] and len(token) >= len(fence):
                    fence = None
                continue
            if fence is None:
                lines.append(line)
        prose = '\n'.join(lines)
        prose = re.sub(r'`[^`\n]+`', '', prose)
        for match in re.finditer(r'\[[^\]]*\]\(([^)\s]+)\)', prose):
            link = unquote(match.group(1)).split('#')[0]
            if not link or ':' in link or link.startswith('/') or '<' in link:
                continue
            target = file.parent / link
            if not target.exists():
                original = root / entry['source_root'] / entry['source'] / relative
                broken.append({'file': file.relative_to(root).as_posix(), 'reference': link,
                               'available_in_full_source': (original.parent / link).exists()})
print(json.dumps({'skills_verified': len(entries), 'files_sha256_verified': checked,
                  'relative_reference_findings': broken}, indent=2))
