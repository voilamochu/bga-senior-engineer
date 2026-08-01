"""Integration tests for the MS-03 CLI: ``python -m tooling.harness prompt``."""

import json
import stat

import pytest

from tooling.harness.cli import main
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.status import RunStatus
from tooling.harness.util.hash import sha256_text


@pytest.fixture
def fresh_run(tmp_path, monkeypatch):
    """A freshly initialized (unprepared) run; returns (run_root, run_id)."""
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path / "runs")]) == 0
    run_id = next(p.name for p in (tmp_path / "runs").iterdir() if p.is_dir())
    return tmp_path / "runs" / run_id, run_id


def _prompt_args(run_id, runs_root):
    return ["prompt", run_id, "--runs-root", str(runs_root)]


def test_prompt_generates_bundle_and_records_hashes(fresh_run, tmp_path):
    run_root, run_id = fresh_run
    assert main(_prompt_args(run_id, tmp_path / "runs")) == 0

    bundle_path = run_root / "protocol" / "prompt-bundle.txt"
    sha_path = run_root / "protocol" / "prompt-bundle.sha256"
    system_path = run_root / "protocol" / "system-prompt.txt"
    assert bundle_path.is_file()
    assert sha_path.is_file()
    assert system_path.is_file()
    assert system_path.read_text().startswith("# BGA Senior Engineer — Benchmark System Prompt")

    bundle = bundle_path.read_text()
    digest = sha_text(bundle)
    assert sha_path.read_text() == f"{digest}  prompt-bundle.txt\n"

    manifest = RunManifest.load(run_root / "manifest.json")
    assert manifest.prompt_bundle_sha256 == digest
    assert manifest.prompt["bundle_sha256"] == digest
    assert manifest.prompt["skill_task"] == "migrate-notifications"
    assert manifest.task == {"id": "NOT-02", "difficulty": "Easy"}
    assert "p2" in manifest.phases
    # bundle files are immutable
    for path in (bundle_path, sha_path, system_path):
        assert stat.S_IMODE(path.stat().st_mode) == 0o444


def sha_text(text):
    from tooling.harness.util.hash import sha256_text

    return sha256_text(text)


def test_prompt_bundle_has_all_expected_sections(fresh_run, tmp_path):
    run_root, run_id = fresh_run
    assert main(_prompt_args(run_id, tmp_path / "runs")) == 0
    bundle = (run_root / "protocol" / "prompt-bundle.txt").read_text()
    for section in (
        "# BGA Senior Engineer Benchmark — Prompt Bundle",
        "# SYSTEM PROMPT",
        "# BENCHMARK PROMPT",
        "# SKILL ARTIFACTS",
        "## Repository Safety (MANDATORY)",
        "## Task",
        "## Expected Outcomes",
        "## Success Criteria",
        "## Required Evidence",
        "## Environment",
        "## Submission",
        "## Attached Documents",
        "## Skill Task: migrate-notifications",
        "## Mandatory Rules",
        "## Lazy Rule Declarations",
        "## Checklists",
        "## Examples",
        "## References",
    ):
        assert section in bundle, section
    assert "### rules/constitution.json" in bundle
    assert "### rules/notifications.json" in bundle
    assert "### prompts/migrate-notifications.md" in bundle
    assert "- docs/evaluation/benchmark-evaluation-spec.md" in bundle
    assert "- bga-senior-engineer-skill/rules/notifications.json" in bundle


def test_two_fresh_runs_produce_byte_identical_bundles(tmp_path, monkeypatch):
    def run_prompt():
        monkeypatch.chdir(tmp_path)
        assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path / "runs")]) == 0
        run_id = next(p.name for p in (tmp_path / "runs").iterdir() if p.is_dir())
        assert main(_prompt_args(run_id, tmp_path / "runs")) == 0
        return (tmp_path / "runs" / run_id / "protocol" / "prompt-bundle.txt").read_text()

    first = run_prompt()
    second = run_prompt()
    assert first == second
    assert sha_text(first) == sha_text(second)


def test_prompt_refuses_second_generation(fresh_run, tmp_path, capsys):
    run_root, run_id = fresh_run
    assert main(_prompt_args(run_id, tmp_path / "runs")) == 0
    assert main(_prompt_args(run_id, tmp_path / "runs")) == 1
    assert "already has a prompt bundle" in capsys.readouterr().err
    # run state unchanged
    status = RunStatus.load(run_root / "status.json")
    assert status.status == "INITIALIZING"


def test_prompt_unknown_task_fails_cleanly(tmp_path, monkeypatch, capsys):
    monkeypatch.chdir(tmp_path)
    assert main(["init", "ZZZ-99", "demo-model", "--runs-root", str(tmp_path / "runs")]) == 0
    run_id = next(p.name for p in (tmp_path / "runs").iterdir() if p.is_dir())
    exit_code = main(_prompt_args(run_id, tmp_path / "runs"))
    assert exit_code == 1
    err = capsys.readouterr().err
    assert "error" in err
    # no bundle artifacts were written
    run_root = tmp_path / "runs" / run_id
    assert not (run_root / "protocol" / "prompt-bundle.txt").exists()


def test_prompt_missing_skill_path_fails_cleanly(fresh_run, tmp_path, capsys):
    run_root, run_id = fresh_run
    exit_code = main(
        ["prompt", run_id, "--skill", str(tmp_path / "nope"), "--runs-root", str(tmp_path / "runs")]
    )
    assert exit_code == 1
    assert "skill package" in capsys.readouterr().err
    assert not (run_root / "protocol" / "prompt-bundle.txt").exists()


def test_prompt_works_after_prepare(tmp_path, git_repo, monkeypatch):
    """The acceptance flow: init -> prepare -> prompt."""
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path / "runs")]) == 0
    run_id = next(p.name for p in (tmp_path / "runs").iterdir() if p.is_dir())
    assert main(
        ["prepare", run_id, "--reference", str(git_repo), "--runs-root", str(tmp_path / "runs")]
    ) == 0
    assert main(_prompt_args(run_id, tmp_path / "runs")) == 0
    run_root = tmp_path / "runs" / run_id
    manifest = RunManifest.load(run_root / "manifest.json")
    assert manifest.prompt_bundle_sha256
    status = RunStatus.load(run_root / "status.json")
    assert status.status == "READY"


def test_prompt_metadata_json(fresh_run, tmp_path):
    run_root, run_id = fresh_run
    assert main(_prompt_args(run_id, tmp_path / "runs")) == 0
    data = json.loads((run_root / "protocol" / "prompt-bundle.json").read_text())
    assert data["task"] == "migrate-notifications"
    artifact_paths = {entry["path"] for entry in data["artifacts"]}
    assert "prompts/migrate-notifications.md" in artifact_paths
    assert "rules/constitution.json" in artifact_paths
    assert "rules/notifications.json" in artifact_paths
    assert "checklists/pre-commit.json" in artifact_paths
    assert "examples/notification-example.json" in artifact_paths
