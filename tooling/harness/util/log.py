"""Deterministic harness logging (MVB-003).

Appends JSON-lines records to a log file inside the run's ``protocol/``
directory and mirrors a human-readable line to stdout.  Every record is
a JSON object of ``{timestamp, level, message}`` with an ISO-8601 UTC
timestamp (harness §6.2.5).
"""

from __future__ import annotations

import json
import sys
from pathlib import Path
from typing import TextIO

from tooling.harness.util.clock import utc_now_iso

LEVELS = ("DEBUG", "INFO", "WARNING", "ERROR")

# Default log file inside a run directory (harness §9.1 protocol/).
DEFAULT_LOG_RELPATH = "protocol/harness.log"


class HarnessLog:
    """Append-only JSONL logger with a stdout mirror.

    Parameters
    ----------
    log_file:
        Path of the JSONL log file; ``None`` disables file logging.
    stream:
        Text stream for the human-readable mirror (default: stdout).
    """

    def __init__(self, log_file: str | Path | None = None, stream: TextIO | None = None):
        self._log_file = Path(log_file) if log_file is not None else None
        self._stream = stream if stream is not None else sys.stdout

    def log(self, level: str, message: str) -> None:
        if level not in LEVELS:
            raise ValueError(f"unknown log level: {level!r}")
        record = {
            "timestamp": utc_now_iso(),
            "level": level,
            "message": message,
        }
        if self._log_file is not None:
            self._log_file.parent.mkdir(parents=True, exist_ok=True)
            with open(self._log_file, "a", encoding="utf-8") as f:
                f.write(json.dumps(record, sort_keys=True) + "\n")
        self._stream.write(f"[{record['timestamp']}] {level:<7} {message}\n")

    def debug(self, message: str) -> None:
        self.log("DEBUG", message)

    def info(self, message: str) -> None:
        self.log("INFO", message)

    def warning(self, message: str) -> None:
        self.log("WARNING", message)

    def error(self, message: str) -> None:
        self.log("ERROR", message)


def harness_log(run_dir: str | Path | None = None, stream: TextIO | None = None) -> HarnessLog:
    """Default logger for a run.

    When *run_dir* is given the JSONL file is written to
    ``<run_dir>/protocol/harness.log``; otherwise the logger is
    stdout-only.
    """
    if run_dir is None:
        return HarnessLog(stream=stream)
    return HarnessLog(Path(run_dir) / DEFAULT_LOG_RELPATH, stream=stream)
