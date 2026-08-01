"""Harness configuration (MVB-001).

Resolves the runs root per harness spec §4.1 (``runs/`` is created by
the harness and never exists inside either repository) and provides the
C/E/H version pins recorded in the run manifest per §4.3.

Precedence for the runs root: explicit argument > settings file >
default (``<parent-of-both-repos>/runs/``).
"""

from __future__ import annotations

import json
from pathlib import Path

SETTINGS_FILE_NAME = "settings.json"

# Canonical document filenames whose headers carry the version pins (§4.3).
CORPUS_DOC = "benchmark-task-corpus.md"
EVALUATION_DOC = "benchmark-evaluation-spec.md"
HARNESS_DOC = "benchmark-harness-spec.md"

# Fallback pins when a canonical document cannot be read; values match the
# committed spec headers (all docs are v1.0, runtime v1.1, validator v1.0.0).
DEFAULT_DOC_VERSION = "1.0"
DEFAULT_RUNTIME_VERSION = "v1.1"
DEFAULT_VALIDATOR_VERSION = "1.0.0"


def repo_root() -> Path:
    """Absolute path of the ``bga-senior-engineer`` repository root."""
    return Path(__file__).resolve().parents[2]


def default_runs_root() -> Path:
    """Default runs root: the sibling ``runs/`` directory of this repo.

    Both repositories live under one parent (harness §4.1); the default
    runs root is that parent's ``runs/`` directory.
    """
    return repo_root().parent / "runs"


def default_reference_root() -> Path:
    """Default ``--reference`` path: the sibling ``bga-mercurio`` checkout.

    Harness §4.1 places the reference repository next to
    ``bga-senior-engineer`` under one parent.
    """
    return repo_root().parent / "bga-mercurio"


def default_skill_root() -> Path:
    """The frozen skill package (``bga-senior-engineer-skill/``)."""
    return repo_root() / "bga-senior-engineer-skill"


def default_system_prompt_path() -> Path:
    """The fixed system prompt asset (harness §3.1.1)."""
    return Path(__file__).resolve().parent / "prompts" / "system-prompt.txt"


def default_evaluation_docs() -> dict[str, Path]:
    """The pinned corpus and evaluation documents (harness §4.3)."""
    docs = repo_root() / "docs" / "evaluation"
    return {
        "corpus": docs / "benchmark-task-corpus.md",
        "evaluation": docs / "benchmark-evaluation-spec.md",
    }


def network_policy(settings: dict | None = None) -> str:
    """Network policy from the settings file (harness §4.5; default
    ``disabled`` per §3.5)."""
    settings = settings if settings is not None else load_settings()
    policy = settings.get("network", "disabled")
    if policy not in ("enabled", "disabled"):
        raise ValueError(f"settings 'network' must be 'enabled' or 'disabled', got {policy!r}")
    return policy


def block_on_tool_version_mismatch(settings: dict | None = None) -> bool:
    """Whether a present-but-wrong-version tool marks the run BLOCKED.

    MVB-006 acceptance: a version mismatch is recorded in
    ``environment.json`` and the run either proceeds flagged (default)
    or is blocked, per config.
    """
    settings = settings if settings is not None else load_settings()
    return bool(settings.get("block_on_tool_version_mismatch", False))


def execution_platform(settings: dict | None = None) -> str:
    """Configured agent execution platform (default ``opencode``)."""
    settings = settings if settings is not None else load_settings()
    platform = settings.get("platform", "opencode")
    if platform not in ("opencode", "stub"):
        raise ValueError(f"settings 'platform' must be 'opencode' or 'stub', got {platform!r}")
    return platform


def launch_model(settings: dict | None = None) -> str | None:
    """Optional provider/model passed to the platform (``opencode run --model``).

    When absent the platform's default model is used and recorded in the
    session metadata.
    """
    settings = settings if settings is not None else load_settings()
    model = settings.get("model")
    return model if isinstance(model, str) and model else None


def default_settings_file() -> Path:
    """Optional settings file next to this module."""
    return Path(__file__).resolve().parent / SETTINGS_FILE_NAME


def load_settings(settings_file: str | Path | None = None) -> dict:
    """Load the settings file; returns ``{}`` when it is absent.

    Raises ValueError if the file exists but is not a JSON object.
    """
    path = Path(settings_file) if settings_file is not None else default_settings_file()
    if not path.is_file():
        return {}
    with open(path, encoding="utf-8") as f:
        data = json.load(f)
    if not isinstance(data, dict):
        raise ValueError(f"settings file {path} must contain a JSON object")
    return data


def resolve_runs_root(runs_root: str | Path | None = None, settings: dict | None = None) -> Path:
    """Resolve the runs root: explicit override > settings file > default."""
    if runs_root is not None:
        return Path(runs_root)
    settings = settings if settings is not None else load_settings()
    configured = settings.get("runs_root")
    if configured:
        return Path(configured)
    return default_runs_root()


def _read_doc_version(path: Path) -> str | None:
    """Parse the ``**Version:**`` line from a canonical benchmark document."""
    try:
        text = path.read_text(encoding="utf-8")
    except OSError:
        return None
    for line in text.splitlines():
        stripped = line.strip()
        if stripped.startswith("**Version:**"):
            value = stripped[len("**Version:**"):].strip()
            if value:
                return value
    return None


def read_pinned_versions() -> dict[str, str]:
    """Version pins per harness §4.3: corpus/evaluation/harness read from the
    canonical document headers; runtime and validator from the frozen pins.

    Missing or unreadable documents fall back to the committed canonical
    values so ``init`` remains deterministic.
    """
    docs = repo_root() / "docs" / "evaluation"
    return {
        "corpus": _read_doc_version(docs / CORPUS_DOC) or DEFAULT_DOC_VERSION,
        "evaluation": _read_doc_version(docs / EVALUATION_DOC) or DEFAULT_DOC_VERSION,
        "harness": _read_doc_version(docs / HARNESS_DOC) or DEFAULT_DOC_VERSION,
        "runtime": DEFAULT_RUNTIME_VERSION,
        "validator": DEFAULT_VALIDATOR_VERSION,
    }
