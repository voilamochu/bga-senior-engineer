"""Archive manager (MS-08, MVB-023).

Per harness §9, the runs root is the archive root: the run directory
``runs/<run-id>/`` is the canonical archive structure, completed at P9
with the ``ARCHIVED`` marker, an append-only ``runs/index.json``
registry entry (§9.5), and — for VERDICTED runs — a normalized
leaderboard entry (§7.6; REJECTED runs are never leaderboard entries,
harness §7.4).  Archive generation is deterministic for identical run
data; verification is mechanical and precise.
"""

from __future__ import annotations

import json
import re
from pathlib import Path

from tooling.harness.evidence.freeze import verify_frozen_evidence
from tooling.harness.report.generator import validate_report
from tooling.harness.report.data import REQUIRED_METADATA_GROUPS, REQUIRED_SECTIONS
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.run_dir import RunDir
from tooling.harness.runtime.status import RunStatus
from tooling.harness.archive.packaging import (
    ARCHIVE_MARKER,
    verify_packaging,
)
from tooling.harness.util.clock import utc_now_iso
from tooling.harness.util.hash import sha256_file

ARCHIVE_SCHEMA = "benchmark-harness-archive/1.0"
ARCHIVE_VERSION = "1.0"

INDEX_SCHEMA = "benchmark-harness-index/1.0"
LEADERBOARD_SCHEMA = "benchmark-harness-leaderboard/1.0"

ARCHIVEABLE_STATUSES = ("VERDICTED", "REJECTED")

# Recorded report hashes in the manifest errata, e.g.
# "P8 reports generated: ... evaluation-report.json sha256=<hex> ...".
_REPORT_ERRATA = re.compile(
    r"evaluation-report\.json sha256=([0-9a-f]{64}).*?report\.md sha256=([0-9a-f]{64})"
)


class ArchiveError(Exception):
    """The run cannot be archived (preconditions not met)."""


def archive_run(
    run: RunDir,
    manifest: RunManifest,
    status: RunStatus,
    *,
    runs_root: str | Path,
) -> dict:
    """Archive *run* in place: marker, registry, leaderboard, record.

    Returns ``{marker_path, registry_entry, leaderboard_entry,
    divergences}``.  Raises :class:`ArchiveError` when the run is not
    archiveable or the packaged contents fail verification.
    """
    _preflight(run, manifest, status, runs_root=runs_root)

    divergences = verify_packaging(run)
    if divergences:
        raise ArchiveError(
            "packaged run contains non-packageable contents: "
            + "; ".join(divergences)
        )

    marker_path = run.root / ARCHIVE_MARKER
    marker_path.write_text(
        f"{ARCHIVE_SCHEMA}\narchive_version={ARCHIVE_VERSION}\n"
        f"run_id={run.run_id}\n",
        encoding="utf-8",
    )

    registry_entry = _registry_entry(run, manifest)
    append_registry_entry(runs_root, registry_entry)

    leaderboard_entry = None
    if status.status == "VERDICTED":
        leaderboard_entry = _leaderboard_entry(run, manifest, status)
        append_leaderboard_entry(runs_root, leaderboard_entry)

    now = utc_now_iso()
    status.transition("ARCHIVED", checkpoint="p9", at=now)
    status.save(run.status_path)
    _record_archive_errata(run, manifest, marker_path, runs_root, leaderboard_entry)

    return {
        "marker_path": marker_path,
        "registry_entry": registry_entry,
        "leaderboard_entry": leaderboard_entry,
        "divergences": [],
    }


