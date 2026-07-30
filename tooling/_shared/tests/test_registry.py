"""Tests for tooling._shared.registry."""

from pathlib import Path

import pytest

from tooling._shared.loader import load_rules
from tooling._shared.registry import RuleRegistry
from tooling._shared.types import Rule

FIXTURES = Path(__file__).parent / "fixtures"


@pytest.fixture()
def registry() -> RuleRegistry:
    collection = load_rules(FIXTURES)
    return RuleRegistry(collection)



class TestGetRule:
    def test_returns_rule_for_existing_id(self, registry):
        rule = registry.get_rule("ARCH-001")
        assert rule is not None
        assert rule.id == "ARCH-001"
        assert rule.priority == 2

    def test_returns_none_for_missing_id(self, registry):
        assert registry.get_rule("NONEXISTENT") is None

    def test_has_rule(self, registry):
        assert registry.has_rule("ARCH-001")
        assert not registry.has_rule("FAKE-999")


class TestCrossReferences:
    def test_incoming_refs(self, registry):
        """CORE-002 is referenced by ARCH-001 see_also."""
        refs = registry.incoming_refs("CORE-002")
        assert "ARCH-001" in refs

    def test_incoming_refs_empty(self, registry):
        """A rule that no one references should have no incoming refs."""
        refs = registry.incoming_refs("ACTN-001")
        assert refs == []

    def test_outgoing_refs(self, registry):
        """ARCH-001 references CORE-002."""
        refs = registry.outgoing_refs("ARCH-001")
        assert "CORE-002" in refs

    def test_outgoing_refs_empty(self, registry):
        """ACTN-001 has no see_also."""
        refs = registry.outgoing_refs("ACTN-001")
        assert refs == []

    def test_outgoing_refs_nonexistent(self, registry):
        assert registry.outgoing_refs("FAKE") == []

    def test_cross_ref_count(self, registry):
        count = registry.cross_ref_count()
        assert count > 0


class TestPrefixQueries:
    def test_rules_by_prefix(self, registry):
        arch_rules = registry.rules_by_prefix("ARCH")
        assert "ARCH-001" in arch_rules
        assert "ARCH-002" in arch_rules
        assert "ARCH-005" in arch_rules

    def test_rules_by_prefix_nonexistent(self, registry):
        assert registry.rules_by_prefix("ZZZZ") == []

    def test_all_prefixes(self, registry):
        prefixes = registry.all_prefixes()
        assert "ARCH" in prefixes
        assert "ACTN" in prefixes


class TestDomainQueries:
    def test_rules_by_domain(self, registry):
        arch_rules = registry.rules_by_domain("architecture")
        assert "ARCH-001" in arch_rules

    def test_rules_by_domain_nonexistent(self, registry):
        assert registry.rules_by_domain("fake-domain") == []

    def test_all_domains(self, registry):
        domains = registry.all_domains()
        assert "architecture" in domains
        assert "actions" in domains


class TestFileQueries:
    def test_rules_in_file(self, registry):
        arch_path = str(FIXTURES / "architecture.json")
        rules = registry.rules_in_file(arch_path)
        assert "ARCH-001" in rules
        assert "ARCH-002" in rules
        assert "ARCH-005" in rules

    def test_rules_in_nonexistent_file(self, registry):
        assert registry.rules_in_file("/nonexistent.json") == []

    def test_all_files(self, registry):
        files = registry.all_files()
        arch_path = str(FIXTURES / "architecture.json")
        assert arch_path in files


class TestEdgeCases:
    def test_empty_collection(self):
        from tooling._shared.types import RuleCollection

        empty = RuleCollection()
        reg = RuleRegistry(empty)
        assert reg.rule_count() == 0
        assert reg.all_rule_ids() == []
        assert reg.all_prefixes() == []
        assert reg.all_domains() == []
        assert reg.all_files() == []

    def test_rule_without_prefix(self, registry):
        from tooling._shared.schema import RULE_ID_RE
        from tooling._shared.types import Rule, RuleCollection

        rc = RuleCollection()
        rc.rules["BAD-ID"] = Rule(
            id="BAD-ID", priority=1, rule="x", violation=["v"],
            check="c", fix="f", tags=["t"]
        )
        reg = RuleRegistry(rc)
        assert reg.rules_by_prefix("BAD") == []
        assert reg.rule_count() == 1
