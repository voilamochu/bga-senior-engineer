"""Score and review-record persistence (MS-07, MVB-021).

Writes the §9.1 review/scoring artifacts deterministically:

- ``review/scoring/scores.json`` — the recorded category scores, the
  §7.3 arithmetic, the applied caps, the verdict, and the evidence
  citations; byte-identical for identical inputs (no wall-clock values).
- ``review/scoring/score-verification.json`` — the §7.5 double
  computation record: both independent computations, the match verdict,
  and (on mismatch) the recorded invalidation.
"""

from __future__ import annotations

import json
from dataclasses import dataclass, field
from pathlib import Path

from tooling.harness.scoring.calculator import Computation
from tooling.harness.scoring.rubric import CATEGORIES

SCORES_SCHEMA = "benchmark-harness-scores/1.0"
VERIFICATION_SCHEMA = "benchmark-harness-score-verification/1.0"


class PersistError(Exception):
    """A scoring record cannot be written or read."""


@dataclass
class CategoryRecord:
    """One reviewed category: score, citations, reviewer notes."""

    category: str
    score: int | None = None
    evidence: list[str] = field(default_factory=list)
    comments: str = ""
    deductions: str = ""
    uncertainty: str = ""
    critical_failure: bool = False

    def to_dict(self) -> dict:
        record: dict = {"category": self.category, "evidence": sorted(self.evidence)}
        if self.score is not None:
            record["score"] = self.score
        if self.comments:
            record["comments"] = self.comments
        if self.deductions:
            record["deductions"] = self.deductions
        if self.uncertainty:
            record["uncertainty"] = self.uncertainty
        if self.critical_failure:
            record["critical_failure"] = True
        return record


def build_scores_document(
    *,
    run_id: str,
    task_id: str,
    family: str,
    weights: dict[str, int],
    categories: list[CategoryRecord],
    computation: Computation,
    critical_failures: list[str],
    non_blocking_findings: list[str],
) -> dict:
    """Deterministic ``scores.json`` content (§7.3-§7.4)."""
    by_category = {record.category: record for record in categories}
    if set(by_category) != set(CATEGORIES):
        missing = [c for c in CATEGORIES if c not in by_category]
        raise PersistError(f"missing category records: {', '.join(missing)}")
    weights_sorted = {category: weights[category] for category in CATEGORIES}
    return {
        "schema": SCORES_SCHEMA,
        "run_id": run_id,
        "task_id": task_id,
        "rubric": {
            "family": family,
            "weights": weights_sorted,
            "weights_sum": sum(weights.values()),
        },
        "category_scores": {
            record.category: {
                k: v for k, v in record.to_dict().items() if k != "category"
            }
            for record in categories
        },
        "arithmetic": {
            "formula": "total = sum(score * weight) / 100",
            "total": computation.total,
            "capped_total": computation.capped_total,
            "caps_applied": computation.capped,
            "cap_reasons": computation.reason,
            "category_cap_50_59": WEAK_BAND_LABEL,
        },
        "critical_failures": sorted(critical_failures),
        "non_blocking_findings": sorted(non_blocking_findings),
        "verdict": computation.verdict,
    }


WEAK_BAND_LABEL = "a category scoring 50-59 caps the total at 85 (eval spec §2.2)"


def build_verification_document(
    *,
    run_id: str,
    first: Computation,
    second: Computation,
    matched: bool,
    reconciliation: str = "",
) -> dict:
    """Deterministic ``score-verification.json`` content (§7.5)."""
    document = {
        "schema": VERIFICATION_SCHEMA,
        "run_id": run_id,
        "double_computation": {
            "first": first.to_dict(),
            "second": second.to_dict(),
            "matched": matched,
        },
        "status": "MATCHED" if matched else "MISMATCH",
    }
    if reconciliation:
        document["reconciliation"] = reconciliation
    return document


def write_scores(run, document: dict) -> Path:
    """Write ``review/scoring/scores.json`` (deterministic JSON)."""
    return _write(run.review_scoring / "scores.json", document)


def write_verification(run, document: dict) -> Path:
    """Write ``review/scoring/score-verification.json``."""
    return _write(run.review_scoring / "score-verification.json", document)


def _write(path: Path, document: dict) -> Path:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(document, indent=2, sort_keys=True) + "\n", encoding="utf-8")
    return path


def load_scores(path: str | Path) -> dict:
    with open(path, encoding="utf-8") as f:
        data = json.load(f)
    if data.get("schema") != SCORES_SCHEMA:
        raise PersistError(f"unsupported scores schema: {data.get('schema')!r}")
    return data
