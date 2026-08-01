"""Evidence collection (MVB-013): harness §6.1–§6.2.

At P4 (and at every checkpoint), artifacts E1–E12 are copied into
``evidence/`` with a relative path, size, and SHA-256 per artifact
recorded in ``evidence.json``.  Optional artifacts (E6/E7/E10) and
artifacts whose producing phase has not run (E4 pre-P5, E9 pre-P8)
are recorded as ``absent`` with a reason — omission is a recorded
fact, never silent (§6.2.3).  Collection never interprets evidence.
"""

from __future__ import annotations

import json
import shutil
from pathlib import Path

from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.run_dir import RunDir
from tooling.harness.runtime.status import RunStatus
from tooling.harness.util.clock import format_iso, utc_now
from tooling.harness.util.hash import sha256_file

EVIDENCE_SCHEMA = "benchmark-harness-evidence/1.0"
EVIDENCE_RELPATH = "evidence/evidence.json"

# §6.1 evidence catalog.
EVIDENCE_TYPES = (
    "E1", "E2", "E3", "E4", "E5", "E6", "E7", "E8", "E9", "E10", "E11", "E12",
)
REQUIRED_EVIDENCE = ("E1", "E2", "E3", "E4", "E5", "E8", "E9", "E11", "E12")
OPTIONAL_EVIDENCE = ("E6", "E7", "E10")


class EvidenceError(Exception):
    """Required evidence is missing or the catalog is inconsistent."""


def _file_entry(relpath: str) -> dict:
    return {"path": relpath, "size": 0, "sha256": ""}


def _copy_file(src: Path, dst: Path) -> dict:
    dst.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(src, dst)
    return {"path": dst.name, "size": dst.stat().st_size, "sha256": sha256_file(dst)}


def _copy_tree(src: Path, dst: Path, evidence_dir: Path) -> list[dict]:
    """Copy *src* (file or tree) to *dst* and index every file.

    Entry paths are relative to the evidence directory root.
    """
    entries: list[dict] = []
    if src.is_dir():
        shutil.copytree(src, dst, symlinks=True, dirs_exist_ok=True)
        for path in sorted(dst.rglob("*")):
            if path.is_file():
                entries.append(_index_entry(path, evidence_dir))
    else:
        dst.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(src, dst)
        entries.append(_index_entry(dst, evidence_dir))
    return entries


def _index_entry(path: Path, evidence_dir: Path) -> dict:
    return {
        "path": str(path.relative_to(evidence_dir)),
        "size": path.stat().st_size,
        "sha256": sha256_file(path),
    }


def _phase_ended(manifest: RunManifest, phase_id: str) -> bool:
    record = manifest.phases.get(phase_id)
    return record is not None and record.ended_at is not None


def collect_evidence(run: RunDir, manifest: RunManifest, status: RunStatus) -> dict:
    """Collect every available evidence artifact into ``evidence/``.

    Pre-flight checks raise :class:`EvidenceError` with a precise
    message when required evidence that must exist is missing.  The
    catalog is deterministic: types in §6.1 order, files sorted by path.
    """
    _preflight(run, manifest)

    evidence_dir = run.evidence
    if evidence_dir.exists():
        _clear_evidence_dir(evidence_dir)
    evidence_dir.mkdir(parents=True, exist_ok=True)

    collected_at = format_iso(utc_now())
    catalog: dict = {
        "schema": EVIDENCE_SCHEMA,
        "run_id": run.run_id,
        "collected_at": collected_at,
        "frozen": False,
        "root_hash": None,
        "types": {},
    }
    for etype in EVIDENCE_TYPES:
        catalog["types"][etype] = _COLLECTORS[etype](run, manifest, status, evidence_dir)

    write_evidence_catalog(run, catalog)
    return catalog


