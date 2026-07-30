"""
Release Validator for Runtime Specification v1.1.

Verifies that the published release document statistics match the actual
runtime files, preventing the release document from drifting out of sync
with the rules.

Contract::

    def validate(rules: RuleCollection, release_doc_path: str) -> ValidatorResult:
        ...
"""

from __future__ import annotations

from pathlib import Path
from typing import Any

from tooling._shared.markdown import extract_stat, find_section, parse_table
from tooling._shared.types import RuleCollection, ValidationError, ValidatorResult
from tooling.validator.src.validators.stats_generator import generate_statistics

VALIDATOR_NAME = "release"
RUNTIME_VERSION = "1.1"
SCHEMA_VERSION = "1.1"

INVENTORY_HEADER = "## 4. Runtime Inventory"
STATS_HEADER = "## 5. Implementation Statistics"


def validate(
    rules: RuleCollection,
    release_doc_path: str = "",
) -> ValidatorResult:
    """Run release validation against the loaded rule collection.

    Args:
        rules: Loaded rule collection with file metadata and indices.
        release_doc_path: Path to the release Markdown document.

    Returns:
        ValidatorResult with status and error list.
    """
    errors: list[ValidationError] = []

    if not release_doc_path:
        return ValidatorResult(name=VALIDATOR_NAME, status="pass")

    release_path = Path(release_doc_path)
    if not release_path.exists():
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                reason=f"Release document not found: {release_doc_path}",
            )
        )
        return ValidatorResult(name=VALIDATOR_NAME, status="fail", errors=errors)

    try:
        release_text = release_path.read_text(encoding="utf-8")
    except OSError as exc:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                reason=f"Cannot read release document: {exc}",
            )
        )
        return ValidatorResult(name=VALIDATOR_NAME, status="fail", errors=errors)

    actual_stats = generate_statistics(rules)

    # ------------------------------------------------------------------
    # Phase 1: Per-file validation via Runtime Inventory table
    # ------------------------------------------------------------------
    inventory_table = parse_table(release_text, table_header=INVENTORY_HEADER)
    if not inventory_table:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                severity="warning",
                reason="Cannot parse Runtime Inventory table — falling back to aggregate validation",
            )
        )
    else:
        _check_inventory(inventory_table, actual_stats, errors)

    # ------------------------------------------------------------------
    # Phase 2: Aggregate validation via Implementation Statistics
    # ------------------------------------------------------------------
    _check_aggregates(release_text, actual_stats, errors)

    # ------------------------------------------------------------------
    # Phase 3: Version checks
    # ------------------------------------------------------------------
    _check_versions(release_text, errors)

    status = "fail" if any(e.severity == "error" for e in errors) else "pass"
    return ValidatorResult(name=VALIDATOR_NAME, status=status, errors=errors)


# ------------------------------------------------------------------
# Inventory table checks
# ------------------------------------------------------------------


def _check_inventory(
    table: list[dict[str, str]],
    actual_stats: dict[str, Any],
    errors: list[ValidationError],
) -> None:
    actual_files = {f["file"]: f for f in actual_stats["files"]}

    # Build total row from table
    total_rules_doc = 0
    total_lines_doc = 0
    file_count_doc = 0

    for row in table:
        file_cell = _cell_ci(row, "file")
        if not file_cell or "total" in file_cell.lower().strip("*"):
            continue
        file_count_doc += 1
        doc_rules = _int_cell_ci(row, "rules")
        doc_lines = _int_cell_ci(row, "lines")

        total_rules_doc += doc_rules
        total_lines_doc += doc_lines

        actual = actual_files.get(file_cell)
        if actual is not None:
            if actual["rule_count"] != doc_rules:
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        file=file_cell,
                        reason=f"release doc says {file_cell} has {doc_rules} rules, actual has {actual['rule_count']}",
                    )
                )
            if actual["line_count"] != doc_lines:
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        file=file_cell,
                        reason=f"release doc says {file_cell} has {doc_lines} lines, actual has {actual['line_count']}",
                    )
                )
        else:
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    file=file_cell,
                    reason=f"release doc lists file '{file_cell}' which does not exist in runtime",
                )
            )

    # Check file count
    if file_count_doc != actual_stats["total_files"]:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                reason=f"release doc says {file_count_doc} files, actual has {actual_stats['total_files']}",
            )
        )

    # Check totals (from table or explicit total row)
    if total_rules_doc != actual_stats["total_rules"]:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                reason=f"release doc says total rules is {total_rules_doc}, actual is {actual_stats['total_rules']}",
            )
        )
    if total_lines_doc != actual_stats["total_lines"]:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                reason=f"release doc says total lines is {total_lines_doc}, actual is {actual_stats['total_lines']}",
            )
        )


