"""Command runner producing E3 command-log records (harness §6.1, §6.2.4).

``run_cmd`` executes a command and captures command, stdout, stderr,
exit code, and wall time — the five fields required for the E3 command
log.  When a *log_file* is supplied, one JSON record per command is
appended, including for commands that succeed with no output.
"""

from __future__ import annotations

import json
import shlex
import subprocess
import time
from dataclasses import dataclass
from pathlib import Path

from tooling.harness.util.clock import utc_now_iso

# Exit code used when the command exceeded *timeout* (not a real exit code).
TIMEOUT_EXIT_CODE = -1


@dataclass(frozen=True)
class CommandResult:
    """Full record of one executed command (E3 fields)."""

    command: str
    exit_code: int
    stdout: str
    stderr: str
    wall_time: float
    started_at: str
    ended_at: str

    def to_record(self) -> dict:
        return {
            "command": self.command,
            "exit_code": self.exit_code,
            "stdout": self.stdout,
            "stderr": self.stderr,
            "wall_time": self.wall_time,
            "started_at": self.started_at,
            "ended_at": self.ended_at,
        }


def run_cmd(
    cmd: str | list[str],
    *,
    log_file: str | Path | None = None,
    cwd: str | Path | None = None,
    timeout: float | None = None,
    env: dict | None = None,
) -> CommandResult:
    """Run *cmd* and capture its complete E3 record.

    Parameters
    ----------
    cmd:
        Command as a single string (split with :func:`shlex.split`) or an
        argv list.
    log_file:
        If given, a JSON record is appended to this file (JSONL).
    cwd, timeout, env:
        Passed through to :func:`subprocess.run`.

    Returns
    -------
    CommandResult
        With exit code, stdout, stderr, wall time (seconds), and ISO-8601
        UTC start/end timestamps.  On timeout the exit code is -1.
    """
    if isinstance(cmd, str):
        argv = shlex.split(cmd)
        display = cmd
    else:
        argv = list(cmd)
        display = " ".join(shlex.quote(part) for part in argv)

    started_at = utc_now_iso()
    start = time.monotonic()
    proc = subprocess.Popen(
        argv,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
        cwd=cwd,
        env=env,
    )
    try:
        stdout, stderr = proc.communicate(timeout=timeout)
        exit_code = proc.returncode
    except subprocess.TimeoutExpired:
        # §5.2: on timeout, retain all evidence.  Terminate gracefully so
        # the platform can flush its output, then escalate to SIGKILL.
        proc.terminate()
        try:
            stdout, stderr = proc.communicate(timeout=5)
        except subprocess.TimeoutExpired:
            proc.kill()
            stdout, stderr = proc.communicate()
        exit_code = TIMEOUT_EXIT_CODE
    stdout = stdout or ""
    stderr = stderr or ""
    wall_time = time.monotonic() - start
    ended_at = utc_now_iso()

    result = CommandResult(
        command=display,
        exit_code=exit_code,
        stdout=stdout,
        stderr=stderr,
        wall_time=wall_time,
        started_at=started_at,
        ended_at=ended_at,
    )
    if log_file is not None:
        _append_record(log_file, result.to_record())
    return result


def _append_record(log_file: str | Path, record: dict) -> None:
    path = Path(log_file)
    path.parent.mkdir(parents=True, exist_ok=True)
    with open(path, "a", encoding="utf-8") as f:
        f.write(json.dumps(record, sort_keys=True) + "\n")


class CommandLog:
    """Append-only JSONL command log (E3, harness §6.1/§6.2.4).

    Every harness-side command executed through :meth:`run` is recorded
    with command, stdout, stderr, exit code, and wall time — including
    commands that succeed with no output.
    """

    def __init__(self, path: str | Path):
        self.path = Path(path)

    def run(self, cmd: str | list[str], **kwargs) -> CommandResult:
        """Run *cmd* through :func:`run_cmd` and append its record."""
        result = run_cmd(cmd, log_file=self.path, **kwargs)
        return result

    def record(self, result: CommandResult) -> None:
        """Append an existing :class:`CommandResult` record."""
        _append_record(self.path, result.to_record())

    def records(self) -> list[dict]:
        """All recorded command records in order."""
        if not self.path.is_file():
            return []
        records = []
        for line in self.path.read_text(encoding="utf-8").splitlines():
            if line.strip():
                records.append(json.loads(line))
        return records
