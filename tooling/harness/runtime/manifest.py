"""Run manifest schema (MVB-002).

Implements harness Appendix A (``manifest.json``): created at P0,
extended at each checkpoint with phase timestamps, frozen at P4.
After freezing, any mutation raises except documented errata
(§8.4).  The ``frozen`` and ``errata`` fields are implementation
extensions of the Appendix A schema required by MVB-002's freeze
semantics.
"""

from __future__ import annotations

import json
from dataclasses import dataclass, field
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

from tooling.harness.config import read_pinned_versions
from tooling.harness.runtime.run_dir import RUN_ID_PATTERN
from tooling.harness.util.clock import is_iso_utc, utc_now_iso

MANIFEST_SCHEMA = "benchmark-harness-manifest/1.0"

PHASE_IDS = ("p0", "p1", "p2", "p3", "p4", "p5", "p6", "p7", "p8", "p9")
VALID_NETWORK_POLICIES = ("enabled", "disabled")
VALID_SUBMISSION_STATUSES = ("complete", "partial")

# Every field of the Appendix A example, in document order, plus the
# ``frozen``/``errata`` extensions and the ``prompt`` extension
# (prompt-generation metadata recorded at P2, MS-03).
APPENDIX_A_FIELDS = (
    "schema",
    "run_id",
    "task",
    "versions",
    "model",
    "network",
    "baseline_amended",
    "prompt_bundle_sha256",
    "evidence_root_hash",
    "phases",
    "timeouts",
    "submission_status",
    "rebaseline",
    "prompt",
    "execution",
    "evidence",
)


class ManifestError(Exception):
    """Base class for manifest errors."""


class InvalidManifestError(ManifestError):
    """Manifest data violates the Appendix A schema."""


class FrozenManifestError(ManifestError):
    """A mutation was attempted after the manifest was frozen at P4."""


@dataclass
class PhaseRecord:
    """started_at/ended_at timestamps of one phase (ISO-8601 UTC)."""

    started_at: str | None = None
    ended_at: str | None = None

    @classmethod
    def from_dict(cls, data: dict) -> "PhaseRecord":
        return cls(started_at=data.get("started_at"), ended_at=data.get("ended_at"))

    def to_dict(self) -> dict:
        return {"started_at": self.started_at, "ended_at": self.ended_at}


