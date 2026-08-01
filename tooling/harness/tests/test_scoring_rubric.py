"""Tests for the scoring rubric resolution (MS-07, MVB-020)."""

import pytest

from tooling.harness.scoring.rubric import (
    CATEGORIES,
    RubricError,
    family_weights,
    normalize_category,
    task_family,
    task_weights,
)

EVAL_DOC = "docs/evaluation/benchmark-evaluation-spec.md"

NOTIF_WEIGHTS = {
    "Correctness": 40,
    "Architecture": 10,
    "Framework Compliance": 25,
    "Maintainability": 15,
    "Testing": 10,
}


def test_not_02_family_and_weights():
    assert task_family(EVAL_DOC, "NOT-02") == "NOTIF"
    assert task_weights(EVAL_DOC, "NOT-02") == NOTIF_WEIGHTS


def test_weights_sum_to_100():
    assert sum(task_weights(EVAL_DOC, "NOT-02").values()) == 100


def test_inline_weights_are_resolved():
    # ARC-01's rubric header carries the weights inline (§3.1)
    weights = task_weights(EVAL_DOC, "ARC-01")
    assert weights["Correctness"] == 30
    assert weights["Architecture"] == 35
    assert sum(weights.values()) == 100


def test_family_table_lookup():
    assert family_weights(EVAL_DOC, "NOTIF") == NOTIF_WEIGHTS


def test_unknown_task_raises():
    with pytest.raises(RubricError):
        task_weights(EVAL_DOC, "NOPE-99")


def test_unknown_family_raises():
    with pytest.raises(RubricError):
        family_weights(EVAL_DOC, "NOPE")


def test_normalize_category():
    assert normalize_category("Framework_Compliance") == "Framework Compliance"
    assert normalize_category("correctness") == "Correctness"
    assert normalize_category("Testing") == "Testing"


def test_normalize_unknown_category_raises():
    with pytest.raises(RubricError):
        normalize_category("Creativity")


def test_categories_are_exactly_the_five():
    assert CATEGORIES == (
        "Correctness",
        "Architecture",
        "Framework Compliance",
        "Maintainability",
        "Testing",
    )
