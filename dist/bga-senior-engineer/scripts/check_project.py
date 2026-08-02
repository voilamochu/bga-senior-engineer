#!/usr/bin/env python3
"""Mechanical BGA rule checks for the bga-senior-engineer skill.

Implements the grep/parse halves of selected rule `check` fields so an
agent does not reinvent them per task. Read-only: never modifies the
project. Non-interactive, idempotent, structured output.

Usage:
  python3 scripts/check_project.py [--project DIR] [--json] [--list]
                                   [--check NAME [--check NAME ...]]
                                   [--limit N]

Exit codes:
  0  all checks passed
  1  one or more checks failed
  2  usage error

Data goes to stdout (JSON when --json, plain table otherwise).
Diagnostics go to stderr.
"""

import argparse
import json
import re
import sys
from pathlib import Path

SKILL = "bga-senior-engineer"
GAME_PHP_LINE_LIMIT = 300

SQL_RE = re.compile(
    r"\b(SELECT|INSERT INTO|UPDATE|DELETE FROM|CREATE TABLE|ALTER TABLE|"
    r"REPLACE INTO|TRUNCATE TABLE)\b",
    re.IGNORECASE,
)
NOTIFY_RE = re.compile(r"\b(notifyAllPlayers|notifyPlayer)\b")
STATE_BLOCK_RE = re.compile(r"([A-Za-z_][A-Za-z0-9_]*|\d+)\s*=>\s*(?:array\s*\(|\[)")


def find_game_php(project: Path) -> Path | None:
    for name in ("game.php",):
        p = project / name
        if p.is_file():
            return p
    for p in sorted(project.glob("*.game.php")):
        return p
    return None


def php_lines(path: Path) -> list[str]:
    try:
        return path.read_text(encoding="utf-8", errors="replace").splitlines()
    except OSError as e:
        raise RuntimeError(f"cannot read {path}: {e}") from e


def is_comment(line: str) -> bool:
    s = line.strip()
    return s.startswith(("//", "#", "*", "/*", "*/"))


def check_game_php_size(project: Path, _cap: int) -> dict:
    path = find_game_php(project)
    if path is None:
        return _check("game_php_size", "ARCH-001",
                      "Game.php stays under the line limit",
                      False, ["no game.php or *.game.php found in project root"])
    lines = php_lines(path)
    passed = len(lines) < GAME_PHP_LINE_LIMIT
    return _check("game_php_size", "ARCH-001",
                  "Game.php stays under the line limit",
                  passed, [], meta={"file": path.name, "lines": len(lines),
                                    "limit": GAME_PHP_LINE_LIMIT})


def check_game_php_sql(project: Path, cap: int) -> dict:
    path = find_game_php(project)
    if path is None:
        return _check("game_php_sql", "ARCH-001",
                      "No SQL statements in Game.php",
                      True, [], meta={"file": None})
    findings = []
    for i, line in enumerate(php_lines(path), 1):
        if is_comment(line):
            continue
        if SQL_RE.search(line):
            findings.append(f"{path.name}:{i}: {line.strip()[:120]}")
            if len(findings) >= cap:
                findings.append(f"... {cap}+ findings suppressed (--limit)")
                break
    return _check("game_php_sql", "ARCH-001",
                  "No SQL statements in Game.php",
                  not findings, findings, meta={"file": path.name})


def check_notify_direct(project: Path, cap: int) -> dict:
    findings = []
    php_files = sorted(project.rglob("*.php"))
    for p in php_files:
        if any(part.startswith((".", "vendor", "node_modules")) for part in p.parts):
            continue
        name = p.name
        if name.startswith("Notifications") or "Notifications" in name:
            continue
        if name in ("_ide_helper.php", "bga-framework.d.ts"):
            continue
        try:
            text = p.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        if re.search(r"class\s+Notifications\b", text):
            continue
        for i, line in enumerate(text.splitlines(), 1):
            if NOTIFY_RE.search(line) and not is_comment(line):
                rel = p.relative_to(project)
                findings.append(f"{rel}:{i}: {line.strip()[:120]}")
                if len(findings) >= cap:
                    findings.append(f"... {cap}+ findings suppressed (--limit)")
                    break
    return _check("notify_direct", "NOTF",
                  "Notification calls are centralized (no direct notify in game code)",
                  not findings, findings)


