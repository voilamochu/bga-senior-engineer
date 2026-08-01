"""Evidence freezing (MVB-014): harness §6.3.

Freezing makes the evidence set immutable at the filesystem level
(not by convention): after P4 the ``evidence/`` tree is read-only,
every artifact is hash-verified against the catalog, the frozen
root hash (Merkle-style digest over the catalog's content index) is
recorded in ``evidence.json`` and the run manifest, and post-P4
re-runs append to ``evidence/reruns/`` which never alters frozen
artifacts.
"""

from __future__ import annotations

import os
from pathlib import Path

from tooling.harness.evidence.collect import (
    EVIDENCE_SCHEMA,
    evidence_root_hash,
    load_evidence_catalog,
    write_evidence_catalog,
)
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.run_dir import RunDir
from tooling.harness.util.clock import format_iso, utc_now
from tooling.harness.util.hash import sha256_file

RERUNS_DIRNAME = "reruns"


class FreezeError(Exception):
    """Evidence cannot be frozen or verified."""


def verify_catalog_hashes(run: RunDir, catalog: dict) -> list[str]:
    """Re-hash every indexed artifact against the catalog.

    Returns a list of precise divergences (empty when all match).
    """
    divergences: list[str] = []
    evidence_dir = run.evidence
    for etype, entry in catalog.get("types", {}).items():
        if entry.get("status") != "present":
            continue
        for file_entry in entry.get("files", []):
            path = evidence_dir / file_entry["path"]
            if not path.is_file():
                divergences.append(
                    f"{etype}: artifact {file_entry['path']} is missing"
                )
                continue
            actual = sha256_file(path)
            if actual != file_entry["sha256"]:
                divergences.append(
                    f"{etype}: artifact {file_entry['path']} hash mismatch "
                    f"(expected {file_entry['sha256']}, actual {actual})"
                )
            if path.stat().st_size != file_entry["size"]:
                divergences.append(
                    f"{etype}: artifact {file_entry['path']} size mismatch "
                    f"(expected {file_entry['size']}, actual {path.stat().st_size})"
                )
    return divergences


def _make_read_only(root: Path, skip: Path | None = None) -> None:
    for dirpath, dirnames, filenames in os.walk(root):
        if skip is not None and Path(dirpath) == skip:
            dirnames[:] = []
            continue
        os.chmod(dirpath, 0o555)
        for name in filenames:
            path = Path(dirpath) / name
            if not path.is_symlink():
                os.chmod(path, 0o444)


def _inventory(catalog: dict, *, frozen: bool, frozen_at: str | None, root_hash: str | None) -> dict:
    """Manifest `evidence` extension: full inventory + freeze status."""
    artifact_count = sum(
        len(entry.get("files", []))
        for entry in catalog["types"].values()
        if entry.get("status") == "present"
    )
    return {
        "version": EVIDENCE_SCHEMA,
        "collected_at": catalog["collected_at"],
        "frozen": frozen,
        "frozen_at": frozen_at,
        "root_hash": root_hash,
        "artifact_count": artifact_count,
        "total_size_bytes": sum(
            f["size"]
            for entry in catalog["types"].values()
            for f in entry.get("files", [])
        ),
        "types": catalog["types"],
    }


def record_collected_evidence(
    run: RunDir, manifest: RunManifest, catalog: dict, *, frozen: bool, frozen_at: str | None
) -> None:
    """Record the evidence inventory in the run manifest."""
    manifest.update(
        evidence=_inventory(catalog, frozen=frozen, frozen_at=frozen_at, root_hash=None),
        evidence_root_hash=catalog.get("root_hash"),
    )


def freeze_evidence(run: RunDir, manifest: RunManifest) -> dict:
    """Freeze the collected evidence set (§6.3).

    Completes P4: hash-verifies every artifact, records the frozen root
    hash, makes ``evidence/`` read-only (with a writable ``reruns/``
    directory), records the evidence inventory in the manifest, closes
    the p4 phase, and freezes the manifest itself.
    """
    catalog = load_evidence_catalog(run)
    if catalog.get("frozen"):
        raise FreezeError("evidence is already frozen")

    divergences = verify_catalog_hashes(run, catalog)
    if divergences:
        raise FreezeError(
            "cannot freeze: " + "; ".join(divergences)
        )

    root_hash = evidence_root_hash(catalog)
    catalog["frozen"] = True
    catalog["root_hash"] = root_hash
    write_evidence_catalog(run, catalog)

    # Post-P4 re-runs land in evidence/reruns/, never altering frozen
    # files; it is created (writable) before the read-only pass and
    # excluded from it.
    reruns = run.evidence / RERUNS_DIRNAME
    reruns.mkdir(parents=True, exist_ok=True)

    # Filesystem-level immutability: files 0444, directories 0555.
    _make_read_only(run.evidence, skip=reruns)

    frozen_at = format_iso(utc_now())
    manifest.update(
        evidence=_inventory(catalog, frozen=True, frozen_at=frozen_at, root_hash=root_hash),
        evidence_root_hash=root_hash,
    )
    record = manifest.phases.get("p4")
    if record is not None and record.started_at is not None and record.ended_at is None:
        manifest.end_phase("p4", at=frozen_at)
    manifest.freeze()

    inventory = _inventory(catalog, frozen=True, frozen_at=frozen_at, root_hash=root_hash)
    return {
        "root_hash": root_hash,
        "frozen_at": frozen_at,
        "artifact_count": inventory["artifact_count"],
    }


def verify_frozen_evidence(run: RunDir, manifest: RunManifest) -> dict:
    """Re-verify frozen evidence: artifact hashes + root hash.

    Returns ``{passed, divergences}`` with precise messages.  A
    corrupted artifact or a manifest root-hash mismatch is reported,
    never silently absorbed.
    """
    catalog = load_evidence_catalog(run)
    divergences = verify_catalog_hashes(run, catalog)
    if catalog.get("frozen") != True:
        divergences.append("evidence catalog is not frozen")
    if catalog.get("root_hash") != evidence_root_hash(catalog):
        divergences.append("evidence root hash does not match the catalog content")
    recorded = manifest.evidence_root_hash
    if recorded and recorded != catalog.get("root_hash"):
        divergences.append(
            f"manifest evidence_root_hash ({recorded}) does not "
            f"match the frozen catalog root hash ({catalog.get('root_hash')})"
        )
    return {"passed": not divergences, "divergences": divergences}
