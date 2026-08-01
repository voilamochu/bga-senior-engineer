"""Tests for the report generator (MS-08, MVB-022)."""

import json
import re
from datetime import datetime, timezone

import pytest

from tooling.harness.report.data import (
    REQUIRED_METADATA_GROUPS,
    REQUIRED_SECTIONS,
    ReportError,
    assemble_report_data,
)
from tooling.harness.report.generator import (
    generate_reports,
    render_report_json,
    render_report_md,
    validate_report,
)
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.tests.review_fixtures import rejected_run, reviewed_run


class TestReportContents:
    def test_reviewed_run_report_has_all_sections(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        outcome = generate_reports(run, manifest, status)
        md = (run.reports / "report.md").read_text(encoding="utf-8")
        for index, section in enumerate(REQUIRED_SECTIONS, start=1):
            assert f"## {index}. {section}" in md, section
        assert outcome["sections"] == list(REQUIRED_SECTIONS)

    def test_json_has_all_metadata_groups(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        doc = json.loads(
            (run.reports / "evaluation-report.json").read_text(encoding="utf-8")
        )
        for group in REQUIRED_METADATA_GROUPS:
            assert group in doc, group
        assert doc["schema"] == "benchmark-harness-evaluation-report/1.0"

    def test_identity_and_versions(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        doc = json.loads(
            (run.reports / "evaluation-report.json").read_text(encoding="utf-8")
        )
        assert doc["identity"]["run_id"] == run.run_id
        assert doc["identity"]["task_id"] == "NOT-02"
        assert doc["identity"]["model"] == "demo-model"
        assert doc["versions"]["corpus"] == "1.0"
        assert doc["versions"]["evaluation"] == "1.0"
        assert doc["versions"]["reference_head"]

    def test_scores_and_verdict_read_from_recorded_artifacts(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        doc = json.loads(
            (run.reports / "evaluation-report.json").read_text(encoding="utf-8")
        )
        assert doc["scores"]["total"] == 80.25
        assert doc["verdict"]["verdict"] == "ACCEPTABLE"
        assert doc["scores"]["weights"]["Correctness"] == 40

    def test_gates_and_evidence_referenced(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        md = (run.reports / "report.md").read_text(encoding="utf-8")
        assert "G0 Repository safety: PASS" in md
        assert "G1 Build gates: PASS" in md
        assert "G2 Catalog checks: PASS" in md
        assert "evidence/e1-transcript.txt" in md
        assert "evidence/evidence.json" in md
        # large evidence artifacts are referenced, never duplicated
        assert "session transcript\n" not in md

    def test_manual_review_and_scoring_referenced(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        md = (run.reports / "report.md").read_text(encoding="utf-8")
        assert "evaluator-1" in md
        assert "review/manual-review.md" in md
        doc = json.loads(
            (run.reports / "evaluation-report.json").read_text(encoding="utf-8")
        )
        assert doc["attribution"]["evaluator_id"] == "evaluator-1"
        assert doc["attribution"]["manual_review_file"] == "review/manual-review.md"

    def test_common_failure_modes_section(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        md = (run.reports / "report.md").read_text(encoding="utf-8")
        assert "## 9. Common Failure Mode Notes" in md
        assert "cardKept" in md  # a corpus failure mode for NOT-02

    def test_verdict_math_is_read_not_recomputed(self, tmp_path, git_repo):
        """The report must never recompute scores (read recorded values)."""
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        scores_path = run.review_scoring / "scores.json"
        scores = json.loads(scores_path.read_text(encoding="utf-8"))
        # corrupt the recorded total; the report must surface the recorded
        # value verbatim (a recomputation would mask the corruption)
        scores["arithmetic"]["total"] = 12.34
        scores["verdict"] = "POOR"
        scores_path.write_text(json.dumps(scores, sort_keys=True), encoding="utf-8")
        generate_reports(run, manifest, status)
        doc = json.loads(
            (run.reports / "evaluation-report.json").read_text(encoding="utf-8")
        )
        assert doc["scores"]["total"] == 12.34
        assert doc["verdict"]["verdict"] == "POOR"


class TestRejectedRunReport:
    def test_rejected_run_report(self, tmp_path, git_repo):
        run, manifest, status = rejected_run(tmp_path, reference_repo=git_repo)
        outcome = generate_reports(run, manifest, status)
        md = (run.reports / "report.md").read_text(encoding="utf-8")
        assert "## 2. Run Status" in md
        assert "REJECTED" in md
        assert "blocking validation failures" in md
        doc = json.loads(
            (run.reports / "evaluation-report.json").read_text(encoding="utf-8")
        )
        assert doc["verdict"]["verdict"] == "REJECTED"
        assert doc["verdict"]["critical_failures"] == ["NOT02-A", "NOT02-B"]
        assert doc["verdict"]["rejection_reason"]
        assert doc["review"]["reviewed"] is False
        # no review data required for rejected runs
        assert "## 8. Manual Review Notes" in md


class TestReportErrors:
    def test_missing_validation_data(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        (run.root / "validation" / "validation.json").unlink()
        with pytest.raises(ReportError) as exc:
            generate_reports(run, manifest, status)
        assert "validation/validation.json" in str(exc.value)

    def test_missing_environment(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        (run.root / "protocol" / "environment.json").unlink()
        with pytest.raises(ReportError) as exc:
            generate_reports(run, manifest, status)
        assert "environment.json" in str(exc.value)

    def test_missing_review_data_for_verdict_run(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        (run.review_scoring / "scores.json").unlink()
        with pytest.raises(ReportError) as exc:
            generate_reports(run, manifest, status)
        assert "scores.json" in str(exc.value)

    def test_missing_review_file(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        (run.review / "manual-review.md").unlink()
        with pytest.raises(ReportError):
            generate_reports(run, manifest, status)


class TestConsistency:
    def test_field_for_field_consistency(self, tmp_path, git_repo):
        """Both renderings derive from one in-memory source: key fields
        must agree between report.md and evaluation-report.json."""
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        md = (run.reports / "report.md").read_text(encoding="utf-8")
        doc = json.loads(
            (run.reports / "evaluation-report.json").read_text(encoding="utf-8")
        )
        assert f"Run ID: {run.run_id}" in md
        assert doc["identity"]["run_id"] == run.run_id
        assert "Verdict: ACCEPTABLE" in md
        assert doc["verdict"]["verdict"] == "ACCEPTABLE"
        assert f"Total: 80.25" in md
        assert doc["scores"]["total"] == 80.25
        assert doc["versions"]["reference_head"] in md

    def test_validate_report_detects_missing_sections(self):
        divergences = validate_report("", {})
        assert any("Header" in d for d in divergences)
        assert any("metadata group 'identity'" in d for d in divergences)

    def test_renderers_are_deterministic(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        data = assemble_report_data(run, manifest, status)
        first = render_report_md(data)
        second = render_report_md(data)
        assert first == second
        assert json.dumps(render_report_json(data), sort_keys=True) == json.dumps(
            render_report_json(data), sort_keys=True
        )

    def test_reports_contain_no_generation_time(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        md = (run.reports / "report.md").read_text(encoding="utf-8")
        doc = json.loads(
            (run.reports / "evaluation-report.json").read_text(encoding="utf-8")
        )
        assert "generated at" not in md.lower()
        assert "generated_at" not in json.dumps(doc)


class TestDeterminism:
    def _freeze_clocks(self, monkeypatch):
        """Fixed clock across every module that writes run data.

        The fixture run's base data is deterministic (AT constants); the
        gates/review/report flows record timestamps through each module's
        own bound clock, so all of them are pinned for byte-identity.
        """
        fixed = datetime(2026, 1, 1, 12, 0, 0, tzinfo=timezone.utc)
        fixed_iso = "2026-01-01T12:00:00.000000Z"
        monkeypatch.setattr("tooling.harness.runtime.run_dir.utc_now", lambda: fixed)
        monkeypatch.setattr(
            "tooling.harness.evidence.collect.utc_now", lambda: fixed
        )
        monkeypatch.setattr(
            "tooling.harness.runtime.status.utc_now_iso", lambda: fixed_iso
        )
        monkeypatch.setattr(
            "tooling.harness.runtime.manifest.utc_now_iso", lambda: fixed_iso
        )
        monkeypatch.setattr(
            "tooling.harness.validation.gates.utc_now_iso", lambda: fixed_iso
        )
        monkeypatch.setattr(
            "tooling.harness.scoring.runner.utc_now_iso", lambda: fixed_iso
        )
        monkeypatch.setattr(
            "tooling.harness.report.generator.utc_now_iso", lambda: fixed_iso
        )

    def test_identical_runs_produce_byte_identical_reports(
        self, tmp_path, git_repo, monkeypatch
    ):
        self._freeze_clocks(monkeypatch)
        pairs = []
        for name in ("a", "b"):
            run, manifest, status = reviewed_run(tmp_path / name, reference_repo=git_repo)
            generate_reports(run, manifest, status)
            pairs.append(
                (
                    (run.reports / "report.md").read_bytes(),
                    (run.reports / "evaluation-report.json").read_bytes(),
                )
            )
        assert pairs[0][0] == pairs[1][0]
        assert pairs[0][1] == pairs[1][1]

    def test_regeneration_is_byte_identical(self, tmp_path, git_repo):
        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        generate_reports(run, manifest, status)
        first = (run.reports / "report.md").read_bytes()
        generate_reports(run, manifest, status)
        second = (run.reports / "report.md").read_bytes()
        assert first == second
        # only one report errata entry is recorded
        errata = [e for e in RunManifest.load(run.manifest_path).errata
                  if "P8 reports" in e["message"]]
        assert len(errata) == 1