@dataclass
class RunManifest:
    """Validated in-memory representation of ``manifest.json``."""

    run_id: str
    schema: str = MANIFEST_SCHEMA
    task: dict[str, Any] = field(default_factory=dict)
    versions: dict[str, Any] = field(default_factory=dict)
    model: dict[str, Any] = field(default_factory=dict)
    network: str = "disabled"
    baseline_amended: bool = False
    prompt_bundle_sha256: str | None = None
    evidence_root_hash: str | None = None
    phases: dict[str, PhaseRecord] = field(default_factory=dict)
    timeouts: dict[str, Any] = field(default_factory=dict)
    submission_status: str | None = None
    rebaseline: dict[str, Any] | None = None
    prompt: dict[str, Any] | None = None
    execution: dict[str, Any] | None = None
    evidence: dict[str, Any] | None = None
    frozen: bool = False
    errata: list[dict[str, Any]] = field(default_factory=list)

    # ------------------------------------------------------------------
    # Mutation (rejected once frozen, except add_errata)
    # ------------------------------------------------------------------

    def update(self, **fields: Any) -> None:
        """Set schema fields in bulk; rejects unknown or frozen fields."""
        self._check_not_frozen()
        for name, value in fields.items():
            if name not in APPENDIX_A_FIELDS:
                raise InvalidManifestError(f"unknown manifest field {name!r}")
        for name, value in fields.items():
            setattr(self, name, value)
        self._validate()

    def start_phase(self, phase_id: str, at: str | None = None) -> None:
        """Record a phase's ``started_at`` timestamp (checkpoint extension)."""
        self._check_not_frozen()
        record = self._phase_record(phase_id)
        if record.started_at is not None:
            raise InvalidManifestError(f"phase {phase_id} already started")
        started_at = at if at is not None else utc_now_iso()
        if not is_iso_utc(started_at):
            raise InvalidManifestError(
                f"phase {phase_id} started_at must be ISO-8601 UTC: {started_at!r}"
            )
        record.started_at = started_at

    def end_phase(self, phase_id: str, at: str | None = None) -> None:
        """Record a phase's ``ended_at`` timestamp (checkpoint extension)."""
        self._check_not_frozen()
        record = self._phase_record(phase_id)
        if record.ended_at is not None:
            raise InvalidManifestError(f"phase {phase_id} already ended")
        if record.started_at is None:
            raise InvalidManifestError(f"phase {phase_id} has not started")
        ended_at = at if at is not None else utc_now_iso()
        if not is_iso_utc(ended_at):
            raise InvalidManifestError(
                f"phase {phase_id} ended_at must be ISO-8601 UTC: {ended_at!r}"
            )
        if _parse_timestamp(ended_at) < _parse_timestamp(record.started_at):
            raise InvalidManifestError(
                f"phase {phase_id} ended_at precedes started_at "
                f"({ended_at} < {record.started_at})"
            )
        record.ended_at = ended_at

    def freeze(self) -> None:
        """Freeze the manifest at P4: mutations are rejected from now on."""
        self.frozen = True

    def add_errata(self, message: str, at: str | None = None) -> None:
        """Append an errata record; the only mutation allowed post-freeze."""
        if not message.strip():
            raise ValueError("errata message must not be empty")
        self.errata.append(
            {"recorded_at": at if at is not None else utc_now_iso(), "message": message}
        )

    # ------------------------------------------------------------------
    # Serialization
    # ------------------------------------------------------------------

    def to_dict(self) -> dict[str, Any]:
        return {
            "schema": self.schema,
            "run_id": self.run_id,
            "task": dict(self.task),
            "versions": dict(self.versions),
            "model": dict(self.model),
            "network": self.network,
            "baseline_amended": self.baseline_amended,
            "prompt_bundle_sha256": self.prompt_bundle_sha256,
            "evidence_root_hash": self.evidence_root_hash,
            "phases": {pid: record.to_dict() for pid, record in self.phases.items()},
            "timeouts": dict(self.timeouts),
            "submission_status": self.submission_status,
            "rebaseline": self.rebaseline,
            "prompt": self.prompt,
            "execution": self.execution,
            "evidence": self.evidence,
            "frozen": self.frozen,
            "errata": [dict(entry) for entry in self.errata],
        }

    @classmethod
    def from_dict(cls, data: dict) -> "RunManifest":
        if not isinstance(data, dict):
            raise InvalidManifestError("manifest must be a JSON object")
        manifest = cls(
            schema=data.get("schema", MANIFEST_SCHEMA),
            run_id=data.get("run_id", ""),
            task=data.get("task", {}),
            versions=data.get("versions", {}),
            model=data.get("model", {}),
            network=data.get("network", "disabled"),
            baseline_amended=data.get("baseline_amended", False),
            prompt_bundle_sha256=data.get("prompt_bundle_sha256"),
            evidence_root_hash=data.get("evidence_root_hash"),
            phases={
                pid: PhaseRecord.from_dict(rec)
                for pid, rec in data.get("phases", {}).items()
            },
            timeouts=data.get("timeouts", {}),
            submission_status=data.get("submission_status"),
            rebaseline=data.get("rebaseline"),
            prompt=data.get("prompt"),
            execution=data.get("execution"),
            evidence=data.get("evidence"),
            frozen=data.get("frozen", False),
            errata=data.get("errata", []),
        )
        manifest._validate()
        return manifest

    def save(self, path: str | Path) -> None:
        """Write deterministic JSON (sorted keys, 2-space indent, newline)."""
        Path(path).parent.mkdir(parents=True, exist_ok=True)
        with open(path, "w", encoding="utf-8") as f:
            f.write(json.dumps(self.to_dict(), indent=2, sort_keys=True) + "\n")

    @classmethod
    def load(cls, path: str | Path) -> "RunManifest":
        with open(path, encoding="utf-8") as f:
            data = json.load(f)
        return cls.from_dict(data)

    # ------------------------------------------------------------------
    # Validation
    # ------------------------------------------------------------------

    def _check_not_frozen(self) -> None:
        if self.frozen:
            raise FrozenManifestError(
                "manifest is frozen at P4; only errata may be appended"
            )

    def _phase_record(self, phase_id: str) -> PhaseRecord:
        if phase_id not in PHASE_IDS:
            raise InvalidManifestError(
                f"unknown phase {phase_id!r}; expected one of {', '.join(PHASE_IDS)}"
            )
        if phase_id not in self.phases:
            self.phases[phase_id] = PhaseRecord()
        return self.phases[phase_id]

    def _validate(self) -> None:
        if self.schema != MANIFEST_SCHEMA:
            raise InvalidManifestError(
                f"unsupported manifest schema {self.schema!r}; expected {MANIFEST_SCHEMA!r}"
            )
        if not RUN_ID_PATTERN.match(self.run_id):
            raise InvalidManifestError(f"invalid run_id {self.run_id!r}")
        if not isinstance(self.task, dict) or not str(self.task.get("id", "")).strip():
            raise InvalidManifestError("task.id must be a non-empty string")
        if self.network not in VALID_NETWORK_POLICIES:
            raise InvalidManifestError(
                f"network must be one of {VALID_NETWORK_POLICIES}, got {self.network!r}"
            )
        if (
            self.submission_status is not None
            and self.submission_status not in VALID_SUBMISSION_STATUSES
        ):
            raise InvalidManifestError(
                "submission_status must be one of "
                f"{VALID_SUBMISSION_STATUSES}, got {self.submission_status!r}"
            )
        for pid, record in self.phases.items():
            if pid not in PHASE_IDS:
                raise InvalidManifestError(f"unknown phase key {pid!r}")
            if record.started_at is not None and not is_iso_utc(record.started_at):
                raise InvalidManifestError(
                    f"phase {pid} started_at must be ISO-8601 UTC: {record.started_at!r}"
                )
            if record.ended_at is not None and not is_iso_utc(record.ended_at):
                raise InvalidManifestError(
                    f"phase {pid} ended_at must be ISO-8601 UTC: {record.ended_at!r}"
                )
        if self.prompt is not None and not isinstance(self.prompt, dict):
            raise InvalidManifestError("prompt must be an object or null")
        if self.execution is not None and not isinstance(self.execution, dict):
            raise InvalidManifestError("execution must be an object or null")
        if self.evidence is not None and not isinstance(self.evidence, dict):
            raise InvalidManifestError("evidence must be an object or null")


