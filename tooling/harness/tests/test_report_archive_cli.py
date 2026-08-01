"""CLI integration tests for MS-08: ``report`` and ``archive``.

Each test drives the pipeline init → prepare → prompt → session (stub)
→ collect --freeze → gates → review --scaffold → (fill md) → score →
report → archive, against a scratch git repository; ``bga-mercurio`` is
never touched.
"""

import json
import re
import shutil
from datetime import datetime, timezone

import pytest

from tooling.harness.cli import main
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.status import RunStatus
from tooling.harness.tests.review_fixtures import ACCEPTANCE_SCORES

MATERIAL_NAMES = (
    "docs",
    "bga-senior-engineer-skill",
    "tooling",
    "official-docs",
    "reference-projects",
)

ACCEPTANCE_SCORES_JSON = json.dumps(ACCEPTANCE_SCORES)

# Volatile values in a real run's data: the runtime validator's own
# subprocess timestamp lands in the E3 command log (and the evidence
# hashes derived from it).  Normalizing them isolates the report
# rendering as the deterministic function under test.
_VOLATILE_HASH = re.compile(r"[0-9a-f]{64}")


def _normalize_report(text: str) -> str:
    return _VOLATILE_HASH.sub("<hash>", text)


@pytest.fixture(autouse=True)
def _scratch_material(monkeypatch, senior_root):
    monkeypatch.setattr(
        "tooling.harness.workspace.provision.default_material_roots",
        lambda: {name: senior_root / name for name in MATERIAL_NAMES},
    )


def _full_flow(tmp_path, monkeypatch, git_repo):
    """Full P0-P7 flow; returns (runs_root, run_id, run_root)."""
    tmp_path.mkdir(parents=True, exist_ok=True)
    monkeypatch.chdir(tmp_path)
    runs = tmp_path / "runs"
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(runs)]) == 0
    run_id = next(p.name for p in runs.iterdir() if p.is_dir())
    for cmd in (
        ["prepare", run_id, "--reference", str(git_repo), "--runs-root", str(runs)],
        ["prompt", run_id, "--runs-root", str(runs)],
        ["session", run_id, "--platform", "stub", "--timeout-min", "1",
         "--runs-root", str(runs)],
    ):
        assert main(cmd) == 0
    run_root = runs / run_id
    from tooling.harness.tests.validation_fixtures import passing_submission

    shutil.rmtree(run_root / "workspace" / "work" / "changes")
    for relpath, content in passing_submission().items():
        path = run_root / "workspace" / "work" / relpath
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content, encoding="utf-8")
    assert main(["collect", run_id, "--freeze", "--runs-root", str(runs)]) == 0
    assert main(["gates", run_id, "--reference", str(git_repo),
                 "--runs-root", str(runs)]) == 0
    assert main(["review", run_id, "--scaffold", "--runs-root", str(runs)]) == 0
    md = (run_root / "review" / "manual-review.md").read_text(encoding="utf-8")
    for category in ("Correctness", "Architecture", "Framework Compliance",
                     "Maintainability", "Testing"):
        md = md.replace(
            f"| {category} |  |  |  |  |  | no |",
            f"| {category} |  | evidence/e1-transcript.txt; "
            f"evidence/e8-diff-bundle/modules/php/Game.php | solid | | | no |",
        )
    (run_root / "review" / "manual-review.md").write_text(md, encoding="utf-8")
    assert main(["score", run_id, "--scores", ACCEPTANCE_SCORES_JSON,
                 "--reviewer", "evaluator-1", "--runs-root", str(runs)]) == 0
    return runs, run_id, run_root


