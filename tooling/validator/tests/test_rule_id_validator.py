"""Comprehensive unit tests for the Rule ID Validator."""

from pathlib import Path

from tooling._shared.loader import load_rules
from tooling._shared.types import RuleCollection, ValidationError, ValidatorResult
from tooling.validator.src.validators.rule_id_validator import validate

FIXTURES = Path(__file__).parent / "fixtures" / "rule_id"


def _run(filename: str) -> ValidatorResult:
    path = FIXTURES / filename
    collection = load_rules(path)
    return validate(collection)


def _run_multi(filenames: list[str]) -> ValidatorResult:
    from tooling._shared.types import RuleCollection

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


def _errors(result: ValidatorResult) -> list[ValidationError]:
    return result.errors


def _error_reasons(result: ValidatorResult) -> list[str]:
    return [e.reason for e in result.errors]


def _error_ids(result: ValidatorResult) -> list[str | None]:
    return [e.rule_id for e in result.errors]


def _error_by_rule_id(result: ValidatorResult, rid: str) -> list[ValidationError]:
    return [e for e in result.errors if e.rule_id == rid]


# ======================================================================
# Positive cases
# ======================================================================


class TestValidStandard:
    def test_valid_file_passes(self):
        result = _run("valid_standard.json")
        assert result.status == "pass", _error_reasons(result)

    def test_numbering_gaps_are_not_errors(self):
        result = _run("valid_standard.json")
        assert result.status == "pass"

    def test_validator_name(self):
        result = _run("valid_standard.json")
        assert result.name == "rule_id"


class TestValidMinMax:
    def test_zero_padded_ids_are_valid(self):
        result = _run("valid_min_max.json")
        assert result.status == "pass"

    def test_TEST_000_is_valid(self):
        result = _run("valid_min_max.json")
        assert result.status == "pass"

    def test_TEST_999_is_valid(self):
        result = _run("valid_min_max.json")
        assert result.status == "pass"


class TestValidSingle:
    def test_single_rule_passes(self):
        result = _run("valid_single.json")
        assert result.status == "pass"


class TestMultipleValidFiles:
    def test_multiple_files_all_valid(self):
        result = _run_multi(["valid_standard.json", "valid_second_file.json"])
        assert result.status == "pass"

    def test_different_prefixes_across_files(self):
        result = _run_multi(["valid_standard.json", "valid_second_file.json"])
        assert result.status == "pass"


# ======================================================================
# Negative cases — Naming convention
# ======================================================================


class TestInvalidPrefix:
    def test_detects_invalid_prefix(self):
        result = _run("invalid_prefix.json")
        reasons = _error_reasons(result)
        assert any("naming convention" in r for r in reasons)

    def test_custom_prefix_no_suggestion(self):
        result = _run("invalid_prefix.json")
        ids = _error_ids(result)
        assert "CUSTOM-001" in ids


class TestBadWidth:
    def test_detects_missing_zero_padding(self):
        result = _run("bad_width.json")
        ids = _error_ids(result)
        assert "ARCH-1" in ids

    def test_suggests_correct_format(self):
        result = _run("bad_width.json")
        reasons = _error_reasons(result)
        assert any("expected ARCH-001" in r for r in reasons)


class TestBadPadding:
    def test_detects_one_digit_short(self):
        result = _run("bad_padding.json")
        reasons = _error_reasons(result)
        assert any("naming convention" in r for r in reasons)
        assert any("expected ARCH-001" in r for r in reasons)

    def test_reports_original_id(self):
        result = _run("bad_padding.json")
        ids = _error_ids(result)
        assert "ARCH-01" in ids


class TestBadSeparator:
    def test_detects_invalid_separator(self):
        result = _run("bad_separator.json")
        reasons = _error_reasons(result)
        assert any("naming convention" in r for r in reasons)


class TestBadCasing:
    def test_detects_lowercase_prefix(self):
        result = _run("bad_casing.json")
        reasons = _error_reasons(result)
        assert any("naming convention" in r for r in reasons)


