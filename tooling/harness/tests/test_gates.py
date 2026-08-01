"""Unit/integration tests for the gate runner (MS-06, MVB-015/018).

Each test builds a fully frozen run (via the shared fixture) against a
scratch git repository standing in for ``bga-mercurio``; the reference
repository itself is never used and never modified.
"""

import json
from pathlib import Path

import pytest

from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.status import RunStatus
from tooling.harness.tests.conftest import git
from tooling.harness.tests.validation_fixtures import (
    build_frozen_run,
    duplicated_submission,
    load_validation,
    passing_submission,
    undeclared_file_submission,
)
from tooling.harness.util.log import harness_log
from tooling.harness.util.proc import CommandLog
from tooling.harness.validation.gates import ValidationError, run_gates
from tooling.harness.validation.result import VERDICTS


def _gate(run, reference_repo, *, command_log=None, log=None):
    command_log = command_log or CommandLog(run.root / "protocol" / "command.log")
    log = log or harness_log(None)
    return run_gates(
        run,
        RunManifest.load(run.manifest_path),
        RunStatus.load(run.status_path),
        reference_root=reference_repo,
        command_log=command_log,
        log=log,
    )


def _status(run):
    return RunStatus.load(run.status_path).status


@pytest.fixture
def scratch_repo(tmp_path):
    repo = tmp_path / "reference"
    repo.mkdir()
    git(repo, "init", "-b", "main")
    (repo / "file.txt").write_text("one\n", encoding="utf-8")
    git(repo, "add", ".")
    git(repo, "commit", "-m", "first commit")
    return repo


