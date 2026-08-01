"""V1 — Runtime validator check (MS-06, evaluation spec §4 row V1).

The check invokes the published runtime validator exactly as an
external consumer would — ``python -m tooling.validator`` with the
catalog's ``--validators`` list (crossref excluded per the documented
limitation in the V1 row) — through the run's command log (E3), and
records the validator's own report as the raw output.  No validator
logic is duplicated in the harness.

Verdicts: exit 0 → PASS; exit 1 (validation failures) → FAIL;
validator error (exit 2) or missing rules → BLOCKED (tooling
malfunction, §5.1 re-runnable).
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

from tooling.harness.config import repo_root
from tooling.harness.validation.result import CheckResult

# Evaluation spec §4 row V1: schema, rule_id, ownership, priority,
# release (crossref excluded per the catalog's documented limitation).
V1_VALIDATORS = "schema,rule_id,ownership,priority,release"

VERSION_PATTERN = re.compile(r"Version:\s*(\S+)")
RESULT_PATTERN = re.compile(r"=== Result:\s*(\w+)(.*?)$", re.MULTILINE)
FAILED_VALIDATOR_PATTERN = re.compile(r"^--- (.+?): FAIL \(\d+ errors\) ---", re.MULTILINE)


def run_v1(
    *,
    rules_root: str | Path,
    command_log,
    cwd: str | Path | None = None,
    python: str | None = None,
    timeout: float = 300.0,
) -> CheckResult:
    """Execute V1 against the skill rules through the validator's CLI.

    Parameters
    ----------
    rules_root:
        The ``bga-senior-engineer-skill/rules`` directory (attached
        reference material; the validator reads it, never modifies it).
    command_log:
        The run's :class:`~tooling.harness.util.proc.CommandLog` so the
        invocation is recorded in the E3 command log.
    cwd:
        Working directory for the subprocess; defaults to the
        ``bga-senior-engineer`` root so ``python -m tooling.validator``
        resolves the ``tooling`` package.
    python:
        Interpreter used to run the validator; defaults to the
        harness's own interpreter (``sys.executable``).
    """
    rules = Path(rules_root)
    if not rules.is_dir():
        return CheckResult(
            id="V1",
            name="Runtime validator",
            verdict="BLOCKED",
            blocking=True,
            detail=f"rules directory missing: {rules}",
            raw_text=f"BLOCKED: rules directory missing: {rules}\n",
            tool="python",
            tool_version=_python_version(),
        )
    interpreter = python if python is not None else sys.executable
    workdir = Path(cwd) if cwd is not None else repo_root()
    argv = [
        interpreter,
        "-m",
        "tooling.validator",
        "--rules",
        str(rules),
        "--validators",
        V1_VALIDATORS,
    ]
    result = command_log.run(argv, cwd=workdir, timeout=timeout)
    raw = ((result.stdout or "") + (result.stderr or "")).strip()
    executed_by = " ".join(_quote_arg(part) for part in argv)
    version = _parse_version(raw)
    if result.exit_code == 0:
        return _finish(
            CheckResult(
                id="V1",
                name="Runtime validator",
                verdict="PASS",
                blocking=True,
                detail=_result_line(raw, default="PASS (0 validators failed)"),
                raw_text=raw,
                exit_code=result.exit_code,
                executed_by=executed_by,
                version=version,
                tool="tooling.validator",
                tool_version=version,
            ),
            result.wall_time,
        )
    if result.exit_code == 1:
        findings = [f"validator {name}" for name in _failed_validators(raw)]
        return _finish(
            CheckResult(
                id="V1",
                name="Runtime validator",
                verdict="FAIL",
                blocking=True,
                detail=_result_line(raw, default="FAIL (validator violations)"),
                findings=findings,
                raw_text=raw,
                exit_code=result.exit_code,
                executed_by=executed_by,
                version=version,
                tool="tooling.validator",
                tool_version=version,
            ),
            result.wall_time,
        )
    return _finish(
        CheckResult(
            id="V1",
            name="Runtime validator",
            verdict="BLOCKED",
            blocking=True,
            detail=f"validator errored (exit {result.exit_code}): "
            f"{(result.stderr or result.stdout or '').strip()[:300]}",
            raw_text=raw,
            exit_code=result.exit_code,
            executed_by=executed_by,
            version=version,
            tool="tooling.validator",
            tool_version=version,
        ),
        result.wall_time,
    )


def _finish(result: CheckResult, wall_time: float) -> CheckResult:
    result.execution_time = wall_time
    return result


def _quote_arg(part: str) -> str:
    if any(char.isspace() for char in part):
        return f'"{part}"'
    return part


def _parse_version(raw: str) -> str | None:
    match = VERSION_PATTERN.search(raw)
    return match.group(1) if match else None


def _result_line(raw: str, default: str) -> str:
    match = RESULT_PATTERN.search(raw)
    if match:
        return f"Result: {match.group(1)}{match.group(2).strip()}"
    return default


def _failed_validators(raw: str) -> list[str]:
    return sorted(set(FAILED_VALIDATOR_PATTERN.findall(raw)))


def _python_version() -> str:
    import platform

    return platform.python_version()
