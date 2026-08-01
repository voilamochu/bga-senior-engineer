"""Tests for the score calculator (MS-07, MVB-020).

Covers the §7.4 verdict-rule branches, the §2.2 50-59 category cap, the
non-blocking Framework Compliance cap, score validation, and the §7.5
double computation (including the recorded-mismatch path).
"""

import pytest

from tooling.harness.scoring.calculator import (
    NON_BLOCKING_FC_CAP,
    ScoreError,
    WEAK_CATEGORY_TOTAL_CAP,
    compute_total,
    compute_verdict,
    double_compute,
    validate_scores,
)

NOTIF = {
    "Correctness": 40,
    "Architecture": 10,
    "Framework Compliance": 25,
    "Maintainability": 15,
    "Testing": 10,
}


def _scores(**values) -> dict:
    from tooling.harness.scoring.rubric import normalize_category

    base = {"Correctness": 80, "Architecture": 80,
            "Framework Compliance": 80, "Maintainability": 80, "Testing": 80}
    for key, value in values.items():
        base[normalize_category(key)] = value
    return base


class TestTotal:
    def test_acceptance_example_total(self):
        scores = {"Correctness": 80, "Architecture": 90,
                  "Framework Compliance": 85, "Maintainability": 70, "Testing": 75}
        assert compute_total(scores, NOTIF) == 80.25

    def test_rounds_to_two_decimals(self):
        assert compute_total({"Correctness": 33, "Architecture": 33,
                              "Framework Compliance": 33, "Maintainability": 33,
                              "Testing": 33}, NOTIF) == 33.0

    def test_missing_category_raises(self):
        with pytest.raises(ScoreError):
            compute_total({"Correctness": 80}, NOTIF)

    def test_weights_must_sum_to_100(self):
        bad = dict(NOTIF)
        bad["Testing"] = 11
        with pytest.raises(ScoreError):
            compute_total(_scores(), bad)


class TestValidateScores:
    def test_valid_scores_pass(self):
        assert validate_scores(_scores(), NOTIF) == _scores()

    def test_framework_compliance_underscore_key(self):
        scores = {"Correctness": 80, "Architecture": 80,
                  "Framework_Compliance": 80, "Maintainability": 80, "Testing": 80}
        assert validate_scores(scores, NOTIF)["Framework Compliance"] == 80

    def test_unknown_category_rejected(self):
        with pytest.raises(ScoreError) as exc:
            validate_scores({**_scores(), "Creativity": 80}, NOTIF)
        assert "unknown rubric category" in str(exc.value)

    def test_missing_category_rejected(self):
        with pytest.raises(ScoreError) as exc:
            validate_scores({"Correctness": 80}, NOTIF)
        assert "missing category scores" in str(exc.value)

    def test_out_of_range_rejected(self):
        with pytest.raises(ScoreError):
            validate_scores(_scores(Correctness=101), NOTIF)
        with pytest.raises(ScoreError):
            validate_scores(_scores(Correctness=-1), NOTIF)

    def test_non_numeric_rejected(self):
        with pytest.raises(ScoreError):
            validate_scores(_scores(Correctness="high"), NOTIF)


class TestVerdictRules:
    """One test per §7.4 rule branch."""

    def test_rule1_critical_failure_incorrect(self):
        result = compute_verdict(_scores(), weights=NOTIF, critical_failures=["C4"])
        assert result.verdict == "INCORRECT"

    def test_rule2_total_below_60_incorrect(self):
        result = compute_verdict(_scores(Correctness=40, Architecture=40,
                                         Framework_Compliance=40,
                                         Maintainability=40, Testing=40),
                                 weights=NOTIF)
        # total = 40.0 < 60
        assert result.total < 60
        assert result.verdict == "INCORRECT"

    def test_rule3_category_below_50_poor(self):
        result = compute_verdict(_scores(Maintainability=45), weights=NOTIF)
        assert result.verdict == "POOR"

    def test_rule4_total_90_excellent(self):
        result = compute_verdict(_scores(Correctness=100, Architecture=90,
                                         Framework_Compliance=90,
                                         Maintainability=90, Testing=90),
                                 weights=NOTIF)
        assert result.total >= 90
        assert result.verdict == "EXCELLENT"

    def test_rule5_total_75_acceptable(self):
        result = compute_verdict(_scores(), weights=NOTIF)  # total 80.0
        assert result.verdict == "ACCEPTABLE"

    def test_rule6_otherwise_poor(self):
        result = compute_verdict(_scores(Correctness=70, Architecture=70,
                                         Framework_Compliance=70,
                                         Maintainability=70, Testing=70),
                                 weights=NOTIF)  # total 70.0
        assert result.verdict == "POOR"


