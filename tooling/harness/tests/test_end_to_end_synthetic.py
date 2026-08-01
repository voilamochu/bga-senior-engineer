"""Synthetic end-to-end validation tests (MS-09, MVB-025).

The complete benchmark pipeline runs deterministically against fixture
submissions — no LLM, no opencode, no external AI providers — using the
stub adapter and scratch reference repository.  These integration and
regression tests exercise every stage, byte-level determinism, and the
failure-injection cases (each failing at the correct pipeline stage).
"""

import hashlib
import json
from pathlib import Path

import pytest

from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.status import RunStatus
from tooling.harness.synthetic.runner import (
    SYNTHETIC_REVIEWER,
    SYNTHETIC_SCORES,
    SyntheticError,
    run_synthetic,
)

ARTIFACTS = (
    "manifest.json",
    "status.json",
    "validation/validation.json",
    "review/review.json",
    "review/scoring/scores.json",
    "review/scoring/score-verification.json",
    "reports/evaluation-report.json",
    "reports/report.md",
    "evidence/evidence.json",
    "ARCHIVED",
)


def _run_root(runs_root: Path) -> Path:
    matches = [p for p in runs_root.iterdir() if p.is_dir() and p.name.startswith("run-")]
    assert len(matches) == 1
    return matches[0]