class TestReportCommand:
    def test_report_generates_both_reports(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _full_flow(tmp_path, monkeypatch, git_repo)
        exit_code = main(["report", run_id, "--runs-root", str(runs)])
        assert exit_code == 0
        out = capsys.readouterr().out
        assert "Reports generated" in out
        assert "evaluation-report.json" in out
        assert "report.md" in out
        assert (run_root / "reports" / "evaluation-report.json").is_file()
        assert (run_root / "reports" / "report.md").is_file()
        manifest = RunManifest.load(run_root / "manifest.json")
        assert any("P8 reports generated" in e["message"] for e in manifest.errata)

    def test_report_requires_completed_review(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _full_flow(tmp_path, monkeypatch, git_repo)
        # remove the scores: the run is no longer reportable
        (run_root / "review" / "scoring" / "scores.json").unlink()
        assert main(["report", run_id, "--runs-root", str(runs)]) == 1
        assert "scores.json" in capsys.readouterr().err

    def test_report_regeneration_is_identical(self, tmp_path, monkeypatch, git_repo):
        runs, run_id, run_root = _full_flow(tmp_path, monkeypatch, git_repo)
        assert main(["report", run_id, "--runs-root", str(runs)]) == 0
        first = (run_root / "reports" / "report.md").read_bytes()
        assert main(["report", run_id, "--runs-root", str(runs)]) == 0
        second = (run_root / "reports" / "report.md").read_bytes()
        assert first == second
        # a single report errata entry
        errata = [e for e in RunManifest.load(run_root / "manifest.json").errata
                  if "P8 reports" in e["message"]]
        assert len(errata) == 1


class TestArchiveCommand:
    def test_archive_full_flow(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _full_flow(tmp_path, monkeypatch, git_repo)
        assert main(["report", run_id, "--runs-root", str(runs)]) == 0
        exit_code = main(["archive", run_id, "--reference", str(git_repo), "--runs-root", str(runs)])
        assert exit_code == 0
        out = capsys.readouterr().out
        assert "Run archived" in out
        assert (run_root / "ARCHIVED").is_file()
        registry = json.loads((runs / "index.json").read_text(encoding="utf-8"))
        assert registry["entries"][0]["run_id"] == run_id
        assert registry["entries"][0]["status"] == "ARCHIVED"
        leaderboard = json.loads(
            (runs / "leaderboard" / "1.0-1.0-1.0" / "leaderboard.json").read_text(
                encoding="utf-8"
            )
        )
        assert leaderboard["entries"][0]["run_id"] == run_id
        assert RunStatus.load(run_root / "status.json").status == "ARCHIVED"
        manifest = RunManifest.load(run_root / "manifest.json")
        assert any("P9 archive" in e["message"] for e in manifest.errata)

    def test_archive_requires_reports(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, _ = _full_flow(tmp_path, monkeypatch, git_repo)
        assert main(["archive", run_id, "--reference", str(git_repo), "--runs-root", str(runs)]) == 1
        assert "report" in capsys.readouterr().err

    def test_archive_rejects_second_archive(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, _ = _full_flow(tmp_path, monkeypatch, git_repo)
        assert main(["report", run_id, "--runs-root", str(runs)]) == 0
        assert main(["archive", run_id, "--reference", str(git_repo), "--runs-root", str(runs)]) == 0
        assert main(["archive", run_id, "--reference", str(git_repo), "--runs-root", str(runs)]) == 1
        assert "already archived" in capsys.readouterr().err

    def test_archive_verify_passes(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, _ = _full_flow(tmp_path, monkeypatch, git_repo)
        assert main(["report", run_id, "--runs-root", str(runs)]) == 0
        assert main(["archive", run_id, "--reference", str(git_repo), "--runs-root", str(runs)]) == 0
        assert main(["archive", run_id, "--verify", "--runs-root", str(runs)]) == 0
        assert "Archive verified" in capsys.readouterr().out

    def test_archive_verify_rejects_malformed(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _full_flow(tmp_path, monkeypatch, git_repo)
        assert main(["report", run_id, "--runs-root", str(runs)]) == 0
        assert main(["archive", run_id, "--reference", str(git_repo), "--runs-root", str(runs)]) == 0
        (run_root / "ARCHIVED").unlink()
        assert main(["archive", run_id, "--verify", "--runs-root", str(runs)]) == 1
        err = capsys.readouterr().err
        assert "Archive verification FAILED" in err
        assert "ARCHIVED" in err

    def test_archive_verify_rejects_stray_files(self, tmp_path, monkeypatch, git_repo, capsys):
        runs, run_id, run_root = _full_flow(tmp_path, monkeypatch, git_repo)
        assert main(["report", run_id, "--runs-root", str(runs)]) == 0
        assert main(["archive", run_id, "--reference", str(git_repo), "--runs-root", str(runs)]) == 0
        (run_root / "scratch.json").write_text("{}", encoding="utf-8")
        assert main(["archive", run_id, "--verify", "--runs-root", str(runs)]) == 1
        assert "unexpected top-level entry" in capsys.readouterr().err


class TestDeterminism:
    def _freeze_flow(self, monkeypatch):
        """Pin every module clock + the validator's recorded output so two
        identical real flows produce byte-identical run data."""
        from datetime import datetime, timezone

        fixed = datetime(2026, 1, 1, 12, 0, 0, tzinfo=timezone.utc)
        fixed_iso = "2026-01-01T12:00:00.000000Z"
        monkeypatch.setattr("tooling.harness.cli.utc_now", lambda: fixed)
        monkeypatch.setattr(
            "tooling.harness.evidence.collect.utc_now", lambda: fixed
        )
        monkeypatch.setattr(
            "tooling.harness.evidence.freeze.utc_now", lambda: fixed
        )
        monkeypatch.setattr(
            "tooling.harness.agent.runtime.utc_now", lambda: fixed
        )
        monkeypatch.setattr(
            "tooling.harness.evidence.freeze.utc_now", lambda: fixed
        )
        monkeypatch.setattr(
            "tooling.harness.util.proc.utc_now_iso", lambda: fixed_iso
        )
        # E3 command-log wall times come from time.monotonic; a fixed
        # monotonic clock makes every recorded wall_time deterministic
        monkeypatch.setattr("time.monotonic", lambda: 1.0)
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
        monkeypatch.setattr(
            "tooling.harness.archive.manager.utc_now_iso", lambda: fixed_iso
        )
        monkeypatch.setattr(
            "tooling.harness.safety.final_verify.utc_now_iso", lambda: fixed_iso
        )
        monkeypatch.setattr(
            "tooling.harness.environment.collect.capture_validator_version",
            lambda rules_path=None, cwd=None: (
                "=== Runtime Specification Validator ===\n"
                "Version: 1.0.0\nTimestamp: 2026-01-01T12:00:00Z\n"
            ),
        )

    def test_two_identical_runs_byte_identical_reports_and_archives(
        self, tmp_path, monkeypatch, git_repo
    ):
        self._freeze_flow(monkeypatch)
        results = []
        for name in ("a", "b"):
            runs, run_id, run_root = _full_flow(tmp_path / name, monkeypatch, git_repo)
            assert main(["report", run_id, "--runs-root", str(runs)]) == 0
            assert main(["archive", run_id, "--reference", str(git_repo), "--runs-root", str(runs)]) == 0
            results.append(
                (
                    _normalize_report(
                        (run_root / "reports" / "evaluation-report.json").read_text(
                            encoding="utf-8"
                        )
                    ),
                    _normalize_report(
                        (run_root / "reports" / "report.md").read_text(encoding="utf-8")
                    ),
                    (run_root / "ARCHIVED").read_bytes(),
                )
            )
        assert results[0][0] == results[1][0]
        assert results[0][1] == results[1][1]
        assert results[0][2] == results[1][2]
