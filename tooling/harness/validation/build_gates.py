"""Build gates B1-B4 (MS-06, evaluation spec §4 catalog rows B1-B4).

The gates execute only against the submission's changed files (the
frozen diff bundle, E8) — never against the reference repository:

- **B1 PHP syntax** — ``php -l`` over every changed ``.php`` file
  (blocking; missing PHP on the host → BLOCKED, not a silent pass).
- **B2 JS syntax** — ``node --check`` over every changed ``.js`` file
  (blocking; missing Node on the host → BLOCKED).
- **B3 JSON validity** — JSON parse of every changed ``.json`` artifact
  and comment-stripped parse of every changed ``.jsonc`` artifact
  (blocking).  ``.sql`` artifacts are not JSON; they are excluded with a
  recorded note (documented interpretation of the catalog row).
- **B4 artifact inventory** — every changed file must be declared in the
  submission's ``subsystems.md`` inventory (eval spec Appendix A
  format); an undeclared changed file fails the gate (blocking).

Pass criteria follow the catalog's "0 errors" definition: a gate with
zero applicable changed files trivially passes (0 files checked, 0
errors).
"""

from __future__ import annotations

import json
import re
import shutil
from pathlib import Path

from tooling.harness.util.proc import CommandLog
from tooling.harness.validation.result import CheckResult

# Eval spec Appendix A inventory row format:
#   | File | Status (A/M/D) | Subsystem | Purpose |
_INVENTORY_ROW = re.compile(r"^\s*\|([^|]+)\|\s*([AMD])\s*\|", re.IGNORECASE)


class BuildGateError(Exception):
    """A build gate could not be evaluated (blocked or internal)."""


def run_build_gates(
    *,
    diff_bundle: Path | None,
    changed_paths: list[str],
    subsystems_md: str | None,
    subsystems_md_evidence: list[str],
    command_log: CommandLog,
) -> list[CheckResult]:
    """Run B1-B4 in catalog order against the frozen diff bundle."""
    if diff_bundle is None:
        reason = "evidence: the submission has no diff bundle (E8 absent)"
        return [
            CheckResult(
                id="B1",
                name="PHP syntax",
                verdict="BLOCKED",
                blocking=True,
                detail=reason,
                raw_text=f"BLOCKED: {reason}\n",
                tool="php",
                tool_version=_tool_version(command_log, "php", ["--version"]),
            ),
            CheckResult(
                id="B2",
                name="JS syntax",
                verdict="BLOCKED",
                blocking=True,
                detail=reason,
                raw_text=f"BLOCKED: {reason}\n",
                tool="node",
                tool_version=_tool_version(command_log, "node", ["--version"]),
            ),
            CheckResult(
                id="B3",
                name="JSON validity",
                verdict="BLOCKED",
                blocking=True,
                detail=reason,
                raw_text=f"BLOCKED: {reason}\n",
            ),
            CheckResult(
                id="B4",
                name="Artifact inventory",
                verdict="BLOCKED",
                blocking=True,
                detail=reason,
                raw_text=f"BLOCKED: {reason}\n",
            ),
        ]

    php_files = sorted(p for p in changed_paths if p.endswith(".php"))
    js_files = sorted(p for p in changed_paths if p.endswith(".js"))
    json_files = sorted(p for p in changed_paths if p.endswith((".json", ".jsonc")))
    sql_files = sorted(p for p in changed_paths if p.endswith(".sql"))
    evidence = [f"evidence/e8-diff-bundle/{p}" for p in changed_paths]

    b1 = _run_b1(php_files, diff_bundle, command_log, evidence)
    b2 = _run_b2(js_files, diff_bundle, command_log, evidence)
    b3 = _run_b3(json_files, sql_files, diff_bundle, evidence)
    b4 = _run_b4(changed_paths, subsystems_md, subsystems_md_evidence)
    return [b1, b2, b3, b4]


