"""Comprehensive unit tests for the Release Validator."""

from pathlib import Path

from tooling._shared.loader import load_rules
from tooling._shared.types import FileInfo, Rule, RuleCollection, ValidationError, ValidatorResult
from tooling.validator.src.validators.release_validator import validate

FIXTURES = Path(__file__).parent / "fixtures" / "release"


def _build_rules(
    file_specs: list[tuple[str, str, int, int, int]],
) -> RuleCollection:
    """Build a RuleCollection from file specs.

    Each spec is (filename, domain, rule_count, line_count, see_also_count).
    Rules are assigned IDs like ``{prefix}-001`` with priority based on count.
    """
    coll = RuleCollection()
    prefix_map = {
        "constitution.json": "CORE",
        "architecture.json": "ARCH",
        "state-machine.json": "STAT",
        "actions.json": "ACTN",
        "testing.json": "TEST",
        "persistence.json": "PERS",
    }
    for filename, domain, count, lines, sa_count in file_specs:
        prefix = prefix_map.get(filename, "ARCH")
        filepath = f"/tmp/{filename}"
        rule_ids: list[str] = []
        for i in range(1, count + 1):
            rid = f"{prefix}-{i:03d}"
            see_also = [f"OTHER-{j:03d}" for j in range(1, sa_count + 1)] if sa_count > 0 else None
            coll.rules[rid] = Rule(
                id=rid,
                priority=1 if domain == "constitution" else 2 + (i % 3),
                rule=f"Rule {i}.",
                violation=["V"],
                check="C.",
                fix="F.",
                tags=["t"],
                applies_to=["All components"] if domain == "constitution" else ["Game.php"],
                see_also=see_also,
                file_path=filepath,
                file_domain=domain,
            )
            rule_ids.append(rid)
        coll.files.append(
            FileInfo(
                path=filepath, domain=domain, version="1.0",
                last_updated="2026-01-01", source="s",
                rule_count=count, line_count=lines,
            )
        )
        coll.file_index[filepath] = rule_ids

    from tooling._shared.loader import _build_indices
    _build_indices(coll)
    return coll


def _run(
    filename: str,
    file_specs: list[tuple[str, str, int, int, int]] | None = None,
) -> ValidatorResult:
    release_path = FIXTURES / filename
    if file_specs is None:
        file_specs = [
            ("constitution.json", "constitution", 16, 488, 1),
            ("architecture.json", "architecture", 22, 616, 1),
            ("state-machine.json", "state-machine", 16, 431, 1),
            ("actions.json", "actions", 14, 392, 1),
            ("testing.json", "testing", 17, 319, 1),
        ]
    rules = _build_rules(file_specs)
    return validate(rules, str(release_path))


def _error_reasons(result: ValidatorResult) -> list[str]:
    return [e.reason for e in result.errors]


def _error_severities(result: ValidatorResult) -> list[str]:
    return [e.severity for e in result.errors]


# ======================================================================
# Positive cases
# ======================================================================


class TestValidRelease:
    def test_synchronized_release_passes(self):
        result = _run("valid_release.md")
        assert result.status == "pass", _error_reasons(result)

    def test_validator_name(self):
        result = _run("valid_release.md")
        assert result.name == "release"

    def test_no_release_path_is_pass(self):
        rules = _build_rules([("testing.json", "testing", 17, 319, 0)])
        result = validate(rules)
        assert result.status == "pass"

    def test_missing_release_file_reports_error(self):
        rules = _build_rules([("testing.json", "testing", 17, 319, 0)])
        result = validate(rules, "/nonexistent/path.md")
        assert result.status == "fail"
        assert any("not found" in r for r in _error_reasons(result))


# ======================================================================
# Negative cases — per-file errors
# ======================================================================


