"""Tests for tooling._shared.loader."""

import json
from pathlib import Path

import pytest

from tooling._shared.loader import get_load_warnings, load_rules
from tooling._shared.types import RuleCollection, ValidationError

FIXTURES = Path(__file__).parent / "fixtures"
MALFORMED = FIXTURES / "malformed"


class TestLoadFromDirectory:
    def test_loads_all_json_files(self):
        collection = load_rules(FIXTURES)
        assert isinstance(collection, RuleCollection)

    def test_includes_architecture_rules(self):
        collection = load_rules(FIXTURES)
        assert "ARCH-001" in collection.rules
        assert "ARCH-002" in collection.rules

    def test_includes_actions_rules(self):
        collection = load_rules(FIXTURES)
        assert "ACTN-001" in collection.rules

    def test_populates_file_info(self):
        collection = load_rules(FIXTURES)
        assert len(collection.files) >= 2


class TestLoadSingleFile:
    def test_load_single_json_file(self):
        path = FIXTURES / "architecture.json"
        collection = load_rules(path)
        assert "ARCH-001" in collection.rules
        assert len(collection.rules) == 3

    def test_single_file_file_info(self):
        path = FIXTURES / "actions.json"
        collection = load_rules(path)
        assert len(collection.files) == 1
        assert collection.files[0].domain == "actions"
        assert collection.files[0].rule_count == 1


class TestFileNotFound:
    def test_raises_on_missing_directory(self):
        with pytest.raises(FileNotFoundError):
            load_rules("/nonexistent/path")

    def test_raises_on_missing_file(self):
        with pytest.raises(FileNotFoundError):
            load_rules("/nonexistent.json")


class TestMalformedJSON:
    def test_raises_on_invalid_json(self):
        path = MALFORMED / "bad.json"
        with pytest.raises(ValueError, match="bad.json"):
            load_rules(path)


class TestNonJSONExtension:
    def test_skips_non_json_files(self):
        """The loader only picks up *.json files from a directory glob."""
        non_json_dir = FIXTURES / "non_json"
        collection = load_rules(non_json_dir)
        not_json = non_json_dir / "not_json.txt"
        assert str(not_json) not in collection.file_index


class TestMissingMetadata:
    def test_warns_on_missing_file_meta(self):
        path = MALFORMED / "invalid_meta.json"
        _ = load_rules(path)
        warnings = get_load_warnings()
        meta_warnings = [w for w in warnings if w.file and "Missing" in w.reason]
        assert len(meta_warnings) >= 4


class TestEmptyFile:
    def test_valid_json_no_rules(self):
        path = FIXTURES / "empty.json"
        collection = load_rules(path)
        assert len(collection.rules) == 0
        assert len(collection.files) == 1
        assert collection.files[0].domain == ""
        assert collection.files[0].rule_count == 0


class TestNoRulesArray:
    def test_file_with_empty_rules_array(self):
        path = FIXTURES / "no_rules.json"
        collection = load_rules(path)
        assert len(collection.rules) == 0
        assert collection.files[0].rule_count == 0
        assert collection.files[0].domain == "testing"


class TestDuplicateIDs:
    def test_warns_on_duplicate_rule_ids(self):
        path = MALFORMED / "duplicate_ids.json"
        _ = load_rules(path)
        warnings = get_load_warnings()
        dup_warnings = [w for w in warnings if "Duplicate" in w.reason]
        assert len(dup_warnings) >= 1


class TestMissingRuleFields:
    def test_warns_on_missing_required_rule_fields(self):
        path = MALFORMED / "missing_fields.json"
        _ = load_rules(path)
        warnings = get_load_warnings()
        missing_field_warnings = [
            w for w in warnings
            if "missing required fields" in w.reason
        ]
        assert len(missing_field_warnings) >= 1


class TestIndices:
    def test_domain_index(self):
        collection = load_rules(FIXTURES)
        assert "architecture" in collection.domain_index
        assert "actions" in collection.domain_index
        assert "ARCH-001" in collection.domain_index["architecture"]
        assert "ACTN-001" in collection.domain_index["actions"]

    def test_crossref_index(self):
        collection = load_rules(FIXTURES)
        assert "CORE-002" in collection.crossref_index
        assert "ARCH-001" in collection.crossref_index
        assert "ARCH-005" in collection.crossref_index

    def test_file_index(self):
        collection = load_rules(FIXTURES)
        arch_path = str(FIXTURES / "architecture.json")
        assert arch_path in collection.file_index
        assert "ARCH-001" in collection.file_index[arch_path]


class TestRuleDataPopulation:
    def test_rule_has_file_path_and_domain(self):
        collection = load_rules(FIXTURES)
        rule = collection.rules["ARCH-001"]
        assert rule.file_path.endswith("architecture.json")
        assert rule.file_domain == "architecture"

    def test_optional_fields_preserved(self):
        collection = load_rules(FIXTURES)
        rule = collection.rules["ARCH-001"]
        assert rule.see_also == ["CORE-002"]
        assert rule.applies_to is None


class TestGetLoadWarnings:
    def test_returns_list_of_validation_errors(self):
        _ = load_rules(FIXTURES)
        warnings = get_load_warnings()
        assert isinstance(warnings, list)
        if warnings:
            assert isinstance(warnings[0], ValidationError)

    def test_warnings_reset_each_load(self):
        _ = load_rules(FIXTURES)
        first_batch = get_load_warnings()
        _ = load_rules(FIXTURES / "architecture.json")
        second_batch = get_load_warnings()
        assert len(second_batch) < len(first_batch)
