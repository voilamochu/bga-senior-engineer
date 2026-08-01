"""Review state persistence (MS-07, MVB-021).

``review/review.json`` is the machine-readable review record: a
versioned schema holding the review status (SCAFFOLDED → IN_PROGRESS →
COMPLETED), the rubric in force, reviewer metadata, the final category
scores and verdict, and the hashes of the review artifacts pinned at
completion.

The record is deterministic — it contains no wall-clock values; review
timestamps are recorded in the run manifest errata at completion
(MS-07 manifest integration) and the scaffold time in the run's harness
log.  This mirrors the MS-06 split (``validation.json`` deterministic,
lifecycle timestamps in the manifest/status).
"""

from __future__ import annotations

import json
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

REVIEW_SCHEMA = "benchmark-harness-review/1.0"
REVIEW_VERSION = "1.0"

VALID_STATUSES = ("SCAFFOLDED", "IN_PROGRESS", "COMPLETED")
# Status graph for the review record.
TRANSITIONS = {
    "SCAFFOLDED": frozenset({"IN_PROGRESS", "COMPLETED"}),
    "IN_PROGRESS": frozenset({"IN_PROGRESS", "COMPLETED"}),
    "COMPLETED": frozenset(),
}


class ReviewStateError(Exception):
    """The review record is invalid or a transition is illegal."""


@dataclass
class ReviewState:
    """Validated in-memory ``review.json``."""

    run_id: str
    task_id: str
    status: str = "SCAFFOLDED"
    review_version: str = REVIEW_VERSION
    schema: str = REVIEW_SCHEMA
    rubric: dict[str, Any] | None = None
    reviewer: str | None = None
    category_scores: dict[str, int] | None = None
    total: float | None = None
    verdict: str | None = None
    critical_failures: list[str] = field(default_factory=list)
    artifact_hashes: dict[str, str] = field(default_factory=dict)

    # ------------------------------------------------------------------
    # Transitions
    # ------------------------------------------------------------------

    def transition(self, new_status: str) -> None:
        if new_status not in VALID_STATUSES:
            raise ReviewStateError(
                f"unknown review status {new_status!r}; expected one of "
                + ", ".join(VALID_STATUSES)
            )
        if new_status not in TRANSITIONS[self.status]:
            raise ReviewStateError(
                f"illegal review status transition {self.status} -> {new_status}"
            )
        self.status = new_status

    def complete(
        self,
        *,
        reviewer: str,
        category_scores: dict[str, int],
        total: float,
        verdict: str,
        critical_failures: list[str],
        artifact_hashes: dict[str, str],
        recompute: bool = False,
    ) -> None:
        """Record the completed review (§7.5 pinning).

        With *recompute* the record is refreshed in place — the §5.1
        arithmetic-error recalculation path, recorded as a correction.
        """
        if recompute:
            if self.status != "COMPLETED":
                raise ReviewStateError(
                    f"recompute requires a completed review, got {self.status}"
                )
        else:
            self.transition("COMPLETED")
        self.reviewer = reviewer
        self.category_scores = dict(category_scores)
        self.total = total
        self.verdict = verdict
        self.critical_failures = sorted(critical_failures)
        self.artifact_hashes = dict(sorted(artifact_hashes.items()))

    def note_attempt(self, reviewer: str | None) -> None:
        """Record that scoring was attempted but not completed (partial).

        A completed review cannot be reopened by a failed attempt —
        recalculation goes through the recorded ``--recompute`` path.
        """
        if self.status == "COMPLETED":
            raise ReviewStateError(
                "review is completed; a scoring attempt cannot reopen it "
                "(use --recompute for a recorded recalculation)"
            )
        self.transition("IN_PROGRESS")
        if reviewer:
            self.reviewer = reviewer

    # ------------------------------------------------------------------
    # Serialization
    # ------------------------------------------------------------------

    def to_dict(self) -> dict[str, Any]:
        record: dict[str, Any] = {
            "schema": self.schema,
            "run_id": self.run_id,
            "task_id": self.task_id,
            "status": self.status,
            "review_version": self.review_version,
            "rubric": dict(self.rubric) if self.rubric else None,
            "reviewer": self.reviewer,
            "critical_failures": list(self.critical_failures),
        }
        if self.category_scores is not None:
            record["category_scores"] = dict(self.category_scores)
        if self.total is not None:
            record["total"] = self.total
        if self.verdict is not None:
            record["verdict"] = self.verdict
        if self.artifact_hashes:
            record["artifact_hashes"] = dict(self.artifact_hashes)
        return record

    @classmethod
    def from_dict(cls, data: dict) -> "ReviewState":
        if not isinstance(data, dict):
            raise ReviewStateError("review record must be a JSON object")
        state = cls(
            schema=data.get("schema", REVIEW_SCHEMA),
            run_id=data.get("run_id", ""),
            task_id=data.get("task_id", ""),
            status=data.get("status", "SCAFFOLDED"),
            review_version=data.get("review_version", REVIEW_VERSION),
            rubric=data.get("rubric"),
            reviewer=data.get("reviewer"),
            category_scores=data.get("category_scores"),
            total=data.get("total"),
            verdict=data.get("verdict"),
            critical_failures=list(data.get("critical_failures", [])),
            artifact_hashes=dict(data.get("artifact_hashes", {})),
        )
        state._validate()
        return state

    def save(self, path: str | Path) -> None:
        Path(path).parent.mkdir(parents=True, exist_ok=True)
        with open(path, "w", encoding="utf-8") as f:
            f.write(json.dumps(self.to_dict(), indent=2, sort_keys=True) + "\n")

    @classmethod
    def load(cls, path: str | Path) -> "ReviewState":
        with open(path, encoding="utf-8") as f:
            data = json.load(f)
        return cls.from_dict(data)

    def _validate(self) -> None:
        if self.schema != REVIEW_SCHEMA:
            raise ReviewStateError(
                f"unsupported review schema {self.schema!r}; expected {REVIEW_SCHEMA!r}"
            )
        if not self.run_id or not self.task_id:
            raise ReviewStateError("review record must carry run_id and task_id")
        if self.status not in VALID_STATUSES:
            raise ReviewStateError(f"unknown review status {self.status!r}")
        if self.verdict is not None and self.verdict not in (
            "EXCELLENT", "ACCEPTABLE", "POOR", "INCORRECT",
        ):
            raise ReviewStateError(f"unknown verdict {self.verdict!r}")


def review_json_path(run) -> Path:
    return run.review / "review.json"
