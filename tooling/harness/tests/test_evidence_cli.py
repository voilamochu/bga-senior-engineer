"""Integration tests for the MS-05 CLI: ``python -m tooling.harness collect``."""

import json
import stat

import pytest

from tooling.harness.cli import main
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.status import RunStatus


def _prepare_flow(runs, run_id, git_repo):
    assert main(["prepare", run_id, "--reference", str(git_repo), "--runs-root", str(runs)]) == 0
    assert main(["prompt", run_id, "--runs-root", str(runs)]) == 0
    assert main(["session", run_id, "--platform", "stub", "--timeout-min", "1",
                 "--runs-root", str(runs)]) == 0


def test_collect_without_freeze_records_catalog(tmp_path, monkeypatch, git_repo):
    runs = tmp_path / "runs"
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(runs)]) == 0
    run_id = next(p.name for p in runs.iterdir() if p.is_dir())
    _prepare_flow(runs, run_id, git_repo)
    run_root = runs / run_id

    assert main(["collect", run_id, "--runs-root", str(runs)]) == 0
    catalog = json.loads((run_root / "evidence" / "evidence.json").read_text())
    assert catalog["frozen"] is False
    assert catalog["types"]["E1"]["status"] == "present"
    assert catalog["types"]["E4"]["status"] == "absent"
    assert catalog["types"]["E8"]["status"] == "present"  # stub changes/
    manifest = RunManifest.load(run_root / "manifest.json")
    assert manifest.evidence["frozen"] is False
    assert manifest.frozen is False
    # evidence dir still writable before freeze
    probe = run_root / "evidence" / "probe.txt"
    probe.write_text("x")


def test_collect_freeze_full_flow(tmp_path, monkeypatch, git_repo, capsys):
    runs = tmp_path / "runs"
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(runs)]) == 0
    run_id = next(p.name for p in runs.iterdir() if p.is_dir())
    _prepare_flow(runs, run_id, git_repo)
    run_root = runs / run_id

    assert main(["collect", run_id, "--freeze", "--runs-root", str(runs)]) == 0
    output = capsys.readouterr().out
    assert "Evidence frozen" in output
    assert "Root hash:" in output

    catalog = json.loads((run_root / "evidence" / "evidence.json").read_text())
    assert catalog["frozen"] is True
    assert len(catalog["root_hash"]) == 64
    manifest = RunManifest.load(run_root / "manifest.json")
    assert manifest.frozen is True
    assert manifest.evidence_root_hash == catalog["root_hash"]
    assert manifest.evidence["artifact_count"] > 0
    status = RunStatus.load(run_root / "status.json")
    assert "p4" in status.checkpoints
    # filesystem immutability
    for path in (run_root / "evidence").rglob("*"):
        if path.name == "reruns":
            continue
        assert stat.S_IMODE(path.stat().st_mode) & 0o222 == 0, path
    assert (run_root / "evidence" / "reruns").is_dir()


def test_collect_refuses_after_freeze(tmp_path, monkeypatch, git_repo, capsys):
    runs = tmp_path / "runs"
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(runs)]) == 0
    run_id = next(p.name for p in runs.iterdir() if p.is_dir())
    _prepare_flow(runs, run_id, git_repo)
    assert main(["collect", run_id, "--freeze", "--runs-root", str(runs)]) == 0
    assert main(["collect", run_id, "--runs-root", str(runs)]) == 1
    assert "frozen" in capsys.readouterr().err


def test_collect_refuses_before_p3(tmp_path, monkeypatch, git_repo, capsys):
    runs = tmp_path / "runs"
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(runs)]) == 0
    run_id = next(p.name for p in runs.iterdir() if p.is_dir())
    assert main(["collect", run_id, "--runs-root", str(runs)]) == 1
    assert "P3" in capsys.readouterr().err


def test_verify_detects_corruption_in_cli_run(tmp_path, monkeypatch, git_repo):
    from tooling.harness.runtime.run_dir import load_run_dir
    from tooling.harness.evidence.freeze import verify_frozen_evidence

    runs = tmp_path / "runs"
    monkeypatch.chdir(tmp_path)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(runs)]) == 0
    run_id = next(p.name for p in runs.iterdir() if p.is_dir())
    _prepare_flow(runs, run_id, git_repo)
    assert main(["collect", run_id, "--freeze", "--runs-root", str(runs)]) == 0
    run_root = runs / run_id

    run = load_run_dir(run_id, runs)
    manifest = RunManifest.load(run.manifest_path)
    artifact = run.evidence / "e1-transcript.txt"
    artifact.chmod(0o644)
    artifact.write_text("corrupted")
    result = verify_frozen_evidence(run, manifest)
    assert result["passed"] is False
    assert any("hash mismatch" in d for d in result["divergences"])
