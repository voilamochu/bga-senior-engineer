"""opencode platform adapter (MVB-010).

Launches a real opencode session: ``opencode run --format json`` with
the prompt bundle as the message, captures the raw JSON event stream
(raw response), and exports the session transcript with
``opencode export <session-id>`` (E1).  All platform commands are
executed through the run's command log (E3).

The launch requires a configured opencode provider; the run's network
policy is recorded in the session metadata but not mechanically
enforced (harness §3.5, FUT-07).  The run-scoped permission policy
(MS-10B G1) is delivered through the ``OPENCODE_PERMISSION``
environment variable, so the session can read the reference material
and write only its own working directory without touching any
configuration file or opencode's global config.

opencode resolves its project directory from the explicit ``--dir``
flag (MS-10B G7) with the ``PWD`` environment variable aligned as a
fallback, so the session always runs inside the run's
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
from tooling.harness.config import default_reference_root
from tooling.harness.util.clock import format_iso, utc_now

# The opencode CLI exposes no temperature flag (harness §5.5: use 0 or
# the platform's lowest supported value; when unsupported the fact is
# recorded and the run is flagged for comparability).
OPENCODE_TEMPERATURE_CAPABILITY = {
    "supported": False,
    "note": (
        "opencode CLI does not expose a temperature flag; "
        "--variant (provider-specific reasoning effort) is available instead"
    ),
    "policy": "flagged for comparability per harness §5.5",
}


def _session_env(cwd: Path, permissions: str) -> dict:
    """Child environment: ``PWD`` aligned to the launch directory plus
    the run-scoped opencode permission policy (MS-10B G1)."""
    env = dict(os.environ)
    env["PWD"] = str(cwd)
    env["OPENCODE_PERMISSION"] = permissions
    return env


def _permission_policy(config: SessionConfig) -> str:
    """Run-scoped opencode permission policy (MS-10B G1).

    The session's project root is ``workspace/work``; every other path
    the agent may touch is an external directory.  The policy:

    - ``external_directory``: allow ``workspace/work`` and
      ``workspace/read`` only; explicitly deny the real ``bga-mercurio``
      checkout (any other external path is denied by opencode's default
      ``ask`` resolving to denial in the headless ``run`` mode).
    - ``edit``: deny every external path (the work directory is the
      project root and remains writable; ``workspace/read`` stays
      read-only both by filesystem mode and by this rule).
    - ``webfetch``/``websearch``: denied (network disabled by default,
      harness §3.5).

    The policy is delivered as inline JSON through the environment, so
    it is strictly run-local and never modifies opencode's global
    configuration.  Allow rules precede deny rules because opencode
    evaluates patterns with the last matching rule winning.
    """
    work = str(config.workspace_work.resolve())
    read = str(config.workspace_read.resolve())
    reference = str(default_reference_root().resolve())
    policy = {
        "external_directory": {
            f"{work}/**": "allow",
            f"{read}/**": "allow",
            f"{reference}/**": "deny",
        },
        "edit": {
            f"{read}/**": "deny",
            f"{reference}/**": "deny",
        },
        "webfetch": "deny",
        "websearch": "deny",
    }
    return json.dumps(policy)


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
        permissions = _permission_policy(config)
        env = _session_env(config.workspace_work, permissions)

        version_check = config.command_log.run(
            ["opencode", "--version"], env=env
        )
        platform_version = (version_check.stdout or version_check.stderr).strip()

        argv = [
            "opencode",
            "run",
            "--dir",
            str(config.workspace_work),
            "--format",
            "json",
        ]
        if config.launch_model:
            argv += ["--model", config.launch_model]
        argv.append(bundle_text)

        result = config.command_log.run(
            argv,
            cwd=config.workspace_work,
            timeout=config.timeout_seconds,
            env=env,
        )
        raw_response = (result.stdout or "") + (result.stderr or "")

        transcript = ""
        session_id = _first_session_id(result.stdout or "")
        if session_id is not None:
            # MS-10B G5: attempt the export whenever a session ID was
            # produced, including after a timeout kill — opencode
            # persists the session while it runs, so the transcript of
            # a killed session is normally recoverable.  A failed
            # export leaves the transcript absent with a recorded
            # reason; it is never silently discarded.
            export = config.command_log.run(
                ["opencode", "export", session_id],
                cwd=config.workspace_work,
                env=env,
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
            capabilities={"temperature": OPENCODE_TEMPERATURE_CAPABILITY},
        )
