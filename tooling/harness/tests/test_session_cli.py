"""Integration tests for the MS-04 CLI: ``python -m tooling.harness session``."""

import json

import pytest

from tooling.harness.cli import main
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.status import RunStatus

MATERIAL_NAMES = (
    "docs",
    "bga-senior-engineer-skill",
    "tooling",
    "official-docs",
    "reference-projects",
)


@pytest.fixture(autouse=True)
def _scratch_material(monkeypatch, senior_root):
    """Prepare in this module provisions scratch material roots only."""
    monkeypatch.setattr(
        "tooling.harness.workspace.provision.default_material_roots",
        lambda: {name: senior_root / name for name in MATERIAL_NAMES},
    )


def _prepare_flow(tmp_path, monkeypatch, git_repo, *, timeout_min="1"):
    """init -> prepare -> prompt -> (return run_id)."""
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path / "runs")]) == 0
    run_id = next(p.name for p in (tmp_path / "runs").iterdir() if p.is_dir())
    assert main(
        ["prepare", run_id, "--reference", str(git_repo), "--runs-root", str(tmp_path / "runs")]
    ) == 0
    assert main(["prompt", run_id, "--runs-root", str(tmp_path / "runs")]) == 0
    return run_id


def _session_args(run_id, runs_root, **extra):
    args = ["session", run_id, "--runs-root", str(runs_root), "--platform", "stub"]
    for key, value in extra.items():
        flag = f"--{key.replace('_', '-')}"
        if value is True:
            args.append(flag)
        else:
            args.extend([flag, str(value)])
    return args


def test_dry_run_verifies_boundaries_and_prints_bundle_hash(tmp_path, monkeypatch, git_repo, capsys):
    run_id = _prepare_flow(tmp_path, monkeypatch, git_repo)
    run_root = tmp_path / "runs" / run_id
    assert main(_session_args(run_id, tmp_path / "runs", dry_run=True)) == 0
    captured = capsys.readouterr().out
    manifest = RunManifest.load(run_root / "manifest.json")
    assert f"Prompt bundle SHA-256: {manifest.prompt_bundle_sha256}" in captured
    assert "workspace boundaries OK" in captured
    # dry run leaves state untouched
    assert RunStatus.load(run_root / "status.json").status == "READY"
    assert not (run_root / "protocol" / "session").exists()


def test_dry_run_fails_on_missing_bundle(tmp_path, monkeypatch, git_repo, capsys):
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path / "runs")]) == 0
    run_id = next(p.name for p in (tmp_path / "runs").iterdir() if p.is_dir())
    run_root = tmp_path / "runs" / run_id
    # a READY run without a prompt bundle (prompt never ran)
    status = RunStatus.load(run_root / "status.json")
    status.transition("READY", checkpoint="p1")
    status.save(run_root / "status.json")
    assert main(_session_args(run_id, tmp_path / "runs", dry_run=True)) == 1
    assert "prompt bundle" in capsys.readouterr().err


def test_session_full_flow_with_stub(tmp_path, monkeypatch, git_repo, capsys):
    run_id = _prepare_flow(tmp_path, monkeypatch, git_repo)
    run_root = tmp_path / "runs" / run_id
    assert main(_session_args(run_id, tmp_path / "runs", timeout_min=1)) == 0
    captured = capsys.readouterr().out
    assert "Run status: COMPLETED" in captured
    assert "Submission: complete" in captured

    status = RunStatus.load(run_root / "status.json")
    assert status.status == "COMPLETED"
    assert set(status.checkpoints) == {"p0", "p1", "p2", "p3"}

    manifest = RunManifest.load(run_root / "manifest.json")
    assert manifest.execution["exit_status"] == "completed"
    assert manifest.execution["platform"] == "stub"
    assert manifest.execution["model"] == "demo-model"
    assert manifest.execution["restarts"] == 0
    assert manifest.execution["timeouts"]["p3_budget_seconds"] == 60
    assert manifest.submission_status == "complete"

    # raw artifacts
    session_dir = run_root / "protocol" / "session"
    assert (session_dir / "transcript.txt").read_text().startswith("# Stub Agent Session")
    assert (session_dir / "raw-response.txt").is_file()
    assert (session_dir / "session.json").is_file()
    assert (session_dir / "intake.json").is_file()
    # command log has records with all E3 fields
    command_log = (run_root / "protocol" / "command.log").read_text().splitlines()
    assert command_log, "command log empty"
    record = json.loads(command_log[0])
    assert set(record) >= {"command", "exit_code", "stdout", "stderr", "wall_time"}
    # submission in work/
    for name in ("reasoning.md", "architecture.md", "subsystems.md",
                 "testing-evidence.md", "validation-evidence.md",
                 "declaration.json"):
        assert (run_root / "workspace" / "work" / name).is_file()
    assert (run_root / "workspace" / "work" / "changes").is_dir()


