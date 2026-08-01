"""Tests for the MS-01 CLI: ``python -m tooling.harness init``."""

import json
from datetime import datetime, timezone

import pytest

from tooling.harness.cli import main
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.run_dir import RUN_ID_PATTERN, RUN_SUBDIRECTORIES
from tooling.harness.runtime.status import RunStatus

FIXED_NOW = datetime(2026, 7, 31, 12, 0, 0, tzinfo=timezone.utc)


def _find_run_dir(runs_root):
    matches = [p for p in runs_root.iterdir() if p.is_dir()]
    assert len(matches) == 1
    return matches[0]


def test_init_creates_valid_run_directory(tmp_path):
    exit_code = main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path)])
    assert exit_code == 0

    run_root = _find_run_dir(tmp_path)
    for relpath in RUN_SUBDIRECTORIES:
        assert (run_root / relpath).is_dir(), f"missing {relpath}"

    manifest = RunManifest.load(run_root / "manifest.json")
    assert manifest.run_id == run_root.name
    assert manifest.task == {"id": "NOT-02", "difficulty": None}
    # round-trips losslessly
    assert RunManifest.from_dict(manifest.to_dict()).to_dict() == manifest.to_dict()

    status = RunStatus.load(run_root / "status.json")
    assert status.status == "INITIALIZING"
    assert "p0" in status.checkpoints


def test_init_run_id_matches_section_9_2(tmp_path):
    exit_code = main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path)])
    assert exit_code == 0
    run_id = _find_run_dir(tmp_path).name
    assert RUN_ID_PATTERN.match(run_id)
    assert run_id.startswith("run-NOT-02-demo-model-")


def test_second_init_in_same_second_gets_next_sequence(tmp_path, monkeypatch):
    monkeypatch.setattr("tooling.harness.cli.utc_now", lambda: FIXED_NOW)
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path)]) == 0
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path)]) == 0
    run_ids = sorted(p.name for p in tmp_path.iterdir() if p.is_dir())
    assert run_ids[0].endswith("-00")
    assert run_ids[1].endswith("-01")
    assert run_ids[0] != run_ids[1]


def test_init_different_tasks_and_models_do_not_share_sequence(tmp_path, monkeypatch):
    monkeypatch.setattr("tooling.harness.cli.utc_now", lambda: FIXED_NOW)
    main(["init", "NOT-02", "demo-model", "--runs-root", str(tmp_path)])
    main(["init", "ARC-01", "demo-model", "--runs-root", str(tmp_path)])
    run_ids = sorted(p.name for p in tmp_path.iterdir() if p.is_dir())
    assert any(r.endswith("-00") and "NOT-02" in r for r in run_ids)
    assert any(r.endswith("-00") and "ARC-01" in r for r in run_ids)


def test_init_invalid_task_fails_without_creating_runs(tmp_path):
    exit_code = main(["init", "NOT 02", "demo-model", "--runs-root", str(tmp_path)])
    assert exit_code == 1
    assert list(tmp_path.iterdir()) == []


def test_init_missing_arguments_is_usage_error(tmp_path):
    with pytest.raises(SystemExit) as exc:
        main(["init"])
    assert exc.value.code == 2


def test_init_unknown_command_is_usage_error():
    with pytest.raises(SystemExit) as exc:
        main(["frobnicate"])
    assert exc.value.code == 2


def test_init_writes_only_inside_runs_root(tmp_path, monkeypatch, capsys):
    monkeypatch.chdir(tmp_path)
    runs_root = tmp_path / "runs"
    assert main(["init", "NOT-02", "demo-model", "--runs-root", str(runs_root)]) == 0
    # everything created lives under the runs root
    created = [
        p
        for p in tmp_path.rglob("*")
        if p.is_file() or (p.is_dir() and p != tmp_path)
    ]
    assert all(str(p).startswith(str(runs_root)) for p in created)
    # no stray files at the working directory root
    assert sorted(p.name for p in tmp_path.iterdir()) == ["runs"]
    # CLI prints the run id and directory
    captured = capsys.readouterr()
    assert "Run initialized:" in captured.out
    assert "Run directory:" in captured.out