def _run_b1(php_files: list[str], diff_bundle: Path, command_log, evidence) -> CheckResult:
    php = shutil.which("php")
    if php is None:
        return CheckResult(
            id="B1",
            name="PHP syntax",
            verdict="BLOCKED",
            blocking=True,
            detail="php binary not found on the host (required tool §4.2)",
            raw_text="BLOCKED: php binary not found on the host (required tool §4.2)\n",
            tool="php",
            tool_version=None,
        )
    tool_version = _tool_version(command_log, php, ["--version"])
    if not php_files:
        return CheckResult(
            id="B1",
            name="PHP syntax",
            verdict="PASS",
            blocking=True,
            detail="0 changed PHP files (0 files checked, 0 errors)",
            raw_text="PASS: 0 changed PHP files (0 files checked, 0 errors)\n",
            evidence=evidence,
            tool="php",
            tool_version=tool_version,
        )
    findings: list[str] = []
    raw_lines: list[str] = []
    for relpath in php_files:
        result = command_log.run([php, "-l", str(diff_bundle / relpath)])
        raw_lines.append(f"$ php -l {relpath} (exit {result.exit_code})")
        if result.stdout:
            raw_lines.append(result.stdout.rstrip())
        if result.stderr:
            raw_lines.append(result.stderr.rstrip())
        if result.exit_code != 0:
            findings.append(
                f"{relpath}: php -l failed (exit {result.exit_code}): "
                f"{(result.stdout + result.stderr).strip()[:200]}"
            )
    return CheckResult(
        id="B1",
        name="PHP syntax",
        verdict="PASS" if not findings else "FAIL",
        blocking=True,
        detail=(
            f"{len(php_files)} changed PHP files, {len(findings)} with errors"
            if not findings
            else "; ".join(findings)
        ),
        findings=findings,
        raw_text="\n".join(raw_lines) + "\n",
        evidence=evidence,
        tool="php",
        tool_version=tool_version,
    )


def _run_b2(js_files: list[str], diff_bundle: Path, command_log, evidence) -> CheckResult:
    node = shutil.which("node")
    if node is None:
        return CheckResult(
            id="B2",
            name="JS syntax",
            verdict="BLOCKED",
            blocking=True,
            detail="node binary not found on the host (required tool §4.2)",
            raw_text="BLOCKED: node binary not found on the host (required tool §4.2)\n",
            tool="node",
            tool_version=None,
        )
    tool_version = _tool_version(command_log, node, ["--version"])
    if not js_files:
        return CheckResult(
            id="B2",
            name="JS syntax",
            verdict="PASS",
            blocking=True,
            detail="0 changed JS files (0 files checked, 0 errors)",
            raw_text="PASS: 0 changed JS files (0 files checked, 0 errors)\n",
            evidence=evidence,
            tool="node",
            tool_version=tool_version,
        )
    findings: list[str] = []
    raw_lines: list[str] = []
    for relpath in js_files:
        result = command_log.run([node, "--check", str(diff_bundle / relpath)])
        raw_lines.append(f"$ node --check {relpath} (exit {result.exit_code})")
        if result.stdout:
            raw_lines.append(result.stdout.rstrip())
        if result.stderr:
            raw_lines.append(result.stderr.rstrip())
        if result.exit_code != 0:
            findings.append(
                f"{relpath}: node --check failed (exit {result.exit_code}): "
                f"{(result.stdout + result.stderr).strip()[:200]}"
            )
    return CheckResult(
        id="B2",
        name="JS syntax",
        verdict="PASS" if not findings else "FAIL",
        blocking=True,
        detail=(
            f"{len(js_files)} changed JS files, {len(findings)} with errors"
            if not findings
            else "; ".join(findings)
        ),
        findings=findings,
        raw_text="\n".join(raw_lines) + "\n",
        evidence=evidence,
        tool="node",
        tool_version=tool_version,
    )


