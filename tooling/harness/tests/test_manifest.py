"""Unit tests for MVB-002: manifest schema, checkpoint extension, freezing."""

import json
from pathlib import Path

import pytest

from tooling.harness.runtime.manifest import (
    APPENDIX_A_FIELDS,
    MANIFEST_SCHEMA,
    PHASE_IDS,
    FrozenManifestError,
    InvalidManifestError,
    RunManifest,
    new_run_manifest,
)
from tooling.harness.util.clock import is_iso_utc

RUN_ID = "run-NOT-02-demo-model-20260731T120000Z-00"


@pytest.fixture
def manifest():
    return new_run_manifest(
        RUN_ID, "NOT-02", model_id="demo-model", started_at="2026-07-31T12:00:00Z"
    )


# ----------------------------------------------------------------------
# Construction / Appendix A coverage
# ----------------------------------------------------------------------


def test_new_manifest_covers_every_appendix_a_field(manifest):
    data = manifest.to_dict()
    for field in APPENDIX_A_FIELDS:
        assert field in data, f"missing Appendix A field {field}"
    assert data["schema"] == MANIFEST_SCHEMA
    assert data["run_id"] == RUN_ID
    assert data["task"] == {"id": "NOT-02", "difficulty": None}
    assert data["network"] == "disabled"
    assert data["baseline_amended"] is False
    assert data["prompt_bundle_sha256"] is None
    assert data["evidence_root_hash"] is None
    assert data["submission_status"] is None
    assert data["rebaseline"] is None
    assert data["timeouts"] == {}
    assert set(data["versions"]) == {
        "corpus",
        "evaluation",
        "harness",
        "runtime",
        "validator",
        "reference_head",
    }
    assert data["versions"]["reference_head"] is None
    assert data["model"]["id"] == "demo-model"
    assert data["model"]["temperature"] == 0
    assert data["model"]["temperature_controlled"] is True


def test_new_manifest_opens_p0_phase(manifest):
    assert list(manifest.phases) == ["p0"]
    assert manifest.phases["p0"].started_at == "2026-07-31T12:00:00Z"
    assert manifest.phases["p0"].ended_at is None


def test_manifest_round_trips_losslessly(manifest):
    manifest.update(prompt_bundle_sha256="abc", submission_status="partial")
    manifest.add_errata("note")
    data = manifest.to_dict()
    restored = RunManifest.from_dict(data)
    assert restored.to_dict() == data


# ----------------------------------------------------------------------
# Phase checkpoint extension
# ----------------------------------------------------------------------


def test_all_phases_accept_start_end_timestamps(manifest):
    at = "2026-07-31T12:00:00.000000Z"
    # p0 is already started by the factory; all other phases accept records
    manifest.end_phase("p0", at=at)
    for phase in PHASE_IDS[1:]:
        manifest.start_phase(phase, at=at)
        manifest.end_phase(phase, at=at)
        assert manifest.phases[phase].started_at == at
        assert manifest.phases[phase].ended_at == at
        assert is_iso_utc(manifest.phases[phase].started_at)
        assert is_iso_utc(manifest.phases[phase].ended_at)


def test_end_before_start_raises(manifest):
    manifest.start_phase("p1", at="2026-07-31T12:00:00Z")
    with pytest.raises(InvalidManifestError):
        manifest.end_phase("p1", at="2026-07-31T11:59:59Z")


def test_unknown_phase_raises(manifest):
    with pytest.raises(InvalidManifestError):
        manifest.start_phase("p10")
    with pytest.raises(InvalidManifestError):
        manifest.end_phase("p10")


def test_non_iso_timestamps_raise(manifest):
    with pytest.raises(InvalidManifestError):
        manifest.start_phase("p1", at="not-a-timestamp")


def test_double_start_and_double_end_raise(manifest):
    at = "2026-07-31T12:00:00Z"
    manifest.start_phase("p1", at=at)
    with pytest.raises(InvalidManifestError):
        manifest.start_phase("p1", at=at)
    manifest.end_phase("p1", at=at)
    with pytest.raises(InvalidManifestError):
        manifest.end_phase("p1", at=at)


