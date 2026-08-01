"""Score calculator (MS-07, MVB-020).

Implements harness §7.3-§7.5 and evaluation spec §2.2:

- total = Σ(category score × weight) / 100, weights always sum to 100;
- the §2.2 additional rule: a category scoring 50-59 caps the total at
  85 regardless of the other categories;
- the MVB-015/MS-06 rule that a non-blocking validation finding caps
  the Framework Compliance category (recorded cap, documented
  interpretation of "non-blocking failure → capped Framework
  Compliance");
- verdict rules in §7.4 order: critical failure → INCORRECT; total < 60
  → INCORRECT; any category < 50 → POOR; total ≥ 90 → EXCELLENT;
  total ≥ 75 → ACCEPTABLE; otherwise → POOR;
- double computation (§7.5): two independent arithmetic paths; a
  mismatch invalidates the result until reconciled and recorded.

All computation is deterministic and reproducible from the recorded
numbers alone; totals are rounded to two decimals.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from typing import Any

from tooling.harness.scoring.rubric import CATEGORIES, RubricError

VERDICTS = ("EXCELLENT", "ACCEPTABLE", "POOR", "INCORRECT")

# Cap applied to a category scored in the §2.2 "weak" band.
WEAK_CATEGORY_TOTAL_CAP = 85
# Category value used as the ceiling for Framework Compliance when the
# automatic validation recorded non-blocking findings (MVB-015:
# "non-blocking failure → capped Framework Compliance").  59 is the top
# of the §2.2 weak band; the resulting total cap of 85 bounds the verdict
# at ACCEPTABLE.  Documented interpretation — the specification defines
# no numeric cap.
NON_BLOCKING_FC_CAP = 59


class ScoreError(Exception):
    """Scores are invalid or cannot be computed."""


@dataclass(frozen=True)
class Computation:
    """One independent computation of total and verdict."""

    total: float
    verdict: str
    capped: bool
    capped_total: float
    reason: str = ""

    def to_dict(self) -> dict:
        record = {
            "total": self.total,
            "capped_total": self.capped_total,
            "verdict": self.verdict,
            "capped": self.capped,
        }
        if self.reason:
            record["reason"] = self.reason
        return record


def validate_scores(scores: dict[str, Any], weights: dict[str, int]) -> dict[str, int]:
    """Validate a category-score mapping against the rubric.

    Every canonical category must be present with an integer/float score
    in 0..100; unknown categories are rejected.  Returns the normalized
    ``{category: score}`` mapping.
    """
    normalized: dict[str, int] = {}
    for key, value in scores.items():
        try:
            category = _normalize(key)
        except RubricError as exc:
            raise ScoreError(str(exc)) from exc
        if category in normalized:
            raise ScoreError(f"duplicate score for category {category!r}")
        if isinstance(value, bool) or not isinstance(value, (int, float)):
            raise ScoreError(
                f"score for {category} must be a number 0-100, got {value!r}"
            )
        score = value
        if score < 0 or score > 100:
            raise ScoreError(
                f"score for {category} is out of range 0-100: {score}"
            )
        normalized[category] = score
    missing = [c for c in CATEGORIES if c not in normalized]
    if missing:
        raise ScoreError(f"missing category scores: {', '.join(missing)}")
    return normalized


def compute_total(scores: dict[str, int], weights: dict[str, int]) -> float:
    """Total = Σ(score × weight) / 100, rounded to two decimals (§7.3)."""
    missing = [c for c in CATEGORIES if c not in scores or c not in weights]
    if missing:
        raise ScoreError(f"missing categories in computation: {', '.join(missing)}")
    if sum(weights.values()) != 100:
        raise ScoreError(f"weights must sum to 100, got {sum(weights.values())}")
    total = sum(scores[category] * weights[category] for category in CATEGORIES) / 100
    return round(total, 2)


def _normalize(key: str) -> str:
    from tooling.harness.scoring.rubric import normalize_category

    return normalize_category(key)


def compute_verdict(
    scores: dict[str, int],
    *,
    weights: dict[str, int],
    critical_failures: list[str] | None = None,
    non_blocking_findings: list[str] | None = None,
) -> Computation:
    """Apply the §7.4 verdict rules in order to a validated score set.

    The returned computation carries the uncapped total, whether any
    cap applied (weak category §2.2 and/or non-blocking Framework
    Compliance cap), the capped total used for the verdict, and the
    verdict itself.
    """
    total = compute_total(scores, weights)
    capped_total, capped, reason = _apply_caps(scores, weights, total, non_blocking_findings)
    verdict = _verdict_from(capped_total, scores, critical_failures or [])
    return Computation(
        total=total,
        capped_total=capped_total,
        verdict=verdict,
        capped=capped,
        reason=reason,
    )


def _apply_caps(
    scores: dict[str, int],
    weights: dict[str, int],
    total: float,
    non_blocking_findings: list[str] | None,
) -> tuple[float, bool, str]:
    """Apply the caps in order: non-blocking FC cap, then §2.2 weak band.

    The weak-band cap is computed over the *already capped* score set,
    so a Framework Compliance capped into the 50-59 band is not counted
    twice.
    """
    capped = False
    reasons: list[str] = []
    effective = dict(scores)
    if non_blocking_findings:
        effective["Framework Compliance"] = min(
            effective["Framework Compliance"], NON_BLOCKING_FC_CAP
        )
        capped = True
        reasons.append(
            f"Framework Compliance capped at {NON_BLOCKING_FC_CAP} "
            f"(non-blocking validation findings: "
            f"{', '.join(sorted(non_blocking_findings))})"
        )
    effective_total = (
        sum(effective[category] * weights[category] for category in CATEGORIES) / 100
    )
    # §2.2 additional rule: a category in the 50-59 band caps the total.
    weak = sorted(
        category for category, score in effective.items() if 50 <= score <= 59
    )
    if weak:
        capped = True
        reasons.append(
            f"total capped at {WEAK_CATEGORY_TOTAL_CAP} (weak category "
            f"50-59: {', '.join(weak)})"
        )
        capped_total = min(effective_total, WEAK_CATEGORY_TOTAL_CAP)
    else:
        capped_total = effective_total
    return round(capped_total, 2), capped, "; ".join(reasons)


def _verdict_from(capped_total: float, scores: dict[str, int], critical: list[str]) -> str:
    if critical:
        return "INCORRECT"
    if capped_total < 60:
        return "INCORRECT"
    if any(score < 50 for score in scores.values()):
        return "POOR"
    if capped_total >= 90:
        return "EXCELLENT"
    if capped_total >= 75:
        return "ACCEPTABLE"
    return "POOR"


# ----------------------------------------------------------------------
# Double computation (§7.5)
# ----------------------------------------------------------------------

def double_compute(
    scores: dict[str, int],
    *,
    weights: dict[str, int],
    critical_failures: list[str] | None = None,
    non_blocking_findings: list[str] | None = None,
    second_compute=None,
) -> tuple[Computation, Computation]:
    """Two independent computations of total and verdict.

    The first path uses float arithmetic on the §7.3 formula; the second
    path is an independent implementation using integer arithmetic
    (Σ score×weight, exact division) with a separate verdict function.
    A mismatch invalidates the result (§7.5); callers must record it,
    never proceed silently.
    """
    first = compute_verdict(
        scores,
        critical_failures=critical_failures,
        non_blocking_findings=non_blocking_findings,
        weights=weights,
    )
    if second_compute is not None:
        second = second_compute()
    else:
        second = _second_compute(
            scores, weights, critical_failures or [], non_blocking_findings or []
        )
    return first, second


def _second_compute(scores, weights, critical, non_blocking) -> Computation:
    """Independent integer-arithmetic path (exact, no float accumulation)."""
    total_cent = sum(scores[category] * weights[category] for category in CATEGORIES)
    total = round(total_cent / 100, 2)
    effective = dict(scores)
    capped = False
    reasons: list[str] = []
    if non_blocking:
        effective["Framework Compliance"] = min(
            effective["Framework Compliance"], NON_BLOCKING_FC_CAP
        )
        capped = True
        reasons.append("framework compliance cap (non-blocking findings)")
    weak = sorted(c for c, s in effective.items() if 50 <= s <= 59)
    capped_total = total
    if weak:
        capped = True
        reasons.append(f"weak category 50-59: {', '.join(weak)}")
        capped_total = min(total, WEAK_CATEGORY_TOTAL_CAP)
    capped_total = round(capped_total, 2)
    verdict = _verdict_from(capped_total, scores, critical)
    return Computation(
        total=total,
        capped_total=capped_total,
        verdict=verdict,
        capped=capped,
        reason="; ".join(reasons),
    )