class TestWrongPerFileStats:
    def test_wrong_rule_count(self):
        # Doc says state-machine has 99 rules; we create only 16
        specs = [
            ("constitution.json", "constitution", 16, 488, 0),
            ("architecture.json", "architecture", 22, 616, 0),
            ("state-machine.json", "state-machine", 16, 431, 0),
            ("actions.json", "actions", 14, 392, 0),
            ("testing.json", "testing", 17, 319, 0),
        ]
        result = _run("wrong_rules.md", file_specs=specs)
        reasons = _error_reasons(result)
        assert any("state-machine.json" in r and "99" in r for r in reasons)

    def test_wrong_line_count(self):
        # Doc says actions has 999 lines; we create only 392
        specs = [
            ("constitution.json", "constitution", 16, 488, 0),
            ("architecture.json", "architecture", 22, 616, 0),
            ("state-machine.json", "state-machine", 16, 431, 0),
            ("actions.json", "actions", 14, 392, 0),
            ("testing.json", "testing", 17, 319, 0),
        ]
        result = _run("wrong_rules.md", file_specs=specs)
        reasons = _error_reasons(result)
        assert any("actions.json" in r and "999" in r for r in reasons)


# ======================================================================
# Negative cases — aggregate errors
# ======================================================================


class TestWrongAggregates:
    def test_wrong_total_rules(self):
        specs = [
            ("constitution.json", "constitution", 16, 488, 0),
            ("testing.json", "testing", 99, 319, 0),
        ]
        result = _run("valid_release.md", file_specs=specs)
        reasons = _error_reasons(result)
        assert any("total rules" in r for r in reasons)

    def test_wrong_total_lines(self):
        specs = [
            ("constitution.json", "constitution", 16, 999, 0),
            ("testing.json", "testing", 17, 319, 0),
        ]
        result = _run("valid_release.md", file_specs=specs)
        reasons = _error_reasons(result)
        assert any("total lines" in r for r in reasons)

    def test_wrong_cross_references(self):
        specs = [
            ("constitution.json", "constitution", 16, 488, 0),
            ("testing.json", "testing", 17, 319, 10),
        ]
        result = _run("valid_release.md", file_specs=specs)
        reasons = _error_reasons(result)
        assert any("cross" in r.lower() for r in reasons)

    def test_wrong_largest_file(self):
        specs = [
            ("constitution.json", "constitution", 16, 488, 0),
            ("actions.json", "actions", 14, 500, 0),
            ("testing.json", "testing", 17, 319, 0),
        ]
        result = _run("valid_release.md", file_specs=specs)
        reasons = _error_reasons(result)
        assert any("largest" in r for r in reasons)


# ======================================================================
# Version checks
# ======================================================================


class TestVersionChecks:
    def test_wrong_schema_version(self):
        result = _run("wrong_version.md")
        reasons = _error_reasons(result)
        assert any("schema version" in r.lower() for r in reasons)

    def test_missing_runtime_version(self):
        result = _run("empty.md")
        reasons = _error_reasons(result)
        assert any("runtime version" in r.lower() for r in reasons)


# ======================================================================
# Warning cases — fallback
# ======================================================================


class TestNoInventorySection:
    def test_warns_on_missing_inventory(self):
        result = _run("no_inventory_section.md")
        reasons = _error_reasons(result)
        assert any("Cannot parse Runtime Inventory" in r for r in reasons)

    def test_warning_is_warning_severity(self):
        result = _run("no_inventory_section.md")
        sevs = _error_severities(result)
        inventory_warnings = [
            i for i, r in enumerate(_error_reasons(result))
            if "Cannot parse Runtime Inventory" in r
        ]
        for idx in inventory_warnings:
            assert sevs[idx] == "warning"


class TestMalformedTable:
    def test_warns_on_malformed_table(self):
        result = _run("malformed_table.md")
        reasons = _error_reasons(result)
        assert any("Cannot parse Runtime Inventory" in r for r in reasons)


# ======================================================================
# Edge cases
# ======================================================================


class TestEmptyRelease:
    def test_empty_doc(self):
        rules = _build_rules([("testing.json", "testing", 17, 319, 0)])
        result = validate(rules, str(FIXTURES / "empty.md"))
        reasons = _error_reasons(result)
        assert any("Cannot parse Runtime Inventory" in r for r in reasons)
        assert any("does not mention runtime version" in r for r in reasons)


class TestNoRules:
    def test_no_rules_no_files(self):
        result = validate(RuleCollection(), str(FIXTURES / "empty.md"))
        assert result.status == "fail"


# ======================================================================
# Validator contract
# ======================================================================


