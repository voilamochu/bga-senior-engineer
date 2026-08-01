"""Shared result record for automatic validation checks (MS-06)."""

from __future__ import annotations

from dataclasses import dataclass, field

# Verdicts produced by gates/checks (milestone MS-06):
#   PASS   — the check executed and its pass criterion is met
#   FAIL   — the check executed and its pass criterion is not met
#   BLOCKED — the check could not execute (missing tool, missing rules,
#             or missing evidence); recorded with a precise reason, and
#             the run stays re-runnable (§5.1 tooling-error re-run)
#   NOT_RUN — the check was short-circuited by a critical failure of an
#             earlier gate (evaluation spec §2.1: gate failure
#             short-circuits later gates)
VERDICTS = ("PASS", "FAIL", "BLOCKED", "NOT_RUN")


@dataclass
class CheckResult:
    """Deterministic result of one automatic validation check.

    ``raw_text`` is the check's raw output; the gate runner persists it
    as ``validation/raw/<check-id>.txt`` (MVB-018).  ``execution_time``
    is recorded by the runner in the run's time-stamped logs only —
    wall-clock values are volatile and are never written into
    ``validation.json``, whose content is byte-identical for identical
    frozen evidence.
    """

    id: str
    name: str
    verdict: str
    blocking: bool
    detail: str = ""
    findings: list[str] = field(default_factory=list)
    raw_text: str = ""
    evidence: list[str] = field(default_factory=list)
    exit_code: int | None = None
    executed_by: str | None = None
    version: str | None = None
    tool: str | None = None
    tool_version: str | None = None
    substituted: bool = False
    substitution_reason: str | None = None
    execution_time: float = 0.0

    def to_dict(self) -> dict:
        """Deterministic serialization (sorted keys, no wall clock)."""
        record: dict = {
            "id": self.id,
            "name": self.name,
            "verdict": self.verdict,
            "blocking": self.blocking,
            "detail": self.detail,
            "findings": list(self.findings),
        }
        if self.raw_text:
            record["raw_output"] = f"raw/{self.id}.txt"
        if self.evidence:
            record["evidence"] = sorted(self.evidence)
        if self.exit_code is not None:
            record["exit_code"] = self.exit_code
        if self.executed_by is not None:
            record["executed_by"] = self.executed_by
        if self.version is not None:
            record["version"] = self.version
        if self.tool is not None:
            record["tool"] = self.tool
        if self.tool_version is not None:
            record["tool_version"] = self.tool_version
        if self.substituted:
            record["substituted"] = True
        if self.substitution_reason is not None:
            record["substitution_reason"] = self.substitution_reason
        return record
