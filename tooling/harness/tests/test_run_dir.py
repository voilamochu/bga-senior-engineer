"""Unit tests for MVB-001: run directory layout, run ID, and config."""

import json
from datetime import datetime, timezone
from pathlib import Path

import pytest

from tooling.harness import config
from tooling.harness.runtime import run_dir
from tooling.harness.runtime.run_dir import (
    RUN_ID_PATTERN,
    RUN_SUBDIRECTORIES,
    RunDir,
    build_run_id,
    create_run_dir,
    load_run_dir,
    next_sequence,
    normalize_model_slug,
    parse_run_id,
)

FIXED_NOW = datetime(2026, 7, 31, 12, 0, 0, tzinfo=timezone.utc)


def test_create_run_dir_produces_full_skeleton(tmp_path):
    run = create_run_dir("NOT-02", "demo-model", tmp_path, now=FIXED_NOW)
    for relpath in RUN_SUBDIRECTORIES:
        assert (run.root / relpath).is_dir(), f"missing {relpath}"
    assert run.root.is_dir()
    assert run.workspace_work.is_dir()


def test_create_run_dir_returns_dataclass_of_paths(tmp_path):
    run = create_run_dir("NOT-02", "demo-model", tmp_path, now=FIXED_NOW)
    assert isinstance(run, RunDir)
    assert run.root == tmp_path / run.run_id
    assert run.protocol == run.root / "protocol"
    assert run.baseline == run.root / "protocol" / "baseline"
    assert run.workspace_read == run.root / "workspace" / "read"
    assert run.workspace_work == run.root / "workspace" / "work"
    assert run.evidence == run.root / "evidence"
    assert run.validation_raw == run.root / "validation" / "raw"
    assert run.review_scoring == run.root / "review" / "scoring"
    assert run.reports == run.root / "reports"
    assert run.manifest_path == run.root / "manifest.json"
    assert run.status_path == run.root / "status.json"


def test_run_id_matches_section_9_2_pattern(tmp_path):
    run = create_run_dir("NOT-02", "demo-model", tmp_path, now=FIXED_NOW)
    assert RUN_ID_PATTERN.match(run.run_id)
    assert run.run_id == "run-NOT-02-demo-model-20260731T120000Z-00"


def test_same_second_runs_get_incrementing_hex_sequence(tmp_path):
    first = create_run_dir("NOT-02", "demo-model", tmp_path, now=FIXED_NOW)
    second = create_run_dir("NOT-02", "demo-model", tmp_path, now=FIXED_NOW)
    assert first.run_id.endswith("-00")
    assert second.run_id.endswith("-01")
    assert first.root != second.root
    assert first.root.is_dir() and second.root.is_dir()


def test_sequence_is_derived_from_existing_dirs(tmp_path):
    ts = "20260731T120000Z"
    prefix = f"run-NOT-02-demo-model-{ts}"
    (tmp_path / f"{prefix}-00").mkdir(parents=True)
    (tmp_path / f"{prefix}-05").mkdir(parents=True)
    assert next_sequence(tmp_path, "NOT-02", "demo-model", ts) == 6


def test_sequence_is_deterministic_across_roots(tmp_path):
    ts = "20260731T120000Z"
    other = tmp_path / "elsewhere"
    assert next_sequence(tmp_path, "NOT-02", "demo-model", ts) == 0
    assert next_sequence(other, "NOT-02", "demo-model", ts) == 0


def test_sequence_exhaustion_raises(tmp_path):
    ts = "20260731T120000Z"
    (tmp_path / f"run-NOT-02-demo-model-{ts}-ff").mkdir(parents=True)
    with pytest.raises(RuntimeError):
        next_sequence(tmp_path, "NOT-02", "demo-model", ts)


def test_creates_runs_root_when_missing(tmp_path):
    runs_root = tmp_path / "nested" / "runs"
    run = create_run_dir("NOT-02", "demo-model", runs_root, now=FIXED_NOW)
    assert run.root.is_dir()


def test_refuses_to_overwrite_existing_run(tmp_path, monkeypatch):
    run = create_run_dir("NOT-02", "demo-model", tmp_path, now=FIXED_NOW)
    monkeypatch.setattr(run_dir, "next_sequence", lambda *a, **k: 0)
    with pytest.raises(FileExistsError):
        create_run_dir("NOT-02", "demo-model", tmp_path, now=FIXED_NOW)
    # original run untouched
    assert run.root.is_dir()


