"""Comprehensive unit tests for the Schema Validator."""

from pathlib import Path

from tooling._shared.loader import load_rules
from tooling._shared.types import ValidationError, ValidatorResult
from tooling.validator.src.validators.schema_validator import validate

FIXTURES = Path(__file__).parent / "fixtures"


def _run(filename: str) -> ValidatorResult:
    path = FIXTURES / filename
    collection = load_rules(path)
    return validate(collection)


def _errors(result: ValidatorResult) -> list[ValidationError]:
    return result.errors


def _error_reasons(result: ValidatorResult) -> list[str]:
    return [e.reason for e in result.errors]


# ======================================================================
# Valid runtime
# ======================================================================


class TestValidRuntime:
    def test_valid_architecture_passes(self):
        result = _run("valid.json")
        assert result.status == "pass"
        assert result.errors == []

    def test_valid_constitution_passes(self):
        result = _run("valid_constitution.json")
        assert result.status == "pass"
        assert result.errors == []

    def test_validator_name_is_schema(self):
        result = _run("valid.json")
        assert result.name == "schema"

    def test_empty_result_structure(self):
        result = _run("valid.json")
        assert isinstance(result, ValidatorResult)
        assert isinstance(result.errors, list)


# ======================================================================
# File-level checks
# ======================================================================


class TestMissingFileMeta:
    def test_detects_missing_all_meta(self):
        result = _run("missing_meta.json")
        reasons = _error_reasons(result)
        for field in ("domain", "version", "last_updated", "source"):
            assert any(f"Missing or empty required file-level field '{field}'" in r for r in reasons)


class TestInvalidVersion:
    def test_rejects_invalid_version_format(self):
        result = _run("invalid_version.json")
        reasons = _error_reasons(result)
        assert any("Invalid version format" in r for r in reasons)

    def test_rejects_non_major_minor_patch(self):
        result = _run("invalid_version.json")
        reasons = _error_reasons(result)
        assert any("1.0" in r for r in reasons)


class TestInvalidDate:
    def test_rejects_invalid_date_format(self):
        result = _run("invalid_date.json")
        reasons = _error_reasons(result)
        assert any("Invalid date format" in r for r in reasons)


class TestMissingRules:
    def test_detects_missing_rules_key(self):
        result = _run("missing_rules.json")
        reasons = _error_reasons(result)
        assert any("Missing required file-level field 'rules'" in r for r in reasons)

    def test_detects_empty_rules_array(self):
        result = _run("empty_rules.json")
        reasons = _error_reasons(result)
        assert any("'rules' array is empty" in r for r in reasons)

    def test_detects_non_array_rules(self):
        result = _run("non_array_rules.json")
        reasons = _error_reasons(result)
        assert any("'rules' must be an array" in r for r in reasons)


class TestNonStringDomain:
    def test_detects_non_string_domain(self):
        result = _run("non_string_domain.json")
        reasons = _error_reasons(result)
        assert any("'domain' must be a string" in r for r in reasons)


# ======================================================================
# Rule-level missing required fields
# ======================================================================


class TestMissingRuleFields:
    def test_detects_missing_id(self):
        result = _run("missing_rule_fields.json")
        reasons = _error_reasons(result)
        assert any("Missing required field 'id'" in r for r in reasons)

    def test_detects_missing_priority(self):
        result = _run("missing_rule_fields.json")
        reasons = _error_reasons(result)
        assert any("Missing required field 'priority'" in r for r in reasons)

    def test_detects_missing_rule(self):
        result = _run("missing_rule_fields.json")
        reasons = _error_reasons(result)
        assert any("Missing required field 'rule'" in r for r in reasons)

    def test_detects_missing_violation(self):
        result = _run("missing_rule_fields.json")
        reasons = _error_reasons(result)
        assert any("Missing required field 'violation'" in r for r in reasons)

    def test_detects_missing_check(self):
        result = _run("missing_rule_fields.json")
        reasons = _error_reasons(result)
        assert any("Missing required field 'check'" in r for r in reasons)

    def test_detects_missing_fix(self):
        result = _run("missing_rule_fields.json")
        reasons = _error_reasons(result)
        assert any("Missing required field 'fix'" in r for r in reasons)

    def test_detects_missing_tags(self):
        result = _run("missing_rule_fields.json")
        reasons = _error_reasons(result)
        assert any("Missing required field 'tags'" in r for r in reasons)

    def test_missing_applies_to(self):
        result = _run("missing_rule_fields.json")
        reasons = _error_reasons(result)
        assert any("Missing required field 'applies_to'" in r for r in reasons)


# ======================================================================
# Empty required fields
# ======================================================================