def _parse_timestamp(value: str) -> datetime:
    """Parse an ISO-8601 UTC timestamp with optional fractional seconds."""
    normalized = value[:-1]  # strip the trailing Z
    if "." in normalized:
        seconds, fraction = normalized.split(".", 1)
        normalized = f"{seconds}.{fraction[:6]}"
    return datetime.fromisoformat(normalized + "+00:00").astimezone(timezone.utc)


def new_run_manifest(
    run_id: str,
    task_id: str,
    *,
    model_id: str | None = None,
    versions: dict | None = None,
    started_at: str | None = None,
) -> RunManifest:
    """Initial P0 manifest: schema, identity, pins, and an open p0 phase.

    ``versions`` defaults to the pinned C/E/H/runtime/validator versions
    (harness §4.3); ``reference_head`` is recorded later at P0 baseline
    capture (MVB-004) and is ``None`` here.  ``model.temperature`` and
    ``model.temperature_controlled`` follow the §5.5 guidance defaults.
    """
    task_id = task_id.strip()
    if not task_id:
        raise ValueError("task_id must not be empty")
    pinned = versions if versions is not None else read_pinned_versions()
    model = {
        "id": model_id if model_id is not None else "",
        "version": None,
        "temperature": 0,
        "temperature_controlled": True,
    }
    manifest = RunManifest(
        run_id=run_id,
        task={"id": task_id, "difficulty": None},
        versions=dict(pinned, reference_head=None),
        model=model,
    )
    manifest.start_phase("p0", at=started_at)
    return manifest