def _run_b3(json_files: list[str], sql_files: list[str], diff_bundle: Path, evidence) -> CheckResult:
    findings: list[str] = []
    raw_lines: list[str] = []
    if sql_files:
        raw_lines.append(
            f"note: {len(sql_files)} changed .sql artifact(s) excluded from "
            "B3 (SQL is not JSON; documented interpretation of catalog row B3)"
        )
    if not json_files and not sql_files:
        raw_lines.append("$ no changed JSON/JSONC artifacts (0 files checked)")
    for relpath in json_files:
        path = diff_bundle / relpath
        try:
            text = path.read_text(encoding="utf-8")
            if relpath.endswith(".jsonc"):
                text = _strip_jsonc_comments(text)
            json.loads(text)
            raw_lines.append(f"$ json parse {relpath}: OK")
        except (ValueError, OSError) as exc:
            findings.append(f"{relpath}: JSON parse failed: {exc}")
            raw_lines.append(f"$ json parse {relpath}: FAIL ({exc})")
    return CheckResult(
        id="B3",
        name="JSON validity",
        verdict="PASS" if not findings else "FAIL",
        blocking=True,
        detail=(
            f"{len(json_files)} changed JSON/JSONC artifacts, 0 errors"
            if not findings
            else "; ".join(findings)
        ),
        findings=findings,
        raw_text="\n".join(raw_lines) + "\n",
        evidence=evidence,
    )


def _run_b4(changed_paths: list[str], subsystems_md: str | None, evidence) -> CheckResult:
    if subsystems_md is None:
        return CheckResult(
            id="B4",
            name="Artifact inventory",
            verdict="BLOCKED",
            blocking=True,
            detail="evidence: the submission's subsystems.md inventory is missing",
            raw_text="BLOCKED: evidence: the submission's subsystems.md inventory is missing\n",
        )
    declared = parse_inventory(subsystems_md)
    changed = {_normalize_path(p) for p in changed_paths}
    undeclared = sorted(changed - declared)
    if undeclared:
        findings = [
            f"changed file {path!r} is not declared in subsystems.md"
            for path in undeclared
        ]
        return CheckResult(
            id="B4",
            name="Artifact inventory",
            verdict="FAIL",
            blocking=True,
            detail="; ".join(findings),
            findings=findings,
            raw_text="\n".join(findings) + "\n",
            evidence=evidence,
        )
    return CheckResult(
        id="B4",
        name="Artifact inventory",
        verdict="PASS",
        blocking=True,
        detail=f"{len(changed)} changed file(s), all declared in subsystems.md",
        raw_text=(
            "PASS: all changed files are declared in subsystems.md "
            f"({len(changed)} files)\n"
        ),
        evidence=evidence,
    )


def parse_inventory(subsystems_md: str) -> set[str]:
    """Declared file paths from the eval spec Appendix A inventory table.

    Recognizes rows of the form ``| File | Status (A/M/D) | ... |``;
    only rows with a status column are inventory entries (the header and
    separator rows are ignored).
    """
    declared: set[str] = set()
    for line in subsystems_md.splitlines():
        match = _INVENTORY_ROW.match(line)
        if match is None:
            continue
        declared.add(_normalize_path(match.group(1)))
    return declared


def _normalize_path(path: str) -> str:
    path = path.strip().strip("`").strip()
    path = path.lstrip("./").rstrip("/")
    return path


def _tool_version(command_log, binary: str, version_args: list[str]) -> str | None:
    try:
        result = command_log.run([binary, *version_args])
    except OSError:
        return None
    output = (result.stdout or result.stderr).strip()
    first_line = output.splitlines()[0].strip() if output else ""
    return first_line or None


def _strip_jsonc_comments(text: str) -> str:
    """Remove ``//`` and ``/* */`` comments from JSONC text."""
    chars = list(text)
    i = 0
    n = len(text)
    in_string = False
    while i < n:
        c = text[i]
        if in_string:
            if c == "\\" and i + 1 < n:
                i += 2
                continue
            if c == '"':
                in_string = False
            i += 1
        elif c == '"':
            in_string = True
            i += 1
        elif text.startswith("//", i):
            end = text.find("\n", i)
            end = n if end == -1 else end
            for j in range(i, end):
                chars[j] = " "
            i = end
        elif text.startswith("/*", i):
            end = text.find("*/", i + 2)
            end = n if end == -1 else end + 2
            for j in range(i, end):
                chars[j] = " "
            i = end
        else:
            i += 1
    return "".join(chars)
