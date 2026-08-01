"""Tests for the manual review kit scaffold (MS-07, MVB-019)."""

import pytest

from tooling.harness.review.kit import ReviewKitError, scaffold_review
from tooling.harness.review.parser import parse_category_table
from tooling.harness.review.state import ReviewState, review_json_path
from tooling.harness.tests.review_fixtures import make_gated_run, scaffolded_run
from tooling.harness.tests.validation_fixtures import passing_submission


class TestScaffold:
    def test_creates_full_package(self, tmp_path, git_repo):
        run, manifest, status = make_gated_run(tmp_path, reference_repo=git_repo)
        outcome = scaffold_review(run, manifest, status)
        assert outcome["files"] == [
            "g3-not-02.md", "manual-review.md", "onboarding.md",
        ]
        for name in outcome["files"]:
            assert (run.review / name).is_file(), name
        assert (run.review / "review.json").is_file()
        state = ReviewState.load(review_json_path(run))
        assert state.status == "SCAFFOLDED"
        assert state.rubric["family"] == "NOTIF"
        assert state.rubric["weights_sum"] == 100
        assert state.rubric["evaluation_version"] == "1.0"

    def test_manual_review_md_has_all_five_categories_and_citation_fields(
        self, tmp_path, git_repo
    ):
        run, manifest, status = make_gated_run(tmp_path, reference_repo=git_repo)
        scaffold_review(run, manifest, status)
        md = (run.review / "manual-review.md").read_text(encoding="utf-8")
        records = parse_category_table(md)
        assert [r.category for r in records] == [
            "Correctness", "Architecture", "Framework Compliance",
            "Maintainability", "Testing",
        ]
        assert "Evidence citations" in md
        assert "Required Review Checklist" in md
        assert "Reviewer Instructions" in md

    def test_evidence_references_point_at_frozen_evidence(self, tmp_path, git_repo):
        run, manifest, status = make_gated_run(tmp_path, reference_repo=git_repo)
        scaffold_review(run, manifest, status)
        md = (run.review / "manual-review.md").read_text(encoding="utf-8")
        assert "evidence/e1-transcript.txt" in md
        assert "evidence/e8-diff-bundle/" in md
        # evidence is referenced by path, never duplicated inline
        assert "session transcript" in md.lower() or "E1:" in md

    def test_validation_results_are_included(self, tmp_path, git_repo):
        run, manifest, status = make_gated_run(tmp_path, reference_repo=git_repo)
        scaffold_review(run, manifest, status)
        md = (run.review / "manual-review.md").read_text(encoding="utf-8")
        assert "G0 Repository safety: PASS" in md
        assert "G1 Build gates: PASS" in md
        assert "G2 Catalog checks: PASS" in md
        assert "Summary: PASS" in md

    def test_rubric_weights_section(self, tmp_path, git_repo):
        run, manifest, status = make_gated_run(tmp_path, reference_repo=git_repo)
        scaffold_review(run, manifest, status)
        md = (run.review / "manual-review.md").read_text(encoding="utf-8")
        assert "- Correctness: 40" in md
        assert "- Framework Compliance: 25" in md
        assert "- Testing: 10" in md

    def test_g3_script_and_onboarding_present(self, tmp_path, git_repo):
        run, manifest, status = make_gated_run(tmp_path, reference_repo=git_repo)
        scaffold_review(run, manifest, status)
        g3 = (run.review / "g3-not-02.md").read_text(encoding="utf-8")
        assert "evidence/e8-diff-bundle/" in g3
        assert "Step 1" in g3 and "Step 4" in g3
        onboarding = (run.review / "onboarding.md").read_text(encoding="utf-8")
        assert "100 | All success criteria satisfied" in onboarding  # anchor verbatim
        assert "80.25" in onboarding  # scoring example


class TestScaffoldPreconditions:
    def test_refuses_when_validation_missing(self, tmp_path, git_repo):
        from tooling.harness.tests.validation_fixtures import build_frozen_run

        run, manifest, status = build_frozen_run(
            tmp_path, reference_repo=git_repo,
            work_files=passing_submission(),
        )
        with pytest.raises(ReviewKitError) as exc:
            scaffold_review(run, manifest, status)
        assert "gates" in str(exc.value)

    def test_refuses_on_rejected_run(self, tmp_path, git_repo):
        from tooling.harness.tests.validation_fixtures import duplicated_submission

        run, manifest, status = make_gated_run(
            tmp_path, reference_repo=git_repo, submission=duplicated_submission()
        )
        assert status.status == "REJECTED"
        with pytest.raises(ReviewKitError) as exc:
            scaffold_review(run, manifest, status)
        assert "REJECTED" in str(exc.value)

    def test_refuses_double_scaffold(self, tmp_path, git_repo):
        run, manifest, status = make_gated_run(tmp_path, reference_repo=git_repo)
        scaffold_review(run, manifest, status)
        with pytest.raises(ReviewKitError) as exc:
            scaffold_review(run, manifest, status)
        assert "already exists" in str(exc.value)

    def test_refuses_on_rubric_version_mismatch(self, tmp_path, git_repo, monkeypatch):
        run, manifest, status = make_gated_run(tmp_path, reference_repo=git_repo)
        manifest.versions = {**manifest.versions, "evaluation": "0.9"}
        manifest.save(run.manifest_path)
        with pytest.raises(ReviewKitError) as exc:
            scaffold_review(run, manifest, status)
        assert "rubric version mismatch" in str(exc.value)


class TestScaffoldDeterminism:
    def test_identical_runs_produce_identical_package(self, tmp_path, git_repo, monkeypatch):
        from datetime import datetime, timezone

        monkeypatch.setattr(
            "tooling.harness.runtime.run_dir.utc_now",
            lambda: datetime(2026, 8, 1, 12, 0, 0, tzinfo=timezone.utc),
        )
        packages = []
        for name in ("a", "b"):
            run, manifest, status = make_gated_run(
                tmp_path / name, reference_repo=git_repo
            )
            scaffold_review(run, manifest, status)
            package = {
                relative: (run.review / relative).read_bytes()
                for relative in ("manual-review.md", "g3-not-02.md",
                                 "onboarding.md", "review.json")
            }
            packages.append(package)
        assert packages[0] == packages[1]


def test_scaffolded_run_fixture_smoke(tmp_path, git_repo):
    run, _, _ = scaffolded_run(tmp_path, reference_repo=git_repo)
    assert (run.review / "manual-review.md").is_file()
    assert (run.review / "review.json").is_file()
