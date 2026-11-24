"""Install pinned, downloaded skill directories using Codex's installer helpers."""
from pathlib import Path
import hashlib
import importlib.util
import json
import re
import sys

ROOT = Path(__file__).resolve().parent.parent
HELPERS = Path(r"C:\Users\MSI\.codex\skills\.system\skill-installer\scripts")
sys.path.insert(0, str(HELPERS))
spec = importlib.util.spec_from_file_location("codex_skill_installer", HELPERS / "install-skill-from-github.py")
installer = importlib.util.module_from_spec(spec)
sys.modules[spec.name] = installer
spec.loader.exec_module(installer)

SOURCES = [
    ("mattpocock/skills", "abeeaf95660ba9e8b7c753eb38f441ca707a8823", "skills-abeeaf95660ba9e8b7c753eb38f441ca707a8823"),
    ("obra/superpowers", "22f0b463e7a5e579904dd6211a520321035526f7", "superpowers-22f0b463e7a5e579904dd6211a520321035526f7"),
]
destination = ROOT / ".agent-skills"
inventory = []
names = set()
for repo, revision, folder in SOURCES:
    source_root = ROOT / ".local" / "skill-sources" / folder
    for skill_md in sorted((source_root / "skills").rglob("SKILL.md")):
        source = skill_md.parent
        name = source.name
        if name in names:
            raise RuntimeError(f"Duplicate skill directory: {name}")
        names.add(name)
        installer._validate_skill(str(source), str(source_root))
        content = skill_md.read_text(encoding="utf-8")
        metadata = content.split("---", 2)[1]
        description = re.search(r"^description:\s*(.*)$", metadata, re.MULTILINE)
        inventory.append({
            "name": name,
            "description": description.group(1).strip().strip('"') if description else "Read SKILL.md",
            "repo": f"https://github.com/{repo}",
            "revision": revision,
            "source": source.relative_to(source_root).as_posix(),
            "source_root": source_root.relative_to(ROOT).as_posix(),
            "destination": f".agent-skills/{name}",
            "experimental": "/in-progress/" in skill_md.as_posix(),
            "files": {},
        })

for entry in inventory:
    source = ROOT / entry["source_root"] / entry["source"]
    target = ROOT / entry["destination"]
    if target.exists():
        source_files = {p.relative_to(source).as_posix(): hashlib.sha256(p.read_bytes()).hexdigest()
                        for p in source.rglob("*") if p.is_file()}
        target_files = {p.relative_to(target).as_posix(): hashlib.sha256(p.read_bytes()).hexdigest()
                        for p in target.rglob("*") if p.is_file()}
        if source_files != target_files:
            raise RuntimeError(f"Conflicting destination: {target}")

for entry in inventory:
    source = ROOT / entry["source_root"] / entry["source"]
    target = ROOT / entry["destination"]
    if not target.exists():
        installer._copy_skill(str(source), str(target))
    for file in source.rglob("*"):
        if file.is_file():
            relative = file.relative_to(source)
            digest = hashlib.sha256(file.read_bytes()).hexdigest()
            assert hashlib.sha256((target / relative).read_bytes()).hexdigest() == digest
            entry["files"][relative.as_posix()] = digest

(destination / "manifest.json").write_text(json.dumps(inventory, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
index = ["# Installed project skills", "", "Pinned, complete skill directories installed for Baca Mudah.", "",
         "This session cannot write the protected `.agents/skills` and `.codex` locations. These skills are loaded through the project's AGENTS.md pointers; native skill-menu discovery is not configured.", "",
         "Full source checkouts and their licenses are retained under `.local/skill-sources/` for repository-level references. Installing a skill does not execute its scripts or configure unrelated tools.", "",
         "| Skill | Purpose | Source directory |", "| --- | --- | --- |"]
for entry in inventory:
    index.append(f"| [{entry['name']}]({entry['name']}/SKILL.md) | {entry['description'].replace('|', '/')} | `{entry['repo'].split('github.com/')[1]}/{entry['source']}` |")
(destination / "README.md").write_text("\n".join(index) + "\n", encoding="utf-8")
print(json.dumps({"skills": len(inventory), "files_verified": sum(len(e["files"]) for e in inventory),
                  "by_repository": {repo: sum(e["repo"] == f"https://github.com/{repo}" for e in inventory) for repo, _, _ in SOURCES}}, indent=2))
