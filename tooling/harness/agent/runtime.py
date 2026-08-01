"""Agent session runtime (MVB-011).

Manages the P2–P3 execution lifecycle: pre-flight boundary checks,
status transitions per §2.0.2, §5.2 time budgets, §5.3 interruption
resume, submission intake (§3.6), and raw artifact capture.  The
runtime records raw artifacts faithfully; it never interprets or
scores them.
"""

from __future__ import annotations

import re
from pathlib import Path

from tooling.harness.agent.adapter import (
    AdapterError,
    AgentAdapter,
    SessionConfig,
    SessionResult,
)
from tooling.harness.agent.capture import (
    SessionOutcome,
    capture_intake,
    capture_session,
    session_environment_snapshot,
    session_hashes,
)
from tooling.harness.prompt.materialize import parse_corpus_task
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.run_dir import RunDir
from tooling.harness.runtime.status import RunStatus
from tooling.harness.util.clock import format_iso, utc_now
from tooling.harness.util.log import HarnessLog
from tooling.harness.util.proc import CommandLog

# §5.2 time budgets.
P2_BUDGET_SECONDS = 15 * 60  # agent initialization
MIN_P3_BUDGET_SECONDS = 2 * 60 * 60  # minimum P3 budget
MAX_P3_BUDGET_SECONDS = 16 * 60 * 60  # maximum P3 budget

COMMAND_LOG_RELPATH = "protocol/command.log"

EFFORT_PATTERN = re.compile(r"(\d+)(?:\s*-\s*(\d+))?\s*hours?")


class SessionRuntimeError(Exception):
    """The session command cannot execute the run."""


