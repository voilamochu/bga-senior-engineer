"""opencode platform adapter (MVB-010).

Launches a real opencode session: ``opencode run --format json`` with
the prompt bundle as the message, captures the raw JSON event stream
(raw response), and exports the session transcript with
``opencode export <session-id>`` (E1).  All platform commands are
executed through the run's command log (E3).

The launch requires a configured opencode provider; the run's network
policy is recorded in the session metadata but not mechanically
enforced (harness §3.5, FUT-07).

opencode resolves its project directory from the ``PWD`` environment
variable in preference to the process working directory, so the child
environment is corrected so the session always runs inside the run's
``workspace/work/`` directory.
"""

from __future__ import annotations

import json
import os
from pathlib import Path

from tooling.harness.agent.adapter import (
    AgentAdapter,
    SessionConfig,
    SessionResult,
)
from tooling.harness.util.clock import format_iso, utc_now


def _session_env(cwd: Path) -> dict:
    """Child environment with ``PWD`` aligned to the launch directory."""
    env = dict(os.environ)
    env["PWD"] = str(cwd)
    return env


def _first_session_id(raw_response: str) -> str | None:
    """Session ID from the first JSON event of ``opencode run --format json``."""
    for line in raw_response.splitlines():
        line = line.strip()
        if not line.startswith("{"):
            continue
        try:
            event = json.loads(line)
        except json.JSONDecodeError:
            continue
        session_id = event.get("sessionID")
        if session_id:
            return session_id
    return None


class OpenCodeAdapter(AgentAdapter):
    """Real opencode execution adapter."""

    platform = "opencode"

    def launch(self, config: SessionConfig) -> SessionResult:
        started_at = format_iso(utc_now())
        bundle_text = config.prompt_bundle.read_text(encoding="utf-8")

        version_check = config.command_log.run(
            ["opencode", "--version"], env=_session_env(config.workspace_work)
        )
        platform_version = (version_check.stdout or version_check.stderr).strip()

        argv = ["opencode", "run", "--format", "json"]
        if config.launch_model:
            argv += ["--model", config.launch_model]
        argv.append(bundle_text)

        result = config.command_log.run(
            argv,
            cwd=config.workspace_work,
            timeout=config.timeout_seconds,
            env=_session_env(config.workspace_work),
        )
        raw_response = (result.stdout or "") + (result.stderr or "")

        transcript = ""
        session_id = _first_session_id(result.stdout or "")
        if session_id is not None and result.exit_code >= 0:
            export = config.command_log.run(
                ["opencode", "export", session_id],
                cwd=config.workspace_work,
                env=_session_env(config.workspace_work),
            )
            if export.exit_code == 0 and export.stdout.strip():
                transcript = export.stdout
        # On timeout the process was killed; partial stdout is retained
        # as the raw response (§5.2).

        return SessionResult(
            exit_code=result.exit_code,
            started_at=started_at,
            ended_at=format_iso(utc_now()),
            duration_seconds=result.wall_time,
            stdout=result.stdout,
            stderr=result.stderr,
            raw_response=raw_response,
            transcript=transcript,
            platform=self.platform,
            agent_id=config.agent_id,
            model=config.model,
            platform_version=platform_version or None,
        )
