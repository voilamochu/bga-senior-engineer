"""CLI integration tests for MS-07: ``review`` and ``score``.

Each test drives the pipeline init → prepare → prompt → session (stub)
→ collect --freeze → gates → review --scaffold → (fill manual-review.md)
→ score, against a scratch git repository; ``bga-mercurio`` is never
touched.
"""

import json
import shutil
from datetime import datetime, timezone

import pytest

from tooling.harness.cli import main
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.status import RunStatus
from tooling.harness.tests.review_fixtures import ACCEPTANCE_SCORES
from tooling.harness.tests.validation_fixtures import passing_submission

MATERIAL_NAMES = (
    "docs",
    "bga-senior-engineer-skill",
    "tooling",
    "official-docs",
    "reference-projects",
)

# Fixed clock in the past: run IDs are identical across scenario roots,
# while live phase timestamps (collect/freeze) stay after the fixed one.
FIXED_NOW = datetime(2026, 1, 1, 12, 0, 0, tzinfo=timezone.utc)

ACCEPTANCE_SCORES_JSON = json.dumps(ACCEPTANCE_SCORES)


@pytest.fixture(autouse=True)
def _scratch_material(monkeypatch, senior_root):
    monkeypatch.setattr(
        "tooling.harness.workspace.provision.default_material_roots",
        lambda: {name: senior_root / name for name in MATERIAL_NAMES},
    )


def _flow(tmp_path, monkeypatch, git_repo):
    """Full P0-P5 flow; returns (runs_root, run_id, run_root)."""
    tmp_path.mkdir(parents=True, exist_ok=True)
    monkeypatch.chdir(tmp_path)
    runs = tmp_path / "runs"
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(runs)]) == 0
    run_id = next(p.name for p in runs.iterdir() if p.is_dir())
    assert (
        main(["prepare", run_id, "--reference", str(git_repo), "--runs-root", str(runs)])
        == 0
    )
    assert main(["prompt", run_id, "--runs-root", str(runs)]) == 0
    assert (
        main(["session", run_id, "--platform", "stub", "--timeout-min", "1",
              "--runs-root", str(runs)])
        == 0
    )
    run_root = runs / run_id
    submission = passing_submission()
    shutil.rmtree(run_root / "workspace" / "work" / "changes")
    for relpath, content in submission.items():
        path = run_root / "workspace" / "work" / relpath
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content, encoding="utf-8")
    assert (
        main(["collect", run_id, "--freeze", "--runs-root", str(runs)]) == 0
    )
    assert (
        main(["gates", run_id, "--reference", str(git_repo), "--runs-root", str(runs)])
        == 0
    )
    return runs, run_id, run_root


def _fill_md(run_root, reviewer="evaluator-1"):
    md = (run_root / "review" / "manual-review.md").read_text(encoding="utf-8")
    md = md.replace("| Reviewer |  |", f"| Reviewer | {reviewer} |")
    for category in ("Correctness", "Architecture", "Framework Compliance",
                     "Maintainability", "Testing"):
        md = md.replace(
            f"| {category} |  |  |  |  |  | no |",
            f"| {category} |  | evidence/e1-transcript.txt; "
            f"evidence/e8-diff-bundle/modules/php/Game.php | solid | | | no |",
        )
    (run_root / "review" / "manual-review.md").write_text(md, encoding="utf-8")


