"""
Schema Validator for Runtime Specification v1.1.

Verifies every rule file and every rule within it conforms to Schema v1.1.

Contract::

    def validate(rules: RuleCollection) -> ValidatorResult:
        ...
"""

from __future__ import annotations

from pathlib import Path
from typing import Any

from tooling._shared.loader import read_raw_json
from tooling._shared.schema import (
    ALL_RULE_FIELDS,
    FILE_META_REQUIRED_FIELDS,
    RULE_REQUIRED_FIELDS,
    find_extra_file_fields,
    find_extra_rule_fields,
    is_integer,
    is_integer_in_range,
    is_non_empty_string,
    is_string,
    is_valid_date,
    is_valid_version,
)
from tooling._shared.types import RuleCollection, ValidationError, ValidatorResult

VALIDATOR_NAME = "schema"


def validate(rules: RuleCollection) -> ValidatorResult:
    """Run schema validation against the loaded rule collection.

    Args:
        rules: Loaded rule collection with file metadata and indices.

    Returns:
        ValidatorResult with status and error list.
    """
    errors: list[ValidationError] = []

    for file_info in rules.files:
        filepath = Path(file_info.path)
        filename = filepath.name

        raw_data = read_raw_json(file_info.path)
        if raw_data is None:
            continue

        _check_file_meta(raw_data, filename, errors)
        _check_rules_present(raw_data, filename, errors)

        file_domain = raw_data.get("domain", "")
        raw_rules = raw_data.get("rules", [])
        if not isinstance(raw_rules, list):
            raw_rules = []

        for rule_dict in raw_rules:
            if not isinstance(rule_dict, dict):
                continue

            rule_id = str(rule_dict.get("id", "<unknown>"))
            _check_rule_required_fields(rule_dict, filename, errors)
            _check_rule_types(rule_dict, filename, errors)

            if file_domain == "constitution":
                _check_constitution_rules(rule_dict, filename, errors)

            extra_fields = find_extra_rule_fields(rule_dict)
            for field in extra_fields:
                _report_extra_field(field, rule_id, filename, errors)

        file_extra_fields = find_extra_file_fields(raw_data)
        for field in file_extra_fields:
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    file=filename,
                    reason=f"Unknown file-level field '{field}'",
                )
            )

    status = "fail" if errors else "pass"
    return ValidatorResult(name=VALIDATOR_NAME, status=status, errors=errors)


# ------------------------------------------------------------------
# File-level checks
# ------------------------------------------------------------------


def _check_file_meta(
    data: dict[str, Any], filename: str, errors: list[ValidationError]
) -> None:
    for field in FILE_META_REQUIRED_FIELDS:
        value = data.get(field)
        if value is None or value == "":
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    file=filename,
                    reason=f"Missing or empty required file-level field '{field}'",
                )
            )
            continue

        if field == "version":
            if not is_valid_version(value):
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        file=filename,
                        reason=f"Invalid version format '{value}' (expected MAJOR.MINOR.PATCH)",
                    )
                )
        elif field == "last_updated":
            if not is_valid_date(value):
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        file=filename,
                        reason=f"Invalid date format '{value}' (expected YYYY-MM-DD)",
                    )
                )
        elif field == "domain":
            if not is_string(value):
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        file=filename,
                        reason="File-level 'domain' must be a string",
                    )
                )


def _check_rules_present(
    data: dict[str, Any], filename: str, errors: list[ValidationError]
) -> None:
    if "rules" not in data:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                file=filename,
                reason="Missing required file-level field 'rules'",
            )
        )
        return

    rules_val = data["rules"]
    if not isinstance(rules_val, list):
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                file=filename,
                reason="File-level 'rules' must be an array",
            )
        )
    elif len(rules_val) == 0:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                file=filename,
                reason="File-level 'rules' array is empty",
            )
        )


# ------------------------------------------------------------------
# Rule-level required-field checks
# ------------------------------------------------------------------


def _check_rule_required_fields(
    rule_dict: dict[str, Any], filename: str, errors: list[ValidationError]
) -> None:
    rule_id = str(rule_dict.get("id", "<unknown>"))

    # applies_to is required per validator-spec table
    required_fields = list(RULE_REQUIRED_FIELDS) + ["applies_to"]
    for field in required_fields:
        if field not in rule_dict:
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    file=filename,
                    reason=f"Missing required field '{field}'",
                )
            )
            continue

        value = rule_dict[field]

        if value is None:
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    file=filename,
                    reason=f"Missing required field '{field}'",
                )
            )
            continue

        if field in ("violation", "tags") and isinstance(value, list) and len(value) == 0:
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    file=filename,
                    reason=f"Required field '{field}' is an empty array",
                )
            )
            continue


# ------------------------------------------------------------------
# Rule-level type and value checks
# ------------------------------------------------------------------


def _check_rule_types(
    rule_dict: dict[str, Any], filename: str, errors: list[ValidationError]
) -> None:
    rule_id = str(rule_dict.get("id", "<unknown>"))

    for field, expected_type in _FIELD_TYPES.items():
        if field not in rule_dict:
            continue
        value = rule_dict[field]

        if field == "id":
            _check_id(value, rule_id, filename, errors)

        elif field == "priority":
            _check_priority(value, rule_id, filename, errors)

        elif expected_type == "string":
            _check_required_string(field, value, rule_id, filename, errors)

        elif expected_type == "array_of_strings":
            _check_array_of_strings(field, value, rule_id, filename, errors)

        elif expected_type == "optional_array_of_strings":
            _check_optional_array_of_strings(field, value, rule_id, filename, errors)

        elif expected_type == "optional_string":
            _check_optional_string(field, value, rule_id, filename, errors)