class TestEdgeCases:
    def test_total_row_skipped(self):
        """Row with 'Total' in file column is skipped, not counted as a file."""
        specs = [
            ("constitution.json", "constitution", 16, 488, 0),
            ("testing.json", "testing", 17, 319, 0),
        ]
        result = _run("with_total_row.md", file_specs=specs)
        reasons = _error_reasons(result)
        # Should pass — the total row is skipped
        assert result.status == "pass", reasons

    def test_unreadable_release_file(self):
        """Pointing the release doc at a directory triggers an OS error."""
        rules = _build_rules([("testing.json", "testing", 17, 319, 0)])
        result = validate(rules, str(FIXTURES))
        assert result.status == "fail"
        assert any("Cannot read" in r for r in _error_reasons(result))

    def test_release_file_not_found(self):
        rules = _build_rules([("testing.json", "testing", 17, 319, 0)])
        result = validate(rules, "/tmp/nonexistent_release_doc.md")
        assert result.status == "fail"
        assert any("not found" in r for r in _error_reasons(result))

    def test_missing_stats_section(self):
        """Release doc without Implementation Statistics section."""
        release_text = "# Random doc\nNothing here."
        path = FIXTURES / "_tmp_no_stats.md"
        path.write_text(release_text)
        try:
            rules = _build_rules([("testing.json", "testing", 17, 319, 0)])
            result = validate(rules, str(path))
            reasons = _error_reasons(result)
            assert any("Cannot find Implementation Statistics" in r for r in reasons)
        finally:
            if path.exists():
                path.unlink()

    def test_file_column_name_variation(self):
        """Inventory table with non-standard column ordering should still work."""
        release_text = """# Runtime Specification v1.1 — Release

## 4. Runtime Inventory

| Lines | File | Rules |
|---|---|---|
| 488 | constitution.json | 16 |

## 5. Implementation Statistics

| Metric | Value |
|---|---|
| Total rules | 16 |
| Total lines | 488 |
| Schema version | 1.1 (frozen) |
"""
        path = FIXTURES / "_tmp_variation.md"
        path.write_text(release_text)
        try:
            specs = [("constitution.json", "constitution", 16, 488, 0)]
            rules = _build_rules(specs)
            result = validate(rules, str(path))
            assert result.status == "pass", _error_reasons(result)
        finally:
            if path.exists():
                path.unlink()


# ======================================================================
# Validator contract
# ======================================================================


class TestParseHelpers:
    """Test internal parsing helper functions for full coverage."""

    def test_invalid_int_cell_returns_zero(self):
        from tooling.validator.src.validators.release_validator import _int_cell_ci

        row = {"Rules": "not-a-number"}
        assert _int_cell_ci(row, "rules") == 0

    def test_average_check_reports_discrepancy(self):
        """Test that average-rules-per-file check fires on mismatch."""
        release_text = """# Runtime Specification v1.1 — Release

## 5. Implementation Statistics

| Metric | Value |
|---|---|
| Average rules per file | 99.9 |
"""
        path = FIXTURES / "_tmp_avg.md"
        path.write_text(release_text)
        try:
            from tooling._shared.types import RuleCollection

            result = validate(RuleCollection(), str(path))
            reasons = [e.reason for e in result.errors]
            assert any("average" in r.lower() for r in reasons)
        finally:
            if path.exists():
                path.unlink()

    def test_empty_doc_does_not_crash_parse(self):
        """Empty release doc should not crash helper functions."""
        from tooling.validator.src.validators.release_validator import _parse_int, _parse_float

        assert _parse_int("") is None
        assert _parse_float("") is None
        assert _parse_int("abc") is None
        assert _parse_float("abc") is None


class TestValidatorContract:
    def test_function_is_callable(self):
        result = validate(RuleCollection())
        assert isinstance(result, ValidatorResult)

    def test_does_not_mutate_collection(self):
        rules = _build_rules([("testing.json", "testing", 17, 319, 0)])
        original_rules = dict(rules.rules)
        original_files = list(rules.files)
        validate(rules, str(FIXTURES / "valid_release.md"))
        assert rules.rules == original_rules
        assert list(rules.files) == original_files