class TestCategoryCap:
    def test_weak_category_caps_total_at_85(self):
        # one category in the 50-59 band, everything else excellent
        result = compute_verdict(
            _scores(Correctness=100, Architecture=100, Framework_Compliance=55,
                    Maintainability=100, Testing=100),
            weights=NOTIF,
        )
        assert result.capped is True
        assert result.capped_total == WEAK_CATEGORY_TOTAL_CAP
        assert result.total > WEAK_CATEGORY_TOTAL_CAP
        assert "weak category" in result.reason
        assert result.verdict == "ACCEPTABLE"  # never EXCELLENT when capped

    def test_weak_category_exactly_50_and_59(self):
        for value in (50, 59):
            result = compute_verdict(_scores(Framework_Compliance=value), weights=NOTIF)
            assert result.capped is True
            assert result.capped_total == min(result.total, 85)

    def test_no_cap_when_no_weak_category(self):
        result = compute_verdict(_scores(Framework_Compliance=60), weights=NOTIF)
        assert result.capped is False
        assert result.capped_total == result.total


class TestNonBlockingCap:
    def test_non_blocking_findings_cap_framework_compliance(self):
        result = compute_verdict(
            _scores(Correctness=90, Architecture=90, Framework_Compliance=95,
                    Maintainability=90, Testing=90),
            weights=NOTIF,
            non_blocking_findings=["NOT02-C"],
        )
        assert result.capped is True
        assert result.reason.startswith("Framework Compliance capped")
        assert "NOT02-C" in result.reason
        # uncapped total 91.25 (EXCELLENT); FC 95 -> 59 => total
        # 59×0.25 + 90×0.75 = 82.25; the 59 is also in the weak band, so the
        # §2.2 cap (min(total, 85)) applies
        assert result.total == 91.25
        assert result.capped_total == 82.25
        assert result.verdict == "ACCEPTABLE"  # findings bound the verdict
        # without the cap the same scores are EXCELLENT
        uncapped = compute_verdict(
            _scores(Correctness=90, Architecture=90, Framework_Compliance=95,
                    Maintainability=90, Testing=90),
            weights=NOTIF,
            non_blocking_findings=[],
        )
        assert uncapped.verdict == "EXCELLENT"

    def test_no_findings_no_cap(self):
        result = compute_verdict(_scores(), weights=NOTIF, non_blocking_findings=[])
        assert result.capped is False


class TestDoubleComputation:
    def test_independent_paths_match(self):
        scores = {"Correctness": 80, "Architecture": 90,
                  "Framework Compliance": 85, "Maintainability": 70, "Testing": 75}
        first, second = double_compute(scores, weights=NOTIF)
        assert first.to_dict() == second.to_dict()
        assert first.verdict == "ACCEPTABLE"
        assert first.total == 80.25

    def test_mismatch_is_surfaced_not_silent(self):
        scores = _scores()

        def broken():
            from tooling.harness.scoring.calculator import Computation

            return Computation(
                total=1.0, capped_total=1.0, verdict="INCORRECT", capped=False,
                reason="injected fault",
            )

        first, second = double_compute(scores, weights=NOTIF, second_compute=broken)
        assert first.to_dict() != second.to_dict()

    def test_critical_failure_propagates_to_both_paths(self):
        scores = _scores()
        first, second = double_compute(
            scores, weights=NOTIF, critical_failures=["C4"]
        )
        assert first.verdict == "INCORRECT"
        assert second.verdict == "INCORRECT"


def test_deterministic_computation():
    scores = {"Correctness": 80, "Architecture": 90,
              "Framework Compliance": 85, "Maintainability": 70, "Testing": 75}
    a = compute_verdict(scores, weights=NOTIF).to_dict()
    b = compute_verdict(scores, weights=NOTIF).to_dict()
    assert a == b