class TestMalformedIDs:
    def test_detects_empty_id(self):
        result = _run("malformed_ids.json")
        reasons = _error_reasons(result)
        assert any("naming convention" in r for r in reasons)

    def test_detects_whitespace_id(self):
        result = _run("malformed_ids.json")
        reasons = _error_reasons(result)
        assert any("naming convention" in r for r in reasons)

    def test_detects_extra_suffix(self):
        result = _run("malformed_ids.json")
        reasons = _error_reasons(result)
        assert any("naming convention" in r for r in reasons)


# ======================================================================
# Prefix-file alignment
# ======================================================================


class TestPrefixFileMismatch:
    def test_detects_prefix_file_mismatch(self):
        result = _run("prefix_file_mismatch.json")
        reasons = _error_reasons(result)
        assert any("prefix ACTN in" in r for r in reasons)
        assert any("expected in actions.json" in r for r in reasons)

    def test_mismatch_references_correct_file(self):
        result = _run("prefix_file_mismatch.json")
        reasons = _error_reasons(result)
        assert any("prefix_file_mismatch.json" in r for r in reasons)
        assert any("expected in actions.json" in r for r in reasons)


# ======================================================================
# Duplicate detection
# ======================================================================


class TestDuplicateWithinFile:
    def test_detects_duplicate_within_file(self):
        result = _run("dup_within_file.json")
        reasons = _error_reasons(result)
        ids = _error_ids(result)
        assert any("duplicate rule ID" in r for r in reasons)
        assert "STAT-001" in ids

    def test_includes_line_numbers(self):
        result = _run("dup_within_file.json")
        reasons = _error_reasons(result)
        assert any("line 8" in r for r in reasons)
        assert any("line 30" in r for r in reasons)

    def test_duplicate_file_is_correct(self):
        result = _run("dup_within_file.json")
        reasons = _error_reasons(result)
        assert any("dup_within_file.json" in r for r in reasons)


class TestDuplicateCrossFile:
    def test_detects_cross_file_duplicate(self):
        result = _run_multi(["dup_cross_file_a.json", "dup_cross_file_b.json"])
        reasons = _error_reasons(result)
        ids = _error_ids(result)
        assert any("duplicate rule ID across files" in r for r in reasons)
        assert "ARCH-001" in ids

    def test_lists_all_files(self):
        result = _run_multi(["dup_cross_file_a.json", "dup_cross_file_b.json"])
        reasons = _error_reasons(result)
        assert any("dup_cross_file_a.json" in r for r in reasons)
        assert any("dup_cross_file_b.json" in r for r in reasons)


# ======================================================================
# Edge cases
# ======================================================================


class TestLargeNumbers:
    def test_migr_999_is_valid(self):
        result = _run("large_number.json")
        assert result.status == "pass"


class TestEmptyCollection:
    def test_empty_collection_passes(self):
        empty = RuleCollection()
        result = validate(empty)
        assert result.status == "pass"
        assert result.errors == []


class TestEmptyFile:
    def test_empty_json_passes(self):
        result = _run("empty.json")
        assert result.status == "pass"


class TestNonDictRule:
    def test_non_dict_rule_skipped(self):
        """Rules that are not dicts are silently skipped."""
        result = _run("rule_is_string.json")
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


# ======================================================================
# Private helper coverage
# ======================================================================


class TestInternalHelpers:
    def test_format_correct_id_valid_prefix(self):
        from tooling.validator.src.validators.rule_id_validator import _format_correct_id

        assert _format_correct_id("ARCH-1") == "ARCH-001"
        assert _format_correct_id("ARCH-01") == "ARCH-001"
        assert _format_correct_id("ARCH-999") == "ARCH-999"

    def test_format_correct_id_invalid_prefix(self):
        from tooling.validator.src.validators.rule_id_validator import _format_correct_id

        assert _format_correct_id("ZZZZ-001") is None

    def test_format_correct_id_no_separator(self):
        from tooling.validator.src.validators.rule_id_validator import _format_correct_id

        assert _format_correct_id("ARCH001") is None

    def test_format_correct_id_garbage(self):
        from tooling.validator.src.validators.rule_id_validator import _format_correct_id

        assert _format_correct_id("") is None
        assert _format_correct_id("abc") is None

    def test_find_id_lines_missing_file(self):
        from tooling.validator.src.validators.rule_id_validator import _find_id_lines

        result = _find_id_lines("/nonexistent/path.json")
        assert result == {}