def _preflight(run: RunDir, manifest: RunManifest) -> None:
    """Fail cleanly when required evidence that must exist is missing."""
    if _phase_ended(manifest, "p1") and not (run.root / "protocol" / "environment.json").is_file():
        raise EvidenceError("E11: protocol/environment.json is missing after P1")
    if _phase_ended(manifest, "p5") and not (run.root / "validation").exists():
        raise EvidenceError("E4: validation outputs are missing after P5")
    if _phase_ended(manifest, "p8") and not (run.root / "reports").exists():
        raise EvidenceError("E9: reports are missing after P8")
    if not (run.root / "manifest.json").is_file():
        raise EvidenceError("E12: manifest.json is missing")
    if not (run.root / "status.json").is_file():
        raise EvidenceError("E12: status.json is missing")


def _clear_evidence_dir(evidence_dir: Path) -> None:
    for child in evidence_dir.iterdir():
        if child.name == "evidence.json":
            continue
        if child.is_dir():
            shutil.rmtree(child)
        else:
            child.unlink()


def _absent(reason: str) -> dict:
    return {"status": "absent", "reason": reason}


def _collect_e1(run, manifest, status, evidence_dir) -> dict:
    transcript = run.root / "protocol" / "session" / "transcript.txt"
    session_json = run.root / "protocol" / "session" / "session.json"
    if transcript.is_file():
        entry = _copy_file(transcript, evidence_dir / "e1-transcript.txt")
        return {"status": "present", "files": [entry]}
    recorded_absent = ""
    if session_json.is_file():
        meta = json.loads(session_json.read_text(encoding="utf-8"))
        recorded_absent = (meta.get("artifacts", {}).get("transcript") or {}).get("absent", "")
    if recorded_absent:
        return _absent(recorded_absent)
    if _phase_ended(manifest, "p3"):
        raise EvidenceError(
            "E1: session transcript is missing after P3 and session.json records "
            "no absence reason"
        )
    return _absent("no session has executed")


def _collect_e2(run, manifest, status, evidence_dir) -> dict:
    work = run.workspace_work
    if not work.is_dir():
        return _absent("workspace/work does not exist")
    files = _copy_tree(work, evidence_dir / "e2", evidence_dir)
    return {"status": "present", "files": files}


def _collect_e3(run, manifest, status, evidence_dir) -> dict:
    command_log = run.root / "protocol" / "command.log"
    session_json = run.root / "protocol" / "session" / "session.json"
    if command_log.is_file():
        entry = _copy_file(command_log, evidence_dir / "e3-command.log")
        return {"status": "present", "files": [entry]}
    recorded_absent = ""
    if session_json.is_file():
        meta = json.loads(session_json.read_text(encoding="utf-8"))
        recorded_absent = (meta.get("artifacts", {}).get("command_log") or {}).get("absent", "")
    if recorded_absent:
        return _absent(recorded_absent)
    if _phase_ended(manifest, "p3"):
        raise EvidenceError(
            "E3: command log is missing after P3 and session.json records no "
            "absence reason"
        )
    return _absent("no commands were logged")


def _collect_e4(run, manifest, status, evidence_dir) -> dict:
    if not _phase_ended(manifest, "p5"):
        return _absent("automatic validation (P5) has not run")
    validation = run.root / "validation"
    if not (validation / "validation.json").is_file():
        raise EvidenceError("E4: validation/validation.json is missing after P5")
    files = []
    files += _copy_tree(validation / "validation.json", evidence_dir / "e4" / "validation.json", evidence_dir)
    raw = validation / "raw"
    if raw.is_dir():
        for path in sorted(raw.iterdir()):
            if path.is_file():
                files += _copy_tree(path, evidence_dir / "e4" / "raw" / path.name, evidence_dir)
    return {"status": "present", "files": files}


def _collect_e5(run, manifest, status, evidence_dir) -> dict:
    phase_times = {
        "phases": {
            pid: record.to_dict() for pid, record in sorted(manifest.phases.items())
        },
        "checkpoints": dict(sorted(status.checkpoints.items())),
    }
    path = evidence_dir / "e5-phase-times.json"
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(phase_times, indent=2, sort_keys=True) + "\n", encoding="utf-8")
    return {
        "status": "present",
        "files": [
            {"path": path.name, "size": path.stat().st_size, "sha256": sha256_file(path)}
        ],
    }


