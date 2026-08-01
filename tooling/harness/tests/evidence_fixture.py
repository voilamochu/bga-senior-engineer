"""Shared fixture: a run at the P4 stage (MS-04 execution artifacts)."""

import json

from tooling.harness.runtime.run_dir import create_run_dir
from tooling.harness.runtime.manifest import new_run_manifest
from tooling.harness.runtime.status import RunStatus
from tooling.harness.util.hash import sha256_text

AT = "2026-07-31T12:00:00Z"


def make_p4_run(tmp_path, *, with_validation=False, with_reports=False):
    """A run with completed P0-P3 phases and session artifacts.

    Mirrors the MS-04 stub-session flow without live execution:
    prompt bundle, session transcript, command log, environment,
    submission in work/, and P3 phase records.
    """
    run = create_run_dir("NOT-02", "demo-model", tmp_path / "runs")
    manifest = new_run_manifest(run.run_id, "NOT-02", model_id="demo-model",
                                started_at=AT)
    manifest.end_phase("p0", at=AT)
    manifest.start_phase("p1", at=AT)
    manifest.end_phase("p1", at=AT)
    manifest.start_phase("p2", at=AT)
    manifest.end_phase("p2", at=AT)
    manifest.start_phase("p3", at=AT)
    manifest.end_phase("p3", at=AT)
    manifest.update(
        prompt_bundle_sha256=sha256_text("bundle"),
        network="disabled",
        versions={**manifest.versions, "reference_head": "a" * 40},
        submission_status="complete",
        execution={
            "platform": "stub", "agent_id": "stub", "model": "demo-model",
            "started_at": AT, "ended_at": AT, "duration_seconds": 1.0,
            "exit_status": "completed", "exit_code": 0, "restarts": 0,
            "session_hashes": {},
        },
    )
    protocol = run.root / "protocol"
    protocol.mkdir(parents=True, exist_ok=True)
    (protocol / "prompt-bundle.txt").write_text("bundle")
    (protocol / "environment.json").write_text(json.dumps({
        "tools": [], "validator_version": "v1", "reference_head": "a" * 40,
        "reference_status": "", "os": {"platform": "Linux", "release": "x",
                                        "architecture": "x"},
        "network": "disabled", "dependencies": [],
    }))
    session_dir = protocol / "session"
    session_dir.mkdir(parents=True, exist_ok=True)
    (session_dir / "transcript.txt").write_text("session transcript\n")
    (session_dir / "raw-response.txt").write_text("raw\n")
    (session_dir / "session.json").write_text(json.dumps({
        "platform": "stub", "exit_status": "completed", "exit_code": 0,
        "artifacts": {
            "transcript": {"sha256": sha256_text("session transcript\n")},
            "command_log": {"sha256": sha256_text("")},
        },
    }))
    (protocol / "command.log").write_text(
        json.dumps({"command": "true", "exit_code": 0, "stdout": "",
                    "stderr": "", "wall_time": 0.1}) + "\n"
    )
    (run.baseline / "workspace-baseline.diff").write_text("")
    work = run.workspace_work
    (work / "reasoning.md").write_text("# Reasoning\n")
    (work / "architecture.md").write_text("# Architecture\n")
    (work / "subsystems.md").write_text("# Subsystems\n")
    (work / "testing-evidence.md").write_text("# Testing\n")
    (work / "validation-evidence.md").write_text("# Validation\n")
    (work / "changes").mkdir()
    (work / "changes" / "patch.php").write_text("<?php\n")
    (work / "declaration.json").write_text(json.dumps({
        "task_id": "NOT-02", "status": "complete", "self_reported_time": "1h",
        "artifacts": [],
    }))
    if with_validation:
        validation = run.root / "validation"
        (validation / "raw").mkdir(parents=True, exist_ok=True)
        (validation / "validation.json").write_text(json.dumps({"gates": {}}))
        (validation / "raw" / "V1.txt").write_text("validator output")
        manifest.start_phase("p5", at=AT)
        manifest.end_phase("p5", at=AT)
    if with_reports:
        reports = run.root / "reports"
        reports.mkdir(parents=True, exist_ok=True)
        (reports / "report.md").write_text("# Report\n")
        manifest.start_phase("p8", at=AT)
        manifest.end_phase("p8", at=AT)
    manifest.save(run.manifest_path)

    status = RunStatus(run_id=run.run_id, updated_at=AT)
    status.transition("READY", checkpoint="p1", at=AT)
    status.transition("RUNNING", checkpoint="p2", at=AT)
    status.transition("COMPLETED", checkpoint="p3", at=AT)
    status.save(run.status_path)
    return run, manifest, status
