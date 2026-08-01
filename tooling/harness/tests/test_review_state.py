"""Tests for the review state record (MS-07, MVB-021)."""

import json
from pathlib import Path

import pytest

from tooling.harness.review.state import (
    REVIEW_SCHEMA,
    REVIEW_VERSION,
    ReviewState,
    ReviewStateError,
    review_json_path,
)
from tooling.harness.runtime.run_dir import create_run_dir

RUBRIC = {
    "family": "NOTIF",
    "weights": {"Correctness": 40, "Architecture": 10,
                "Framework Compliance": 25, "Maintainability": 15,
                "Testing": 10},
    "weights_sum": 100,
    "evaluation_version": "1.0",
}


def _state(run_id="run-NOT-02-demo-model-20260731T120000Z-00", task_id="NOT-02"):
    return ReviewState(run_id=run_id, task_id=task_id, rubric=dict(RUBRIC))


class TestTransitions:
    def test_scaffolded_to_completed(self):
        state = _state()
        state.complete(
            reviewer="evaluator-1", category_scores={"Correctness": 80},
            total=80.0, verdict="ACCEPTABLE", critical_failures=[],
            artifact_hashes={"review/manual-review.md": "a" * 64},
        )
        assert state.status == "COMPLETED"

    def test_scaffolded_to_in_progress_and_later_completed(self):
        state = _state()
        state.note_attempt(reviewer=None)
        assert state.status == "IN_PROGRESS"
        state.note_attempt(reviewer="evaluator-2")
        assert state.reviewer == "evaluator-2"
        state.complete(
            reviewer="evaluator-2", category_scores={"Correctness": 80},
            total=80.0, verdict="ACCEPTABLE", critical_failures=[],
            artifact_hashes={},
        )
        assert state.status == "COMPLETED"

    def test_completed_is_terminal(self):
        state = _state()
        state.complete(
            reviewer="e", category_scores={"Correctness": 80}, total=80.0,
            verdict="ACCEPTABLE", critical_failures=[], artifact_hashes={},
        )
        with pytest.raises(ReviewStateError):
            state.note_attempt(reviewer=None)

    def test_illegal_transition_raises(self):
        state = _state()
        state.status = "COMPLETED"
        with pytest.raises(ReviewStateError):
            state.transition("IN_PROGRESS")

    def test_recompute_refreshes_completed_record(self):
        state = _state()
        state.complete(
            reviewer="e", category_scores={"Correctness": 80}, total=80.0,
            verdict="ACCEPTABLE", critical_failures=[], artifact_hashes={},
        )
        state.complete(
            reviewer="e", category_scores={"Correctness": 81}, total=81.0,
            verdict="ACCEPTABLE", critical_failures=[],
            artifact_hashes={"review/manual-review.md": "b" * 64},
            recompute=True,
        )
        assert state.total == 81.0
        assert state.artifact_hashes["review/manual-review.md"] == "b" * 64

    def test_recompute_requires_completed(self):
        state = _state()
        with pytest.raises(ReviewStateError):
            state.complete(
                reviewer="e", category_scores={}, total=1.0, verdict="POOR",
                critical_failures=[], artifact_hashes={}, recompute=True,
            )


class TestSerialization:
    def test_round_trip(self, tmp_path):
        run = create_run_dir("NOT-02", "demo-model", tmp_path / "runs")
        state = _state(run.run_id)
        state.complete(
            reviewer="evaluator-1", category_scores={"Correctness": 80},
            total=80.0, verdict="ACCEPTABLE", critical_failures=["C4"],
            artifact_hashes={"review/manual-review.md": "a" * 64},
        )
        path = review_json_path(run)
        state.save(path)
        loaded = ReviewState.load(path)
        assert loaded.to_dict() == state.to_dict()
        assert loaded.verdict == "ACCEPTABLE"
        assert loaded.reviewer == "evaluator-1"

    def test_schema_and_version(self):
        state = _state()
        record = state.to_dict()
        assert record["schema"] == REVIEW_SCHEMA
        assert record["review_version"] == REVIEW_VERSION

    def test_deterministic_content(self):
        """The review record carries no wall-clock values."""
        record = _state().to_dict()
        text = json.dumps(record, sort_keys=True)
        assert "started_at" not in text
        assert "completed_at" not in text
        assert "Timestamp" not in text

    def test_rejects_unknown_schema(self, tmp_path):
        path = tmp_path / "review.json"
        path.write_text(json.dumps({"schema": "benchmark-harness-review/9.9"}),
                        encoding="utf-8")
        with pytest.raises(ReviewStateError):
            ReviewState.load(path)

    def test_rejects_unknown_verdict(self):
        record = _state().to_dict()
        record["verdict"] = "SORTED"
        with pytest.raises(ReviewStateError):
            ReviewState.from_dict(record)


def test_save_is_deterministic_json(tmp_path):
    run = create_run_dir("NOT-02", "demo-model", tmp_path / "runs")
    state = _state(run.run_id)
    path = review_json_path(run)
    state.save(path)
    assert json.loads(path.read_text(encoding="utf-8"))["status"] == "SCAFFOLDED"
    # sorted keys, newline-terminated
    text = path.read_text(encoding="utf-8")
    assert text.endswith("\n")
    assert text.index('"run_id"') < text.index('"status"')