# ------------------------------------------------------------------
# Aggregate checks
# ------------------------------------------------------------------


def _check_aggregates(
    release_text: str,
    actual_stats: dict[str, Any],
    errors: list[ValidationError],
) -> None:
    stats_section = find_section(release_text, STATS_HEADER)
    if not stats_section:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                severity="warning",
                reason="Cannot find Implementation Statistics section",
            )
        )
        return

    checks: list[tuple[str, Any, str]] = [
        ("total files", actual_stats["total_files"], "file"),
        ("total rules", actual_stats["total_rules"], "rule"),
        ("total lines", actual_stats["total_lines"], "line"),
    ]

    for label, actual, kind in checks:
        doc_val = extract_stat(stats_section, label)
        if doc_val is not None:
            doc_num = _parse_int(doc_val)
            if doc_num is not None and doc_num != actual:
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        reason=f"release doc says {label} is {doc_num}, actual is {actual}",
                    )
                )

    # Largest file
    doc_largest = extract_stat(stats_section, "largest file")
    if doc_largest is not None and doc_largest != actual_stats["largest_file"]:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                reason=f"release doc says largest file is '{doc_largest}', actual is '{actual_stats['largest_file']}'",
            )
        )

    # Smallest file
    doc_smallest = extract_stat(stats_section, "smallest file")
    if doc_smallest is not None and doc_smallest != actual_stats["smallest_file"]:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                reason=f"release doc says smallest file is '{doc_smallest}', actual is '{actual_stats['smallest_file']}'",
            )
        )

    # Cross-references
    doc_cross = extract_stat(stats_section, "cross")
    if doc_cross is not None:
        doc_num = _parse_int(doc_cross)
        if doc_num is not None and doc_num != actual_stats["cross_reference_count"]:
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    reason=f"release doc says cross-references is {doc_num}, actual is {actual_stats['cross_reference_count']}",
                )
            )

    # Average rules per file
    doc_avg = extract_stat(stats_section, "average")
    if doc_avg is not None:
        doc_num = _parse_float(doc_avg)
        if doc_num is not None and abs(doc_num - actual_stats["average_rules_per_file"]) > 0.01:
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    reason=f"release doc says average rules per file is {doc_num}, actual is {actual_stats['average_rules_per_file']}",
                )
            )


# ------------------------------------------------------------------
# Version checks
# ------------------------------------------------------------------


def _check_versions(
    release_text: str,
    errors: list[ValidationError],
) -> None:
    # Check for "v1.1" or "1.1" in the document title / summary
    has_runtime_version = RUNTIME_VERSION in release_text
    if not has_runtime_version:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                reason=f"release doc does not mention runtime version {RUNTIME_VERSION}",
            )
        )

    # Check schema version in the release summary table
    doc_schema = extract_stat(release_text, "schema version")
    if doc_schema is not None:
        if SCHEMA_VERSION not in doc_schema:
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    reason=f"release doc says schema version is '{doc_schema}', actual is {SCHEMA_VERSION}",
                )
            )


# ------------------------------------------------------------------
# Cell parsing helpers
# ------------------------------------------------------------------


def _cell_ci(row: dict[str, str], key: str) -> str:
    """Case-insensitive cell lookup."""
    for k, v in row.items():
        if k.lower() == key.lower():
            return v.strip()
    return ""


def _int_cell_ci(row: dict[str, str], key: str) -> int:
    """Parse an integer cell with case-insensitive column lookup."""
    raw = _cell_ci(row, key).replace(",", "")
    try:
        return int(raw)
    except (ValueError, TypeError):
        return 0


def _parse_int(text: str) -> int | None:
    """Parse an integer from text that may contain commas and extra words."""
    cleaned = text.strip().replace(",", "").split()[0] if text.strip() else ""
    try:
        return int(cleaned)
    except (ValueError, TypeError):
        return None


def _parse_float(text: str) -> float | None:
    cleaned = text.strip().split()[0] if text.strip() else ""
    try:
        return float(cleaned)
    except (ValueError, TypeError):
        return None
