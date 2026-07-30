"""Comprehensive unit tests for the Ownership Validator."""

from pathlib import Path

from tooling._shared.loader import load_rules
from tooling._shared.types import Rule, RuleCollection, ValidationError, ValidatorResult
from tooling.validator.src.validators.ownership_validator import validate

FIXTURES = Path(__file__).parent / "fixtures" / "ownership"


def _run(filename: str) -> ValidatorResult:
    path = FIXTURES / filename
    collection = load_rules(path)
    return validate(collection)


def _run_multi(filenames: list[str]) -> ValidatorResult:
    collection = RuleCollection()
    for fn in filenames:
        path = FIXTURES / fn
        sub = load_rules(path)
        for rid, rule in sub.rules.items():
            collection.rules[rid] = rule
        collection.files.extend(sub.files)
        collection.file_index.update(sub.file_index)
    from tooling._shared.loader import _build_indices

    _build_indices(collection)
    return validate(collection)


def _error_reasons(result: ValidatorResult) -> list[str]:
    return [e.reason for e in result.errors]


def _error_ids(result: ValidatorResult) -> list[str | None]:
    return [e.rule_id for e in result.errors]


def _error_files(result: ValidatorResult) -> list[str | None]:
    return [e.file for e in result.errors]


# ======================================================================
# Positive cases
# ======================================================================


class TestValidStandard:
    def test_valid_file_passes(self):
        result = _run("valid_standard.json")
        assert result.status == "pass", _error_reasons(result)

    def test_validator_name(self):
        result = _run("valid_standard.json")
        assert result.name == "ownership"


class TestValidCrossDomain:
    def test_constitution_multiple_components(self):
        result = _run("valid_cross_domain.json")
        assert result.status == "pass"


class TestValidAllComponents:
    def test_all_components_is_valid(self):
        result = _run("valid_all_components.json")
        assert result.status == "pass"


# ======================================================================
# File-level domain checks
# ======================================================================


class TestInvalidDomain:
    def test_detects_invalid_domain(self):
        result = _run("invalid_domain.json")
        reasons = _error_reasons(result)
        assert any("invalid domain value" in r for r in reasons)
        assert any("UnknownDomain" in r for r in reasons)


# ======================================================================
# Duplicate prefix ownership
# ======================================================================


class TestDuplicatePrefixOwnership:
    def test_detects_duplicate_prefix(self):
        result = _run_multi(["dup_prefix_a.json", "dup_prefix_b.json"])
        reasons = _error_reasons(result)
        assert any("duplicate prefix ownership" in r for r in reasons)
        assert any("ARCH" in r for r in reasons)

    def test_errors_on_both_files(self):
        result = _run_multi(["dup_prefix_a.json", "dup_prefix_b.json"])
        files = _error_files(result)
        dup_files = [f for i, f in enumerate(files) if "duplicate prefix" in _error_reasons(result)[i]]
        assert "dup_prefix_a.json" in dup_files
        assert "dup_prefix_b.json" in dup_files


# ======================================================================
# Domain-prefix consistency
# ======================================================================


class TestDomainMismatch:
    def test_detects_domain_mismatch(self):
        result = _run("domain_mismatch.json")
        reasons = _error_reasons(result)
        assert any("does not match expected for prefix" in r for r in reasons)
        assert any("ACTN" in r for r in reasons)

    def test_reports_file_domain(self):
        result = _run("domain_mismatch.json")
        reasons = _error_reasons(result)
        assert any("architecture" in r for r in reasons)


# ======================================================================
# Rule-level applies_to checks
# ======================================================================


class TestInvalidAppliesTo:
    def test_detects_invalid_applies_to_value(self):
        result = _run("invalid_applies_to.json")
        reasons = _error_reasons(result)
        assert any("invalid applies_to value" in r for r in reasons)
        assert any("Persistence" in r for r in reasons)

    def test_invalid_value_is_error(self):
        result = _run("invalid_applies_to.json")
        assert result.status == "fail"


class TestDuplicateAppliesTo:
    def test_detects_duplicate_applies_to(self):
        result = _run("duplicate_applies_to.json")
        reasons = _error_reasons(result)
        assert any("duplicate applies_to entry" in r for r in reasons)
        assert any("Game.php" in r for r in reasons)


class TestEmptyAppliesTo:
    def test_detects_empty_array(self):
        result = _run("empty_applies_to.json")
        reasons = _error_reasons(result)
        assert any("applies_to array is empty" in r for r in reasons)

    def test_detects_missing_field(self):
        result = _run("missing_applies_to.json")
        reasons = _error_reasons(result)
        assert any("applies_to array is empty" in r for r in reasons)


class TestMalformedAppliesTo:
    def test_detects_non_array_applies_to(self):
        result = _run("malformed_applies_to.json")
        reasons = _error_reasons(result)
        assert any("applies_to must be an array" in r for r in reasons)

    def test_detects_non_string_elements(self):
        result = _run("non_string_applies_to.json")
        reasons = _error_reasons(result)
        assert any("applies_to entry must be a string" in r for r in reasons)
        assert any("int" in r for r in reasons)
        assert any("bool" in r for r in reasons)


# ======================================================================
# Edge cases
# ======================================================================


class TestEmptyRuntime:
    def test_empty_collection_passes(self):
        result = validate(RuleCollection())
        assert result.status == "pass"
        assert result.errors == []


class TestSingleRule:
    def test_single_rule_passes(self):
        result = _run("single_rule.json")
        assert result.status == "pass"


class TestEmptyFile:
    def test_empty_json_passes(self):
        result = _run("empty.json")
        assert result.status == "pass"


# ======================================================================
# Validator contract
# ======================================================================


class TestValidatorContract:
    def test_function_is_callable(self):
        result = validate(RuleCollection())
        assert isinstance(result, ValidatorResult)

    def test_does_not_mutate_collection(self):
        rc = RuleCollection()
        original = RuleCollection(
            rules=dict(rc.rules),
            files=list(rc.files),
        )
        result = validate(rc)
        assert rc.rules == original.rules
        assert rc.files == original.files
