"""Tests for scoring record persistence (MS-07, MVB-021)."""

import json

import pytest

from tooling.harness.runtime.run_dir import create_run_dir
from tooling.harness.scoring.calculator import Computation, compute_verdict
from tooling.harness.scoring.persist import (
    CategoryRecord,
    PersistError,
    build_scores_document,
    build_verification_document,
    load_scores,
    write_scores,
    write_verification,
)
from tooling.harness.tests.review_fixtures import (
    ACCEPTANCE_SCORES,
    fill_review_md,
    make_gated_run,
)
from tooling.harness.review.kit import scaffold_review
from tooling.harness.scoring.runner import run_scoring

NOTIF = {
    "Correctness": 40,
    "Architecture": 10,
    "Framework Compliance": 25,
    "Maintainability": 15,
    "Testing": 10,
}

CATEGORY_RECORDS = [
    CategoryRecord(category="Correctness", score=80,
                   evidence=["evidence/e1-transcript.txt"], comments="ok"),
    CategoryRecord(category="Architecture", score=90,
                   evidence=["evidence/e1-transcript.txt"]),
    CategoryRecord(category="Framework Compliance", score=85,
                   evidence=["evidence/e8-diff-bundle/modules/php/Game.php"]),
    CategoryRecord(category="Maintainability", score=70,
                   evidence=["evidence/e1-transcript.txt"], uncertainty="low"),
    CategoryRecord(category="Testing", score=75,
                   evidence=["evidence/e1-transcript.txt"], deductions="no unit tests"),
]


def _document(computation):
    return build_scores_document(
        run_id="run-NOT-02-demo-model-20260731T120000Z-00",
        task_id="NOT-02",
        family="NOTIF",
        weights=NOTIF,
        categories=CATEGORY_RECORDS,
        computation=computation,
        critical_failures=[],
        non_blocking_findings=[],
    )


class TestScoresDocument:
    def test_schema_and_fields(self):
        computation = compute_verdict(
            {r.category: r.score for r in CATEGORY_RECORDS}, weights=NOTIF
        )
        doc = _document(computation)
        assert doc["schema"] == "benchmark-harness-scores/1.0"
        assert doc["run_id"].startswith("run-NOT-02-")
        assert doc["rubric"] == {
            "family": "NOTIF",
            "weights": NOTIF,
            "weights_sum": 100,
        }
        assert set(doc["category_scores"]) == set(NOTIF)
        assert doc["arithmetic"]["total"] == 80.25
        assert doc["verdict"] == "ACCEPTABLE"

    def test_missing_category_record_raises(self):
        with pytest.raises(PersistError) as exc:
            build_scores_document(
                run_id="r", task_id="NOT-02", family="NOTIF", weights=NOTIF,
                categories=CATEGORY_RECORDS[:-1],
                computation=Computation(80.0, "ACCEPTABLE", False, 80.0),
                critical_failures=[], non_blocking_findings=[],
            )
        assert "missing category records" in str(exc.value)

    def test_deterministic_serialization(self):
        computation = compute_verdict(
            {r.category: r.score for r in CATEGORY_RECORDS}, weights=NOTIF
        )
        first = json.dumps(_document(computation), sort_keys=True)
        second = json.dumps(_document(computation), sort_keys=True)
        assert first == second


class TestVerificationDocument:
    def test_matched_record(self):
        doc = build_verification_document(
            run_id="run-x-00",
            first=Computation(80.25, "ACCEPTABLE", False, 80.25),
            second=Computation(80.25, "ACCEPTABLE", False, 80.25),
            matched=True,
        )
        assert doc["schema"] == "benchmark-harness-score-verification/1.0"
        assert doc["status"] == "MATCHED"
        assert doc["double_computation"]["matched"] is True
        assert doc["double_computation"]["first"]["total"] == 80.25

    def test_mismatch_record_is_explicit(self):
        doc = build_verification_document(
            run_id="run-x-00",
            first=Computation(80.25, "ACCEPTABLE", False, 80.25),
            second=Computation(1.0, "INCORRECT", False, 1.0),
            matched=False,
        )
        assert doc["status"] == "MISMATCH"
        assert doc["double_computation"]["matched"] is False


class TestWriteAndLoad:
    def test_scores_round_trip(self, tmp_path):
        run = create_run_dir("NOT-02", "demo-model", tmp_path / "runs")
        computation = compute_verdict(
            {r.category: r.score for r in CATEGORY_RECORDS}, weights=NOTIF
        )
        path = write_scores(run, _document(computation))
        assert path == run.review_scoring / "scores.json"
        loaded = load_scores(path)
        assert loaded["verdict"] == "ACCEPTABLE"

    def test_verification_round_trip(self, tmp_path):
        run = create_run_dir("NOT-02", "demo-model", tmp_path / "runs")
        doc = build_verification_document(
            run_id=run.run_id,
            first=Computation(80.25, "ACCEPTABLE", False, 80.25),
            second=Computation(80.25, "ACCEPTABLE", False, 80.25),
            matched=True,
        )
        path = write_verification(run, doc)
        assert path.is_file()
        assert json.loads(path.read_text(encoding="utf-8"))["status"] == "MATCHED"

    def test_scores_have_no_wall_clock_values(self, tmp_path):
        run = create_run_dir("NOT-02", "demo-model", tmp_path / "runs")
        computation = compute_verdict(
            {r.category: r.score for r in CATEGORY_RECORDS}, weights=NOTIF
        )
        path = write_scores(run, _document(computation))
        text = path.read_text(encoding="utf-8")
        assert "Timestamp" not in text
        assert "started_at" not in text
        assert "completed_at" not in text


def test_identical_runs_produce_byte_identical_scoring_records(
    tmp_path, git_repo, monkeypatch
):
    """Two identical gated+scaffolded+scored runs -> identical records."""
    from datetime import datetime, timezone

    from tooling.harness.tests.validation_fixtures import AT

    # identical run identity for both runs (same-second run ID)
    monkeypatch.setattr(
        "tooling.harness.runtime.run_dir.utc_now",
        lambda: datetime(2026, 8, 1, 12, 0, 0, tzinfo=timezone.utc),
    )
    results = []
    for name in ("a", "b"):
        run, manifest, status = make_gated_run(
            tmp_path / name, reference_repo=git_repo
        )
        scaffold_review(run, manifest, status)
        fill_review_md(run)
        run_scoring(run, manifest, status, scores_json=ACCEPTANCE_SCORES,
                    reviewer="evaluator-1")
        results.append(
            (
                (run.review_scoring / "scores.json").read_bytes(),
                (run.review_scoring / "score-verification.json").read_bytes(),
            )
        )
    assert results[0][0] == results[1][0]
    assert results[0][1] == results[1][1]