class TestEmptyRuleFields:
    def test_detects_empty_rule_string(self):
        result = _run("empty_rule_fields.json")
        reasons = _error_reasons(result)
        assert any("Required field 'rule' is empty" in r for r in reasons)

    def test_detects_empty_check_string(self):
        result = _run("empty_rule_fields.json")
        reasons = _error_reasons(result)
        assert any("Required field 'check' is empty" in r for r in reasons)

    def test_detects_empty_violation_array(self):
        result = _run("empty_rule_fields.json")
        reasons = _error_reasons(result)
        assert any("Required field 'violation' is an empty array" in r for r in reasons)

    def test_detects_empty_tags_array(self):
        result = _run("empty_rule_fields.json")
        reasons = _error_reasons(result)
        assert any("Required field 'tags' is an empty array" in r for r in reasons)


# ======================================================================
# Type errors
# ======================================================================


class TestBadTypes:
    def test_detects_non_string_id(self):
        result = _run("bad_types.json")
        reasons = _error_reasons(result)
        assert any("'id' must be a string" in r for r in reasons)

    def test_detects_string_priority(self):
        result = _run("bad_types.json")
        reasons = _error_reasons(result)
        assert any("'priority' must be an integer" in r for r in reasons)

    def test_detects_non_string_rule(self):
        result = _run("bad_types.json")
        reasons = _error_reasons(result)
        assert any("'rule' must be a string" in r for r in reasons)

    def test_detects_non_array_violation(self):
        result = _run("bad_types.json")
        reasons = _error_reasons(result)
        assert any("'violation' must be an array" in r for r in reasons)

    def test_detects_non_string_check(self):
        result = _run("bad_types.json")
        reasons = _error_reasons(result)
        assert any("'check' must be a string" in r for r in reasons)

    def test_detects_null_fix(self):
        result = _run("bad_types.json")
        reasons = _error_reasons(result)
        assert any("Missing required field 'fix'" in r for r in reasons)

    def test_detects_non_array_tags(self):
        result = _run("bad_types.json")
        reasons = _error_reasons(result)
        assert any("'tags' must be an array" in r for r in reasons)


# ======================================================================
# Array element type errors
# ======================================================================


class TestBadArrayElements:
    def test_detects_non_string_in_violation(self):
        result = _run("bad_array_elements.json")
        reasons = _error_reasons(result)
        assert any("'violation' contains non-string element at index 0" in r for r in reasons)
        assert any("'violation' contains non-string element at index 1" in r for r in reasons)

    def test_detects_non_string_in_tags(self):
        result = _run("bad_array_elements.json")
        reasons = _error_reasons(result)
        assert any("'tags' contains non-string element at index 1" in r for r in reasons)

    def test_detects_non_string_in_applies_to(self):
        result = _run("bad_array_elements.json")
        reasons = _error_reasons(result)
        assert any("'applies_to' contains non-string element at index 1" in r for r in reasons)

    def test_detects_non_string_in_exceptions(self):
        result = _run("bad_array_elements.json")
        reasons = _error_reasons(result)
        assert any("'exceptions' contains non-string element at index 0" in r for r in reasons)
        assert any("'exceptions' contains non-string element at index 1" in r for r in reasons)


# ======================================================================
# Priority out of range
# ======================================================================


class TestBadPriority:
    def test_detects_priority_zero(self):
        result = _run("bad_priority.json")
        reasons = _error_reasons(result)
        assert any("'priority' 0 is out of range" in r for r in reasons)

    def test_detects_priority_six(self):
        result = _run("bad_priority.json")
        reasons = _error_reasons(result)
        assert any("'priority' 6 is out of range" in r for r in reasons)


# ======================================================================
# Optional field type errors
# ======================================================================


class TestBadOptionalFields:
    def test_detects_non_array_exceptions(self):
        result = _run("bad_optional.json")
        reasons = _error_reasons(result)
        assert any("'exceptions' must be an array when present" in r for r in reasons)

    def test_detects_non_array_see_also(self):
        result = _run("bad_optional.json")
        reasons = _error_reasons(result)
        assert any("'see_also' must be an array when present" in r for r in reasons)

    def test_detects_non_string_rationale(self):
        result = _run("bad_optional.json")
        reasons = _error_reasons(result)
        assert any("'rationale' must be a string when present" in r for r in reasons)

    def test_detects_non_string_source(self):
        result = _run("bad_optional.json")
        reasons = _error_reasons(result)
        assert any("'source' must be a string when present" in r for r in reasons)


# ======================================================================
# Unknown fields
# ======================================================================