_FIELD_TYPES: dict[str, str] = {
    "id": "id",
    "priority": "priority",
    "rule": "string",
    "violation": "array_of_strings",
    "check": "string",
    "fix": "string",
    "tags": "array_of_strings",
    "applies_to": "array_of_strings",
    "exceptions": "optional_array_of_strings",
    "see_also": "optional_array_of_strings",
    "rationale": "optional_string",
    "source": "optional_string",
}


def _check_id(
    value: Any, rule_id: str, filename: str, errors: list[ValidationError]
) -> None:
    if not is_string(value):
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                rule_id=rule_id,
                file=filename,
                reason="'id' must be a string",
            )
        )
    elif not is_non_empty_string(value):
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                rule_id=rule_id,
                file=filename,
                reason="'id' must not be empty",
            )
        )


def _check_priority(
    value: Any, rule_id: str, filename: str, errors: list[ValidationError]
) -> None:
    if not is_integer(value):
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                rule_id=rule_id,
                file=filename,
                reason=f"'priority' must be an integer (got {type(value).__name__})",
            )
        )
    elif not is_integer_in_range(value):
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                rule_id=rule_id,
                file=filename,
                reason=f"'priority' {value} is out of range (expected 1-5)",
            )
        )


def _check_required_string(
    field: str, value: Any, rule_id: str, filename: str, errors: list[ValidationError]
) -> None:
    if not is_string(value):
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                rule_id=rule_id,
                file=filename,
                reason=f"'{field}' must be a string (got {type(value).__name__})",
            )
        )
    elif not is_non_empty_string(value):
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                rule_id=rule_id,
                file=filename,
                reason=f"Required field '{field}' is empty",
            )
        )


def _check_array_of_strings(
    field: str, value: Any, rule_id: str, filename: str, errors: list[ValidationError]
) -> None:
    if not isinstance(value, list):
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                rule_id=rule_id,
                file=filename,
                reason=f"'{field}' must be an array (got {type(value).__name__})",
            )
        )
        return

    for idx, item in enumerate(value):
        if not isinstance(item, str):
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    file=filename,
                    reason=f"'{field}' contains non-string element at index {idx} (got {type(item).__name__})",
                )
            )


def _check_optional_array_of_strings(
    field: str, value: Any, rule_id: str, filename: str, errors: list[ValidationError]
) -> None:
    if not isinstance(value, list):
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                rule_id=rule_id,
                file=filename,
                reason=f"'{field}' must be an array when present (got {type(value).__name__})",
            )
        )
        return

    for idx, item in enumerate(value):
        if not isinstance(item, str):
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    file=filename,
                    reason=f"'{field}' contains non-string element at index {idx}",
                )
            )


def _check_optional_string(
    field: str, value: Any, rule_id: str, filename: str, errors: list[ValidationError]
) -> None:
    if not is_string(value):
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                rule_id=rule_id,
                file=filename,
                reason=f"'{field}' must be a string when present (got {type(value).__name__})",
            )
        )


# ------------------------------------------------------------------
# Constitution-specific checks
# ------------------------------------------------------------------


def _check_constitution_rules(
    rule_dict: dict[str, Any], filename: str, errors: list[ValidationError]
) -> None:
    rule_id = str(rule_dict.get("id", "<unknown>"))

    priority = rule_dict.get("priority")
    if priority is not None and is_integer(priority) and priority != 1:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                rule_id=rule_id,
                file=filename,
                reason=f"Constitutional rule must have priority 1 (got {priority})",
            )
        )

    rationale = rule_dict.get("rationale")
    if not rationale or (is_string(rationale) and not is_non_empty_string(rationale)):
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                rule_id=rule_id,
                file=filename,
                reason="Constitutional rule missing 'rationale'",
            )
        )


# ------------------------------------------------------------------
# Extra-field detection helper
# ------------------------------------------------------------------


def _report_extra_field(
    field: str, rule_id: str, filename: str, errors: list[ValidationError]
) -> None:
    suggestion = _did_you_mean(field, ALL_RULE_FIELDS)
    msg = f"Unknown field '{field}'"
    if suggestion:
        msg += f" (did you mean '{suggestion}'?)"
    errors.append(
        ValidationError(
            validator=VALIDATOR_NAME,
            rule_id=rule_id,
            file=filename,
            reason=msg,
        )
    )


def _did_you_mean(field: str, candidates: list[str]) -> str | None:
    best: str | None = None
    best_score = 0
    for candidate in candidates:
        score = _levenshtein_similarity(field, candidate)
        if score > best_score and score >= 50:
            best = candidate
            best_score = score
    return best


def _levenshtein_similarity(a: str, b: str) -> float:
    """Return a similarity percentage (0-100) between two strings."""
    if not a and not b:
        return 100.0
    if not a or not b:
        return 0.0

    n, m = len(a), len(b)
    dp = [[0] * (m + 1) for _ in range(n + 1)]
    for i in range(n + 1):
        dp[i][0] = i
    for j in range(m + 1):
        dp[0][j] = j

    for i in range(1, n + 1):
        for j in range(1, m + 1):
            cost = 0 if a[i - 1] == b[j - 1] else 1
            dp[i][j] = min(
                dp[i - 1][j] + 1,
                dp[i][j - 1] + 1,
                dp[i - 1][j - 1] + cost,
            )

    dist = dp[n][m]
    max_len = max(n, m)
    if max_len == 0:
        return 100.0
    return (1 - dist / max_len) * 100
