"""P1 environment collection (MVB-006).

Implements harness §4.4–4.5: tool presence and versions, the runtime
validator's version output, reference HEAD/status, OS and
architecture, and the network policy.  Every value is captured
dynamically at prepare time; nothing is hardcoded.  Writes
``protocol/environment.json`` in the §4.5 schema.
"""

from __future__ import annotations

import json
import platform
import re
import shutil
from dataclasses import dataclass
from pathlib import Path

from tooling.harness.config import repo_root
from tooling.harness.safety.baseline import capture_baseline
from tooling.harness.util.proc import run_cmd

# §4.5 schema fields, in document order.
ENVIRONMENT_FIELDS = (
    "tools",
    "validator_version",
    "reference_head",
    "reference_status",
    "os",
    "network",
    "dependencies",
)


class EnvironmentError(Exception):
    """Environment collection failed."""


@dataclass(frozen=True)
class ToolSpec:
    """One required tool from §4.2."""

    name: str
    version_args: list[str]
    version_pattern: str  # regex capturing the version in the tool's output
    min_version: tuple[int, int] | None  # (major, minor) minimum, None = any


REQUIRED_TOOLS = (
    ToolSpec("python3", ["--version"], r"Python (\d+)\.(\d+)(?:\.(\d+))?", (3, 10)),
    ToolSpec("php", ["-v"], r"PHP (\d+)\.(\d+)(?:\.(\d+))?", (8, 1)),
    ToolSpec("node", ["-v"], r"v?(\d+)\.(\d+)(?:\.(\d+))?", (18, 0)),
    ToolSpec("git", ["--version"], r"git version (\d+)\.(\d+)(?:\.(\d+))?", None),
    # The agent execution platform (harness §4.2: any additional tool
    # used by a run must be recorded in the environment manifest with
    # its version; a missing platform blocks prepare at P1).
    ToolSpec("opencode", ["--version"], r"(\d+)\.(\d+)(?:\.(\d+))?", None),
)


@dataclass(frozen=True)
class ToolRecord:
    """Recorded state of one required tool (§4.5 ``tools`` entry)."""

    name: str
    required_version: str
    version: str | None
    path: str | None
    present: bool
    version_ok: bool


def _parse_version(value: str) -> tuple[int, int, int] | None:
    match = re.search(r"(\d+)\.(\d+)(?:\.(\d+))?", value)
    if match is None:
        return None
    major, minor, patch = match.groups()
    return (int(major), int(minor), int(patch) if patch else 0)


def _first_line(result) -> str:
    for stream in (result.stdout, result.stderr):
        if stream and stream.strip():
            return stream.splitlines()[0]
    return ""


def capture_tool(spec: ToolSpec) -> ToolRecord:
    """Detect one tool: presence via PATH, version via its version command."""
    path = shutil.which(spec.name)
    if path is None:
        return ToolRecord(
            name=spec.name,
            required_version=_required_label(spec),
            version=None,
            path=None,
            present=False,
            version_ok=False,
        )
    result = run_cmd([path, *spec.version_args])
    version = _first_line(result)
    parsed = _parse_version(version)
    if spec.min_version is None:
        version_ok = True
    else:
        version_ok = parsed is not None and parsed >= (*spec.min_version, 0)
    return ToolRecord(
        name=spec.name,
        required_version=_required_label(spec),
        version=version,
        path=path,
        present=True,
        version_ok=version_ok,
    )


def _required_label(spec: ToolSpec) -> str:
    if spec.min_version is None:
        return "any"
    return f"{spec.min_version[0]}.{spec.min_version[1]}+"


def collect_tools() -> list[dict]:
    return [record_to_dict(capture_tool(spec)) for spec in REQUIRED_TOOLS]


def record_to_dict(record: ToolRecord) -> dict:
    return {
        "name": record.name,
        "required_version": record.required_version,
        "version": record.version,
        "path": record.path,
        "present": record.present,
        "version_ok": record.version_ok,
    }


def capture_validator_version(*, rules_path: Path | None = None, cwd: Path | None = None) -> str:
    """Record the runtime validator's human report (its version output).

    Harness §4.4 item 2 records ``python -m tooling.validator --report
    human``.  The validator CLI requires ``--rules``, so the canonical
    command is run with the skill rule files; the report header carries
    the validator version.  The raw output is recorded regardless of
    the validator's exit code (this is a recording, not a gate).
    """
    cwd = cwd if cwd is not None else repo_root()
    rules = rules_path if rules_path is not None else repo_root() / "bga-senior-engineer-skill" / "rules"
    result = run_cmd(
        [shutil.which("python3") or "python3", "-m", "tooling.validator", "--rules", str(rules), "--report", "human"],
        cwd=cwd,
    )
    output = (result.stdout + result.stderr).strip()
    return output if output else f"validator run failed (exit {result.exit_code})"


def collect_environment(
    reference: str | Path,
    *,
    network: str = "disabled",
) -> dict:
    """Collect the §4.5 environment manifest for *reference*.

    All values are captured dynamically.  Missing or wrong-version
    tools are recorded in the ``tools`` entries (``present`` /
    ``version_ok``); use :func:`missing_tools` and
    :func:`mismatched_tools` for the gate decision.
    """
    baseline = capture_baseline(reference)
    return {
        "tools": collect_tools(),
        "validator_version": capture_validator_version(),
        "reference_head": baseline["head"],
        "reference_status": baseline["status_porcelain"],
        "os": {
            "platform": platform.system(),
            "release": platform.release(),
            "architecture": platform.machine(),
        },
        "network": network,
        "dependencies": [],
    }


def missing_tools(environment: dict) -> list[str]:
    """Names of required tools that are not present."""
    return [tool["name"] for tool in environment["tools"] if not tool["present"]]


def mismatched_tools(environment: dict) -> list[str]:
    """Names of present tools whose version is below the §4.2 minimum."""
    return [
        tool["name"]
        for tool in environment["tools"]
        if tool["present"] and not tool["version_ok"]
    ]


def validate_environment(data: dict) -> None:
    """Validate an environment manifest against the §4.5 field list."""
    if not isinstance(data, dict):
        raise EnvironmentError("environment manifest must be a JSON object")
    for field_name in ENVIRONMENT_FIELDS:
        if field_name not in data:
            raise EnvironmentError(f"environment manifest missing field {field_name!r}")
    if not isinstance(data["tools"], list):
        raise EnvironmentError("environment manifest 'tools' must be a list")
    if data["network"] not in ("enabled", "disabled"):
        raise EnvironmentError(
            f"network must be 'enabled' or 'disabled', got {data['network']!r}"
        )


def save_environment(environment: dict, path: str | Path) -> None:
    validate_environment(environment)
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        f.write(json.dumps(environment, indent=2, sort_keys=True) + "\n")


def load_environment(path: str | Path) -> dict:
    with open(path, encoding="utf-8") as f:
        data = json.load(f)
    validate_environment(data)
    return data
