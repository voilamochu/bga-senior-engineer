"""Run status schema and §2.0.2 status graph (MVB-002).

``status.json`` holds the current status and a checkpoint index
(harness §9.1).  Transitions are constrained to the status graph
derived from §2.0.2 (statuses), §5.3 (interruption: any active phase
may be aborted, and an aborted run is archived with its evidence),
§5.1 (a blocked run may be retried after fixing the environment), and
§7.2 (G0/G1 rejections; verdict at P7).
"""

from __future__ import annotations

import json
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

from tooling.harness.runtime.manifest import PHASE_IDS
from tooling.harness.runtime.run_dir import RUN_ID_PATTERN
from tooling.harness.util.clock import is_iso_utc, utc_now_iso

STATUS_SCHEMA = "benchmark-harness-status/1.0"

VALID_STATUSES = (
    "INITIALIZING",
    "READY",
    "RUNNING",
    "COMPLETED",
    "TIMEOUT",
    "ABORTED",
    "BLOCKED",
    "REJECTED",
    "VERDICTED",
    "ARCHIVED",
)

INITIAL_STATUS = "INITIALIZING"

# §2.0.2 status graph.  The key is the current status; the value is the
# set of legal successor statuses.
TRANSITIONS: dict[str, frozenset[str]] = {
    "INITIALIZING": frozenset({"READY", "BLOCKED", "ABORTED"}),
    "READY": frozenset({"RUNNING", "BLOCKED", "ABORTED"}),
    "RUNNING": frozenset({"COMPLETED", "TIMEOUT", "ABORTED", "BLOCKED"}),
    "COMPLETED": frozenset({"REJECTED", "VERDICTED", "ABORTED"}),
    "TIMEOUT": frozenset({"REJECTED", "VERDICTED", "ABORTED"}),
    "BLOCKED": frozenset({"READY", "ABORTED"}),
    "REJECTED": frozenset({"ARCHIVED"}),
    "VERDICTED": frozenset({"ARCHIVED"}),
    "ABORTED": frozenset({"ARCHIVED"}),
    "ARCHIVED": frozenset(),
}


class StatusError(Exception):
    """Base class for status errors."""


class InvalidStatusError(StatusError):
    """The status name is not one of the §2.0.2 statuses."""


class InvalidTransitionError(StatusError):
    """The transition is not allowed by the §2.0.2 status graph."""


@dataclass
class RunStatus:
    """Current status and checkpoint index of a run.

    ``blocked_reason`` is an optional recorded reason for the
    ``BLOCKED`` status (MVB-006: a blocked run must carry a recorded
    reason); it is cleared on any transition away from ``BLOCKED``.
    """

    run_id: str
    status: str = INITIAL_STATUS
    checkpoints: dict[str, str] = field(default_factory=dict)
    updated_at: str | None = None
    blocked_reason: str | None = None
    schema: str = STATUS_SCHEMA

    def transition(
        self,
        new_status: str,
        *,
        checkpoint: str | None = None,
        at: str | None = None,
    ) -> None:
        """Transition to *new_status*; raises on any illegal transition."""
        if new_status not in VALID_STATUSES:
            raise InvalidStatusError(
                f"unknown status {new_status!r}; expected one of {', '.join(VALID_STATUSES)}"
            )
        if new_status not in TRANSITIONS[self.status]:
            raise InvalidTransitionError(
                f"illegal status transition {self.status} -> {new_status}"
            )
        timestamp = at if at is not None else utc_now_iso()
        self.status = new_status
        self.updated_at = timestamp
        if new_status != "BLOCKED":
            self.blocked_reason = None
        if checkpoint is not None:
            self.record_checkpoint(checkpoint, at=timestamp)

    def record_checkpoint(self, phase_id: str, at: str | None = None) -> None:
        """Record a phase boundary in the checkpoint index (§9.1)."""
        if phase_id not in PHASE_IDS:
            raise InvalidStatusError(
                f"unknown phase {phase_id!r}; expected one of {', '.join(PHASE_IDS)}"
            )
        timestamp = at if at is not None else utc_now_iso()
        self.checkpoints[phase_id] = timestamp
        self.updated_at = timestamp

    def is_terminal(self) -> bool:
        return not TRANSITIONS[self.status]

    # ------------------------------------------------------------------
    # Serialization
    # ------------------------------------------------------------------

    def to_dict(self) -> dict[str, Any]:
        return {
            "schema": self.schema,
            "run_id": self.run_id,
            "status": self.status,
            "checkpoints": dict(self.checkpoints),
            "updated_at": self.updated_at,
            "blocked_reason": self.blocked_reason,
        }

    @classmethod
    def from_dict(cls, data: dict) -> "RunStatus":
        if not isinstance(data, dict):
            raise InvalidStatusError("status must be a JSON object")
        status = cls(
            schema=data.get("schema", STATUS_SCHEMA),
            run_id=data.get("run_id", ""),
            status=data.get("status", INITIAL_STATUS),
            checkpoints=dict(data.get("checkpoints", {})),
            updated_at=data.get("updated_at"),
            blocked_reason=data.get("blocked_reason"),
        )
        status._validate()
        return status

    def save(self, path: str | Path) -> None:
        Path(path).parent.mkdir(parents=True, exist_ok=True)
        with open(path, "w", encoding="utf-8") as f:
            f.write(json.dumps(self.to_dict(), indent=2, sort_keys=True) + "\n")

    @classmethod
    def load(cls, path: str | Path) -> "RunStatus":
        with open(path, encoding="utf-8") as f:
            data = json.load(f)
        return cls.from_dict(data)

    # ------------------------------------------------------------------
    # Validation
    # ------------------------------------------------------------------

    def _validate(self) -> None:
        if self.schema != STATUS_SCHEMA:
            raise InvalidStatusError(
                f"unsupported status schema {self.schema!r}; expected {STATUS_SCHEMA!r}"
            )
        if not RUN_ID_PATTERN.match(self.run_id):
            raise InvalidStatusError(f"invalid run_id {self.run_id!r}")
        if self.status not in VALID_STATUSES:
            raise InvalidStatusError(
                f"unknown status {self.status!r}; expected one of {', '.join(VALID_STATUSES)}"
            )
        for phase_id, timestamp in self.checkpoints.items():
            if phase_id not in PHASE_IDS:
                raise InvalidStatusError(f"unknown phase {phase_id!r}")
            if not is_iso_utc(timestamp):
                raise InvalidStatusError(
                    f"checkpoint {phase_id} timestamp must be ISO-8601 UTC: {timestamp!r}"
                )
        if self.blocked_reason is not None and not isinstance(self.blocked_reason, str):
            raise InvalidStatusError("blocked_reason must be a string or null")
