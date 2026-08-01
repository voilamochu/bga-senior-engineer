"""Unit tests for MVB-014: evidence freezing (§6.3)."""

import os
import stat

import pytest

from tooling.harness.evidence.collect import collect_evidence, evidence_root_hash
from tooling.harness.evidence.freeze import (
    FreezeError,
    freeze_evidence,
    verify_frozen_evidence,
)
from tooling.harness.runtime.manifest import FrozenManifestError, RunManifest
from tooling.harness.tests.evidence_fixture import make_p4_run


def _frozen(tmp_path):
    run, manifest, status = make_p4_run(tmp_path)
    collect_evidence(run, manifest, status)
    result = freeze_evidence(run, manifest)
    return run, manifest, result


def test_freeze_makes_evidence_read_only(tmp_path):
    run, manifest, result = _frozen(tmp_path)
    for path in run.evidence.rglob("*"):
        if path.name == "reruns":
            continue
        assert stat.S_IMODE(path.stat().st_mode) & 0o222 == 0, path


def test_write_to_frozen_artifact_fails(tmp_path):
    run, manifest, result = _frozen(tmp_path)
    artifact = run.evidence / "e1-transcript.txt"
    assert not os.access(artifact, os.W_OK)
    with pytest.raises(PermissionError):
        with open(artifact, "a", encoding="utf-8"):
            pass


def test_reruns_dir_accepts_files_without_altering_frozen(tmp_path):
    run, manifest, result = _frozen(tmp_path)
    reruns = run.evidence / "reruns"
    assert reruns.is_dir()
    probe = reruns / "rerun-check.txt"
    probe.write_text("rerun output")
    assert probe.read_text() == "rerun output"
    # frozen artifacts untouched and still verified
    verification = verify_frozen_evidence(run, manifest)
    assert verification["passed"] is True


def test_root_hash_recorded_in_catalog_and_manifest(tmp_path):
    run, manifest, result = _frozen(tmp_path)
    import json

    catalog = json.loads((run.evidence / "evidence.json").read_text())
    assert catalog["frozen"] is True
    assert catalog["root_hash"] == result["root_hash"]
    assert manifest.evidence_root_hash == result["root_hash"]
    assert manifest.evidence["frozen"] is True
    assert manifest.evidence["root_hash"] == result["root_hash"]
    assert manifest.evidence["version"] == "benchmark-harness-evidence/1.0"
    assert manifest.evidence["artifact_count"] > 0
    assert manifest.evidence["frozen_at"]


def test_root_hash_deterministic_across_two_runs(tmp_path):
    _, _, result_a = _frozen(tmp_path / "a")
    _, _, result_b = _frozen(tmp_path / "b")
    assert result_a["root_hash"] == result_b["root_hash"]


def test_verify_passes_on_intact_evidence(tmp_path):
    run, manifest, result = _frozen(tmp_path)
    verification = verify_frozen_evidence(run, manifest)
    assert verification["passed"] is True
    assert verification["divergences"] == []


def test_verify_detects_corrupted_artifact(tmp_path):
    run, manifest, result = _frozen(tmp_path)
    artifact = run.evidence / "e1-transcript.txt"
    os.chmod(artifact, 0o644)
    artifact.write_text("tampered\n")
    verification = verify_frozen_evidence(run, manifest)
    assert verification["passed"] is False
    assert any("e1-transcript.txt" in d and "hash mismatch" in d
               for d in verification["divergences"])


def test_verify_detects_manifest_root_hash_mismatch(tmp_path):
    run, manifest, result = _frozen(tmp_path)
    # simulate tampering with the frozen manifest on disk (the API rejects
    # mutations, so the file is edited directly)
    import json

    path = run.manifest_path
    data = json.loads(path.read_text())
    data["evidence_root_hash"] = "0" * 64
    path.write_text(json.dumps(data))
    tampered = RunManifest.load(path)
    verification = verify_frozen_evidence(run, tampered)
    assert verification["passed"] is False
    assert any("evidence_root_hash" in d for d in verification["divergences"])


def test_manifest_is_frozen_after_evidence_freeze(tmp_path):
    run, manifest, result = _frozen(tmp_path)
    assert manifest.frozen is True
    with pytest.raises(FrozenManifestError):
        manifest.update(prompt_bundle_sha256="x")


def test_double_freeze_rejected(tmp_path):
    run, manifest, status = make_p4_run(tmp_path)
    collect_evidence(run, manifest, status)
    freeze_evidence(run, manifest)
    with pytest.raises(FreezeError, match="already frozen"):
        freeze_evidence(run, manifest)


def test_freeze_refuses_on_hash_mismatch(tmp_path):
    run, manifest, status = make_p4_run(tmp_path)
    catalog = collect_evidence(run, manifest, status)
    artifact = run.evidence / "e1-transcript.txt"
    artifact.write_text("tampered before freeze\n")
    with pytest.raises(FreezeError, match="cannot freeze"):
        freeze_evidence(run, manifest)
