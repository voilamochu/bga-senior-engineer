"""Submission manifest intake (harness §3.6).

After P3, the run's working directory is inspected for the submission
manifest: the five evidence documents, the ``changes/`` diff bundle,
and ``declaration.json``.  Intake records what exists and what is
missing — it never interprets or scores the content.
"""

from __future__ import annotations

import json
from pathlib import Path

# Required submission items per harness §3.6.
REQUIRED_DOCUMENTS = (
    "reasoning.md",
    "architecture.md",
    "subsystems.md",
    "testing-evidence.md",
    "validation-evidence.md",
)
CHANGES_DIR = "changes"
DECLARATION_FILE = "declaration.json"

VALID_DECLARATION_STATUSES = ("complete", "partial")
REQUIRED_DECLARATION_FIELDS = ("task_id", "status", "self_reported_time", "artifacts")


class IntakeError(Exception):
    """The submission manifest is malformed (recorded, never blocking)."""


def intake_submission(work_dir: str | Path) -> dict:
    """Inspect *work_dir* for the §3.6 submission manifest.

    Returns a faithful record: ``status`` (``complete``/``partial``),
    ``found``/``missing`` item lists, the parsed declaration (or null),
    and ``issues`` for malformed declarations.
    """
    work_dir = Path(work_dir)
    found: list[str] = []
    missing: list[str] = []

    for name in REQUIRED_DOCUMENTS:
        path = work_dir / name
        if path.is_file():
            found.append(name)
        else:
            missing.append(name)
    if (work_dir / CHANGES_DIR).is_dir():
        found.append(CHANGES_DIR)
    else:
        missing.append(CHANGES_DIR)
    if (work_dir / DECLARATION_FILE).is_file():
        found.append(DECLARATION_FILE)
    else:
        missing.append(DECLARATION_FILE)

    declaration = None
    issues: list[str] = []
    if (work_dir / DECLARATION_FILE).is_file():
        declaration, declaration_issues = _validate_declaration(
            work_dir / DECLARATION_FILE
        )
        issues.extend(declaration_issues)

    declared_status = "partial"
    if declaration is not None and declaration.get("status") in VALID_DECLARATION_STATUSES:
        declared_status = declaration["status"]
    if missing and declared_status == "complete":
        issues.append(
            f"declaration claims 'complete' but items are missing: {', '.join(missing)}"
        )
    return {
        "status": declared_status if declaration is not None else "partial",
        "found": found,
        "missing": missing,
        "declaration": declaration,
        "issues": issues,
    }


def _validate_declaration(path: Path) -> tuple[dict | None, list[str]]:
    issues: list[str] = []
    try:
        with open(path, encoding="utf-8") as f:
            data = json.load(f)
    except (json.JSONDecodeError, OSError) as exc:
        return None, [f"declaration.json is not valid JSON: {exc}"]
    if not isinstance(data, dict):
        return None, ["declaration.json must be a JSON object"]
    for field in REQUIRED_DECLARATION_FIELDS:
        if field not in data:
            issues.append(f"declaration.json missing field {field!r}")
    status = data.get("status")
    if status is not None and status not in VALID_DECLARATION_STATUSES:
        issues.append(
            f"declaration.json status must be one of {VALID_DECLARATION_STATUSES}, "
            f"got {status!r}"
        )
    return data, issues