def _collect_e6(run, manifest, status, evidence_dir) -> dict:
    return _absent("token usage was not recorded by the execution platform")


def _collect_e7(run, manifest, status, evidence_dir) -> dict:
    return _absent("cost was not recorded by the execution platform")


def _collect_e8(run, manifest, status, evidence_dir) -> dict:
    changes = run.workspace_work / "changes"
    if not changes.is_dir():
        return _absent("the submission has no changes/ directory")
    files = _copy_tree(changes, evidence_dir / "e8-diff-bundle", evidence_dir)
    return {
        "status": "present",
        "files": files,
        "baseline": "protocol/baseline/workspace-baseline.diff",
    }


def _collect_e9(run, manifest, status, evidence_dir) -> dict:
    if not _phase_ended(manifest, "p8"):
        return _absent("reports (P8) have not run")
    reports = run.root / "reports"
    files = _copy_tree(reports, evidence_dir / "e9-reports", evidence_dir)
    if not files:
        raise EvidenceError("E9: reports directory is empty after P8")
    return {"status": "present", "files": files}


def _collect_e10(run, manifest, status, evidence_dir) -> dict:
    return _absent("no browser/automation artifacts were produced in this run")


def _collect_e11(run, manifest, status, evidence_dir) -> dict:
    source = run.root / "protocol" / "environment.json"
    if not source.is_file():
        raise EvidenceError("E11: protocol/environment.json is missing")
    entry = _copy_file(source, evidence_dir / "e11-environment.json")
    return {"status": "present", "files": [entry]}


def _collect_e12(run, manifest, status, evidence_dir) -> dict:
    files = []
    files += _copy_tree(
        run.root / "manifest.json", evidence_dir / "e12-checkpoint-states" / "manifest.json", evidence_dir
    )
    files += _copy_tree(
        run.root / "status.json", evidence_dir / "e12-checkpoint-states" / "status.json", evidence_dir
    )
    return {"status": "present", "files": files}


_COLLECTORS = {
    "E1": _collect_e1,
    "E2": _collect_e2,
    "E3": _collect_e3,
    "E4": _collect_e4,
    "E5": _collect_e5,
    "E6": _collect_e6,
    "E7": _collect_e7,
    "E8": _collect_e8,
    "E9": _collect_e9,
    "E10": _collect_e10,
    "E11": _collect_e11,
    "E12": _collect_e12,
}


def evidence_catalog_path(run: RunDir) -> Path:
    return run.root / EVIDENCE_RELPATH


def write_evidence_catalog(run: RunDir, catalog: dict) -> None:
    path = evidence_catalog_path(run)
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(catalog, indent=2, sort_keys=True) + "\n", encoding="utf-8")


def load_evidence_catalog(run: RunDir) -> dict:
    path = evidence_catalog_path(run)
    if not path.is_file():
        raise EvidenceError(f"evidence catalog missing: {path}")
    data = json.loads(path.read_text(encoding="utf-8"))
    if data.get("schema") != EVIDENCE_SCHEMA:
        raise EvidenceError(f"unsupported evidence schema: {data.get('schema')!r}")
    return data


def evidence_artifact_index(catalog: dict) -> dict:
    """Canonical content index over the catalog's artifact entries.

    The Merkle-style root hash is computed over this index: paths,
    sizes, hashes, and statuses only — volatile metadata (run identity,
    timestamps, freeze state) is excluded so that identical evidence
    always yields an identical root hash.
    """
    index = {}
    for etype, entry in sorted(catalog["types"].items()):
        files = [dict(f) for f in entry.get("files", [])]
        index[etype] = {
            "status": entry.get("status"),
            "files": sorted(files, key=lambda f: f["path"]),
        }
        for key in ("reason", "baseline"):
            if entry.get(key):
                index[etype][key] = entry[key]
    return index


def evidence_root_hash(catalog: dict) -> str:
    """Merkle-style digest over the catalog's content index (§6.3.4)."""
    from tooling.harness.util.hash import sha256_text

    index = evidence_artifact_index(catalog)
    return sha256_text(json.dumps(index, indent=2, sort_keys=True) + "\n")