def test_path_length_is_bounded(tmp_path):
    long_root = tmp_path / ("x" * 200)
    with pytest.raises(ValueError):
        create_run_dir("NOT-02", "demo-model", long_root, now=FIXED_NOW)


def test_invalid_task_id_rejected(tmp_path):
    with pytest.raises(ValueError):
        create_run_dir("NOT 02", "demo-model", tmp_path)
    with pytest.raises(ValueError):
        create_run_dir("", "demo-model", tmp_path)


def test_model_slug_normalization(tmp_path):
    run = create_run_dir("NOT-02", "My Model  v1.0!", tmp_path, now=FIXED_NOW)
    assert "my-model-v1.0" in run.run_id
    assert normalize_model_slug("demo-model") == "demo-model"
    assert normalize_model_slug("GPT-5") == "gpt-5"
    with pytest.raises(ValueError):
        normalize_model_slug("!!!")


def test_build_run_id_validation():
    assert build_run_id("NOT-02", "demo-model", "20260731T120000Z", 0) == (
        "run-NOT-02-demo-model-20260731T120000Z-00"
    )
    assert build_run_id("ARC-01", "gpt5", "20260731T120000Z", 255).endswith("-ff")
    with pytest.raises(ValueError):
        build_run_id("bad task", "m", "20260731T120000Z", 0)
    with pytest.raises(ValueError):
        build_run_id("NOT-02", "BAD", "20260731T120000Z", 0)
    with pytest.raises(ValueError):
        build_run_id("NOT-02", "m", "12:00:00", 0)
    with pytest.raises(ValueError):
        build_run_id("NOT-02", "m", "20260731T120000Z", 256)


def test_parse_run_id_round_trip():
    run_id = "run-NOT-02-demo-model-20260731T120000Z-00"
    parsed = parse_run_id(run_id)
    assert parsed == {
        "task": "NOT-02",
        "model_slug": "demo-model",
        "timestamp": "20260731T120000Z",
        "seq": "00",
    }
    with pytest.raises(ValueError):
        parse_run_id("not-a-run-id")


def test_load_run_dir(tmp_path):
    run = create_run_dir("NOT-02", "demo-model", tmp_path, now=FIXED_NOW)
    loaded = load_run_dir(run.run_id, tmp_path)
    assert loaded == run
    with pytest.raises(FileNotFoundError):
        load_run_dir(run.run_id, tmp_path / "missing")
    with pytest.raises(ValueError):
        load_run_dir("garbage", tmp_path)


def test_work_dir_is_writable(tmp_path):
    run = create_run_dir("NOT-02", "demo-model", tmp_path, now=FIXED_NOW)
    marker = run.workspace_work / "probe.txt"
    marker.write_text("probe")
    assert marker.read_text() == "probe"


# ----------------------------------------------------------------------
# Config (MVB-001 settings file + runs root)
# ----------------------------------------------------------------------


def test_default_runs_root_is_sibling_of_repo():
    root = config.default_runs_root()
    assert root == config.repo_root().parent / "runs"
    assert root.parent == config.repo_root().parent


def test_resolve_runs_root_precedence(tmp_path):
    explicit = tmp_path / "explicit"
    configured = tmp_path / "configured"
    settings = {"runs_root": str(configured)}
    assert config.resolve_runs_root(explicit, settings) == explicit
    assert config.resolve_runs_root(None, settings) == configured
    assert config.resolve_runs_root(None, {}) == config.default_runs_root()


def test_settings_file_override(tmp_path):
    settings_file = tmp_path / "settings.json"
    settings_file.write_text(json.dumps({"runs_root": str(tmp_path / "from-file")}))
    settings = config.load_settings(settings_file)
    assert config.resolve_runs_root(None, settings) == tmp_path / "from-file"


def test_missing_settings_file_loads_empty(tmp_path):
    assert config.load_settings(tmp_path / "nope.json") == {}


def test_invalid_settings_file_raises(tmp_path):
    bad = tmp_path / "bad.json"
    bad.write_text("[1, 2]")
    with pytest.raises(ValueError):
        config.load_settings(bad)


def test_pinned_versions_from_documents():
    versions = config.read_pinned_versions()
    assert versions["corpus"] == "1.0"
    assert versions["evaluation"] == "1.0"
    assert versions["harness"] == "1.0"
    assert versions["runtime"] == "v1.1"
    assert versions["validator"] == "1.0.0"
