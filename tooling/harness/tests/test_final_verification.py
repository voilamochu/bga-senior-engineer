"""Tests for the §13 final verification (MS-09.5, MVB-024).

Covers the four items (no modified files, no created files, no git
metadata changes, artifact inventory), the archive integration
(``ARCHIVED`` blocked on failure, run left in its pre-archive state),
the report integration (``report.md`` §13 section), and the manifest
errata record.  All tests run against scratch git repositories; the
reference repository is never touched.
"""

import json

import pytest

from tooling.harness.archive.manager import (
    ArchiveError,
    archive_run,
    load_registry,
    verify_archive,
)
from tooling.harness.report.generator import generate_reports
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.status import RunStatus
from tooling.harness.safety.final_verify import (
    FINAL_VERIFICATION_SCHEMA,
    final_verification_record_path,
    load_final_verification,
    run_final_verification,
    save_final_verification,
)
from tooling.harness.tests.conftest import git
from tooling.harness.tests.review_fixtures import rejected_run, reviewed_run


def _item_verdicts(record):
    return {item["id"]: item["verdict"] for item in record["items"]}


def _archive(run, manifest, status, runs_root, reference):
    return archive_run(run, manifest, status, runs_root=runs_root,
                       reference_root=reference)


def _record(run):
    return json.loads(final_verification_record_path(run).read_text(encoding="utf-8"))


