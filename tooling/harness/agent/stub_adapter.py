"""Deterministic stub agent adapter (test/acceptance only).

Reproduces the agent execution contract exactly: it launches a
scripted "agent" whose commands are real commands executed through the
run's command log (E3), writes a deterministic submission into the
writable working directory, and returns a fixed transcript and raw
response.  Replaying the same session produces byte-identical
artifacts.
"""

from __future__ import annotations

import json
from pathlib import Path

from tooling.harness.agent.adapter import (
    AgentAdapter,
    SessionConfig,
    SessionResult,
)
from tooling.harness.util.clock import format_iso, utc_now

STUB_TRANSCRIPT = """# Stub Agent Session Transcript (deterministic)

This transcript is produced by the deterministic stub adapter. It
reproduces the execution contract without an LLM: the session reads
the prompt bundle, performs the declared work by executing commands
against the workspace copy, and submits the submission manifest.

## Steps

1. Read the prompt bundle and the read-only reference material.
2. Identify the duplicated notification blocks described in the task.
3. Execute the consolidation plan by writing the changed files into
   the working directory.
4. Write the submission manifest documents and the declaration.
5. Conclude the session.
"""

STUB_RAW_RESPONSE = "Stub session completed: deterministic submission written.\n"

SUBMISSION_FILES = (
    ("reasoning.md", "# Reasoning\n\nStub agent reasoning for the consolidation.\n"),
    ("architecture.md", "# Architecture\n\nHelper methods introduced per notification type.\n"),
    ("subsystems.md", "# Subsystems\n\nGame.php, notifications helpers.\n"),
    ("testing-evidence.md", "# Testing Evidence\n\nPayload parity verified on all call sites.\n"),
    ("validation-evidence.md", "# Validation Evidence\n\nDuplication scan clean.\n"),
    ("changes/notifications-helpers.php", "<?php\n// consolidated notification helpers\n"),
)

DECLARATION = {
    "task_id": "NOT-02",
    "status": "complete",
    "self_reported_time": "1.5h",
    "artifacts": [
        "reasoning.md",
        "architecture.md",
        "subsystems.md",
        "testing-evidence.md",
        "validation-evidence.md",
        "changes/",
    ],
}


class StubAdapter(AgentAdapter):
    """Deterministic stub: real commands, fixed content, fixed transcript."""

    platform = "stub"

    def launch(self, config: SessionConfig) -> SessionResult:
        started_at = format_iso(utc_now())
        work = config.workspace_work
        # The stub's simulated session executes real commands through the
        # command log, exactly like a real platform session would.
        config.command_log.run(["mkdir", "-p", str(work / "changes")])
        for relpath, content in SUBMISSION_FILES:
            staged = work / f".stub-stage-{relpath.replace('/', '-')}"
            staged.write_text(content, encoding="utf-8")
            config.command_log.run(["cp", str(staged), str(work / relpath)])
            staged.unlink()
        declaration = work / "declaration.json"
        declaration_text = (
            json.dumps(DECLARATION, indent=2, sort_keys=True) + "\n"
        )
        staged_decl = work / ".stub-stage-declaration.json"
        staged_decl.write_text(declaration_text, encoding="utf-8")
        config.command_log.run(["cp", str(staged_decl), str(declaration)])
        staged_decl.unlink()
        ended_at = format_iso(utc_now())
        return SessionResult(
            exit_code=0,
            started_at=started_at,
            ended_at=ended_at,
            duration_seconds=0.0,
            stdout=STUB_RAW_RESPONSE,
            stderr="",
            raw_response=STUB_RAW_RESPONSE,
            transcript=STUB_TRANSCRIPT,
            platform=self.platform,
            agent_id=config.agent_id,
            model=config.model,
            platform_version="stub 1.0",
        )
