"""
Priority Validator for Runtime Specification v1.1.

Verifies every rule's ``priority`` field is a valid integer in the range
1–5, that constitutional rules have priority 1, and that no non-constitutional
rule has priority 1.

Contract::

    def validate(rules: RuleCollection) -> ValidatorResult:
        ...
"""

from __future__ import annotations

from tooling._shared.schema import is_integer, is_integer_in_range
from tooling._shared.types import RuleCollection, ValidationError, ValidatorResult

VALIDATOR_NAME = "priority"


def validate(rules: RuleCollection) -> ValidatorResult:
    """Run priority validation against the loaded rule collection.

    Args:
        rules: Loaded rule collection with file metadata and indices.

    Returns:
        ValidatorResult with status and error list.
    """
    errors: list[ValidationError] = []

    for rule_id, rule in rules.rules.items():
        priority = rule.priority
        is_constitutional = rule.file_domain == "constitution"

        # ---- Type check -------------------------------------------------
        if not is_integer(priority):
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    reason=f"priority {repr(priority)} is a {type(priority).__name__}, not integer",
                )
            )
            continue

        # ---- Range check ------------------------------------------------
        if not is_integer_in_range(priority):
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    reason=f"priority {priority} (out of range 1-5)",
                )
            )
            continue

        # ---- Constitutional constraints ---------------------------------
        if is_constitutional and priority != 1:
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    reason=f"constitutional rule must have priority 1 (got {priority})",
                )
            )
            continue

        if not is_constitutional and priority == 1:
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    reason="priority 1 is reserved for constitutional rules",
                )
            )

    status = "fail" if errors else "pass"
    return ValidatorResult(name=VALIDATOR_NAME, status=status, errors=errors)