def check_states_sanity(project: Path, cap: int) -> dict:
    path = project / "states.inc.php"
    if not path.is_file():
        return _check("states_sanity", "STAT",
                      "states.inc.php has complete state definitions",
                      False, ["states.inc.php not found in project root"])
    lines = php_lines(path)
    text = "\n".join(lines)
    ids = [m.group(1) for m in STATE_BLOCK_RE.finditer(text)]
    if not ids:
        return _check("states_sanity", "STAT",
                      "states.inc.php has complete state definitions",
                      False, ["no state blocks parsed from states.inc.php"])
    findings = []
    n = 0
    for i, m in enumerate(STATE_BLOCK_RE.finditer(text)):
        end = STATE_BLOCK_RE.search(text, m.end())
        block = text[m.start(): end.start() if end else len(text)]
        n += 1
        missing = [k for k in ("name", "description", "type", "action")
                   if re.search(rf"['\"]{k}['\"]\s*=>", block) is None]
        if missing:
            findings.append(f"state {m.group(1)} missing: {', '.join(missing)}")
            if len(findings) >= cap:
                findings.append(f"... {cap}+ findings suppressed (--limit)")
                break
    return _check("states_sanity", "STAT",
                  "states.inc.php has complete state definitions",
                  not findings, findings, meta={"states": n})


CHECKS = [
    check_game_php_size,
    check_game_php_sql,
    check_notify_direct,
    check_states_sanity,
]


def _check(cid: str, rule_id: str, title: str, passed: bool,
           findings: list, meta: dict | None = None) -> dict:
    return {"id": cid, "rule_id": rule_id, "title": title,
            "passed": bool(passed), "findings": list(findings), "meta": meta or {}}


def run_checks(project: Path, selected: list[str], cap: int) -> list[dict]:
    results = []
    for fn in CHECKS:
        cid = fn.__name__[len("check_"):]
        if selected and cid not in selected:
            continue
        try:
            results.append(fn(project, cap))
        except (RuntimeError, OSError) as e:
            results.append(_check(cid, "", fn.__doc__ or cid, False, [str(e)]))
    return results


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(
        prog="check_project.py",
        description="Mechanical BGA rule checks for the bga-senior-engineer skill.",
        epilog="Exit codes: 0 all pass, 1 failures, 2 usage error. "
               "Data on stdout, diagnostics on stderr.")
    parser.add_argument("--project", default=".", type=Path,
                        help="BGA project directory (default: current directory)")
    parser.add_argument("--json", action="store_true",
                        help="emit structured JSON on stdout")
    parser.add_argument("--list", action="store_true",
                        help="list available checks and exit")
    parser.add_argument("--check", action="append", default=[],
                        metavar="NAME", help="run only this check (repeatable)")
    parser.add_argument("--limit", type=int, default=20,
                        help="max findings per check (default: 20)")
    args = parser.parse_args(argv)

    ids = {fn.__name__[len("check_"):] for fn in CHECKS}
    if args.list:
        for fn in CHECKS:
            print(f"{fn.__name__[len('check_'):]:16s} {fn.__doc__ or ''}")
        return 0
    unknown = [c for c in args.check if c not in ids]
    if unknown:
        print(f"error: unknown check(s): {', '.join(unknown)}", file=sys.stderr)
        print(f"available: {', '.join(sorted(ids))}", file=sys.stderr)
        return 2
    if not args.project.is_dir():
        print(f"error: --project is not a directory: {args.project}",
              file=sys.stderr)
        return 2

    results = run_checks(args.project, args.check, max(1, args.limit))
    summary = {"checks": len(results), "passed": sum(1 for r in results if r["passed"]),
               "failed": sum(1 for r in results if not r["passed"])}

    if args.json:
        payload = {
            "skill": SKILL,
            "project": str(args.project.resolve()),
            "summary": summary,
            "checks": results,
        }
        print(json.dumps(payload, indent=2))
    else:
        for r in results:
            status = "PASS" if r["passed"] else "FAIL"
            print(f"[{status}] {r['id']} ({r['rule_id'] or 'n/a'}) {r['title']}")
            for f in r["findings"]:
                print(f"        {f}")
        print(f"summary: {summary['passed']}/{summary['checks']} checks passed")

    return 0 if summary["failed"] == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