def _sha(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def _tree(root: Path) -> dict:
    return {
        str(p.relative_to(root)): _sha(p)
        for p in root.rglob("*")
        if p.is_file()
    }


class TestSyntheticPipeline:
    def test_passing_variant_reaches_archived(self, tmp_path, git_repo, monkeypatch):
        runs_root = tmp_path / "runs"
        dataset = tmp_path / "dataset" / "pilot-0.json"
        outcome = run_synthetic(runs_root=runs_root, dataset_path=dataset)
        assert outcome["status"] == "ARCHIVED"
        assert outcome["variant"] == "passing"
        assert outcome["verification"]["passed"] is True

        run = Path(outcome["run_root"])
        assert RunStatus.load(run / "status.json").status == "ARCHIVED"
        # every pipeline stage produced its artifact
        for artifact in ARTIFACTS:
            assert (run / artifact).is_file(), artifact
        assert (runs_root / "index.json").is_file()
        assert (runs_root / "leaderboard" / "1.0-1.0-1.0" / "leaderboard.json").is_file()

    def test_no_llm_participates(self, tmp_path, git_repo, monkeypatch):
        outcome = run_synthetic(runs_root=tmp_path / "runs",
                                dataset_path=tmp_path / "pilot-0.json")
        run = Path(outcome["run_root"])
        manifest = RunManifest.load(run / "manifest.json")
        assert manifest.execution["platform"] == "stub"
        assert manifest.execution["agent_id"] == "stub"
        # E1 is the stub transcript, not a platform session log
        transcript = (run / "evidence" / "e1-transcript.txt").read_text(encoding="utf-8")
        assert "Stub Agent Session Transcript" in transcript
        assert "opencode" not in (run / "evidence" / "e3-command.log").read_text(
            encoding="utf-8"
        ).lower()

    def test_known_verdict_from_fixture_scores(self, tmp_path, git_repo, monkeypatch):
        outcome = run_synthetic(runs_root=tmp_path / "runs",
                                dataset_path=tmp_path / "pilot-0.json")
        run = Path(outcome["run_root"])
        scores = json.loads((run / "review" / "scoring" / "scores.json").read_text(encoding="utf-8"))
        assert scores["arithmetic"]["total"] == 80.25
        assert scores["verdict"] == "ACCEPTABLE"
        verification = json.loads(
            (run / "review" / "scoring" / "score-verification.json").read_text(encoding="utf-8")
        )
        assert verification["status"] == "MATCHED"

    def test_every_gate_executed_and_recorded(self, tmp_path, git_repo, monkeypatch):
        outcome = run_synthetic(runs_root=tmp_path / "runs",
                                dataset_path=tmp_path / "pilot-0.json")
        run = Path(outcome["run_root"])
        validation = json.loads((run / "validation" / "validation.json").read_text(encoding="utf-8"))
        for gate_id in ("G0", "G1", "G2"):
            assert validation["gates"][gate_id]["verdict"] == "PASS", gate_id
        assert validation["summary"]["executed_check_count"] == 11
        # manual review consumed the scaffolded template
        review = json.loads((run / "review" / "review.json").read_text(encoding="utf-8"))
        assert review["status"] == "COMPLETED"
        assert review["reviewer"] == SYNTHETIC_REVIEWER

    def test_failing_variant_rejected_at_p5(self, tmp_path, git_repo, monkeypatch):
        outcome = run_synthetic(runs_root=tmp_path / "runs", variant="failing",
                                dataset_path=tmp_path / "pilot-0.json")
        run = Path(outcome["run_root"])
        assert outcome["status"] == "ARCHIVED"
        validation = json.loads((run / "validation" / "validation.json").read_text(encoding="utf-8"))
        assert validation["summary"]["verdict"] == "REJECTED"
        assert "NOT02-A" in validation["summary"]["blocking_failures"]
        assert validation["gates"]["G0"]["verdict"] == "PASS"
        # rejected runs are never reviewed, never scored, never on the leaderboard
        assert not (run / "review" / "review.json").exists()
        assert not (run / "review" / "scoring" / "scores.json").exists()
        assert not (Path(outcome["runs_root"]) / "leaderboard").exists()
        registry = json.loads(
            (Path(outcome["runs_root"]) / "index.json").read_text(encoding="utf-8")
        )
        assert registry["entries"][0]["verdict"] == "REJECTED"

    def test_seed_recorded_for_passing_variant(self, tmp_path, git_repo, monkeypatch):
        dataset = tmp_path / "pilot-0.json"
        run_synthetic(runs_root=tmp_path / "runs", dataset_path=dataset)
        seed = json.loads(dataset.read_text(encoding="utf-8"))
        assert seed["schema"] == "benchmark-validation-dataset/1.0"
        assert seed["cycle"]["id"] == "pilot-0"
        assert seed["cycle"]["type"] == "pilot"
        assert seed["runs"][0]["verdict"] == "ACCEPTABLE"
        assert seed["scoring_pairs"][0]["matched"] is True
        # idempotent: an identical rerun does not change the seed
        seed_bytes = dataset.read_bytes()
        run_synthetic(runs_root=tmp_path / "runs2", dataset_path=dataset)
        assert dataset.read_bytes() == seed_bytes

    def test_patches_are_restored(self, tmp_path, git_repo, monkeypatch):
        import tooling.harness.cli as cli

        original = cli.utc_now
        run_synthetic(runs_root=tmp_path / "runs", dataset_path=tmp_path / "pilot-0.json")
        assert cli.utc_now is original


class TestSyntheticDeterminism:
    def test_two_runs_byte_identical_deterministic_artifacts(self, tmp_path, git_repo, monkeypatch):
        runs = []
        for name in ("a", "b"):
            runs_root = tmp_path / name
            run_synthetic(runs_root=runs_root,
                          dataset_path=tmp_path / f"{name}-pilot-0.json")
            runs.append((runs_root, _run_root(runs_root)))
        (root_a, run_a), (root_b, run_b) = runs
        for artifact in ARTIFACTS:
            assert _sha(run_a / artifact) == _sha(run_b / artifact), artifact
        # the whole frozen evidence tree is byte-identical
        assert _tree(run_a / "evidence") == _tree(run_b / "evidence")
        # archive metadata, registry, leaderboard, seed
        assert (root_a / "index.json").read_bytes() == (root_b / "index.json").read_bytes()
        assert (root_a / "leaderboard" / "1.0-1.0-1.0" / "leaderboard.json").read_bytes() == \
            (root_b / "leaderboard" / "1.0-1.0-1.0" / "leaderboard.json").read_bytes()
        assert (tmp_path / "a-pilot-0.json").read_bytes() == (tmp_path / "b-pilot-0.json").read_bytes()

    def test_location_dependent_records_documented(self, tmp_path, git_repo, monkeypatch):
        """protocol/harness.log and protocol/session/session.json embed the
        run's absolute paths; their variability across runs roots is
        expected and documented (they are not deterministic artifacts)."""
        runs = []
        for name in ("a", "b"):
            runs_root = tmp_path / name
            run_synthetic(runs_root=runs_root,
                          dataset_path=tmp_path / f"{name}-pilot-0.json")
            runs.append(_run_root(runs_root))
        run_a, run_b = runs
        log_a = (run_a / "protocol" / "harness.log").read_text(encoding="utf-8")
        log_b = (run_b / "protocol" / "harness.log").read_text(encoding="utf-8")
        # the harness log embeds the reference path (location-dependent)
        assert "synthetic-reference" in log_a and "synthetic-reference" in log_b
        assert "a/synthetic-reference" in log_a and "b/synthetic-reference" in log_b
        session_a = json.loads((run_a / "protocol" / "session" / "session.json").read_text(encoding="utf-8"))
        session_b = json.loads((run_b / "protocol" / "session" / "session.json").read_text(encoding="utf-8"))
        assert session_a["environment"]["cwd"] != session_b["environment"]["cwd"]
        # everything else in the run dir is byte-identical
        tree_a = _tree(run_a)
        tree_b = _tree(run_b)
        for rel in tree_a:
            if rel.startswith(("protocol/harness.log", "protocol/session/session.json")):
                continue
            assert tree_a[rel] == tree_b[rel], rel


class TestFailureInjection:
    """Each injected failure surfaces at the correct pipeline stage."""

    def _make_writable(self, root: Path) -> None:
        """Frozen evidence is chmod read-only; tests restoring writability
        before injecting a fault (test-only)."""
        import os

        for dirpath, dirnames, filenames in os.walk(root):
            os.chmod(dirpath, 0o755)
            for name in filenames:
                os.chmod(Path(dirpath) / name, 0o644)

    def test_corrupted_evidence_fails_gates(self, tmp_path, git_repo, monkeypatch):
        """Corrupted frozen evidence is refused at the P5 gates stage."""
        from tooling.harness.tests.review_fixtures import make_gated_run
        from tooling.harness.util.proc import CommandLog
        from tooling.harness.util.log import harness_log
        from tooling.harness.validation.gates import ValidationError

        run, manifest, status = make_gated_run(tmp_path / "pre", reference_repo=git_repo)
        self._make_writable(run.evidence)
        (run.evidence / "e1-transcript.txt").write_text("corrupted", encoding="utf-8")
        with pytest.raises(ValidationError) as exc:
            from tooling.harness.validation.gates import run_gates

            run_gates(run, manifest, status, reference_root=git_repo,
                      command_log=CommandLog(run.root / "protocol" / "command.log"),
                      log=harness_log(None))
        assert "frozen evidence verification failed" in str(exc.value)

    def test_invalid_report_data_fails_report(self, tmp_path, git_repo, monkeypatch):
        from tooling.harness.report.generator import ReportError

        outcome = run_synthetic(runs_root=tmp_path / "runs",
                                dataset_path=tmp_path / "pilot-0.json")
        run = Path(outcome["run_root"])
        (run / "review" / "scoring" / "scores.json").unlink()
        from tooling.harness.runtime.run_dir import load_run_dir

        run_dir = load_run_dir(run.name, outcome["runs_root"])
        manifest = RunManifest.load(run_dir.manifest_path)
        status = RunStatus.load(run_dir.status_path)
        with pytest.raises(ReportError) as exc:
            from tooling.harness.report.generator import generate_reports

            generate_reports(run_dir, manifest, status)
        assert "scores.json" in str(exc.value)

    def test_failed_validator_rejects_run_at_p5(self, tmp_path, git_repo, monkeypatch):
        from tooling.harness.tests.review_fixtures import make_gated_run
        from tooling.harness.validation.gates import run_gates
        from tooling.harness.util.proc import CommandLog
        from tooling.harness.util.log import harness_log

        run, manifest, status = make_gated_run(tmp_path / "pre", reference_repo=git_repo)
        # a skill root whose rules violate the priority rules makes V1 fail
        bad_rules = tmp_path / "bad-rules"
        (bad_rules / "rules").mkdir(parents=True)
        (bad_rules / "rules" / "bad_priority.json").write_text(
            (Path(__file__).resolve().parents[2] / "validator" / "tests" / "fixtures"
             / "bad_priority.json").read_text(encoding="utf-8"),
            encoding="utf-8",
        )
        run_gates(run, manifest, status, reference_root=git_repo,
                  command_log=CommandLog(run.root / "protocol" / "command.log"),
                  log=harness_log(None), skill_root=bad_rules)
        validation = json.loads((run.validation / "validation.json").read_text(encoding="utf-8"))
        assert validation["summary"]["verdict"] == "REJECTED"
        assert "V1" in validation["summary"]["blocking_failures"]
        assert RunStatus.load(run.status_path).status == "REJECTED"

    def test_rejected_review_refused_at_score(self, tmp_path, git_repo, monkeypatch):
        from tooling.harness.scoring.runner import ScoringError
        from tooling.harness.tests.review_fixtures import rejected_run

        run, manifest, status = rejected_run(tmp_path, reference_repo=git_repo)
        with pytest.raises(ScoringError) as exc:
            from tooling.harness.scoring.runner import run_scoring

            run_scoring(run, manifest, status, scores_json=SYNTHETIC_SCORES)
        assert "REJECTED" in str(exc.value)

    def test_archive_corruption_fails_verification(self, tmp_path, git_repo, monkeypatch):
        from tooling.harness.archive.manager import verify_archive
        from tooling.harness.runtime.run_dir import load_run_dir

        outcome = run_synthetic(runs_root=tmp_path / "runs",
                                dataset_path=tmp_path / "pilot-0.json")
        run = Path(outcome["run_root"])
        (run / "ARCHIVED").unlink()
        run_dir = load_run_dir(run.name, outcome["runs_root"])
        manifest = RunManifest.load(run_dir.manifest_path)
        status = RunStatus.load(run_dir.status_path)
        result = verify_archive(run_dir, manifest, status, runs_root=outcome["runs_root"])
        assert result["passed"] is False
        assert any("ARCHIVED" in d for d in result["divergences"])

    def test_registry_entry_blocks_archive(self, tmp_path, git_repo, monkeypatch):
        """A forged registry entry for a not-yet-archived run is refused
        at the archive preflight."""
        from tooling.harness.archive.manager import ArchiveError
        from tooling.harness.tests.review_fixtures import reviewed_run

        run, manifest, status = reviewed_run(tmp_path, reference_repo=git_repo)
        from tooling.harness.archive.manager import append_registry_entry
        from tooling.harness.report.generator import generate_reports

        generate_reports(run, manifest, status)
        append_registry_entry(tmp_path, {"run_id": run.run_id, "status": "ARCHIVED"})
        with pytest.raises(ArchiveError) as exc:
            from tooling.harness.archive.manager import archive_run

            archive_run(run, manifest, status, runs_root=tmp_path)
        assert "registry entry" in str(exc.value)


class TestSyntheticCLI:
    def test_synthetic_cli_command(self, tmp_path, monkeypatch, capsys):
        monkeypatch.chdir(tmp_path)
        from tooling.harness.cli import main

        runs = tmp_path / "runs"
        exit_code = main(["synthetic", "--runs-root", str(runs),
                          "--dataset", str(tmp_path / "pilot-0.json")])
        assert exit_code == 0
        out = capsys.readouterr().out
        assert "Synthetic benchmark complete" in out
        assert "verification: 0 divergences" in out
        assert "artifact inventory" in out
        assert "reports/report.md" in out
        assert (runs / "index.json").is_file()