class TestFinalVerificationUnit:
    def test_passing_verification_records_all_four_items(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        record = run_final_verification(run, reference_root=git_repo)
        assert record["schema"] == FINAL_VERIFICATION_SCHEMA
        assert record["run_id"] == run.run_id
        assert record["passed"] is True
        assert record["divergences"] == []
        assert _item_verdicts(record) == {
            "FV-1": "PASS", "FV-2": "PASS", "FV-3": "PASS", "FV-4": "PASS",
        }
        assert record["reference_head"] == git(git_repo, "rev-parse", "HEAD").strip()

    def test_modified_tracked_file_fails_fv1(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        (git_repo / "file.txt").write_text("two\n", encoding="utf-8")
        record = run_final_verification(run, reference_root=git_repo)
        assert record["passed"] is False
        verdicts = _item_verdicts(record)
        assert verdicts["FV-1"] == "FAIL"
        assert verdicts["FV-2"] == "PASS"
        assert verdicts["FV-3"] == "PASS"
        assert any("file.txt" in d for d in record["divergences"])

    def test_untracked_file_fails_fv2(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        (git_repo / "created.txt").write_text("new\n", encoding="utf-8")
        record = run_final_verification(run, reference_root=git_repo)
        assert record["passed"] is False
        verdicts = _item_verdicts(record)
        assert verdicts["FV-1"] == "PASS"
        assert verdicts["FV-2"] == "FAIL"
        assert verdicts["FV-3"] == "PASS"
        assert any("created.txt" in d for d in record["divergences"])

    def test_changed_git_metadata_fails_fv3(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        (git_repo / "file.txt").write_text("two\n", encoding="utf-8")
        git(git_repo, "add", ".")
        git(git_repo, "commit", "-m", "second commit")
        record = run_final_verification(run, reference_root=git_repo)
        assert record["passed"] is False
        verdicts = _item_verdicts(record)
        assert verdicts["FV-1"] == "PASS"
        assert verdicts["FV-2"] == "PASS"
        assert verdicts["FV-3"] == "FAIL"
        assert any("HEAD differs" in d for d in record["divergences"])

    def test_missing_baseline_blocks_all_git_items(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        (run.baseline / "safety-baseline.json").unlink()
        record = run_final_verification(run, reference_root=git_repo)
        assert record["passed"] is False
        verdicts = _item_verdicts(record)
        assert verdicts == {
            "FV-1": "BLOCKED", "FV-2": "BLOCKED", "FV-3": "BLOCKED",
            "FV-4": "PASS",
        }
        assert any("baseline" in d for d in record["divergences"])

    def test_missing_reference_repo_blocks_all_git_items(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        record = run_final_verification(
            run, reference_root=tmp_path / "no-such-checkout"
        )
        assert record["passed"] is False
        verdicts = _item_verdicts(record)
        assert verdicts["FV-1"] == "BLOCKED"
        assert verdicts["FV-4"] == "PASS"
        assert any("git checkout" in d for d in record["divergences"])

    def test_stray_run_artifact_fails_fv4(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        (run.validation / "scratch.tmp").write_text("x", encoding="utf-8")
        record = run_final_verification(run, reference_root=git_repo)
        assert record["passed"] is False
        assert _item_verdicts(record)["FV-4"] == "FAIL"

    def test_record_is_deterministic(self, tmp_path, git_repo, monkeypatch):
        from datetime import datetime, timezone

        fixed = datetime(2026, 1, 1, 12, 0, 0, tzinfo=timezone.utc)
        monkeypatch.setattr(
            "tooling.harness.runtime.run_dir.utc_now", lambda: fixed
        )
        run_a, _, _ = reviewed_run(tmp_path / "a", reference_repo=git_repo)
        run_b, _, _ = reviewed_run(tmp_path / "b", reference_repo=git_repo)
        assert run_a.run_id == run_b.run_id
        first = json.dumps(run_final_verification(run_a, reference_root=git_repo),
                           indent=2, sort_keys=True)
        second = json.dumps(run_final_verification(run_b, reference_root=git_repo),
                            indent=2, sort_keys=True)
        assert first == second


class TestSaveFinalVerification:
    def test_persists_record_and_errata(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        record = run_final_verification(run, reference_root=git_repo)
        path = save_final_verification(run, manifest, record, at="2026-08-01T12:00:00Z")
        assert path == final_verification_record_path(run)
        assert _record(run) == record
        assert load_final_verification(run) == record
        errata = RunManifest.load(run.manifest_path).errata[-1]["message"]
        assert "P9 final verification" in errata
        assert "passed=True" in errata
        assert "FV-1=PASS" in errata and "FV-4=PASS" in errata
        assert "record sha256=" in errata

    def test_load_returns_none_when_absent(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        assert load_final_verification(run) is None


class TestArchiveIntegration:
    def test_archive_runs_final_verification_and_blocks_on_failure(
        self, tmp_path, git_repo
    ):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        (git_repo / "file.txt").write_text("two\n", encoding="utf-8")
        with pytest.raises(ArchiveError) as exc:
            _archive(run, manifest, status, tmp_path, git_repo)
        assert "final verification failed" in str(exc.value)
        assert "FV-1" in str(exc.value)
        # the run remains in its pre-archive state
        assert RunStatus.load(run.status_path).status == "VERDICTED"
        assert not (run.root / "ARCHIVED").is_file()
        assert load_registry(tmp_path) == []
        # the failed attempt is recorded, never silent
        assert _record(run)["passed"] is False

    def test_archive_blocks_on_changed_git_metadata(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        (git_repo / "file.txt").write_text("two\n", encoding="utf-8")
        git(git_repo, "add", ".")
        git(git_repo, "commit", "-m", "second commit")
        with pytest.raises(ArchiveError) as exc:
            _archive(run, manifest, status, tmp_path, git_repo)
        assert "FV-3" in str(exc.value)
        assert RunStatus.load(run.status_path).status == "VERDICTED"

    def test_archive_blocks_on_missing_baseline(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        (run.baseline / "safety-baseline.json").unlink()
        with pytest.raises(ArchiveError) as exc:
            _archive(run, manifest, status, tmp_path, git_repo)
        assert "final verification failed" in str(exc.value)
        assert RunStatus.load(run.status_path).status == "VERDICTED"
        assert not (run.root / "ARCHIVED").is_file()

    def test_archive_records_verification_and_regenerates_report(
        self, tmp_path, git_repo
    ):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        # the §13 record is present and passed
        record = _record(run)
        assert record["passed"] is True
        assert _item_verdicts(record) == {
            "FV-1": "PASS", "FV-2": "PASS", "FV-3": "PASS", "FV-4": "PASS",
        }
        # report.md carries the completed §13 section
        md = (run.reports / "report.md").read_text(encoding="utf-8")
        assert "Final verification (§13): PASS" in md
        assert "FV-1 No files in bga-mercurio were modified: PASS" in md
        assert "FV-4 Generated artifacts exist only in the run archive: PASS" in md
        # the machine-readable report carries the record
        report_json = json.loads(
            (run.reports / "evaluation-report.json").read_text(encoding="utf-8")
        )
        assert report_json["final_verification"]["passed"] is True
        # the manifest errata carries the verification
        errata = RunManifest.load(run.manifest_path).errata
        assert any("P9 final verification" in e["message"] for e in errata)
        assert any("P9 archive" in e["message"] for e in errata)

    def test_rejected_run_archives_with_verification(self, tmp_path, git_repo):
        run, manifest, status = rejected_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        assert RunStatus.load(run.status_path).status == "ARCHIVED"
        assert _record(run)["passed"] is True
        md = (run.reports / "report.md").read_text(encoding="utf-8")
        assert "Final verification (§13): PASS" in md

    def test_report_without_verification_states_not_performed(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        md = (run.reports / "report.md").read_text(encoding="utf-8")
        assert "Final verification (§13): not yet performed" in md

    def test_verify_archive_detects_missing_verification_record(
        self, tmp_path, git_repo
    ):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        final_verification_record_path(run).unlink()
        result = verify_archive(run, manifest, status, runs_root=tmp_path)
        assert result["passed"] is False
        assert any("final-verification record" in d for d in result["divergences"])

    def test_verify_archive_detects_failed_record(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        _archive(run, manifest, status, tmp_path, git_repo)
        record = _record(run)
        record["passed"] = False
        record["items"][0]["verdict"] = "FAIL"
        final_verification_record_path(run).write_text(
            json.dumps(record, indent=2, sort_keys=True) + "\n", encoding="utf-8"
        )
        result = verify_archive(run, manifest, status, runs_root=tmp_path)
        assert result["passed"] is False
        assert any("did not pass" in d for d in result["divergences"])

    def test_archive_is_retryable_after_reference_restored(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        (git_repo / "file.txt").write_text("two\n", encoding="utf-8")
        with pytest.raises(ArchiveError):
            _archive(run, manifest, status, tmp_path, git_repo)
        assert RunStatus.load(run.status_path).status == "VERDICTED"
        git(git_repo, "checkout", "--", "file.txt")
        _archive(run, manifest, status, tmp_path, git_repo)
        assert RunStatus.load(run.status_path).status == "ARCHIVED"
        assert _record(run)["passed"] is True
