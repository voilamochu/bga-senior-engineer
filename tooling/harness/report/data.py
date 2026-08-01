"""Report source data (MS-08, MVB-022).

Assembles the report's single in-memory source of truth from the run's
recorded artifacts — manifest, status, environment, validation results,
evidence catalog, review records, and scoring records.  The generator
consumes only completed artifacts and never recomputes scores or
re-runs validation: every value below is read from the recorded files.

The assembly is deterministic: identical run data produces identical
``ReportData`` (no wall-clock values are introduced; all timestamps come
from the recorded manifest/status).
"""

from __future__ import annotations

import json
import re
from dataclasses import dataclass, field
from pathlib import Path

from tooling.harness.evidence.collect import EVIDENCE_TYPES, load_evidence_catalog
from tooling.harness.runtime.manifest import PHASE_IDS, RunManifest
from tooling.harness.runtime.run_dir import RunDir
from tooling.harness.runtime.status import RunStatus

REPORT_SCHEMA = "benchmark-harness-evaluation-report/1.0"
REPORT_VERSION = "1.0"

# §8.2 required sections, in document order (report.md headings).
REQUIRED_SECTIONS = (
    "Header",
    "Run Status",
    "Environment Summary",
    "Gate Results",
    "Evidence Index",
    "Category Scores",
    "Total and Verdict",
    "Manual Review Notes",
    "Common Failure Mode Notes",
    "Safety Verification",
    "Errata History",
)

# §8.3 required metadata groups (evaluation-report.json).
REQUIRED_METADATA_GROUPS = (
    "identity",
    "versions",
    "timing",
    "usage",
    "evidence",
    "scores",
    "verdict",
    "attribution",
)


class ReportError(Exception):
    """The report cannot be assembled from the run's recorded artifacts."""


@dataclass
class ReportData:
    """Single in-memory source for both report renderings (§8.2/§8.3)."""

    run_id: str
    status: str
    identity: dict
    versions: dict
    timing: dict
    usage: dict
    evidence: dict
    evidence_types: dict
    gates: dict
    scores: dict
    verdict: dict
    attribution: dict
    environment: dict
    review_notes: dict
    common_failure_modes: list[str]
    errata: list[dict]
    rejected: bool


def assemble_report_data(
    run: RunDir,
    manifest: RunManifest,
    status: RunStatus,
    *,
    eval_doc: str | Path | None = None,
) -> ReportData:
    """Assemble the report data from the run's recorded artifacts.

    Required artifacts: manifest.json, status.json,
    protocol/environment.json, validation/validation.json, and the
    evidence catalog.  For VERDICTED runs, the review records and
    scores are additionally required; for REJECTED runs they are
    recorded as not performed.
    """
    _require_manifest(run, manifest)
    environment = _load_json(run.root / "protocol" / "environment.json",
                              "protocol/environment.json")
    validation = _load_json(run.root / "validation" / "validation.json",
                            "validation/validation.json")
    catalog = load_evidence_catalog(run)
    if not catalog.get("frozen"):
        raise ReportError("evidence is not frozen; reports require the P4 freeze")

    rejected = status.status in ("REJECTED",)
    scores = {}
    review_notes = {
        "reviewed": False,
        "reviewer": None,
        "review_status": "not reviewed (run rejected at P5)" if rejected else None,
        "manual_review_file": None,
        "findings": [],
        "category_records": {},
    }
    if not rejected:
        review_notes = _assemble_review(run)
        scores = _assemble_scores(run)

    versions = {key: value for key, value in (manifest.versions or {}).items()}
    model = manifest.model or {}
    identity = {
        "run_id": run.run_id,
        "model": model.get("id") or "unknown",
        "model_version": model.get("version") or "unknown",
        "task_id": (manifest.task or {}).get("id"),
        "task_difficulty": (manifest.task or {}).get("difficulty") or "unknown",
    }
    timing = _assemble_timing(manifest, status)
    usage = {
        "token_usage": None,
        "cost": None,
        "note": "not recorded (evidence E6/E7 absent)",
    }
    evidence = {
        "evidence_manifest_hash": _sha256(run.evidence / "evidence.json"),
        "evidence_root_hash": catalog.get("root_hash") or manifest.evidence_root_hash,
    }
    evidence_types = {
        etype: {
            "status": catalog["types"].get(etype, {}).get("status"),
            "reason": catalog["types"].get(etype, {}).get("reason"),
            "files": [
                {"path": f["path"]}
                for f in catalog["types"].get(etype, {}).get("files", [])
            ],
        }
        for etype in EVIDENCE_TYPES
    }
    verdict = _assemble_verdict(validation, scores)
    # The Errata History (§8.2 #11) lists the run's corrections and
    # lifecycle records.  The report's own generation records ("P8
    # reports") are excluded: a report cannot record its own recording,
    # and excluding them keeps regeneration byte-stable.
    errata = [
        dict(entry)
        for entry in manifest.errata
        if "P8 reports" not in entry.get("message", "")
    ]
    return ReportData(
        run_id=run.run_id,
        status=status.status,
        identity=identity,
        versions=versions,
        timing=timing,
        usage=usage,
        evidence=evidence,
        evidence_types=evidence_types,
        gates=_assemble_gates(validation),
        scores=scores,
        verdict=verdict,
        attribution=_assemble_attribution(manifest, review_notes),
        environment=environment,
        review_notes=review_notes,
        common_failure_modes=_common_failure_modes(eval_doc, identity["task_id"]),
        errata=errata,
        rejected=rejected,
    )