def test_session_refuses_repeat_execution(tmp_path, monkeypatch, git_repo, capsys):
    run_id = _prepare_flow(tmp_path, monkeypatch, git_repo)
    assert main(_session_args(run_id, tmp_path / "runs", timeout_min=1)) == 0
    assert main(_session_args(run_id, tmp_path / "runs", timeout_min=1)) == 1
    assert "expected READY or RUNNING" in capsys.readouterr().err


def test_session_resume_after_interrupted_run(tmp_path, monkeypatch, git_repo):
    run_id = _prepare_flow(tmp_path, monkeypatch, git_repo)
    run_root = tmp_path / "runs" / run_id
    # simulate an interruption mid-P3: status RUNNING, execution recorded
    status = RunStatus.load(run_root / "status.json")
    status.transition("RUNNING", checkpoint="p2")
    status.save(run_root / "status.json")
    manifest = RunManifest.load(run_root / "manifest.json")
    manifest.start_phase("p3")
    manifest.save(run_root / "manifest.json")

    assert main(_session_args(run_id, tmp_path / "runs", timeout_min=1)) == 0
    status2 = RunStatus.load(run_root / "status.json")
    assert status2.status == "COMPLETED"
    manifest2 = RunManifest.load(run_root / "manifest.json")
    assert manifest2.execution["restarts"] == 1
    # P0-P2 phase records unchanged
    assert manifest2.phases["p0"].started_at == manifest.phases["p0"].started_at
    assert manifest2.phases["p3"].started_at == manifest.phases["p3"].started_at


def test_session_deterministic_replay(tmp_path, monkeypatch, git_repo):
    run_id = _prepare_flow(tmp_path, monkeypatch, git_repo)
    run_root = tmp_path / "runs" / run_id
    assert main(_session_args(run_id, tmp_path / "runs", timeout_min=1)) == 0
    first_transcript = (run_root / "protocol" / "session" / "transcript.txt").read_text()
    first_response = (run_root / "protocol" / "session" / "raw-response.txt").read_text()
    first_submission = (run_root / "workspace" / "work" / "reasoning.md").read_text()

    # second fresh run: identical artifacts
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path / "runs")]) == 0
    run_id2 = next(p.name for p in (tmp_path / "runs").iterdir() if p.is_dir() and p.name != run_id)
    assert main(
        ["prepare", run_id2, "--reference", str(git_repo), "--runs-root", str(tmp_path / "runs")]
    ) == 0
    assert main(["prompt", run_id2, "--runs-root", str(tmp_path / "runs")]) == 0
    assert main(_session_args(run_id2, tmp_path / "runs", timeout_min=1)) == 0
    run_root2 = tmp_path / "runs" / run_id2
    assert (run_root2 / "protocol" / "session" / "transcript.txt").read_text() == first_transcript
    assert (run_root2 / "protocol" / "session" / "raw-response.txt").read_text() == first_response
    assert (run_root2 / "workspace" / "work" / "reasoning.md").read_text() == first_submission