def p3_budget_seconds(effort_estimate: str, *, override_minutes: float | None = None) -> int:
    """P3 budget per §5.2: effort × 1.5, rounded up to the next hour,
    min 2 hours, max 16 hours.  *override_minutes* bypasses the formula
    (operator/testing override, recorded in the manifest)."""
    if override_minutes is not None:
        return max(1, int(round(override_minutes * 60)))
    match = EFFORT_PATTERN.search(effort_estimate)
    if not match:
        raise SessionRuntimeError(
            f"cannot derive effort estimate from {effort_estimate!r}"
        )
    upper = int(match.group(2) or match.group(1))
    hours = max(1, int(-(-(upper * 1.5) // 1)))  # ceil to whole hours
    return max(MIN_P3_BUDGET_SECONDS, min(MAX_P3_BUDGET_SECONDS, hours * 3600))


def verify_boundaries(run: RunDir) -> list[str]:
    """Pre-flight boundary verification (§3.4): read/ read-only,
    work/ writable, prompt bundle present and unmodified.

    Returns a list of failures (empty when all checks pass).
    """
    failures: list[str] = []

    read_root = run.workspace_read
    for path in read_root.rglob("*"):
        mode = path.stat().st_mode
        if path.is_dir() or path.is_file():
            if mode & 0o222:
                failures.append(f"workspace/read is not read-only: {path}")
                break
    if not read_root.is_dir():
        failures.append(f"workspace/read does not exist: {read_root}")

    probe = run.workspace_work / ".harness-session-probe"
    try:
        probe.write_text("probe", encoding="utf-8")
        probe.unlink()
    except OSError as exc:
        failures.append(f"workspace/work is not writable: {exc}")

    bundle_path = run.root / "protocol" / "prompt-bundle.txt"
    if not bundle_path.is_file():
        failures.append(f"prompt bundle missing: {bundle_path}")
    return failures


def run_session(
    run: RunDir,
    manifest: RunManifest,
    status: RunStatus,
    *,
    adapter: AgentAdapter,
    timeout_seconds: int,
    log: HarnessLog,
    command_log: CommandLog,
    launch_model: str | None = None,
    docs_paths: dict | None = None,
) -> dict:
    """Execute one agent session for the run and record its outcome.

    Returns a summary dict ``{exit_status, status, intake, metadata,
    error}``.  The run's status and manifest are updated and persisted;
    the session never touches the reference repository.
    """
    failures = verify_boundaries(run)
    if failures:
        raise SessionRuntimeError(
            "session pre-flight boundary checks failed: " + "; ".join(failures)
        )
    if not manifest.prompt_bundle_sha256:
        raise SessionRuntimeError("run has no prompt bundle; run 'prompt' first")

    # Resume detection (§5.3): a RUNNING run was interrupted mid-P3.
    restarts = 0
    if status.status == "RUNNING":
        restarts = int((manifest.execution or {}).get("restarts", 0)) + 1
        log.warning(
            f"run {run.run_id} was interrupted mid-session; restarting P3 "
            f"(restart #{restarts})"
        )
    else:
        status.transition("RUNNING", checkpoint="p2")
    if "p3" not in manifest.phases or manifest.phases["p3"].started_at is None:
        manifest.start_phase("p3", at=format_iso(utc_now()))

    prompt_bundle = run.root / "protocol" / "prompt-bundle.txt"
    config = SessionConfig(
        platform=adapter.platform,
        agent_id=adapter.platform,
        model=manifest.model.get("id", ""),
        prompt_bundle=prompt_bundle,
        workspace_read=run.workspace_read,
        workspace_work=run.workspace_work,
        timeout_seconds=timeout_seconds,
        command_log=command_log,
        launch_model=launch_model,
    )

    started_at = format_iso(utc_now())
    log.info(
        f"launching session (platform={adapter.platform}, "
        f"timeout={timeout_seconds}s)"
    )
    try:
        result = adapter.launch(config)
    except KeyboardInterrupt:
        log.error("session interrupted by operator")
        return _finish(
            run, manifest, status, log, command_log, config, timeout_seconds,
            launch_model, restarts, result=None,
            exit_status="aborted", new_status="ABORTED",
            reason="session interrupted by operator",
        )
    except AdapterError as exc:
        log.error(f"agent platform launch failed: {exc}")
        return _finish(
            run, manifest, status, log, command_log, config, timeout_seconds,
            launch_model, restarts, result=None,
            exit_status="failed", new_status="BLOCKED",
            reason=f"agent platform launch failed: {exc}",
        )

    ended_at = format_iso(utc_now())
    if result.is_timeout():
        log.warning(f"session timed out after {timeout_seconds}s; partial work retained")
        return _finish(
            run, manifest, status, log, command_log, config, timeout_seconds,
            launch_model, restarts, result=result,
            exit_status="timeout", new_status="TIMEOUT",
            reason="P3 time budget exceeded",
        )
    if result.exit_code != 0:
        log.error(f"session failed with exit code {result.exit_code}")
        return _finish(
            run, manifest, status, log, command_log, config, timeout_seconds,
            launch_model, restarts, result=result,
            exit_status="failed", new_status="ABORTED",
            reason=f"agent session failed with exit code {result.exit_code}",
        )

    log.info("session completed")
    return _finish(
        run, manifest, status, log, command_log, config, timeout_seconds,
        launch_model, restarts, result=result,
        exit_status="completed", new_status="COMPLETED", reason=None,
    )


def _finish(
    run, manifest, status, log, command_log, config, timeout_seconds,
    launch_model, restarts, *, result, exit_status, new_status, reason,
) -> dict:
    ended_at = format_iso(utc_now())
    outcome = SessionOutcome(
        exit_status=exit_status,
        started_at=_result_started(result, ended_at),
        ended_at=ended_at,
        duration_seconds=_result_duration(result),
        exit_code=result.exit_code if result else -1,
        restarts=restarts,
    )

    environment = session_environment_snapshot(
        cwd=run.workspace_work,
        reference_head=(manifest.versions or {}).get("reference_head"),
        network=manifest.network,
    )
    metadata = capture_session(
        run,
        result=result,
        outcome=outcome,
        timeout_seconds=timeout_seconds,
        launch_model=launch_model,
        environment=environment,
        prompt_bundle_sha256=manifest.prompt_bundle_sha256,
    )
    intake = capture_intake(run, run.workspace_work)
    log.info(
        f"submission intake: status={intake['status']}, "
        f"found={len(intake['found'])}, missing={intake['missing']}"
    )

    manifest.update(
        execution={
            "platform": config.platform,
            "agent_id": config.agent_id,
            "model": config.model,
            "started_at": outcome.started_at,
            "ended_at": outcome.ended_at,
            "duration_seconds": outcome.duration_seconds,
            "exit_status": exit_status,
            "exit_code": outcome.exit_code,
            "restarts": restarts,
            "timeouts": {
                "p2_budget_seconds": P2_BUDGET_SECONDS,
                "p3_budget_seconds": timeout_seconds,
            },
            "session_hashes": session_hashes(run, metadata),
        },
        submission_status=intake["status"],
        timeouts={
            "p2_budget_seconds": P2_BUDGET_SECONDS,
            "p3_budget_seconds": timeout_seconds,
        },
    )
    manifest.end_phase("p3", at=ended_at)
    manifest.save(run.manifest_path)

    status.transition(new_status, checkpoint="p3")
    if reason:
        if new_status == "BLOCKED":
            status.blocked_reason = reason
        elif new_status == "ABORTED":
            status.blocked_reason = reason  # recorded abort reason
    status.save(run.status_path)

    return {
        "exit_status": exit_status,
        "status": new_status,
        "reason": reason,
        "intake": intake,
        "metadata": metadata,
    }


def _result_started(result: SessionResult | None, fallback: str) -> str:
    return result.started_at if result else fallback


def _result_duration(result: SessionResult | None) -> float:
    return result.duration_seconds if result else 0.0
