"""Comprehensive unit tests for the Priority Validator."""

from pathlib import Path

from tooling._shared.loader import load_rules
from tooling._shared.types import Rule, RuleCollection, ValidationError, ValidatorResult
from tooling.validator.src.validators.priority_validator import validate

FIXTURES = Path(__file__).parent / "fixtures" / "priority"


def _run(filename: str) -> ValidatorResult:
    path = FIXTURES / filename
    collection = load_rules(path)
    return validate(collection)


def _make_collection(
    rules: list[tuple[str, object, str]],
) -> RuleCollection:
    """Build a RuleCollection from (rule_id, priority, domain) tuples."""
    rc = RuleCollection()
    for rid, priority, domain in rules:
        rc.rules[rid] = Rule(
            id=rid,
            priority=priority,
            rule="R.",
            violation=["V"],
            check="C.",
            fix="F.",
            tags=["t"],
            file_domain=domain,
        )
    return rc


def _error_reasons(result: ValidatorResult) -> list[str]:
    return [e.reason for e in result.errors]


def _error_ids(result: ValidatorResult) -> list[str | None]:
    return [e.rule_id for e in result.errors]


# ======================================================================
# Positive cases
# ======================================================================


class TestAllLegalPriorities:
    def test_priorities_2_to_5_pass(self):
        result = _run("all_legal.json")
        assert result.status == "pass", _error_reasons(result)

    def test_validator_name(self):
        result = _run("all_legal.json")
        assert result.name == "priority"


class TestConstitutionalValid:
    def test_constitutional_priority_1_passes(self):
        result = _run("constitutional_valid.json")
        assert result.status == "pass"


class TestMixedValid:
    def test_mixed_valid_passes(self):
        result = _run("mixed_valid.json")
        assert result.status == "pass"


class TestEveryLegalPriority:
    def test_priority_1_constitutional(self):
        result = _make_collection([("CORE-001", 1, "constitution")])
        assert validate(result).status == "pass"

    def test_priority_2(self):
        result = _make_collection([("ARCH-001", 2, "architecture")])
        assert validate(result).status == "pass"

    def test_priority_3(self):
        result = _make_collection([("ARCH-001", 3, "architecture")])
        assert validate(result).status == "pass"

    def test_priority_4(self):
        result = _make_collection([("ARCH-001", 4, "architecture")])
        assert validate(result).status == "pass"

    def test_priority_5(self):
        result = _make_collection([("ARCH-001", 5, "architecture")])
        assert validate(result).status == "pass"

    def test_multiple_files_valid(self):
        result = _make_collection([
            ("CORE-001", 1, "constitution"),
            ("ARCH-001", 2, "architecture"),
            ("ACTN-001", 3, "actions"),
            ("STAT-001", 4, "state-machine"),
            ("TEST-001", 5, "testing"),
        ])
        assert validate(result).status == "pass"


# ======================================================================
# Negative cases — out of range
# ======================================================================


class TestOutOfRangeLow:
    def test_priority_zero_is_error(self):
        result = _run("out_of_range_low.json")
        reasons = _error_reasons(result)
        assert any("out of range" in r for r in reasons)

    def test_priority_zero_reports_value(self):
        result = _run("out_of_range_low.json")
        reasons = _error_reasons(result)
        assert any("0" in r for r in reasons)


class TestOutOfRangeHigh:
    def test_priority_six_is_error(self):
        result = _run("out_of_range_high.json")
        reasons = _error_reasons(result)
        assert any("out of range" in r for r in reasons)

    def test_priority_six_reports_value(self):
        result = _run("out_of_range_high.json")
        reasons = _error_reasons(result)
        assert any("6" in r for r in reasons)


class TestNegativePriority:
    def test_negative_priority_is_out_of_range(self):
        coll = _make_collection([("ARCH-001", -1, "architecture")])
        result = validate(coll)
        reasons = _error_reasons(result)
        assert any("out of range" in r for r in reasons)


# ======================================================================
# Negative cases — type errors
# ======================================================================


class TestStringPriority:
    def test_string_priority_detected(self):
        result = _run("string_priority.json")
        reasons = _error_reasons(result)
        assert any("not integer" in r for r in reasons)

    def test_string_priority_shows_value(self):
        result = _run("string_priority.json")
        reasons = _error_reasons(result)
        assert any("'4'" in r for r in reasons)


class TestFloatPriority:
    def test_float_priority_detected(self):
        result = _run("float_priority.json")
        reasons = _error_reasons(result)
        assert any("not integer" in r for r in reasons)

    def test_float_priority_shows_type(self):
        result = _run("float_priority.json")
        reasons = _error_reasons(result)
        assert any("float" in r for r in reasons)


class TestNullPriority:
    def test_null_priority_detected(self):
        result = _run("null_priority.json")
        reasons = _error_reasons(result)
        assert any("not integer" in r for r in reasons)

    def test_null_priority_shows_type(self):
        result = _run("null_priority.json")
        reasons = _error_reasons(result)
        assert any("NoneType" in r for r in reasons)


class TestMissingPriority:
    def test_missing_priority_detected(self):
        """Missing priority defaults to 0 in the loader — flagged as out of range."""
        result = _run("missing_priority.json")
        reasons = _error_reasons(result)
        assert any("0" in r for r in reasons)
        assert any("out of range" in r for r in reasons)


class TestBooleanPriority:
    def test_boolean_true_as_priority(self):
        """Bool priority is coerced by loader to int, but validator should still flag it."""
        coll = RuleCollection()
        coll.rules["ARCH-001"] = Rule(
            id="ARCH-001", priority=True, rule="R.", violation=["V"],
            check="C.", fix="F.", tags=["t"], file_domain="architecture",
        )
        result = validate(coll)
        reasons = _error_reasons(result)
        assert any("not integer" in r for r in reasons)
        assert any("bool" in r for r in reasons)


# ======================================================================
# Constitutional violations
# ======================================================================


class TestConstitutionalWrongPriority:
    def test_constitutional_priority_not_1(self):
        result = _run("constitution_bad_priority.json")
        reasons = _error_reasons(result)
        assert any("constitutional rule must have priority 1" in r for r in reasons)

    def test_reports_actual_priority(self):
        result = _run("constitution_bad_priority.json")
        reasons = _error_reasons(result)
        assert any("got 2" in r for r in reasons)


class TestNonConstitutionalPriority1:
    def test_non_constitutional_priority_1(self):
        result = _run("non_constitutional_priority1.json")
        reasons = _error_reasons(result)
        assert any("priority 1 is reserved" in r for r in reasons)

    def test_non_constitutional_priority_1_by_code(self):
        coll = _make_collection([("ARCH-001", 1, "architecture")])
        result = validate(coll)
        reasons = _error_reasons(result)
        assert any("priority 1 is reserved" in r for r in reasons)


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


class TestMalformedRuleObject:
    def test_rule_with_no_priority_defaults_to_zero(self):
        """When priority is missing, loader defaults to 0, validator flags it."""
        result = _run("missing_priority.json")
        reasons = _error_reasons(result)
        assert any("0" in r for r in reasons)
        assert any("out of range" in r for r in reasons)


# ======================================================================
# Validator contract
# ======================================================================


class TestValidatorContract:
    def test_function_is_callable(self):
        result = validate(RuleCollection())
        assert isinstance(result, ValidatorResult)

    def test_does_not_mutate_collection(self):
        rc = _make_collection([("ARCH-001", 2, "architecture")])
        original_rules = dict(rc.rules)
        validate(rc)
        assert rc.rules == original_rules
