"""
Rule ID Validator for Runtime Specification v1.1.

Verifies every rule ID follows the naming convention ``PREFIX-NNN``,
is unique within and across files, and appears in the correct file
for its prefix.

Contract::

    def validate(rules: RuleCollection) -> ValidatorResult:
        ...
"""

from __future__ import annotations

import re
from pathlib import Path
from typing import Any

from tooling._shared.loader import read_raw_json
from tooling._shared.schema import (
    PREFIX_TO_DOMAIN,
    VALID_PREFIXES,
    get_prefix_from_rule_id,
    is_valid_rule_id,
)
from tooling._shared.types import RuleCollection, ValidationError, ValidatorResult

VALIDATOR_NAME = "rule_id"

_ID_LINE_RE = re.compile(r'"id"\s*:\s*"([^"]+)"')
_BROAD_ID_RE = re.compile(r"^([A-Z]+)-(\d+)$")


def validate(rules: RuleCollection) -> ValidatorResult:
    """Run rule ID validation against the loaded rule collection.

    Args:
        rules: Loaded rule collection with file metadata and indices.

    Returns:
        ValidatorResult with status and error list.
    """
    errors: list[ValidationError] = []

    # Track which files each rule ID appears in (from raw JSON)
    id_file_map: dict[str, list[str]] = {}

    for file_info in rules.files:
        filepath = Path(file_info.path)
        filename = filepath.name

        # ---- Within-file duplicate detection ---------------------------
        id_lines = _find_id_lines(file_info.path)
        for rid, lines in id_lines.items():
            if len(lines) > 1:
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        rule_id=rid,
                        file=filename,
                        reason=_within_file_dup_msg(rid, filename, lines),
                    )
                )

        # ---- Build cross-file ID tracking -----------------------------
        raw_data = read_raw_json(file_info.path)
        if raw_data is None:
            continue
        raw_rules = raw_data.get("rules", [])
        if not isinstance(raw_rules, list):
            continue

        for rule_entry in raw_rules:
            if not isinstance(rule_entry, dict):
                continue
            rid = rule_entry.get("id")
            if not isinstance(rid, str) or not rid:
                continue
            if rid not in id_file_map:
                id_file_map[rid] = []
            if filename not in id_file_map[rid]:
                id_file_map[rid].append(filename)

    # ---- Naming convention & prefix-file alignment --------------------
    for rule_id, rule in rules.rules.items():
        filename = Path(rule.file_path).name

        if not is_valid_rule_id(rule_id):
            suggestion = _format_correct_id(rule_id)
            msg = f"ID does not match naming convention"
            if suggestion:
                msg += f" (expected {suggestion})"
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    file=filename,
                    reason=msg,
                )
            )
            continue

        prefix = get_prefix_from_rule_id(rule_id)
        expected_domain = PREFIX_TO_DOMAIN.get(prefix) if prefix else None
        if expected_domain and rule.file_domain != expected_domain:
            expected_file = f"{expected_domain}.json"
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    file=filename,
                    reason=f"prefix {prefix} in {filename} (expected in {expected_file})",
                )
            )

    # ---- Cross-file duplicate detection --------------------------------
    for rule_id, filenames in id_file_map.items():
        if len(filenames) > 1:
            sorted_files = sorted(filenames)
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    file=sorted_files[0],
                    reason=f"duplicate rule ID across files ({' and '.join(sorted_files)})",
                )
            )

    status = "fail" if errors else "pass"
    return ValidatorResult(name=VALIDATOR_NAME, status=status, errors=errors)


# ------------------------------------------------------------------
# Helpers
# ------------------------------------------------------------------


def _find_id_lines(filepath: str | Path) -> dict[str, list[int]]:
    """Return a mapping of rule_id → list of 1-based line numbers."""
    path = Path(filepath)
    try:
        text = path.read_text(encoding="utf-8")
    except OSError:
        return {}
    lines = text.splitlines()

    result: dict[str, list[int]] = {}
    for lineno, line in enumerate(lines, 1):
        match = _ID_LINE_RE.search(line)
        if match:
            rid = match.group(1)
            result.setdefault(rid, []).append(lineno)
    return result


def _within_file_dup_msg(rid: str, filename: str, lines: list[int]) -> str:
    """Format a within-file duplicate error message with line numbers."""
    first, second = lines[0], lines[1]
    return f"duplicate rule ID in {filename} (line {first} and line {second})"


def _format_correct_id(rule_id: str) -> str | None:
    """Suggest the correctly formatted version of a rule ID.

    Returns ``None`` when no sensible suggestion can be made.
    """
    match = _BROAD_ID_RE.match(rule_id)
    if match:
        prefix = match.group(1)
        num_str = match.group(2)
        if prefix in VALID_PREFIXES:
            return f"{prefix}-{int(num_str):03d}"
    return None