def _require_manifest(run: RunDir, manifest: RunManifest) -> None:
    if not manifest.frozen:
        raise ReportError("run manifest is not frozen; reports require P4")
    if not run.root.exists():
        raise ReportError(f"run directory missing: {run.root}")


def _load_json(path: Path, label: str) -> dict:
    if not path.is_file():
        raise ReportError(f"missing required artifact: {label}")
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError) as exc:
        raise ReportError(f"cannot read {label}: {exc}") from exc
    if not isinstance(data, dict):
        raise ReportError(f"{label} must be a JSON object")
    return data


def _assemble_review(run: RunDir) -> dict:
    review_json = _load_json(run.review / "review.json", "review/review.json")
    md_path = run.review / "manual-review.md"
    if not md_path.is_file():
        raise ReportError("missing required artifact: review/manual-review.md")
    scores_path = run.review_scoring / "scores.json"
    if not scores_path.is_file():
        raise ReportError("missing required artifact: review/scoring/scores.json")
    md_text = md_path.read_text(encoding="utf-8")
    return {
        "reviewed": True,
        "reviewer": review_json.get("reviewer"),
        "review_status": review_json.get("status"),
        "review_version": review_json.get("review_version"),
        "manual_review_file": "review/manual-review.md",
        "findings": _section_text(md_text, "G3 Findings") + _section_text(md_text, "G4 Findings"),
        "category_records": review_json.get("category_scores") or {},
    }


def _assemble_scores(run: RunDir) -> dict:
    scores = _load_json(run.review_scoring / "scores.json", "review/scoring/scores.json")
    return {
        "category_scores": scores.get("category_scores", {}),
        "weights": scores.get("rubric", {}).get("weights", {}),
        "total": scores.get("arithmetic", {}).get("total"),
        "capped_total": scores.get("arithmetic", {}).get("capped_total"),
        "caps": scores.get("arithmetic", {}).get("cap_reasons"),
        "verdict": scores.get("verdict"),
        "critical_failures": scores.get("critical_failures", []),
    }


