"""
Statistics Generator for Runtime Specification v1.1.

Generates aggregate statistics from the runtime rule set.  This validator
never fails — it always produces data.

Contract::

    def validate(rules: RuleCollection) -> ValidatorResult:
        ...

    def generate_statistics(rules: RuleCollection) -> dict:
        ...
"""

from __future__ import annotations

import re
from collections import Counter
from pathlib import Path
from typing import Any

from tooling._shared.schema import VALID_PREFIXES, get_prefix_from_rule_id
from tooling._shared.types import Rule, RuleCollection, ValidationError, ValidatorResult

VALIDATOR_NAME = "stats"

_NUM_RE = re.compile(r"-(\d+)$")


def validate(rules: RuleCollection) -> ValidatorResult:
    """Run statistics generation.  Always returns status ``"pass"``."""
    return ValidatorResult(name=VALIDATOR_NAME, status="pass")


def generate_statistics(rules: RuleCollection) -> dict[str, Any]:
    """Compute and return full statistics for a loaded rule collection.

    Returns a dict with keys ``files``, ``total_files``, ``total_rules``,
    ``total_lines``, ``priority_distribution``, ``tag_distribution``,
    ``applies_to_distribution``, ``cross_reference_count``,
    ``largest_file``, ``smallest_file``, ``average_rules_per_file``,
    and ``gaps``.
    """
    file_stats = _compute_file_stats(rules)
    aggregate = _compute_aggregate(rules, file_stats)
    gaps = _compute_gaps(rules)

    return {
        "files": file_stats,
        **aggregate,
        "gaps": gaps,
    }


# ------------------------------------------------------------------
# Per-file statistics
# ------------------------------------------------------------------


def _compute_file_stats(rules: RuleCollection) -> list[dict[str, Any]]:
    result: list[dict[str, Any]] = []

    for file_info in rules.files:
        filepath = str(file_info.path)
        filename = Path(filepath).name
        rule_ids = rules.file_index.get(filepath, [])

        priorities: Counter[str] = Counter()
        sorted_ids = _sorted_rule_ids(rule_ids)

        for rid in rule_ids:
            rule = rules.rules.get(rid)
            if rule is not None:
                p = rule.priority
                if isinstance(p, int):
                    priorities[str(p)] += 1

        rules_from = sorted_ids[0] if sorted_ids else None
        rules_to = sorted_ids[-1] if sorted_ids else None

        result.append(
            {
                "file": filename,
                "domain": file_info.domain,
                "rule_count": file_info.rule_count,
                "line_count": file_info.line_count,
                "rules_from": rules_from,
                "rules_to": rules_to,
                "priorities": dict(sorted(priorities.items())),
            }
        )

    return result


# ------------------------------------------------------------------
# Aggregate statistics
# ------------------------------------------------------------------


def _compute_aggregate(
    rules: RuleCollection,
    file_stats: list[dict[str, Any]],
) -> dict[str, Any]:
    total_files = len(rules.files)
    total_rules = len(rules.rules)
    total_lines = sum(fi.line_count for fi in rules.files)

    priority_dist: Counter[str] = Counter()
    tag_dist: Counter[str] = Counter()
    applies_to_dist: Counter[str] = Counter()
    cross_ref_count = 0

    for rule in rules.rules.values():
        p = rule.priority
        if isinstance(p, int):
            priority_dist[str(p)] += 1

        tags = rule.tags if isinstance(rule.tags, list) else []
        for tag in tags:
            if isinstance(tag, str):
                tag_dist[tag] += 1

        applies_to = rule.applies_to if isinstance(rule.applies_to, list) else []
        for component in applies_to:
            if isinstance(component, str):
                applies_to_dist[component] += 1

        see_also = rule.see_also if isinstance(rule.see_also, list) else []
        cross_ref_count += sum(1 for t in see_also if isinstance(t, str))

    largest = max(file_stats, key=lambda f: f["line_count"]) if file_stats else {}
    smallest = min(file_stats, key=lambda f: f["line_count"]) if file_stats else {}

    avg_rules = round(total_rules / total_files, 1) if total_files > 0 else 0.0

    return {
        "total_files": total_files,
        "total_rules": total_rules,
        "total_lines": total_lines,
        "priority_distribution": dict(sorted(priority_dist.items())),
        "tag_distribution": dict(sorted(tag_dist.items())),
        "applies_to_distribution": dict(sorted(applies_to_dist.items())),
        "cross_reference_count": cross_ref_count,
        "largest_file": f"{largest.get('file', '')} ({largest.get('line_count', 0)} lines)",
        "smallest_file": f"{smallest.get('file', '')} ({smallest.get('line_count', 0)} lines)",
        "average_rules_per_file": avg_rules,
    }


# ------------------------------------------------------------------
# Gap analysis
# ------------------------------------------------------------------


def _compute_gaps(rules: RuleCollection) -> dict[str, list[str]]:
    prefix_numbers: dict[str, list[int]] = {}

    for rule_id in rules.rules:
        prefix = get_prefix_from_rule_id(rule_id)
        match = _NUM_RE.search(str(rule_id))
        if prefix and match:
            prefix_numbers.setdefault(prefix, []).append(int(match.group(1)))

    gaps: dict[str, list[str]] = {}
    for prefix in sorted(prefix_numbers.keys()):
        nums = sorted(prefix_numbers[prefix])
        if not nums:
            gaps[prefix] = []
            continue
        lo, hi = nums[0], nums[-1]
        present = set(nums)
        missing: list[str] = []
        for n in range(lo, hi + 1):
            if n not in present:
                missing.append(f"{prefix}-{n:03d}")
        gaps[prefix] = missing

    return gaps


# ------------------------------------------------------------------
# Sorting helpers
# ------------------------------------------------------------------


def _sorted_rule_ids(rule_ids: list[str]) -> list[str]:
    """Sort rule IDs by their numeric suffix, ascending."""
    return sorted(rule_ids, key=_numeric_key)


def _numeric_key(rule_id: str) -> tuple[int, int]:
    """Return ``(sort_group, numeric_value)`` for sorting rule IDs.

    Rules without a recognizable numeric suffix sort at the end.
    """
    match = _NUM_RE.search(rule_id)
    if match:
        return (0, int(match.group(1)))
    return (1, 0)
