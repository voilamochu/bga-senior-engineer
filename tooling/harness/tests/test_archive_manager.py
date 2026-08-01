"""Tests for the archive manager and result packaging (MS-08, MVB-023/024)."""

import json

import pytest

from tooling.harness.archive.manager import (
    ARCHIVE_SCHEMA,
    ArchiveError,
    append_registry_entry,
    archive_run,
    leaderboard_path,
    load_leaderboard,
    load_registry,
    registry_path,
    verify_archive,
)
from tooling.harness.archive.packaging import (
    EXPECTED_TOP_LEVEL,
    verify_packaging,
)
from tooling.harness.report.generator import generate_reports
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.status import RunStatus
from tooling.harness.tests.review_fixtures import rejected_run, reviewed_run


def _archive(run, manifest, status, runs_root, git_repo):
    return archive_run(run, manifest, status, runs_root=runs_root,
                       reference_root=git_repo)


class TestArchive:
    def test_archive_creates_marker_registry_and_leaderboard(
        self, tmp_path, git_repo
    ):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        outcome = _archive(run, manifest, status, tmp_path, git_repo)
        marker = run.root / "ARCHIVED"
        assert marker.is_file()
        assert ARCHIVE_SCHEMA in marker.read_text(encoding="utf-8")
        assert run.run_id in marker.read_text(encoding="utf-8")

        registry = load_registry(tmp_path)
        assert len(registry) == 1
        entry = registry[0]
        assert entry["run_id"] == run.run_id
        assert entry["status"] == "ARCHIVED"
        assert entry["verdict"] == "ACCEPTABLE"
        assert entry["total"] == 80.25
        assert entry["version_tuple"] == {
            "corpus": "1.0", "evaluation": "1.0", "harness": "1.0",
        }

        leaderboard = load_leaderboard(tmp_path, entry["version_tuple"])
        assert len(leaderboard) == 1
        assert leaderboard[0]["run_id"] == run.run_id
        assert leaderboard[0]["total"] == 80.25
        assert leaderboard[0]["task_id"] == "NOT-02"
        assert set(leaderboard[0]) >= {
            "run_id", "model", "model_version", "task_id", "difficulty",
            "category_scores", "total", "verdict", "version_tuple",
        }

        assert RunStatus.load(run.status_path).status == "ARCHIVED"
        assert "p9" in RunStatus.load(run.status_path).checkpoints

    def test_registry_is_append_only(self, tmp_path, git_repo, monkeypatch):
        # distinct fixed run timestamps: two runs created in the same
        # second would otherwise collide on the run ID
        from datetime import datetime, timezone

        times = iter([
            datetime(2026, 1, 1, 12, 0, 0, tzinfo=timezone.utc),
            datetime(2026, 1, 1, 12, 0, 1, tzinfo=timezone.utc),
        ])
        monkeypatch.setattr(
            "tooling.harness.runtime.run_dir.utc_now", lambda: next(times)
        )
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        first = json.loads((tmp_path / "index.json").read_text(encoding="utf-8"))
        # a second run appends without touching the first entry
        run2, manifest2, status2 = reviewed_run(
            tmp_path / "second", reference_repo=git_repo
        )
        generate_reports(run2, manifest2, status2)
        _archive(run2, manifest2, status2, tmp_path, git_repo)
        second = json.loads((tmp_path / "index.json").read_text(encoding="utf-8"))
        assert len(second["entries"]) == 2
        assert second["entries"][0] == first["entries"][0]

    def test_rearchive_rejected(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        with pytest.raises(ArchiveError) as exc:
            _archive(run, manifest, status, tmp_path, git_repo)
        assert "already archived" in str(exc.value)

    def test_archive_requires_reports(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        with pytest.raises(ArchiveError) as exc:
            _archive(run, manifest, status, tmp_path, git_repo)
        assert "report" in str(exc.value)

    def test_archive_refuses_unfinished_run(self, tmp_path, git_repo):
        from tooling.harness.tests.review_fixtures import scaffolded_run

        run, manifest, status = scaffolded_run(tmp_path, reference_repo=git_repo)
        with pytest.raises(ArchiveError) as exc:
            _archive(run, manifest, status, tmp_path, git_repo)
        assert "status" in str(exc.value)

    def test_rejected_run_archive_has_no_leaderboard_entry(self, tmp_path, git_repo):
        run, manifest, status = rejected_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        outcome = _archive(run, manifest, status, tmp_path, git_repo)
        assert outcome["leaderboard_entry"] is None
        registry = load_registry(tmp_path)
        assert registry[0]["verdict"] == "REJECTED"
        assert registry[0]["total"] is None
        tuple_key = {"corpus": "1.0", "evaluation": "1.0", "harness": "1.0"}
        assert load_leaderboard(tmp_path, tuple_key) == []

    def test_archive_verifies_frozen_evidence_first(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        (run.evidence / "e1-transcript.txt").write_text("corrupted", encoding="utf-8")
        with pytest.raises(ArchiveError) as exc:
            _archive(run, manifest, status, tmp_path, git_repo)
        assert "frozen evidence verification failed" in str(exc.value)

    def test_archive_errata_records_version_and_hashes(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        errata = RunManifest.load(run.manifest_path).errata[-1]["message"]
        assert "P9 archive" in errata
        assert "archive_version=1.0" in errata
        assert "ARCHIVED marker sha256=" in errata
        assert "index.json sha256=" in errata
        assert "leaderboard.json sha256=" in errata


class TestVerifyArchive:
    def test_verify_passes_for_clean_archive(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        result = verify_archive(run, manifest, status, runs_root=tmp_path)
        assert result["passed"] is True
        assert result["divergences"] == []

    def test_verify_detects_missing_marker(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        (run.root / "ARCHIVED").unlink()
        result = verify_archive(run, manifest, status, runs_root=tmp_path)
        assert result["passed"] is False
        assert any("ARCHIVED" in d for d in result["divergences"])

    def test_verify_detects_missing_registry_entry(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        (tmp_path / "index.json").unlink()
        result = verify_archive(run, manifest, status, runs_root=tmp_path)
        assert any("registry entry" in d for d in result["divergences"])

    def test_verify_detects_corrupted_evidence(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        (run.evidence / "e1-transcript.txt").write_text("tampered", encoding="utf-8")
        result = verify_archive(run, manifest, status, runs_root=tmp_path)
        assert result["passed"] is False
        assert any("hash mismatch" in d for d in result["divergences"])

    def test_verify_detects_missing_reports(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        (run.reports / "report.md").unlink()
        result = verify_archive(run, manifest, status, runs_root=tmp_path)
        assert any("reports are missing" in d for d in result["divergences"])

    def test_verify_detects_tampered_report_vs_recorded_hash(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        report = run.reports / "evaluation-report.json"
        doc = json.loads(report.read_text(encoding="utf-8"))
        doc["verdict"]["verdict"] = "POOR"
        report.write_text(json.dumps(doc, indent=2, sort_keys=True) + "\n",
                          encoding="utf-8")
        result = verify_archive(run, manifest, status, runs_root=tmp_path)
        assert any("do not match the hashes recorded" in d for d in result["divergences"])

    def test_verify_detects_stray_file(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        (run.root / "unexpected.txt").write_text("x", encoding="utf-8")
        result = verify_archive(run, manifest, status, runs_root=tmp_path)
        assert any("unexpected top-level entry" in d for d in result["divergences"])


class TestPackaging:
    def test_canonical_layout_is_expected(self):
        assert set(EXPECTED_TOP_LEVEL) == {
            "manifest.json", "status.json", "protocol", "workspace",
            "evidence", "validation", "review", "reports", "ARCHIVED",
        }

    def test_workspace_is_excluded_from_verification_scope(self, tmp_path, git_repo):
        """Mutable workspace state is not packageable content; stray files
        inside workspace/ are not flagged (their snapshot is evidence E2)."""
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        (run.workspace_work / "agent-scratch.tmp").write_text("x", encoding="utf-8")
        assert verify_packaging(run) == []

    def test_cache_and_temp_files_flagged(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        (run.validation / "__pycache__").mkdir()
        (run.validation / "__pycache__" / "x.pyc").write_text("", encoding="utf-8")
        (run.reports / "draft.tmp").write_text("x", encoding="utf-8")
        divergences = verify_packaging(run)
        assert any("__pycache__" in d for d in divergences)
        assert any("draft.tmp" in d for d in divergences)

    def test_stray_top_level_flagged(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        (run.root / "scratch.txt").write_text("x", encoding="utf-8")
        divergences = verify_packaging(run)
        assert any("unexpected top-level entry" in d for d in divergences)

    def test_clean_run_passes_packaging(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        assert verify_packaging(run) == []


class TestRegistryHelpers:
    def test_missing_registry_loads_empty(self, tmp_path):
        assert load_registry(tmp_path) == []

    def test_append_is_append_only(self, tmp_path):
        append_registry_entry(tmp_path, {"run_id": "a", "status": "ARCHIVED"})
        append_registry_entry(tmp_path, {"run_id": "b", "status": "ARCHIVED"})
        registry = load_registry(tmp_path)
        assert [e["run_id"] for e in registry] == ["a", "b"]
        assert (tmp_path / "index.json").is_file()

    def test_leaderboard_path_uses_version_tuple(self, tmp_path):
        path = leaderboard_path(tmp_path, {"corpus": "1.0", "evaluation": "1.0",
                                           "harness": "1.0"})
        assert path == tmp_path / "leaderboard" / "1.0-1.0-1.0" / "leaderboard.json"