class TestUnknownFields:
    def test_detects_unknown_rule_fields(self):
        result = _run("unknown_fields.json")
        reasons = _error_reasons(result)
        assert any("Unknown field 'exeptions'" in r for r in reasons)
        assert any("Unknown field 'reeason'" in r for r in reasons)
        assert any("Unknown field 'category'" in r for r in reasons)

    def test_offers_did_you_mean_suggestion(self):
        result = _run("unknown_fields.json")
        reasons = _error_reasons(result)
        assert any("Unknown field 'exeptions' (did you mean 'exceptions'?)" in r for r in reasons)


class TestUnknownFileFields:
    def test_detects_unknown_file_level_fields(self):
        result = _run("unknown_file_fields.json")
        reasons = _error_reasons(result)
        assert any("Unknown file-level field 'description'" in r for r in reasons)
        assert any("Unknown file-level field 'schema_note'" in r for r in reasons)


# ======================================================================
# Constitutional violations
# ======================================================================


class TestConstitutionalViolations:
    def test_detects_wrong_constitutional_priority(self):
        result = _run("constitution_bad.json")
        reasons = _error_reasons(result)
        assert any("Constitutional rule must have priority 1" in r for r in reasons)

    def test_detects_missing_constitutional_rationale(self):
        result = _run("constitution_bad.json")
        reasons = _error_reasons(result)
        assert any("Constitutional rule missing 'rationale'" in r for r in reasons)


# ======================================================================
# Multiple files
# ======================================================================


class TestMultipleFiles:
    def test_validates_directory_of_files(self):
        collection = load_rules(FIXTURES)
        result = validate(collection)
        assert result.status == "fail"
        assert len(result.errors) > 0


# ======================================================================
# Edge cases
# ======================================================================


class TestEdgeCases:
    def test_empty_collection(self):
        from tooling._shared.types import RuleCollection

        empty = RuleCollection()
        result = validate(empty)
        assert result.status == "pass"
        assert result.errors == []

    def test_missing_json_field_is_explicit_error(self):
        result = _run("missing_meta.json")
        reasons = _error_reasons(result)
        assert any("'domain'" in r for r in reasons)
        assert any("'version'" in r for r in reasons)
        assert any("'last_updated'" in r for r in reasons)
        assert any("'source'" in r for r in reasons)

    def test_empty_id_string(self):
        result = _run("empty_id.json")
        reasons = _error_reasons(result)
        assert any("'id' must not be empty" in r for r in reasons)

    def test_non_dict_rule_skipped(self):
        """Rules that are not dicts are silently skipped."""
        result = _run("non_dict_rule.json")
        reasons = _error_reasons(result)
        assert len(result.errors) == 0


# ======================================================================
# Internal helper coverage (private functions)
# ======================================================================


class TestInternalHelpers:
    """Test private helper functions for full branch coverage."""

    def test_read_raw_json_nonexistent(self):
        from tooling._shared.loader import read_raw_json

        result = read_raw_json("/nonexistent_file_xyz.json")
        assert result is None

    def test_read_raw_json_non_dict(self):
        from tooling._shared.loader import read_raw_json
        import tempfile, json, os

        with tempfile.NamedTemporaryFile(mode="w", suffix=".json", delete=False) as f:
            json.dump([1, 2, 3], f)
            tmp = f.name
        try:
            result = read_raw_json(tmp)
        finally:
            os.unlink(tmp)
        assert result is None

    def test_levenshtein_both_empty(self):
        from tooling.validator.src.validators.schema_validator import _levenshtein_similarity

        assert _levenshtein_similarity("", "") == 100.0

    def test_levenshtein_one_empty(self):
        from tooling.validator.src.validators.schema_validator import _levenshtein_similarity

        assert _levenshtein_similarity("a", "") == 0.0
        assert _levenshtein_similarity("", "a") == 0.0

    def test_did_you_mean_exact_match_returns_none(self):
        from tooling.validator.src.validators.schema_validator import _did_you_mean

        result = _did_you_mean("exceptions", ["exceptions", "tags"])
        assert result is None or result == "exceptions"


# ======================================================================
# Validator contract
# ======================================================================


class TestValidatorContract:
    def test_function_is_callable(self):
        from tooling._shared.types import RuleCollection
        from tooling.validator.src.validators.schema_validator import validate as v

        result = v(RuleCollection())
        assert isinstance(result, ValidatorResult)

    def test_does_not_mutate_collection(self):
        from tooling._shared.types import RuleCollection

        rc = RuleCollection()
        original = RuleCollection(
            rules=dict(rc.rules),
            files=list(rc.files),
        )
        result = validate(rc)
        assert rc.rules == original.rules
        assert rc.files == original.files
