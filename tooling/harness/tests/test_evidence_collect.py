"""Unit tests for MVB-013: evidence collection (§6.1–6.2)."""

import json

import pytest

from tooling.harness.evidence.collect import (
    EVIDENCE_TYPES,
    OPTIONAL_EVIDENCE,
    REQUIRED_EVIDENCE,
    EvidenceError,
    collect_evidence,
    evidence_root_hash,
    load_evidence_catalog,
)
from tooling.harness.util.hash import sha256_file, sha256_text
from tooling.harness.tests.evidence_fixture import make_p4_run


def _catalog(tmp_path, **kwargs):
    run, manifest, status = make_p4_run(tmp_path, **kwargs)
    catalog = collect_evidence(run, manifest, status)
    return run, manifest, status, catalog


def test_every_required_type_is_recorded(tmp_path):
    run, manifest, status, catalog = _catalog(tmp_path)
    assert set(catalog["types"]) == set(EVIDENCE_TYPES)
    for etype in REQUIRED_EVIDENCE:
        assert etype in catalog["types"], etype


def test_present_artifacts_collected_with_hashes(tmp_path):
    run, manifest, status, catalog = _catalog(tmp_path)
    e1 = catalog["types"]["E1"]
    assert e1["status"] == "present"
    file_entry = e1["files"][0]
    assert file_entry["path"] == "e1-transcript.txt"
    assert file_entry["size"] == 19
    assert file_entry["sha256"] == sha256_text("session transcript\n")
    assert sha256_file(run.evidence / file_entry["path"]) == file_entry["sha256"]


def test_e2_work_tree_mirrored(tmp_path):
    run, manifest, status, catalog = _catalog(tmp_path)
    e2 = catalog["types"]["E2"]
    assert e2["status"] == "present"
    paths = {f["path"] for f in e2["files"]}
    assert "e2/reasoning.md" in paths
    assert "e2/changes/patch.php" in paths
    assert "e2/declaration.json" in paths
    for file_entry in e2["files"]:
        assert (run.evidence / file_entry["path"]).is_file()
        assert sha256_file(run.evidence / file_entry["path"]) == file_entry["sha256"]


def test_e5_phase_times_generated(tmp_path):
    run, manifest, status, catalog = _catalog(tmp_path)
    e5 = catalog["types"]["E5"]
    assert e5["status"] == "present"
    content = json.loads((run.evidence / "e5-phase-times.json").read_text())
    assert set(content["phases"]) == {"p0", "p1", "p2", "p3"}
    assert content["checkpoints"]["p3"]


def test_e8_diff_bundle_present_with_baseline(tmp_path):
    run, manifest, status, catalog = _catalog(tmp_path)
    e8 = catalog["types"]["E8"]
    assert e8["status"] == "present"
    assert e8["baseline"] == "protocol/baseline/workspace-baseline.diff"
    assert any(f["path"] == "e8-diff-bundle/patch.php" for f in e8["files"])


def test_e11_and_e12_present(tmp_path):
    run, manifest, status, catalog = _catalog(tmp_path)
    assert catalog["types"]["E11"]["status"] == "present"
    e12_paths = {f["path"] for f in catalog["types"]["E12"]["files"]}
    assert e12_paths == {
        "e12-checkpoint-states/manifest.json",
        "e12-checkpoint-states/status.json",
    }


def test_optional_types_absent_with_reason(tmp_path):
    run, manifest, status, catalog = _catalog(tmp_path)
    for etype in OPTIONAL_EVIDENCE:
        entry = catalog["types"][etype]
        assert entry["status"] == "absent", etype
        assert entry["reason"], etype


def test_e4_and_e9_absent_before_their_phases(tmp_path):
    run, manifest, status, catalog = _catalog(tmp_path)
    assert catalog["types"]["E4"]["status"] == "absent"
    assert "P5" in catalog["types"]["E4"]["reason"]
    assert catalog["types"]["E9"]["status"] == "absent"
    assert "P8" in catalog["types"]["E9"]["reason"]


def test_e4_and_e9_collected_after_their_phases(tmp_path):
    run, manifest, status, catalog = _catalog(
        tmp_path, with_validation=True, with_reports=True
    )
    e4 = catalog["types"]["E4"]
    assert e4["status"] == "present"
    assert any(f["path"] == "e4/validation.json" for f in e4["files"])
    assert any(f["path"] == "e4/raw/V1.txt" for f in e4["files"])
    e9 = catalog["types"]["E9"]
    assert e9["status"] == "present"
    assert any(f["path"] == "e9-reports/report.md" for f in e9["files"])


def test_missing_e11_after_p1_fails_cleanly(tmp_path):
    run, manifest, status = make_p4_run(tmp_path)
    (run.root / "protocol" / "environment.json").unlink()
    with pytest.raises(EvidenceError, match="E11"):
        collect_evidence(run, manifest, status)


def test_missing_e4_after_p5_fails_cleanly(tmp_path):
    run, manifest, status = make_p4_run(tmp_path, with_validation=True)
    import shutil

    shutil.rmtree(run.root / "validation")
    with pytest.raises(EvidenceError, match="E4"):
        collect_evidence(run, manifest, status)


def test_collect_is_idempotent(tmp_path):
    run, manifest, status, catalog = _catalog(tmp_path)
    first = load_evidence_catalog(run)
    second = collect_evidence(run, manifest, status)
    # re-collection regenerates the catalog: no duplicate entries, and the
    # deterministic inventory (and its root hash) is unchanged
    assert first["types"] == second["types"]
    assert len(second["types"]["E2"]["files"]) == len(first["types"]["E2"]["files"])
    assert evidence_root_hash(second) == evidence_root_hash(first)


def test_deterministic_ordering(tmp_path):
    run, manifest, status, catalog = _catalog(tmp_path)
    assert list(catalog["types"]) == list(EVIDENCE_TYPES)
    for entry in catalog["types"].values():
        files = entry.get("files", [])
        assert files == sorted(files, key=lambda f: f["path"])


def test_root_hash_deterministic_across_identical_runs(tmp_path):
    # "Identical runs" = identical evidence content: the root hash is a pure
    # function of the artifact index.  Real runs differ only in recorded
    # timestamps (E3 command log, E5 phase times, E12 state copies), which
    # are legitimately recorded facts; the digest covers content, and the
    # milestone's determinism criterion is demonstrated at this level.
    _, _, _, catalog_a = _catalog(tmp_path / "a")
    _, _, _, catalog_b = _catalog(tmp_path / "b")
    assert evidence_root_hash(catalog_a) == evidence_root_hash(catalog_b)
    assert len(evidence_root_hash(catalog_a)) == 64


def test_root_hash_excludes_volatile_metadata(tmp_path):
    _, _, _, catalog = _catalog(tmp_path)
    base = evidence_root_hash(catalog)
    mutated = json.loads(json.dumps(catalog))
    mutated["collected_at"] = "2099-01-01T00:00:00Z"
    mutated["run_id"] = "different"
    assert evidence_root_hash(mutated) == base