class TestSuccessfulValidation:
    def test_fixture_a_all_gates_pass(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        outcome = _gate(run, scratch_repo)
        assert outcome["exit_code"] == 0
        summary = outcome["summary"]
        assert summary["verdict"] == "PASS"
        assert summary["rejected"] is False
        assert summary["blocking_failures"] == []
        assert summary["non_blocking_findings"] == []
        assert summary["check_count"] == 11
        assert summary["executed_check_count"] == 11
        assert _status(run) == "COMPLETED"

    def test_validation_json_schema_and_gate_order(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        _gate(run, scratch_repo)
        document = load_validation(run)
        assert document["schema"] == "benchmark-harness-validation/1.0"
        assert document["run_id"] == run.run_id
        assert document["task_id"] == "NOT-02"
        assert document["evidence_root_hash"]
        # gate ordering per the specification: G0 -> G1 -> G2
        assert list(document["gates"]) == ["G0", "G1", "G2"]
        assert [c["id"] for c in document["gates"]["G1"]["checks"]] == [
            "B1", "B2", "B3", "B4",
        ]
        assert [c["id"] for c in document["gates"]["G2"]["checks"]] == [
            "V1", "NOT02-A", "NOT02-B", "NOT02-C", "NOT02-D", "V9",
        ]
        assert document["gates"]["G0"]["verdict"] == "PASS"
        assert document["gates"]["G1"]["verdict"] == "PASS"
        assert document["gates"]["G2"]["verdict"] == "PASS"

    def test_raw_outputs_one_file_per_check_id(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        _gate(run, scratch_repo)
        raw_dir = run.root / "validation" / "raw"
        expected = {
            "G0", "B1", "B2", "B3", "B4",
            "V1", "NOT02-A", "NOT02-B", "NOT02-C", "NOT02-D", "V9",
        }
        assert {p.stem for p in raw_dir.iterdir()} == expected
        for path in raw_dir.iterdir():
            assert path.read_text(encoding="utf-8").strip(), path.name

    def test_v1_record_invokes_validator_through_public_cli(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        _gate(run, scratch_repo)
        document = load_validation(run)
        v1 = document["gates"]["G2"]["checks"][0]
        assert v1["id"] == "V1"
        assert v1["verdict"] == "PASS"
        assert v1["version"] == "1.0.0"
        assert v1["exit_code"] == 0
        assert "-m tooling.validator" in v1["executed_by"]
        assert v1["raw_output"] == "raw/V1.txt"
        raw = (run.root / "validation" / "raw" / "V1.txt").read_text(encoding="utf-8")
        assert "Runtime Specification Validator" in raw

    def test_reruns_evidence_appended_to_frozen_tree(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        _gate(run, scratch_repo)
        reruns = run.evidence / "reruns"
        assert (reruns / "e4" / "validation.json").is_file()
        assert (reruns / "e4" / "raw" / "V1.txt").is_file()
        catalog = json.loads((reruns / "catalog.json").read_text(encoding="utf-8"))
        assert catalog["schema"] == "benchmark-harness-evidence-reruns/1.0"
        entries = catalog["entries"]
        for relative, entry in entries.items():
            path = reruns / relative
            assert path.is_file()
            assert entry["size"] == path.stat().st_size
            assert len(entry["sha256"]) == 64
        assert "e4/validation.json" in entries

    def test_manifest_errata_records_p5(self, tmp_path, scratch_repo):
        run, manifest, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        _gate(run, scratch_repo)
        reloaded = RunManifest.load(run.manifest_path)
        assert reloaded.frozen is True
        assert len(reloaded.errata) == 1
        assert "validation/validation.json" in reloaded.errata[0]["message"]
        assert "PASS" in reloaded.errata[0]["message"]

    def test_status_checkpoint_p5_recorded(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        _gate(run, scratch_repo)
        assert "p5" in _status_record(run).checkpoints

    def test_checks_execute_only_against_diff_bundle(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        _gate(run, scratch_repo)
        assert git(scratch_repo, "status", "--porcelain") == ""
        assert git(scratch_repo, "rev-parse", "HEAD")


class TestRejections:
    def test_fixture_b_duplicated_block_rejects(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=duplicated_submission()
        )
        outcome = _gate(run, scratch_repo)
        assert outcome["summary"]["verdict"] == "REJECTED"
        assert outcome["summary"]["rejected"] is True
        assert outcome["summary"]["blocking_failures"] == ["NOT02-A", "NOT02-B"]
        # G0 and G1 are still recorded
        document = load_validation(run)
        assert document["gates"]["G0"]["verdict"] == "PASS"
        assert document["gates"]["G1"]["verdict"] == "PASS"
        not02_a = document["gates"]["G2"]["checks"][1]
        assert not02_a["verdict"] == "FAIL"
        assert _status(run) == "REJECTED"

    def test_fixture_c_undeclared_file_fails_b4(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=undeclared_file_submission()
        )
        outcome = _gate(run, scratch_repo)
        assert outcome["summary"]["verdict"] == "REJECTED"
        assert outcome["summary"]["blocking_failures"] == ["B4"]
        document = load_validation(run)
        assert document["gates"]["G1"]["verdict"] == "FAIL"
        # G1 failure short-circuits G2 per §2.1 ("not evaluated further")
        assert document["gates"]["G2"]["verdict"] == "NOT_RUN"
        assert sorted(document["summary"]["not_run_checks"]) == [
            "NOT02-A", "NOT02-B", "NOT02-C", "NOT02-D", "V1", "V9",
        ]
        assert _status(run) == "REJECTED"

    def test_g0_failure_rejects_with_precise_divergence(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        (scratch_repo / "sneaky.txt").write_text("x", encoding="utf-8")
        git(scratch_repo, "add", ".")
        git(scratch_repo, "commit", "-m", "external change")
        outcome = _gate(run, scratch_repo)
        assert outcome["summary"]["verdict"] == "REJECTED"
        document = load_validation(run)
        g0 = document["gates"]["G0"]["checks"][0]
        assert g0["verdict"] == "FAIL"
        assert any("head" in f for f in g0["findings"])
        assert any("expected" in f and "actual" in f for f in g0["findings"])
        new_head = git(scratch_repo, "rev-parse", "HEAD").strip()
        assert any(new_head in f for f in g0["findings"])
        # every later gate short-circuited
        assert sorted(document["summary"]["not_run_checks"]) == [
            "B1", "B2", "B3", "B4", "NOT02-A", "NOT02-B", "NOT02-C",
            "NOT02-D", "V1", "V9",
        ]
        assert _status(run) == "REJECTED"


class TestBlockedAndPreconditions:
    def test_missing_diff_bundle_blocks_build_and_content_gates(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path,
            reference_repo=scratch_repo,
            work_files={"subsystems.md": "# empty submission\n"},
        )
        outcome = _gate(run, scratch_repo)
        assert outcome["summary"]["verdict"] == "BLOCKED"
        assert outcome["exit_code"] == 1
        assert sorted(outcome["summary"]["blocked_checks"]) == [
            "B1", "B2", "B3", "B4", "NOT02-A", "NOT02-B", "NOT02-C", "V9",
        ]
        # blocked gates never reject the run (re-runnable per §5.1)
        assert _status(run) == "COMPLETED"
        document = load_validation(run)
        assert document["gates"]["G1"]["verdict"] == "BLOCKED"
        assert document["gates"]["G0"]["verdict"] == "PASS"

    def test_missing_baseline_blocks_g0_but_not_later_gates(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        (run.baseline / "safety-baseline.json").unlink()
        outcome = _gate(run, scratch_repo)
        assert outcome["summary"]["verdict"] == "BLOCKED"
        document = load_validation(run)
        assert document["gates"]["G0"]["checks"][0]["verdict"] == "BLOCKED"
        assert document["gates"]["G1"]["verdict"] == "PASS"
        assert _status(run) == "COMPLETED"

    def test_unfrozen_run_is_refused(self, tmp_path, scratch_repo):
        run, manifest, status = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        # unfreeze the markers (simulating collect without --freeze)
        catalog_path = run.evidence / "evidence.json"
        catalog = json.loads(catalog_path.read_text(encoding="utf-8"))
        catalog["frozen"] = False
        catalog_path.write_text(json.dumps(catalog), encoding="utf-8")
        manifest.frozen = False
        manifest.save(run.manifest_path)
        with pytest.raises(ValidationError) as exc:
            _gate(run, scratch_repo)
        assert "not frozen" in str(exc.value)

    def test_run_in_wrong_status_is_refused(self, tmp_path, scratch_repo):
        run, _, status = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        status.status = "READY"
        status.save(run.status_path)
        with pytest.raises(ValidationError) as exc:
            _gate(run, scratch_repo)
        assert "READY" in str(exc.value)

    def test_corrupted_evidence_is_refused(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        (run.evidence / "e1-transcript.txt").write_text("corrupted", encoding="utf-8")
        with pytest.raises(ValidationError) as exc:
            _gate(run, scratch_repo)
        assert "frozen evidence verification failed" in str(exc.value)

    def test_blocked_gates_leave_run_rerunnable_and_deterministic(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path,
            reference_repo=scratch_repo,
            work_files={"subsystems.md": "# empty\n"},
        )
        command_log = CommandLog(run.root / "protocol" / "command.log")
        _gate(run, scratch_repo, command_log=command_log)
        assert _status(run) == "COMPLETED"
        first = (run.root / "validation" / "validation.json").read_bytes()
        # a re-run on identical frozen evidence stays BLOCKED, never rejects
        outcome = _gate(run, scratch_repo, command_log=command_log)
        assert outcome["summary"]["verdict"] == "BLOCKED"
        assert _status(run) == "COMPLETED"
        assert (run.root / "validation" / "validation.json").read_bytes() == first


class TestDeterminism:
    def test_repeated_gates_produce_byte_identical_validation_json(self, tmp_path, scratch_repo):
        run, manifest, status = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        command_log = CommandLog(run.root / "protocol" / "command.log")
        _gate(run, scratch_repo, command_log=command_log)
        first = (run.root / "validation" / "validation.json").read_bytes()
        first_e4 = (run.evidence / "reruns" / "e4" / "validation.json").read_bytes()
        _gate(run, scratch_repo, command_log=command_log)
        second = (run.root / "validation" / "validation.json").read_bytes()
        second_e4 = (run.evidence / "reruns" / "e4" / "validation.json").read_bytes()
        assert first == second
        assert first_e4 == second_e4
        # only the raw validator report carries the validator's own
        # volatile timestamp line; everything else is byte-identical

    def test_identical_runs_produce_identical_validation(self, tmp_path, scratch_repo):
        run_a, _, _ = build_frozen_run(
            tmp_path / "a", reference_repo=scratch_repo, work_files=passing_submission()
        )
        run_b, _, _ = build_frozen_run(
            tmp_path / "b", reference_repo=scratch_repo, work_files=passing_submission()
        )
        _gate(run_a, scratch_repo)
        _gate(run_b, scratch_repo)
        assert (run_a.root / "validation" / "validation.json").read_bytes() == (
            run_b.root / "validation" / "validation.json"
        ).read_bytes()

    def test_validation_json_has_no_wall_clock_values(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        _gate(run, scratch_repo)
        text = (run.root / "validation" / "validation.json").read_text(encoding="utf-8")
        assert "Timestamp" not in text
        assert "wall_time" not in text
        assert "execution_time" not in text

    def test_gate_verdicts_are_exactly_the_four_allowed(self, tmp_path, scratch_repo):
        run, _, _ = build_frozen_run(
            tmp_path, reference_repo=scratch_repo, work_files=passing_submission()
        )
        _gate(run, scratch_repo)
        document = load_validation(run)
        for gate in document["gates"].values():
            assert gate["verdict"] in VERDICTS
            for check in gate["checks"]:
                assert check["verdict"] in VERDICTS


def _status_record(run):
    return RunStatus.load(run.status_path)
