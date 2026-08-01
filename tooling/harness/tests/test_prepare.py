"""Integration tests for MS-02: ``python -m tooling.harness prepare``."""

import stat

import pytest

from tooling.harness.cli import main
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.status import RunStatus
from tooling.harness.safety.baseline import load_baseline
from tooling.harness.environment.collect import ENVIRONMENT_FIELDS, load_environment
from tooling.harness.workspace.provision import count_files
from tooling.harness.tests.conftest import git


MATERIAL_NAMES = (
    "docs",
    "bga-senior-engineer-skill",
    "tooling",
    "official-docs",
    "reference-projects",
)


@pytest.fixture(autouse=True)
def _scratch_material(monkeypatch, senior_root):
    """Every prepare in this module provisions scratch material roots.

    The real bga-senior-engineer material lives on the slow /mnt/c
    filesystem; tests must never copy it.
    """
    monkeypatch.setattr(
        "tooling.harness.workspace.provision.default_material_roots",
        lambda: {name: senior_root / name for name in MATERIAL_NAMES},
    )


@pytest.fixture
def prepared(tmp_path, git_repo, senior_root, monkeypatch):
    """A fully prepared run directory (init + prepare against a scratch repo)."""
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path / "runs")]) == 0
    run_id = next(p.name for p in (tmp_path / "runs").iterdir() if p.is_dir())
    assert main(
        ["prepare", run_id, "--reference", str(git_repo), "--runs-root", str(tmp_path / "runs")]
    ) == 0
    return tmp_path / "runs" / run_id, run_id


def test_prepare_creates_all_p0_p1_artifacts(prepared, git_repo):
    run_root, run_id = prepared
    baseline = load_baseline(run_root / "protocol" / "baseline" / "safety-baseline.json")
    assert baseline["head"] == git(git_repo, "rev-parse", "HEAD").strip()
    assert baseline["status_porcelain"] == ""
    assert baseline["reflog_top"]

    env = load_environment(run_root / "protocol" / "environment.json")
    assert set(env) == set(ENVIRONMENT_FIELDS)
    assert env["reference_head"] == baseline["head"]


def test_prepare_provisions_workspace(prepared, git_repo):
    run_root, _ = prepared
    read_target = run_root / "workspace" / "read" / "bga-mercurio"
    assert count_files(read_target) == count_files(git_repo)
    assert count_files(read_target) > 0
    # read-only at the mode-bit level
    for path in (run_root / "workspace" / "read").rglob("*"):
        assert stat.S_IMODE(path.stat().st_mode) & 0o222 == 0, path
    probe = run_root / "workspace" / "work" / "probe.txt"
    probe.write_text("ok", encoding="utf-8")
    assert probe.read_text() == "ok"
    assert (run_root / "protocol" / "baseline" / "workspace-baseline.diff").read_text() == ""


def test_prepare_records_manifest_and_status(prepared, git_repo):
    run_root, run_id = prepared
    manifest = RunManifest.load(run_root / "manifest.json")
    assert manifest.versions["reference_head"] == git(git_repo, "rev-parse", "HEAD").strip()
    assert manifest.network == "disabled"
    assert "p0" in manifest.phases and manifest.phases["p0"].ended_at
    assert "p1" in manifest.phases
    assert manifest.phases["p1"].started_at and manifest.phases["p1"].ended_at

    status = RunStatus.load(run_root / "status.json")
    assert status.status == "READY"
    assert set(status.checkpoints) == {"p0", "p1"}


def test_prepare_g0_passes_and_reference_unchanged(prepared, git_repo):
    run_root, _ = prepared
    baseline = load_baseline(run_root / "protocol" / "baseline" / "safety-baseline.json")
    assert git(git_repo, "rev-parse", "HEAD").strip() == baseline["head"]
    assert git(git_repo, "status", "--porcelain") == baseline["status_porcelain"]


def test_prepare_refuses_already_prepared_run(prepared, capsys):
    run_root, run_id = prepared
    assert main(["prepare", run_id, "--runs-root", str(run_root.parent)]) == 1
    assert "already prepared" in capsys.readouterr().err
    status = RunStatus.load(run_root / "status.json")
    assert status.status == "READY"


