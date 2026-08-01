"""Repository safety baseline and G0 verification (MVB-004).

Implements harness §12.2 (baseline capture at P0) and §12.3 (G0
verification): the three git commands ``git rev-parse HEAD``,
``git status --porcelain``, and ``git reflog -1`` are recorded at P0
and compared on every re-verification.  Any divergence is reported
with the precise check name, expected value, and actual value.

The reference repository is never opened for write; only read-only
git inspection is used.
"""

from __future__ import annotations

import json
from dataclasses import dataclass, field
from pathlib import Path

from tooling.harness.util.clock import is_iso_utc, utc_now_iso
from tooling.harness.util.proc import run_cmd

# §12.2 schema fields, in document order.
BASELINE_FIELDS = ("head", "status_porcelain", "reflog_top", "recorded_at")


class BaselineError(Exception):
    """Baseline capture/verification failed (e.g., not a git repository)."""


@dataclass(frozen=True)
class Divergence:
    """One §12.3 check where the recorded baseline differs from the current
    repository state."""

    check: str
    expected: str
    actual: str


@dataclass(frozen=True)
class BaselineVerification:
    """Result of a §12.3 G0 comparison."""

    passed: bool
    divergences: list[Divergence] = field(default_factory=list)

    def describe(self) -> str:
        """Human-readable precise diff of every divergence."""
        lines = ["G0 safety verification FAILED:"]
        for divergence in self.divergences:
            lines.append(
                f"- {divergence.check}: expected {divergence.expected!r}, "
                f"actual {divergence.actual!r}"
            )
        return "\n".join(lines)


def _git(repo: Path, *args: str) -> str:
    try:
        result = run_cmd(["git", *args], cwd=repo)
    except OSError as exc:
        raise BaselineError(f"cannot run git in {repo}: {exc}") from exc
    if result.exit_code != 0:
        raise BaselineError(
            f"git {' '.join(args)} failed in {repo}: {result.stderr.strip()}"
        )
    return result.stdout


def capture_baseline(repo: str | Path, *, at: str | None = None) -> dict:
    """Capture the §12.2 safety baseline of *repo*.

    Returns the exact §12.2 schema: ``{head, status_porcelain,
    reflog_top, recorded_at}``.  ``status_porcelain`` is the raw
    ``git status --porcelain`` output; the other values are stripped
    of their trailing newline.
    """
    repo = Path(repo)
    baseline = {
        "head": _git(repo, "rev-parse", "HEAD").strip(),
        "status_porcelain": _git(repo, "status", "--porcelain"),
        "reflog_top": _git(repo, "reflog", "-1").strip(),
        "recorded_at": at if at is not None else utc_now_iso(),
    }
    validate_baseline(baseline)
    return baseline


def verify_baseline(repo: str | Path, baseline: dict) -> BaselineVerification:
    """Re-run the §12.3 checks against *baseline* and report the precise diff."""
    current = capture_baseline(repo)
    divergences = [
        Divergence(check, baseline[check], current[check])
        for check in BASELINE_FIELDS[:3]
        if current[check] != baseline[check]
    ]
    return BaselineVerification(passed=not divergences, divergences=divergences)


def validate_baseline(data: dict) -> None:
    """Validate a baseline against the §12.2 schema exactly."""
    if not isinstance(data, dict):
        raise BaselineError("safety baseline must be a JSON object")
    if set(data) != set(BASELINE_FIELDS):
        raise BaselineError(
            f"safety baseline must have exactly the fields {BASELINE_FIELDS}, "
            f"got {list(data)}"
        )
    for field_name in BASELINE_FIELDS:
        value = data[field_name]
        if not isinstance(value, str):
            raise BaselineError(f"safety baseline field {field_name!r} must be a string")
    if not is_iso_utc(data["recorded_at"]):
        raise BaselineError(
            f"recorded_at must be ISO-8601 UTC: {data['recorded_at']!r}"
        )


def save_baseline(baseline: dict, path: str | Path) -> None:
    validate_baseline(baseline)
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        f.write(json.dumps(baseline, indent=2, sort_keys=True) + "\n")


def load_baseline(path: str | Path) -> dict:
    with open(path, encoding="utf-8") as f:
        data = json.load(f)
    validate_baseline(data)
    return data
