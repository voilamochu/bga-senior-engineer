"""
Cross-Reference Validator for Runtime Specification v1.1.

Verifies every ``see_also`` reference resolves to an existing rule ID,
detects self-references, and identifies circular reference chains.

Contract::

    def validate(rules: RuleCollection) -> ValidatorResult:
        ...
"""

from __future__ import annotations

from collections import deque
from typing import Any

from tooling._shared.registry import RuleRegistry
from tooling._shared.types import RuleCollection, ValidationError, ValidatorResult

VALIDATOR_NAME = "crossref"
MAX_CYCLE_DEPTH = 10


def validate(rules: RuleCollection) -> ValidatorResult:
    """Run cross-reference validation against the loaded rule collection.

    Args:
        rules: Loaded rule collection with full cross-reference index.

    Returns:
        ValidatorResult with status and error list.
    """
    registry = RuleRegistry(rules)
    errors: list[ValidationError] = []

    # ------------------------------------------------------------------
    # Phase 1: Per-reference checks (unresolved, self, same-file)
    # ------------------------------------------------------------------
    for rule_id in registry.all_rule_ids():
        rule = registry.get_rule(rule_id)
        if rule is None:
            continue

        for target in registry.outgoing_refs(rule_id):
            if not registry.has_rule(target):
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        rule_id=rule_id,
                        reason=f"{target} referenced by {rule_id} does not exist",
                    )
                )
                continue

            if target == rule_id:
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        rule_id=rule_id,
                        reason=f"{rule_id} references itself in see_also",
                    )
                )
                continue

            target_rule = registry.get_rule(target)
            if target_rule and target_rule.file_path == rule.file_path:
                errors.append(
                    ValidationError(
                        validator=VALIDATOR_NAME,
                        rule_id=rule_id,
                        severity="warning",
                        reason=f"{rule_id} -> {target} is a same-file reference",
                    )
                )

    # ------------------------------------------------------------------
    # Phase 2: Circular reference detection (BFS, depth-limited)
    # ------------------------------------------------------------------
    outgoing_index = _build_outgoing_index(registry)
    cycle_errors = _detect_cycles(outgoing_index)
    errors.extend(cycle_errors)

    # ------------------------------------------------------------------
    # Phase 3: Orphan rule detection
    # ------------------------------------------------------------------
    for rule_id in registry.all_rule_ids():
        incoming = registry.incoming_refs(rule_id)
        if len(incoming) == 0:
            errors.append(
                ValidationError(
                    validator=VALIDATOR_NAME,
                    rule_id=rule_id,
                    severity="warning",
                    reason="no incoming references (orphan rule)",
                )
            )

    status = "fail" if any(e.severity == "error" for e in errors) else "pass"
    return ValidatorResult(name=VALIDATOR_NAME, status=status, errors=errors)


# ------------------------------------------------------------------
# Cycle detection
# ------------------------------------------------------------------


def _build_outgoing_index(
    registry: RuleRegistry,
) -> dict[str, list[str]]:
    """Build the full outgoing adjacency map from the registry."""
    return {
        rid: list(registry.outgoing_refs(rid))
        for rid in registry.all_rule_ids()
    }


def _detect_cycles(
    outgoing_index: dict[str, list[str]],
) -> list[ValidationError]:
    """BFS-based cycle detection with depth limit.

    Implements the algorithm from the published specification.
    """
    errors: list[ValidationError] = []
    reported: set[str] = set()

    for start_id in sorted(outgoing_index.keys()):
        outgoing = outgoing_index.get(start_id, [])
        if not outgoing:
            continue

        visited: set[str] = set()
        # queue entries: (current_node, path_from_start, depth)
        queue: deque[tuple[str, list[str], int]] = deque()
        queue.append((start_id, [start_id], 0))

        while queue:
            current, path, depth = queue.popleft()

            if current in visited:
                continue
            visited.add(current)

            if depth >= MAX_CYCLE_DEPTH:
                for target in outgoing_index.get(current, []):
                    if target == start_id and len(path) > 1:
                        full = path + [target]
                        norm = _normalize_cycle(full)
                        if norm not in reported:
                            reported.add(norm)
                            errors.append(
                                ValidationError(
                                    validator=VALIDATOR_NAME,
                                    reason=f"possible circular reference (depth limit exceeded): {' -> '.join(full)}",
                                )
                            )
                continue

            for target in outgoing_index.get(current, []):
                if target == start_id and len(path) > 1:
                    full = path + [target]
                    norm = _normalize_cycle(full)
                    if norm not in reported:
                        reported.add(norm)
                        errors.append(
                            ValidationError(
                                validator=VALIDATOR_NAME,
                                reason=f"circular reference: {' -> '.join(full)}",
                            )
                        )
                elif target not in visited:
                    queue.append((target, path + [target], depth + 1))

    return errors


def _normalize_cycle(path: list[str]) -> str:
    """Normalize a cycle path to a canonical string for deduplication.

    Rotates the cycle so it starts at the minimum element (lexicographic),
    then joins with `` -> ``.  The repeated start node is included at the
    end to make the cycle visually obvious.
    """
    cycle = path[:-1]
    if not cycle:
        return ""
    min_idx = 0
    for i in range(1, len(cycle)):
        if cycle[i] < cycle[min_idx]:
            min_idx = i
    rotated = cycle[min_idx:] + cycle[:min_idx] + [cycle[min_idx]]
    return " -> ".join(rotated)
