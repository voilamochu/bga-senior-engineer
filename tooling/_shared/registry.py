"""
Rule ID registry and cross-reference index builder.

Wraps a :class:`RuleCollection` and provides query methods for
navigating the rule set by ID, prefix, domain, or file.
"""

from __future__ import annotations

from collections import defaultdict

from tooling._shared.schema import get_prefix_from_rule_id
from tooling._shared.types import Rule, RuleCollection


class RuleRegistry:
    """Indexed view over a :class:`RuleCollection`.

    The registry is built once from a loaded collection and provides
    fast lookup methods used by validators and other tools.
    """

    def __init__(self, collection: RuleCollection) -> None:
        self._rules: dict[str, Rule] = collection.rules
        self._crossref_index: dict[str, list[str]] = collection.crossref_index
        self._domain_index: dict[str, list[str]] = collection.domain_index
        self._file_index: dict[str, list[str]] = collection.file_index

        self._prefix_index: dict[str, list[str]] = defaultdict(list)
        self._incoming_index: dict[str, list[str]] = defaultdict(list)
        self._outgoing_index: dict[str, list[str]] = defaultdict(list)

        self._build()

    def _build(self) -> None:
        for rule_id, rule in self._rules.items():
            prefix = get_prefix_from_rule_id(rule_id)
            if prefix:
                self._prefix_index[prefix].append(rule_id)

            outgoing = rule.see_also if isinstance(rule.see_also, list) else []
            self._outgoing_index[rule_id] = outgoing
            for target in outgoing:
                self._incoming_index[target].append(rule_id)

    # ------------------------------------------------------------------
    # Rule ID lookup
    # ------------------------------------------------------------------

    def get_rule(self, rule_id: str) -> Rule | None:
        """Return the :class:`Rule` for *rule_id*, or ``None``."""
        return self._rules.get(rule_id)

    def has_rule(self, rule_id: str) -> bool:
        """Return ``True`` if *rule_id* exists in the collection."""
        return rule_id in self._rules

    def all_rule_ids(self) -> list[str]:
        """Return every rule ID in the collection."""
        return list(self._rules.keys())

    def rule_count(self) -> int:
        """Return the total number of rules."""
        return len(self._rules)

    # ------------------------------------------------------------------
    # Cross-reference queries
    # ------------------------------------------------------------------

    def incoming_refs(self, rule_id: str) -> list[str]:
        """Return IDs of rules whose ``see_also`` includes *rule_id*."""
        return self._incoming_index.get(rule_id, [])

    def outgoing_refs(self, rule_id: str) -> list[str]:
        """Return IDs listed in *rule_id* ``see_also`` field.

        Returns an empty list if the rule has no ``see_also`` or does
        not exist.
        """
        return self._outgoing_index.get(rule_id, [])

    def cross_ref_count(self) -> int:
        """Return total number of ``see_also`` entries across all rules."""
        return sum(len(v) for v in self._outgoing_index.values())

    # ------------------------------------------------------------------
    # Prefix queries
    # ------------------------------------------------------------------

    def rules_by_prefix(self, prefix: str) -> list[str]:
        """Return rule IDs whose prefix matches *prefix* (e.g. ``"ARCH"``)."""
        return self._prefix_index.get(prefix, [])

    def all_prefixes(self) -> list[str]:
        """Return every prefix present in the collection."""
        return list(self._prefix_index.keys())

    # ------------------------------------------------------------------
    # Domain queries
    # ------------------------------------------------------------------

    def rules_by_domain(self, domain: str) -> list[str]:
        """Return rule IDs belonging to *domain*."""
        return self._domain_index.get(domain, [])

    def all_domains(self) -> list[str]:
        """Return every domain present in the collection."""
        return list(self._domain_index.keys())

    # ------------------------------------------------------------------
    # File queries
    # ------------------------------------------------------------------

    def rules_in_file(self, file_path: str) -> list[str]:
        """Return rule IDs contained in *file_path*."""
        return self._file_index.get(file_path, [])

    def all_files(self) -> list[str]:
        """Return every file path present in the collection."""
        return list(self._file_index.keys())