class TestReviewCommand:
    def test_scaffold_creates_package(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _flow(tmp_path, monkeypatch, git_repo)
        exit_code = main(["review", run_id, "--scaffold", "--runs-root", str(runs)])
        assert exit_code == 0
        out = capsys.readouterr().out
        assert "Review package assembled" in out
        assert "NOTIF" in out
        assert (run_root / "review" / "manual-review.md").is_file()
        assert (run_root / "review" / "g3-not-02.md").is_file()
        assert (run_root / "review" / "onboarding.md").is_file()
        assert (run_root / "review" / "review.json").is_file()

    def test_scaffold_refuses_double(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, _ = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
        assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 1
        assert "already exists" in capsys.readouterr().err

    def test_resume_view_shows_partial_state(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
        _fill_md(run_root)
        # partial: no scores entered yet
        assert main(["review", run_id, "--runs-root", str(runs)]) == 0
        out = capsys.readouterr().out
        assert "status: SCAFFOLDED" in out
        assert "categories missing scores" in out
        assert "resume" in out

    def test_resume_view_after_completion(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
        _fill_md(run_root)
        assert (
            main(["score", run_id, "--scores", ACCEPTANCE_SCORES_JSON,
                  "--runs-root", str(runs)])
            == 0
        )
        assert main(["review", run_id, "--runs-root", str(runs)]) == 0
        out = capsys.readouterr().out
        assert "status: COMPLETED" in out
        assert "verdict: ACCEPTABLE" in out

    def test_resume_view_without_scaffold(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, _ = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["review", run_id, "--runs-root", str(runs)]) == 1
        assert "review --scaffold" in capsys.readouterr().err


class TestScoreCommand:
    def test_acceptance_example_scores(self, tmp_path, monkeypatch, git_repo, capsys):
        """Milestone acceptance #1: 80/90/85/70/75 -> 80.25 -> ACCEPTABLE."""
        runs, run_id, run_root = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
        _fill_md(run_root)
        exit_code = main(
            ["score", run_id, "--scores", ACCEPTANCE_SCORES_JSON,
             "--reviewer", "evaluator-1", "--runs-root", str(runs)]
        )
        assert exit_code == 0
        out = capsys.readouterr().out
        assert "total: 80.25" in out
        assert "verdict: ACCEPTABLE" in out

        scores = json.loads(
            (run_root / "review" / "scoring" / "scores.json").read_text(encoding="utf-8")
        )
        verification = json.loads(
            (run_root / "review" / "scoring" / "score-verification.json").read_text(encoding="utf-8")
        )
        assert scores["arithmetic"]["total"] == 80.25
        assert scores["verdict"] == "ACCEPTABLE"
        # the two records match (§7.5 double computation)
        assert verification["status"] == "MATCHED"
        assert verification["double_computation"]["first"]["total"] == \
            verification["double_computation"]["second"]["total"] == 80.25
        # run status moves to VERDICTED
        assert RunStatus.load(run_root / "status.json").status == "VERDICTED"
        # manifest errata records the review metadata
        manifest = RunManifest.load(run_root / "manifest.json")
        errata = manifest.errata[-1]["message"]
        assert "P7 review completed" in errata
        assert "reviewer=evaluator-1" in errata
        assert "review_version=1.0" in errata
        assert "verdict=ACCEPTABLE total=80.25" in errata
        assert "scores.json sha256=" in errata
        # review.json completed with pinned hashes
        review = json.loads((run_root / "review" / "review.json").read_text(encoding="utf-8"))
        assert review["status"] == "COMPLETED"
        assert len(review["artifact_hashes"]) == 5

    def test_score_from_manual_review_md_alone(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
        _fill_md(run_root)
        # enter the scores in the md instead of --scores
        md = (run_root / "review" / "manual-review.md").read_text(encoding="utf-8")
        for category, score in ACCEPTANCE_SCORES.items():
            canonical = category.replace("_", " ")
            md = md.replace(
                f"| {canonical} |  | evidence/",
                f"| {canonical} | {score} | evidence/",
            )
        (run_root / "review" / "manual-review.md").write_text(md, encoding="utf-8")
        assert main(["score", run_id, "--runs-root", str(runs)]) == 0
        assert "verdict: ACCEPTABLE" in capsys.readouterr().out

    def test_category_without_citation_fails(self, tmp_path, monkeypatch, git_repo, capsys):
        """Milestone acceptance #3: citation-less score is rejected."""
        runs, run_id, run_root = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
        # fill the md but leave the citations column empty
        md = (run_root / "review" / "manual-review.md").read_text(encoding="utf-8")
        for category in ("Correctness", "Architecture", "Framework Compliance",
                         "Maintainability", "Testing"):
            md = md.replace(
                f"| {category} |  |  |  |  |  | no |",
                f"| {category} |  |  | reviewed | | | no |",
            )
        (run_root / "review" / "manual-review.md").write_text(md, encoding="utf-8")
        exit_code = main(
            ["score", run_id, "--scores", ACCEPTANCE_SCORES_JSON,
             "--runs-root", str(runs)]
        )
        assert exit_code == 1
        err = capsys.readouterr().err
        assert "no evidence citation" in err
        assert "Correctness" in err
        # review marked IN_PROGRESS (partial attempt recorded, never silent)
        review = json.loads((run_root / "review" / "review.json").read_text(encoding="utf-8"))
        assert review["status"] == "IN_PROGRESS"
        # no scores.json is written for the failed attempt
        assert not (run_root / "review" / "scoring" / "scores.json").exists()

    def test_non_frozen_citation_fails(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
        md = (run_root / "review" / "manual-review.md").read_text(encoding="utf-8")
        for category in ("Correctness", "Architecture", "Framework Compliance",
                         "Maintainability", "Testing"):
            md = md.replace(
                f"| {category} |  |  |  |  |  | no |",
                f"| {category} |  | workspace/work/Game.php | | | | no |",
            )
        (run_root / "review" / "manual-review.md").write_text(md, encoding="utf-8")
        assert main(["score", run_id, "--scores", ACCEPTANCE_SCORES_JSON,
                     "--runs-root", str(runs)]) == 1
        assert "non-frozen" in capsys.readouterr().err

    def test_invalid_score_rejected(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
        _fill_md(run_root)
        bad = dict(ACCEPTANCE_SCORES)
        bad["Correctness"] = 120
        assert main(["score", run_id, "--scores", json.dumps(bad),
                     "--runs-root", str(runs)]) == 1
        assert "out of range" in capsys.readouterr().err

    def test_missing_category_rejected(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
        _fill_md(run_root)
        partial = dict(ACCEPTANCE_SCORES)
        del partial["Testing"]
        assert main(["score", run_id, "--scores", json.dumps(partial),
                     "--runs-root", str(runs)]) == 1
        assert "Testing" in capsys.readouterr().err

    def test_score_without_scaffold(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, _ = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["score", run_id, "--scores", ACCEPTANCE_SCORES_JSON,
                     "--runs-root", str(runs)]) == 1
        assert "review --scaffold" in capsys.readouterr().err

    def test_rejected_run_never_scored(self, tmp_path, monkeypatch, git_repo, capsys):
        from tooling.harness.tests.validation_fixtures import duplicated_submission

        runs = tmp_path / "runs"
        monkeypatch.chdir(tmp_path)
        assert main(["init", "NOT-02", "demo-model", "--runs-root", str(runs)]) == 0
        run_id = next(p.name for p in runs.iterdir() if p.is_dir())
        assert main(["prepare", run_id, "--reference", str(git_repo),
                     "--runs-root", str(runs)]) == 0
        assert main(["prompt", run_id, "--runs-root", str(runs)]) == 0
        assert main(["session", run_id, "--platform", "stub", "--timeout-min", "1",
                     "--runs-root", str(runs)]) == 0
        run_root = runs / run_id
        shutil.rmtree(run_root / "workspace" / "work" / "changes")
        for relpath, content in duplicated_submission().items():
            path = run_root / "workspace" / "work" / relpath
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(content, encoding="utf-8")
        assert main(["collect", run_id, "--freeze", "--runs-root", str(runs)]) == 0
        assert main(["gates", run_id, "--reference", str(git_repo),
                     "--runs-root", str(runs)]) == 0
        assert RunStatus.load(run_root / "status.json").status == "REJECTED"
        assert main(["score", run_id, "--scores", ACCEPTANCE_SCORES_JSON,
                     "--runs-root", str(runs)]) == 1
        assert "REJECTED" in capsys.readouterr().err

    def test_completed_review_refuses_repeat_without_recompute(
        self, tmp_path, monkeypatch, git_repo, capsys
    ):
        runs, run_id, run_root = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
        _fill_md(run_root)
        assert main(["score", run_id, "--scores", ACCEPTANCE_SCORES_JSON,
                     "--runs-root", str(runs)]) == 0
        assert main(["score", run_id, "--scores", ACCEPTANCE_SCORES_JSON,
                     "--runs-root", str(runs)]) == 1
        assert "already completed" in capsys.readouterr().err

    def test_recompute_records_correction(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _flow(tmp_path, monkeypatch, git_repo)
        assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
        _fill_md(run_root)
        assert main(["score", run_id, "--scores", ACCEPTANCE_SCORES_JSON,
                     "--runs-root", str(runs)]) == 0
        corrected = dict(ACCEPTANCE_SCORES)
        corrected["Correctness"] = 85
        assert main(["score", run_id, "--scores", json.dumps(corrected),
                     "--recompute", "--runs-root", str(runs)]) == 0
        assert "verdict: ACCEPTABLE" in capsys.readouterr().out
        manifest = RunManifest.load(run_root / "manifest.json")
        assert "P7 review recomputed" in manifest.errata[-1]["message"]
        review = json.loads((run_root / "review" / "review.json").read_text(encoding="utf-8"))
        assert review["category_scores"]["Correctness"] == 85


class TestScoreDeterminism:
    def test_two_identical_runs_byte_identical_records(
        self, tmp_path, monkeypatch, git_repo
    ):
        # fixed clock => identical run IDs across both scenario roots
        monkeypatch.setattr("tooling.harness.cli.utc_now", lambda: FIXED_NOW)
        records = []
        for name in ("a", "b"):
            runs, run_id, run_root = _flow(tmp_path / name, monkeypatch, git_repo)
            assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
            _fill_md(run_root)
            assert main(["score", run_id, "--scores", ACCEPTANCE_SCORES_JSON,
                         "--runs-root", str(runs)]) == 0
            records.append(
                (
                    (run_root / "review" / "scoring" / "scores.json").read_bytes(),
                    (run_root / "review" / "scoring" / "score-verification.json").read_bytes(),
                )
            )
        assert records[0][0] == records[1][0]
        assert records[0][1] == records[1][1]