def verify_archive(
    run: RunDir,
    manifest: RunManifest,
    status: RunStatus,
    *,
    runs_root: str | Path,
) -> dict:
    """Verify an archived run; returns ``{passed, divergences}``.

    Checks: ARCHIVED status and marker, the registry entry, the
    leaderboard entry (VERDICTED runs), frozen-evidence hashes, the
    reports and their §8.2/§8.3 checklists, recorded report hashes, and
    the packaged contents.
    """
    divergences: list[str] = []
    if status.status != "ARCHIVED":
        divergences.append(
            f"run status is {status.status}, expected ARCHIVED"
        )
    marker_path = run.root / ARCHIVE_MARKER
    if not marker_path.is_file():
        divergences.append(f"missing ARCHIVED marker: {marker_path.name}")
    else:
        marker_text = marker_path.read_text(encoding="utf-8")
        if ARCHIVE_SCHEMA not in marker_text:
            divergences.append("ARCHIVED marker does not carry the archive schema")
        if run.run_id not in marker_text:
            divergences.append("ARCHIVED marker run_id does not match the run")

    registry = load_registry(runs_root)
    entries = [e for e in registry if e.get("run_id") == run.run_id]
    if not entries:
        divergences.append(f"no registry entry for {run.run_id} in runs/index.json")
    elif entries[0].get("status") != "ARCHIVED":
        divergences.append(
            f"registry entry for {run.run_id} has status "
            f"{entries[0].get('status')!r}, expected ARCHIVED"
        )

    was_verdict = (run.review_scoring / "scores.json").is_file()
    if was_verdict:
        leaderboard = load_leaderboard(runs_root, _version_tuple(manifest))
        if not any(e.get("run_id") == run.run_id for e in leaderboard):
            divergences.append(
                f"missing leaderboard entry for {run.run_id} "
                f"under leaderboard/{_version_tuple_label(manifest)}/"
            )
    else:
        leaderboard = load_leaderboard(runs_root, _version_tuple(manifest))
        if any(e.get("run_id") == run.run_id for e in leaderboard):
            divergences.append(
                f"REJECTED run {run.run_id} must not have a leaderboard entry"
            )

    evidence = verify_frozen_evidence(run, manifest)
    if not evidence["passed"]:
        divergences.extend(evidence["divergences"])

    md_path = run.reports / "report.md"
    json_path = run.reports / "evaluation-report.json"
    if not md_path.is_file() or not json_path.is_file():
        divergences.append("reports are missing (reports/report.md, "
                           "reports/evaluation-report.json)")
    else:
        report_divergences = validate_report(
            md_path.read_text(encoding="utf-8"),
            json.loads(json_path.read_text(encoding="utf-8")),
        )
        divergences.extend(report_divergences)
        recorded = _recorded_report_hashes(manifest)
        if recorded:
            current = (sha256_file(json_path), sha256_file(md_path))
            if current != recorded:
                divergences.append(
                    "report files do not match the hashes recorded in the "
                    "manifest errata"
                )
        else:
            divergences.append("no recorded report hashes in the manifest errata")

    divergences.extend(verify_packaging(run))
    return {"passed": not divergences, "divergences": divergences}


def _preflight(run, manifest, status, *, runs_root) -> None:
    if status.status in ("ARCHIVED",):
        raise ArchiveError(f"run {run.run_id} is already archived")
    if status.status not in ARCHIVEABLE_STATUSES:
        raise ArchiveError(
            f"cannot archive {run.run_id} in status {status.status} "
            f"(expected {' or '.join(ARCHIVEABLE_STATUSES)})"
        )
    if not manifest.frozen:
        raise ArchiveError(
            "run manifest is not frozen; complete P4 with 'collect --freeze' first"
        )
    if not (run.reports / "evaluation-report.json").is_file() or not (
        run.reports / "report.md"
    ).is_file():
        raise ArchiveError(f"reports are missing; run 'report' first (P8)")
    if load_registry(runs_root) and any(
        entry.get("run_id") == run.run_id for entry in load_registry(runs_root)
    ):
        raise ArchiveError(
            f"run {run.run_id} already has a registry entry in runs/index.json"
        )
    evidence = verify_frozen_evidence(run, manifest)
    if not evidence["passed"]:
        raise ArchiveError(
            "frozen evidence verification failed: "
            + "; ".join(evidence["divergences"])
        )


# ----------------------------------------------------------------------
# Registry (§9.5)
# ----------------------------------------------------------------------

def registry_path(runs_root: str | Path) -> Path:
    return Path(runs_root) / "index.json"


def load_registry(runs_root: str | Path) -> list[dict]:
    path = registry_path(runs_root)
    if not path.is_file():
        return []
    data = json.loads(path.read_text(encoding="utf-8"))
    entries = data.get("entries", []) if isinstance(data, dict) else data
    return list(entries)


def append_registry_entry(runs_root: str | Path, entry: dict) -> Path:
    """Append one §9.5 registry entry (append-only; entries never edited)."""
    path = registry_path(runs_root)
    entries = load_registry(runs_root)
    entries.append(entry)
    document = {"schema": INDEX_SCHEMA, "entries": entries}
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(document, indent=2, sort_keys=True) + "\n",
                    encoding="utf-8")
    return path


