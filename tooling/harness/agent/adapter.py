"""Agent adapter contract (MVB-010: Agent Session Launcher).

The adapter launches one agent session against the run's workspace with
the prompt bundle produced by MS-03, supervises it under the §5.2 time
budget, and returns the raw session artifacts.  Adapters are
deterministic with respect to their outputs: the same inputs produce
the same artifacts.
"""

from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path

from tooling.harness.util.proc import CommandLog

# Exit code for a session ended by the P3 timeout (not a real exit code).
SESSION_TIMEOUT_EXIT_CODE = -1


class AdapterError(Exception):
    """The agent platform could not launch or complete a session."""


@dataclass(frozen=True)
class SessionConfig:
    """Everything an adapter needs to launch one agent session."""

    platform: str
    agent_id: str
    model: str
    prompt_bundle: Path
    workspace_read: Path
    workspace_work: Path
    timeout_seconds: float
    command_log: CommandLog
    launch_model: str | None = None  # optional provider/model for the platform


@dataclass(frozen=True)
class SessionResult:
    """Raw session outcome; no interpretation, only faithful capture."""

    exit_code: int
    started_at: str
    ended_at: str
    duration_seconds: float
    stdout: str
    stderr: str
    raw_response: str
    transcript: str
    platform: str
    agent_id: str
    model: str
    platform_version: str | None = None

    def is_timeout(self) -> bool:
        return self.exit_code == SESSION_TIMEOUT_EXIT_CODE


class AgentAdapter:
    """Base class for agent platform adapters."""

    platform: str = "base"

    def launch(self, config: SessionConfig) -> SessionResult:
        raise NotImplementedError


def create_adapter(platform: str) -> AgentAdapter:
    """Adapter factory for the configured execution platform."""
    if platform == "opencode":
        from tooling.harness.agent.opencode_adapter import OpenCodeAdapter

        return OpenCodeAdapter()
    if platform == "stub":
        from tooling.harness.agent.stub_adapter import StubAdapter

        return StubAdapter()
    raise AdapterError(
        f"unknown execution platform {platform!r}; supported: opencode, stub"
    )
