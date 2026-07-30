"""Tests for tooling._shared.schema."""

from tooling._shared.schema import (
    ALL_RULE_FIELDS,
    DOMAIN_TO_PREFIX,
    PREFIX_TO_DOMAIN,
    RULE_OPTIONAL_FIELDS,
    RULE_REQUIRED_FIELDS,
    VALID_COMPONENTS,
    VALID_DOMAINS,
    VALID_PREFIXES,
    find_extra_file_fields,
    find_extra_rule_fields,
    get_prefix_from_rule_id,
    is_array_of_strings,
    is_integer,
    is_integer_in_range,
    is_non_empty_array,
    is_non_empty_string,
    is_string,
    is_valid_component,
    is_valid_date,
    is_valid_domain,
    is_valid_rule_id,
    is_valid_version,
)


class TestCanonicalLists:
    def test_valid_domains(self):
        assert "architecture" in VALID_DOMAINS
        assert "constitution" in VALID_DOMAINS
        assert "migration" in VALID_DOMAINS
        assert len(VALID_DOMAINS) == 12

    def test_valid_components(self):
        assert "Game.php" in VALID_COMPONENTS
        assert "All components" in VALID_COMPONENTS
        assert len(VALID_COMPONENTS) == 10

    def test_valid_prefixes(self):
        assert "ARCH" in VALID_PREFIXES
        assert "CORE" in VALID_PREFIXES
        assert len(VALID_PREFIXES) == 12

    def test_prefix_to_domain(self):
        assert PREFIX_TO_DOMAIN["ARCH"] == "architecture"
        assert PREFIX_TO_DOMAIN["CORE"] == "constitution"
        assert PREFIX_TO_DOMAIN["MIGR"] == "migration"

    def test_domain_to_prefix(self):
        assert DOMAIN_TO_PREFIX["architecture"] == "ARCH"
        assert DOMAIN_TO_PREFIX["constitution"] == "CORE"


class TestFieldDefinitions:
    def test_required_fields(self):
        assert "id" in RULE_REQUIRED_FIELDS
        assert "priority" in RULE_REQUIRED_FIELDS
        assert "rule" in RULE_REQUIRED_FIELDS
        assert "violation" in RULE_REQUIRED_FIELDS
        assert "check" in RULE_REQUIRED_FIELDS
        assert "fix" in RULE_REQUIRED_FIELDS
        assert "tags" in RULE_REQUIRED_FIELDS
        assert len(RULE_REQUIRED_FIELDS) == 7

    def test_optional_fields(self):
        assert "exceptions" in RULE_OPTIONAL_FIELDS
        assert "see_also" in RULE_OPTIONAL_FIELDS
        assert "rationale" in RULE_OPTIONAL_FIELDS
        assert "applies_to" in RULE_OPTIONAL_FIELDS
        assert "source" in RULE_OPTIONAL_FIELDS
        assert len(RULE_OPTIONAL_FIELDS) == 5

    def test_all_rule_fields(self):
        assert set(ALL_RULE_FIELDS) == set(RULE_REQUIRED_FIELDS + RULE_OPTIONAL_FIELDS)


class TestTypeChecks:
    def test_is_string(self):
        assert is_string("hello")
        assert is_string("")
        assert not is_string(42)
        assert not is_string([])
        assert not is_string(None)

    def test_is_non_empty_string(self):
        assert is_non_empty_string("hello")
        assert not is_non_empty_string("")
        assert not is_non_empty_string(42)

    def test_is_integer(self):
        assert is_integer(42)
        assert is_integer(0)
        assert is_integer(-1)
        assert not is_integer(True)
        assert not is_integer("42")
        assert not is_integer(4.2)

    def test_is_integer_in_range(self):
        assert is_integer_in_range(1)
        assert is_integer_in_range(3)
        assert is_integer_in_range(5)
        assert not is_integer_in_range(0)
        assert not is_integer_in_range(6)
        assert not is_integer_in_range("3")

    def test_is_array_of_strings(self):
        assert is_array_of_strings(["a", "b"])
        assert is_array_of_strings([])
        assert not is_array_of_strings(["a", 2])
        assert not is_array_of_strings("abc")
        assert not is_array_of_strings(None)

    def test_is_non_empty_array(self):
        assert is_non_empty_array([1, 2])
        assert not is_non_empty_array([])
        assert not is_non_empty_array("abc")

    def test_is_valid_version(self):
        assert is_valid_version("1.0.0")
        assert is_valid_version("2.15.3")
        assert not is_valid_version("1.0")
        assert not is_valid_version("1.0.0-beta")
        assert not is_valid_version("")

    def test_is_valid_date(self):
        assert is_valid_date("2026-07-29")
        assert is_valid_date("2000-01-01")
        assert not is_valid_date("2026-7-29")
        assert not is_valid_date("07-29-2026")
        assert not is_valid_date("")

    def test_is_valid_rule_id(self):
        assert is_valid_rule_id("ARCH-001")
        assert is_valid_rule_id("CORE-016")
        assert is_valid_rule_id("MIGR-014")
        assert not is_valid_rule_id("ARCH-01")
        assert not is_valid_rule_id("ARCH-1")
        assert not is_valid_rule_id("arch-001")
        assert not is_valid_rule_id("CUSTOM-001")
        assert not is_valid_rule_id("")
        assert not is_valid_rule_id("ARCH-0012")

    def test_is_valid_domain(self):
        assert is_valid_domain("architecture")
        assert is_valid_domain("constitution")
        assert not is_valid_domain("Architecture")
        assert not is_valid_domain("unknown")

    def test_is_valid_component(self):
        assert is_valid_component("Game.php")
        assert is_valid_component("All components")
        assert not is_valid_component("Model")
        assert not is_valid_component("")

    def test_get_prefix_from_rule_id(self):
        assert get_prefix_from_rule_id("ARCH-001") == "ARCH"
        assert get_prefix_from_rule_id("CORE-016") == "CORE"
        assert get_prefix_from_rule_id("INVALID") is None


class TestExtraFieldDetection:
    def test_find_extra_file_fields(self):
        data = {"domain": "x", "version": "1", "last_updated": "d",
                "source": "s", "rules": [], "extra_field": True}
        extras = find_extra_file_fields(data)
        assert "extra_field" in extras
        assert "domain" not in extras

    def test_no_extra_file_fields(self):
        data = {"domain": "x", "version": "1", "last_updated": "d",
                "source": "s", "rules": []}
        assert find_extra_file_fields(data) == []

    def test_find_extra_rule_fields(self):
        rule = {"id": "T-1", "priority": 1, "rule": "x", "violation": ["v"],
                "check": "c", "fix": "f", "tags": ["t"], "unknown": "val"}
        extras = find_extra_rule_fields(rule)
        assert "unknown" in extras
        assert "id" not in extras

    def test_no_extra_rule_fields(self):
        rule = {"id": "T-1", "priority": 1, "rule": "x", "violation": ["v"],
                "check": "c", "fix": "f", "tags": ["t"]}
        assert find_extra_rule_fields(rule) == []
