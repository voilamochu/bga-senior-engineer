"""Unit tests for MVB-004: safety baseline capture and G0 verification."""

import json

import pytest

from tooling.harness.safety.baseline import (
    BASELINE_FIELDS,
    BaselineError,
    BaselineVerification,
    capture_baseline,
    load_baseline,
    save_baseline,
    validate_baseline,
    verify_baseline,
)
from tooling.harness.util.clock import is_iso_utc
from tooling.harness.tests.conftest import git


def _git_head(repo):
    return git(repo, "rev-parse", "HEAD").strip()


def test_capture_baseline_matches_section_12_2_schema_exactly(git_repo):
    baseline = capture_baseline(git_repo)
    assert list(baseline) == list(BASELINE_FIELDS)
    assert baseline["head"] == _git_head(git_repo)
    assert baseline["status_porcelain"] == ""
    assert baseline["reflog_top"] == git(git_repo, "reflog", "-1").strip()
    assert "HEAD@{0}:" in baseline["reflog_top"]
    assert is_iso_utc(baseline["recorded_at"])


def test_capture_baseline_records_dirty_state(git_repo):
    (git_repo / "file.txt").write_text("two\n", encoding="utf-8")
    baseline = capture_baseline(git_repo)
    assert " M file.txt" in baseline["status_porcelain"]


def test_verify_passes_when_state_unchanged(git_repo):
    baseline = capture_baseline(git_repo)
    result = verify_baseline(git_repo, baseline)
    assert isinstance(result, BaselineVerification)
    assert result.passed is True
    assert result.divergences == []


def test_verify_detects_second_commit_with_precise_diff(git_repo):
    baseline = capture_baseline(git_repo)
    (git_repo / "file.txt").write_text("two\n", encoding="utf-8")
    git(git_repo, "add", ".")
    git(git_repo, "commit", "-m", "second commit")
    result = verify_baseline(git_repo, baseline)
    assert result.passed is False
    checks = {d.check: d for d in result.divergences}
    assert set(checks) == {"head", "reflog_top"}
    assert checks["head"].expected == baseline["head"]
    assert checks["head"].actual == _git_head(git_repo)
    describe = result.describe()
    assert "head" in describe and "reflog_top" in describe
    assert baseline["head"] in describe
    assert _git_head(git_repo) in describe


def test_verify_detects_working_tree_change(git_repo):
    baseline = capture_baseline(git_repo)
    (git_repo / "file.txt").write_text("changed\n", encoding="utf-8")
    result = verify_baseline(git_repo, baseline)
    assert result.passed is False
    checks = {d.check: d for d in result.divergences}
    assert set(checks) == {"status_porcelain"}
    assert "file.txt" in checks["status_porcelain"].actual
    assert checks["status_porcelain"].expected == ""


def test_verify_detects_reflog_change_without_head_change(git_repo):
    baseline = capture_baseline(git_repo)
    git(git_repo, "checkout", "-b", "feature")
    result = verify_baseline(git_repo, baseline)
    assert result.passed is False
    checks = {d.check: d for d in result.divergences}
    assert set(checks) == {"reflog_top"}
    assert "feature" in checks["reflog_top"].actual


def test_capture_raises_on_non_git_repository(tmp_path):
    with pytest.raises(BaselineError):
        capture_baseline(tmp_path / "missing")


def test_save_and_load_round_trip(git_repo, tmp_path):
    baseline = capture_baseline(git_repo)
    path = tmp_path / "safety-baseline.json"
    save_baseline(baseline, path)
    assert set(json.loads(path.read_text())) == set(BASELINE_FIELDS)
    assert load_baseline(path) == baseline


def test_validate_rejects_bad_schema(git_repo):
    baseline = capture_baseline(git_repo)
    with pytest.raises(BaselineError):
        validate_baseline({k: v for k, v in baseline.items() if k != "head"})
    with pytest.raises(BaselineError):
        validate_baseline({**baseline, "extra": 1})
    with pytest.raises(BaselineError):
        validate_baseline({**baseline, "recorded_at": "not-a-timestamp"})
