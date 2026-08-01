"""Session capture (MVB-012).

Persists every raw execution artifact faithfully: the session
transcript (E1), the raw platform response, the command log (E3), and
a session metadata file with hashes.  No interpretation or scoring
happens here.
"""

from __future__ import annotations

import json
import platform as platform_module
from dataclasses import dataclass
from pathlib import Path

from tooling.harness.agent.adapter import SessionResult
from tooling.harness.agent.intake import intake_submission
from tooling.harness.util.hash import sha256_file, sha256_text

SESSION_DIR_RELPATH = "protocol/session"
TRANSCRIPT_RELPATH = "protocol/session/transcript.txt"
RAW_RESPONSE_RELPATH = "protocol/session/raw-response.txt"
SESSION_JSON_RELPATH = "protocol/session/session.json"
INTAKE_RELPATH = "protocol/session/intake.json"
COMMAND_LOG_RELPATH = "protocol/command.log"


class CaptureError(Exception):
    """Session artifacts could not be persisted."""


@dataclass(frozen=True)
class SessionOutcome:
    """The session command's recorded outcome (status graph friendly)."""

    exit_status: str  # completed | timeout | aborted | failed
    started_at: str
    ended_at: str
    duration_seconds: float
    exit_code: int
    restarts: int


def write_text_artifact(run, relpath: str, text: str) -> Path:
    path = run.root / relpath
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(text, encoding="utf-8")
    return path


def artifact_record(run, relpath: str) -> dict:
    path = run.root / relpath
    return {
        "path": relpath,
        "size": path.stat().st_size,
        "sha256": sha256_file(path),
    }


def capture_session(
    run,
    *,
    result: SessionResult | None,
    outcome: SessionOutcome,
    timeout_seconds: float,
    launch_model: str | None,
    environment: dict,
    prompt_bundle_sha256: str,
) -> dict:
    """Persist the raw session artifacts and return the session metadata.

    ``result`` may be None for sessions that failed before producing
    output (e.g., a cancelled launch); the artifacts are then recorded
    as absent with a reason, never silently skipped (§6.2.3).
    """
    artifacts: dict[str, dict | str] = {}

    if result is not None and result.transcript:
        write_text_artifact(run, TRANSCRIPT_RELPATH, result.transcript)
        artifacts["transcript"] = artifact_record(run, TRANSCRIPT_RELPATH)
    else:
        artifacts["transcript"] = {"absent": "no transcript captured"}

    if result is not None and result.raw_response:
        write_text_artifact(run, RAW_RESPONSE_RELPATH, result.raw_response)
        artifacts["raw_response"] = artifact_record(run, RAW_RESPONSE_RELPATH)
    else:
        artifacts["raw_response"] = {"absent": "no raw response captured"}

    command_log_path = run.root / COMMAND_LOG_RELPATH
    if command_log_path.is_file():
        artifacts["command_log"] = artifact_record(run, COMMAND_LOG_RELPATH)
    else:
        artifacts["command_log"] = {"absent": "no commands were logged"}

    metadata = {
        "platform": result.platform if result else environment.get("platform", "unknown"),
        "agent_id": result.agent_id if result else environment.get("agent_id"),
        "model": result.model if result else environment.get("model"),
        "launch_model": launch_model,
        "prompt_bundle_sha256": prompt_bundle_sha256,
        "started_at": outcome.started_at,
        "ended_at": outcome.ended_at,
        "duration_seconds": outcome.duration_seconds,
        "timeout_seconds": timeout_seconds,
        "exit_status": outcome.exit_status,
        "exit_code": outcome.exit_code,
        "restarts": outcome.restarts,
        "environment": environment,
        "platform_version": result.platform_version if result else None,
        "capabilities": result.capabilities if result else None,
        "artifacts": artifacts,
    }
    write_text_artifact(
        run,
        SESSION_JSON_RELPATH,
        json.dumps(metadata, indent=2, sort_keys=True) + "\n",
    )
    return metadata


def capture_intake(run, work_dir) -> dict:
    """Record the §3.6 submission intake faithfully."""
    record = intake_submission(work_dir)
    write_text_artifact(
        run,
        INTAKE_RELPATH,
        json.dumps(record, indent=2, sort_keys=True) + "\n",
    )
    return record


def session_environment_snapshot(
    *,
    cwd: str | Path,
    reference_head: str | None,
    network: str,
) -> dict:
    """Raw environment snapshot captured at session time (recorded facts)."""
    return {
        "cwd": str(cwd),
        "platform": platform_module.system(),
        "architecture": platform_module.machine(),
        "python": platform_module.python_version(),
        "reference_head": reference_head,
        "network": network,
    }


def session_hashes(run, metadata: dict) -> dict:
    """SHA-256 of the captured session artifacts."""
    hashes: dict[str, str] = {}
    artifacts = metadata.get("artifacts", {})
    for name, record in artifacts.items():
        if isinstance(record, dict) and "sha256" in record:
            hashes[name] = record["sha256"]
    command_log = run.root / COMMAND_LOG_RELPATH
    if command_log.is_file():
        hashes["command_log"] = sha256_file(command_log)
    return hashes