def test_prepare_missing_tool_blocks_run(tmp_path, git_repo, senior_root, monkeypatch, capsys):
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path / "runs")]) == 0
    run_id = next(p.name for p in (tmp_path / "runs").iterdir() if p.is_dir())
    monkeypatch.setattr(
        "tooling.harness.cli.collect_environment",
        _with_missing_php(git_repo),
    )
    exit_code = main(
        ["prepare", run_id, "--reference", str(git_repo), "--runs-root", str(tmp_path / "runs")]
    )
    assert exit_code == 1
    run_root = tmp_path / "runs" / run_id
    status = RunStatus.load(run_root / "status.json")
    assert status.status == "BLOCKED"
    assert status.blocked_reason and "php" in status.blocked_reason
    assert "blocked" in capsys.readouterr().err


def _with_missing_php(repo):
    """Wrap the real collector, simulating a host without php."""
    import tooling.harness.environment.collect as collect

    def wrapped(reference, *, network="disabled"):
        import shutil

        real_which = shutil.which
        shutil.which = lambda name: None if name == "php" else real_which(name)
        try:
            return collect.collect_environment(reference, network=network)
        finally:
            shutil.which = real_which

    return wrapped


def test_blocked_run_can_be_reprepared_after_fix(tmp_path, git_repo, senior_root, monkeypatch):
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path / "runs")]) == 0
    run_id = next(p.name for p in (tmp_path / "runs").iterdir() if p.is_dir())
    monkeypatch.setattr("tooling.harness.cli.collect_environment", _with_missing_php(git_repo))
    assert main(
        ["prepare", run_id, "--reference", str(git_repo), "--runs-root", str(tmp_path / "runs")]
    ) == 1
    run_root = tmp_path / "runs" / run_id
    assert RunStatus.load(run_root / "status.json").status == "BLOCKED"
    # environment fixed: re-prepare succeeds; workspace is skipped, not re-copied
    monkeypatch.undo()
    exit_code = main(
        ["prepare", run_id, "--reference", str(git_repo), "--runs-root", str(tmp_path / "runs")]
    )
    assert exit_code == 0
    status = RunStatus.load(run_root / "status.json")
    assert status.status == "READY"
    assert status.blocked_reason is None
    assert count_files(run_root / "workspace" / "read" / "bga-mercurio") == count_files(git_repo)


def test_prepare_fails_with_precise_diff_when_reference_changes_mid_prepare(
    tmp_path, git_repo, senior_root, monkeypatch, capsys
):
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path / "runs")]) == 0
    run_id = next(p.name for p in (tmp_path / "runs").iterdir() if p.is_dir())

    def modifying_collector(reference, *, network="disabled"):
        # simulate a concurrent modification of the reference between the
        # P0 baseline capture and the final G0 re-verification
        (git_repo / "sneaky.txt").write_text("x", encoding="utf-8")
        git(git_repo, "add", ".")
        git(git_repo, "commit", "-m", "external change")
        import tooling.harness.environment.collect as collect

        return collect.collect_environment(reference, network=network)

    monkeypatch.setattr("tooling.harness.cli.collect_environment", modifying_collector)
    exit_code = main(
        ["prepare", run_id, "--reference", str(git_repo), "--runs-root", str(tmp_path / "runs")]
    )
    assert exit_code == 1
    run_root = tmp_path / "runs" / run_id
    status = RunStatus.load(run_root / "status.json")
    assert status.status == "BLOCKED"
    assert status.blocked_reason
    err = capsys.readouterr().err
    assert "G0 safety verification FAILED" in status.blocked_reason
    new_head = git(git_repo, "rev-parse", "HEAD").strip()
    assert new_head in status.blocked_reason


def test_prepare_rejects_bad_run_id(tmp_path, capsys):
    assert main(["prepare", "nope", "--runs-root", str(tmp_path)]) == 1
    assert "error" in capsys.readouterr().err


def test_prepare_rejects_non_git_reference(tmp_path, git_repo, capsys):
    plain = tmp_path / "plain"
    plain.mkdir()
    (plain / "x.txt").write_text("x", encoding="utf-8")
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path / "runs")]) == 0
    run_id = next(p.name for p in (tmp_path / "runs").iterdir() if p.is_dir())
    exit_code = main(["prepare", run_id, "--reference", str(plain), "--runs-root", str(tmp_path / "runs")])
    assert exit_code == 1
    assert "not a git repository" in capsys.readouterr().err
    # run state untouched
    from tooling.harness.runtime.status import RunStatus

    assert RunStatus.load(tmp_path / "runs" / run_id / "status.json").status == "INITIALIZING"
