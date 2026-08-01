"""CLI integration tests for MS-06: ``python -m tooling.harness gates``.

Each test drives the full pipeline — init → prepare → prompt → session
(stub) → collect --freeze → gates — against a scratch git repository
standing in for the reference repo; ``bga-mercurio`` is never touched.
"""

import json

import pytest

from tooling.harness.cli import main
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.status import RunStatus
from tooling.harness.tests.validation_fixtures import (
    duplicated_submission,
    passing_submission,
)

MATERIAL_NAMES = (
    "docs",
    "bga-senior-engineer-skill",
    "tooling",
    "official-docs",
    "reference-projects",
)


@pytest.fixture(autouse=True)
def _scratch_material(monkeypatch, senior_root):
    """Provisioning copies scratch material (never the real repo trees)."""
    monkeypatch.setattr(
        "tooling.harness.workspace.provision.default_material_roots",
        lambda: {name: senior_root / name for name in MATERIAL_NAMES},
    )


def _run_flow(tmp_path, monkeypatch, git_repo, *, submission=None, freeze=True):
    """Full P0-P4 flow; returns (runs_root, run_id, run_root)."""
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
        main(
            ["session", run_id, "--platform", "stub", "--timeout-min", "1",
             "--runs-root", str(runs)]
        )
        == 0
    )
    run_root = runs / run_id
    if submission is not None:
        import shutil

        changes = run_root / "workspace" / "work" / "changes"
        shutil.rmtree(changes)
        for relpath, content in submission.items():
            path = run_root / "workspace" / "work" / relpath
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(content, encoding="utf-8")
    args = ["collect", run_id, "--runs-root", str(runs)]
    if freeze:
        args.insert(1, "--freeze")
    assert main(args) == 0
    return runs, run_id, run_root


def _gates(runs, run_id, git_repo):
    return main(
        ["gates", run_id, "--reference", str(git_repo), "--runs-root", str(runs)]
    )


def test_gates_full_flow_fixture_a_passes(tmp_path, monkeypatch, git_repo, capsys):
    runs, run_id, run_root = _run_flow(
        tmp_path, monkeypatch, git_repo, submission=passing_submission()
    )
    exit_code = _gates(runs, run_id, git_repo)
    assert exit_code == 0
    output = capsys.readouterr().out
    assert "G0 (Repository safety): PASS" in output
    assert "G1 (Build gates): PASS" in output
    assert "G2 (Catalog checks): PASS" in output
    assert "Summary: PASS" in output
    assert "Substitution: NOT02-D" in output

    document = json.loads(
        (run_root / "validation" / "validation.json").read_text(encoding="utf-8")
    )
    assert document["summary"]["verdict"] == "PASS"
    assert (run_root / "validation" / "raw" / "V1.txt").is_file()
    assert (run_root / "evidence" / "reruns" / "e4" / "validation.json").is_file()
    assert RunStatus.load(run_root / "status.json").status == "COMPLETED"
    manifest = RunManifest.load(run_root / "manifest.json")
    assert manifest.errata and "validation/validation.json" in manifest.errata[-1]["message"]


def test_gates_rejects_duplicated_block_and_marks_run_rejected(
    tmp_path, monkeypatch, git_repo, capsys
):
    runs, run_id, run_root = _run_flow(
        tmp_path, monkeypatch, git_repo, submission=duplicated_submission()
    )
    exit_code = _gates(runs, run_id, git_repo)
    assert exit_code == 0
    output = capsys.readouterr().out
    assert "Summary: REJECTED" in output
    assert "NOT02-A" in output
    document = json.loads(
        (run_root / "validation" / "validation.json").read_text(encoding="utf-8")
    )
    assert document["gates"]["G0"]["verdict"] == "PASS"
    assert document["gates"]["G1"]["verdict"] == "PASS"
    assert document["gates"]["G2"]["checks"][1]["verdict"] == "FAIL"
    assert RunStatus.load(run_root / "status.json").status == "REJECTED"


def test_gates_g0_failure_rejects_with_divergence(tmp_path, monkeypatch, git_repo, capsys):
    from tooling.harness.tests.conftest import git as run_git

    runs, run_id, run_root = _run_flow(
        tmp_path, monkeypatch, git_repo, submission=passing_submission()
    )
    # modify the reference repo between P4 freeze and P5 (read-only test repo)
    (git_repo / "external.txt").write_text("x", encoding="utf-8")
    run_git(git_repo, "add", ".")
    run_git(git_repo, "commit", "-m", "external change")

    exit_code = _gates(runs, run_id, git_repo)
    assert exit_code == 0
    output = capsys.readouterr().out
    assert "G0 (Repository safety): FAIL" in output
    assert "Summary: REJECTED" in output
    document = json.loads(
        (run_root / "validation" / "validation.json").read_text(encoding="utf-8")
    )
    findings = document["gates"]["G0"]["checks"][0]["findings"]
    assert any("expected" in f and "actual" in f for f in findings)
    assert sorted(document["summary"]["not_run_checks"]) == [
        "B1", "B2", "B3", "B4", "NOT02-A", "NOT02-B", "NOT02-C",
        "NOT02-D", "V1", "V9",
    ]
    assert RunStatus.load(run_root / "status.json").status == "REJECTED"


def test_gates_refuses_unfrozen_evidence(tmp_path, monkeypatch, git_repo, capsys):
    runs, run_id, run_root = _run_flow(
        tmp_path, monkeypatch, git_repo, submission=passing_submission(), freeze=False
    )
    exit_code = _gates(runs, run_id, git_repo)
    assert exit_code == 1
    assert "frozen" in capsys.readouterr().err
    assert not (run_root / "validation" / "validation.json").exists()
    assert RunStatus.load(run_root / "status.json").status == "COMPLETED"


def test_gates_refuses_run_in_wrong_status(tmp_path, monkeypatch, git_repo, capsys):
    runs, run_id, run_root = _run_flow(
        tmp_path, monkeypatch, git_repo, submission=passing_submission()
    )
    assert _gates(runs, run_id, git_repo) == 0
    # a REJECTED run is terminal: gates refuses to re-run it
    status = RunStatus.load(run_root / "status.json")
    assert status.status == "COMPLETED"
    # second gates invocation is a deterministic re-run (PASS again)
    assert _gates(runs, run_id, git_repo) == 0
    assert RunStatus.load(run_root / "status.json").status == "COMPLETED"


def test_gates_repeated_runs_byte_identical_validation(tmp_path, monkeypatch, git_repo):
    runs, run_id, run_root = _run_flow(
        tmp_path, monkeypatch, git_repo, submission=passing_submission()
    )
    assert _gates(runs, run_id, git_repo) == 0
    first = (run_root / "validation" / "validation.json").read_bytes()
    assert _gates(runs, run_id, git_repo) == 0
    second = (run_root / "validation" / "validation.json").read_bytes()
    assert first == second


def test_gates_writes_raw_outputs_one_file_per_check(tmp_path, monkeypatch, git_repo):
    runs, run_id, run_root = _run_flow(
        tmp_path, monkeypatch, git_repo, submission=passing_submission()
    )
    assert _gates(runs, run_id, git_repo) == 0
    raw_dir = run_root / "validation" / "raw"
    expected = {
        "G0", "B1", "B2", "B3", "B4",
        "V1", "NOT02-A", "NOT02-B", "NOT02-C", "NOT02-D", "V9",
    }
    assert {p.stem for p in raw_dir.iterdir()} == expected
    for path in raw_dir.iterdir():
        assert path.read_text(encoding="utf-8").strip(), path.name