def test_update_sets_fields_and_rejects_unknown(manifest):
    manifest.update(prompt_bundle_sha256="h" * 64, network="enabled")
    assert manifest.prompt_bundle_sha256 == "h" * 64
    assert manifest.network == "enabled"
    with pytest.raises(InvalidManifestError):
        manifest.update(no_such_field=True)


# ----------------------------------------------------------------------
# Freezing (P4) semantics
# ----------------------------------------------------------------------


def test_mutation_after_freeze_raises(manifest):
    manifest.freeze()
    with pytest.raises(FrozenManifestError):
        manifest.start_phase("p1")
    with pytest.raises(FrozenManifestError):
        manifest.end_phase("p1")
    with pytest.raises(FrozenManifestError):
        manifest.update(prompt_bundle_sha256="x")


def test_errata_allowed_after_freeze(manifest):
    manifest.freeze()
    manifest.add_errata("corrected score typo")
    assert manifest.errata == [
        {"recorded_at": manifest.errata[0]["recorded_at"], "message": "corrected score typo"}
    ]


def test_freeze_state_survives_save_and_load(manifest, tmp_path):
    manifest.freeze()
    manifest.add_errata("note")
    path = tmp_path / "manifest.json"
    manifest.save(path)
    loaded = RunManifest.load(path)
    assert loaded.frozen is True
    assert loaded.errata == manifest.errata
    with pytest.raises(FrozenManifestError):
        loaded.update(network="enabled")


def test_freeze_is_idempotent(manifest):
    manifest.freeze()
    manifest.freeze()


# ----------------------------------------------------------------------
# Validation of loaded data
# ----------------------------------------------------------------------


def test_load_rejects_bad_schema(manifest):
    data = manifest.to_dict()
    data["schema"] = "wrong"
    with pytest.raises(InvalidManifestError):
        RunManifest.from_dict(data)


def test_load_rejects_bad_run_id(manifest):
    data = manifest.to_dict()
    data["run_id"] = "nope"
    with pytest.raises(InvalidManifestError):
        RunManifest.from_dict(data)


def test_load_rejects_bad_network(manifest):
    data = manifest.to_dict()
    data["network"] = "sometimes"
    with pytest.raises(InvalidManifestError):
        RunManifest.from_dict(data)


def test_load_rejects_bad_submission_status(manifest):
    data = manifest.to_dict()
    data["submission_status"] = "maybe"
    with pytest.raises(InvalidManifestError):
        RunManifest.from_dict(data)


def test_load_rejects_non_iso_phase_timestamps(manifest):
    data = manifest.to_dict()
    data["phases"]["p1"] = {"started_at": "yesterday", "ended_at": None}
    with pytest.raises(InvalidManifestError):
        RunManifest.from_dict(data)


def test_load_rejects_empty_task_id(manifest):
    data = manifest.to_dict()
    data["task"] = {"id": ""}
    with pytest.raises(InvalidManifestError):
        RunManifest.from_dict(data)


# ----------------------------------------------------------------------
# Persistence
# ----------------------------------------------------------------------


def test_save_and_load_round_trip(manifest, tmp_path):
    manifest.update(prompt_bundle_sha256="h" * 64)
    manifest.end_phase("p0", at="2026-07-31T12:05:00Z")
    path = tmp_path / "manifest.json"
    manifest.save(path)
    loaded = RunManifest.load(path)
    assert loaded.to_dict() == manifest.to_dict()


def test_save_is_deterministic(manifest, tmp_path):
    a, b = tmp_path / "a.json", tmp_path / "b.json"
    manifest.save(a)
    manifest.save(b)
    assert a.read_bytes() == b.read_bytes()
    assert a.read_text().endswith("\n")
    json.loads(a.read_text())  # valid JSON


def test_save_uses_sorted_keys(manifest, tmp_path):
    path = tmp_path / "m.json"
    manifest.save(path)
    parsed = json.loads(path.read_text())
    assert list(parsed) == sorted(parsed)


def test_manifest_requires_nonempty_task(manifest):
    with pytest.raises(ValueError):
        new_run_manifest(RUN_ID, "   ")