def _assemble_timing(manifest: RunManifest, status: RunStatus) -> dict:
    phase_times: dict = {}
    for phase_id in PHASE_IDS:
        record = manifest.phases.get(phase_id)
        started = record.started_at if record else None
        ended = record.ended_at if record else None
        if ended is None and phase_id in status.checkpoints:
            ended = status.checkpoints[phase_id]
        phase_times[phase_id] = {"started_at": started, "ended_at": ended}
    started_at = None
    p0 = manifest.phases.get("p0")
    if p0 and p0.started_at:
        started_at = p0.started_at
    return {
        "started_at": started_at,
        "ended_at": status.updated_at,
        "phase_times": phase_times,
        "note": "p6 (manual review) has no recorded checkpoint in the "
        "current baselines; recorded from manifest/status records",
    }


def _assemble_gates(validation: dict) -> dict:
    gates: dict = {}
    for gate_id in ("G0", "G1", "G2"):
        gate = validation.get("gates", {}).get(gate_id, {})
        gates[gate_id] = {
            "name": gate.get("name"),
            "verdict": gate.get("verdict"),
            "checks": [
                {
                    "id": check.get("id"),
                    "verdict": check.get("verdict"),
                    "raw_output": check.get("raw_output"),
                }
                for check in gate.get("checks", [])
            ],
        }
    summary = validation.get("summary", {})
    return {
        "gates": gates,
        "summary": {
            "verdict": summary.get("verdict"),
            "blocking_failures": summary.get("blocking_failures", []),
            "non_blocking_findings": summary.get("non_blocking_findings", []),
            "substitutions": summary.get("substitutions", []),
        },
        "validation_file": "validation/validation.json",
    }


def _assemble_verdict(validation: dict, scores: dict) -> dict:
    summary = validation.get("summary", {})
    if scores.get("verdict"):
        return {
            "verdict": scores["verdict"],
            "critical_failures": scores.get("critical_failures", []),
            "rejection_reason": None,
        }
    blocking = summary.get("blocking_failures", [])
    return {
        "verdict": "REJECTED",
        "critical_failures": list(blocking),
        "rejection_reason": (
            "blocking validation failures: " + ", ".join(blocking)
            if blocking
            else "rejected by G0/G1"
        ),
    }


def _assemble_attribution(manifest: RunManifest, review_notes: dict) -> dict:
    return {
        "evaluator_id": review_notes.get("reviewer"),
        "operator_id": None,
        "operator_note": "operator identity is recorded in the run postmortem "
        "(not in the run artifacts)",
        "manual_review_file": review_notes.get("manual_review_file"),
    }


def _section_text(md_text: str, heading: str) -> list[str]:
    """Bullet lines of a ``## <heading>`` section of manual-review.md."""
    lines = md_text.splitlines()
    matches = [i for i, line in enumerate(lines) if line.strip() == f"## {heading}"]
    if not matches:
        return []
    start = matches[-1] + 1
    content: list[str] = []
    for line in lines[start:]:
        if line.startswith("## "):
            break
        stripped = line.strip()
        if stripped.startswith("- ") and len(stripped) > 2:
            content.append(stripped[2:].strip())
    return content


def _common_failure_modes(eval_doc, task_id: str) -> list[str]:
    doc = Path(eval_doc) if eval_doc is not None else (
        Path(__file__).resolve().parents[3] / "docs" / "evaluation"
        / "benchmark-evaluation-spec.md"
    )
    try:
        lines = doc.read_text(encoding="utf-8").splitlines()
    except OSError:
        return []
    in_task = False
    in_section = False
    modes: list[str] = []
    for line in lines:
        if re.match(rf"^### \d+\.\d+ {re.escape(task_id)}\s*$", line):
            in_task = True
            continue
        if in_task and re.match(r"^### ", line):
            break
        if not in_task:
            continue
        if line.strip() == "#### Common Failure Modes":
            in_section = True
            continue
        if in_section:
            if line.startswith("#"):
                break
            stripped = line.strip()
            if stripped.startswith("- "):
                modes.append(stripped[2:].strip())
    return modes


def _sha256(path: Path) -> str | None:
    from tooling.harness.util.hash import sha256_file

    if not path.is_file():
        return None
    return sha256_file(path)