def _registry_entry(run: RunDir, manifest: RunManifest) -> dict:
    from tooling.harness.scoring.persist import load_scores

    entry = {
        "run_id": run.run_id,
        "task_id": (manifest.task or {}).get("id"),
        "model": (manifest.model or {}).get("id"),
        "model_version": (manifest.model or {}).get("version"),
        "version_tuple": _version_tuple(manifest),
        "status": "ARCHIVED",
        "evidence_root_hash": manifest.evidence_root_hash,
        "archived_at": utc_now_iso(),
    }
    scores_path = run.review_scoring / "scores.json"
    if scores_path.is_file():
        scores = load_scores(scores_path)
        entry["verdict"] = scores.get("verdict")
        entry["total"] = scores.get("arithmetic", {}).get("capped_total")
        entry["category_scores"] = scores.get("category_scores", {})
    else:
        entry["verdict"] = "REJECTED"
        entry["total"] = None
        entry["category_scores"] = {}
    return entry


# ----------------------------------------------------------------------
# Leaderboard (§7.6)
# ----------------------------------------------------------------------

def _version_tuple(manifest: RunManifest) -> dict:
    versions = manifest.versions or {}
    return {
        "corpus": versions.get("corpus"),
        "evaluation": versions.get("evaluation"),
        "harness": versions.get("harness"),
    }


def _version_tuple_label(manifest: RunManifest) -> str:
    versions = manifest.versions or {}
    return "-".join(
        str(versions.get(key) or "?")
        for key in ("corpus", "evaluation", "harness")
    )


def leaderboard_path(runs_root: str | Path, version_tuple: dict) -> Path:
    label = "-".join(str(version_tuple.get(key) or "?")
                     for key in ("corpus", "evaluation", "harness"))
    return Path(runs_root) / "leaderboard" / label / "leaderboard.json"


def load_leaderboard(runs_root: str | Path, version_tuple: dict) -> list[dict]:
    path = leaderboard_path(runs_root, version_tuple)
    if not path.is_file():
        return []
    data = json.loads(path.read_text(encoding="utf-8"))
    return list(data.get("entries", []))


def append_leaderboard_entry(runs_root: str | Path, entry: dict) -> Path:
    """Append one §7.6 leaderboard entry (append-only)."""
    version_tuple = entry["version_tuple"]
    path = leaderboard_path(runs_root, version_tuple)
    entries = load_leaderboard(runs_root, version_tuple)
    entries.append(entry)
    document = {"schema": LEADERBOARD_SCHEMA, "entries": entries}
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(document, indent=2, sort_keys=True) + "\n",
                    encoding="utf-8")
    return path


def _leaderboard_entry(run, manifest, status) -> dict:
    from tooling.harness.scoring.persist import load_scores

    scores = load_scores(run.review_scoring / "scores.json")
    return {
        "run_id": run.run_id,
        "model": (manifest.model or {}).get("id"),
        "model_version": (manifest.model or {}).get("version"),
        "task_id": (manifest.task or {}).get("id"),
        "difficulty": (manifest.task or {}).get("difficulty"),
        "category_scores": scores.get("category_scores", {}),
        "total": scores.get("arithmetic", {}).get("capped_total"),
        "verdict": scores.get("verdict"),
        "version_tuple": _version_tuple(manifest),
        "reproducibility_notes": None,
    }


# ----------------------------------------------------------------------
# Manifest recording
# ----------------------------------------------------------------------

def _record_archive_errata(run, manifest, marker_path, runs_root, leaderboard_entry) -> None:
    leaderboard_text = (
        f"leaderboard.json sha256={sha256_file(leaderboard_path(runs_root, leaderboard_entry['version_tuple']))}"
        if leaderboard_entry
        else "no leaderboard entry (REJECTED run)"
    )
    message = (
        "P9 archive: "
        f"archive_version={ARCHIVE_VERSION} "
        f"ARCHIVED marker sha256={sha256_file(marker_path)} "
        f"index.json sha256={sha256_file(registry_path(runs_root))} "
        f"{leaderboard_text}"
    )
    manifest.add_errata(message, at=utc_now_iso())
    manifest.save(run.manifest_path)


def _recorded_report_hashes(manifest: RunManifest) -> tuple[str, str] | None:
    for entry in manifest.errata:
        match = _REPORT_ERRATA.search(entry.get("message", ""))
        if match:
            return match.group(1), match.group(2)
    return None
