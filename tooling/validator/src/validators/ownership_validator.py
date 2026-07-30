"""
Ownership Validator for Runtime Specification v1.1.

Verifies that ``applies_to`` values reference valid architectural components,
that no two files claim ownership of the same rule prefix, and that domain
values are consistent.

Contract::

    def validate(rules: RuleCollection) -> ValidatorResult:
        ...
"""

from __future__ import annotations

from pathlib import Path
from typing import Any

from tooling._shared.schema import (
    PREFIX_TO_DOMAIN,
    get_prefix_from_rule_id,
    is_valid_component,
    is_valid_domain,
)
from tooling._shared.types import Rule, RuleCollection, ValidationError, ValidatorResult

VALIDATOR_NAME = "ownership"


def validate(rules: RuleCollection) -> ValidatorResult:
    """Run ownership validation against the loaded rule collection.

    Args:
        rules: Loaded rule collection with file metadata and indices.

    Returns:
        ValidatorResult with status and error list.
    """
    errors: list[ValidationError] = []

    # ------------------------------------------------------------------
    # Phase 1: File-level domain validation
    # ------------------------------------------------------------------
    for file_info in rules.files:
        domain = file_info.domain
        filename = Path(file_info.path).name
        if domain and not is_valid_domain(domain):
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    file=filename,
                    reason=f"invalid domain value '{domain}' (expected canonical value)",
                )
            )

    # ------------------------------------------------------------------
    # Phase 2: Duplicate prefix ownership
    # ------------------------------------------------------------------
    prefix_files: dict[str, list[str]] = {}
    for file_info in rules.files:
        filepath = str(file_info.path)
        filename = Path(filepath).name
        for rule_id in rules.file_index.get(filepath, []):
            prefix = get_prefix_from_rule_id(rule_id)
            if prefix:
                if prefix not in prefix_files:
                    prefix_files[prefix] = []
                if filename not in prefix_files[prefix]:
                    prefix_files[prefix].append(filename)

    for prefix, filenames in prefix_files.items():
        if len(filenames) > 1:
            dup_msg = " and ".join(sorted(f for f in filenames))
            for filename in filenames:
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        file=filename,
                        reason=f"duplicate prefix ownership: prefix {prefix} appears in {dup_msg}",
                    )
                )

    # ------------------------------------------------------------------
    # Phase 3: Domain-prefix consistency
    # ------------------------------------------------------------------
    _check_domain_prefix_consistency(rules, errors)

    # ------------------------------------------------------------------
    # Phase 4: Rule-level applies_to validation
    # ------------------------------------------------------------------
    for rule_id, rule in rules.rules.items():
        _check_applies_to(rule, rule_id, errors)

    status = "fail" if errors else "pass"
    return ValidatorResult(name=VALIDATOR_NAME, status=status, errors=errors)


# ------------------------------------------------------------------
# Domain-prefix consistency
# ------------------------------------------------------------------


def _check_domain_prefix_consistency(
    rules: RuleCollection, errors: list[ValidationError]
) -> None:
    file_domains: dict[str, str] = {
        str(fi.path): fi.domain for fi in rules.files
    }

    file_prefixes: dict[str, set[str]] = {}
    for rule_id, rule in rules.rules.items():
        filepath = rule.file_path
        prefix = get_prefix_from_rule_id(rule_id)
        if prefix:
            file_prefixes.setdefault(filepath, set()).add(prefix)

    for filepath, prefixes in file_prefixes.items():
        filename = Path(filepath).name
        file_domain = file_domains.get(filepath, "")
        for prefix in sorted(prefixes):
            expected_domain = PREFIX_TO_DOMAIN.get(prefix)
            if expected_domain and file_domain != expected_domain:
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        file=filename,
                        reason=f"domain '{file_domain}' does not match expected for prefix {prefix}",
                    )
                )


# ------------------------------------------------------------------
# Rule-level applies_to checks
# ------------------------------------------------------------------


def _check_applies_to(
    rule: Rule, rule_id: str, errors: list[ValidationError]
) -> None:
    filename = Path(rule.file_path).name
    applies_to = rule.applies_to

    # Missing or empty
    if applies_to is None:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                file=filename,
                rule_id=rule_id,
                reason="applies_to array is empty",
            )
        )
        return

    if isinstance(applies_to, list) and len(applies_to) == 0:
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                file=filename,
                rule_id=rule_id,
                reason="applies_to array is empty",
            )
        )
        return

    # Wrong type
    if not isinstance(applies_to, list):
        errors.append(
            ValidationError(
                validator=VALIDATOR_NAME,
                file=filename,
                rule_id=rule_id,
                reason=f"applies_to must be an array when present (got {type(applies_to).__name__})",
            )
        )
        return

    # Validate each entry
    seen: set[str] = set()
    for value in applies_to:
        if isinstance(value, str):
            if value in seen:
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        file=filename,
                        rule_id=rule_id,
                        reason=f"duplicate applies_to entry '{value}'",
                    )
                )
            seen.add(value)

            if not is_valid_component(value):
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        file=filename,
                        rule_id=rule_id,
                        reason=f"invalid applies_to value '{value}' (expected canonical value)",
                    )
                )
        else:
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    file=filename,
                    rule_id=rule_id,
                    reason=f"applies_to entry must be a string (got {type(value).__name__})",
                )
            )
