"""Comprehensive unit tests for the Statistics Generator."""

from pathlib import Path

from tooling._shared.loader import load_rules
from tooling._shared.types import RuleCollection, ValidatorResult
from tooling.validator.src.validators.stats_generator import (
    generate_statistics,
    validate,
)

FIXTURES = Path(__file__).parent / "fixtures" / "stats"


def _stats(filename: str) -> dict:
    path = FIXTURES / filename
    collection = load_rules(path)
    return generate_statistics(collection)


def _stats_multi(filenames: list[str]) -> dict:
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
    return generate_statistics(collection)


# ======================================================================
# Validator contract
# ======================================================================


class TestValidatorContract:
    def test_always_passes(self):
        result = validate(RuleCollection())
        assert result.status == "pass"
        assert result.errors == []

    def test_validator_name(self):
        result = validate(RuleCollection())
        assert result.name == "stats"


# ======================================================================
# Per-file statistics
# ======================================================================


class TestPerFileStats:
    def test_rule_count(self):
        stats = _stats("multi_file_a.json")
        assert len(stats["files"]) == 1
        assert stats["files"][0]["rule_count"] == 3

    def test_domain(self):
        stats = _stats("multi_file_a.json")
        assert stats["files"][0]["domain"] == "architecture"

    def test_file_name(self):
        stats = _stats("multi_file_a.json")
        assert stats["files"][0]["file"] == "multi_file_a.json"

    def test_rules_from_to(self):
        stats = _stats("multi_file_b.json")
        fi = stats["files"][0]
        assert fi["rules_from"] == "ACTN-001"
        assert fi["rules_to"] == "ACTN-005"

    def test_priority_distribution_per_file(self):
        stats = _stats("multi_file_a.json")
        fi = stats["files"][0]
        assert fi["priorities"] == {"2": 2, "3": 1}

    def test_multiple_files(self):
        stats = _stats_multi(["multi_file_a.json", "multi_file_b.json"])
        assert len(stats["files"]) == 2


# ======================================================================
# Aggregate statistics
# ======================================================================


class TestAggregateStats:
    def test_total_files(self):
        stats = _stats_multi(["multi_file_a.json", "multi_file_b.json"])
        assert stats["total_files"] == 2

    def test_total_rules(self):
        stats = _stats_multi(["multi_file_a.json", "multi_file_b.json"])
        assert stats["total_rules"] == 6

    def test_total_lines(self):
        stats = _stats("single.json")
        assert stats["total_lines"] > 0

    def test_priority_distribution(self):
        stats = _stats("distributions.json")
        assert stats["priority_distribution"] == {"2": 2, "5": 1}

    def test_tag_distribution(self):
        stats = _stats("distributions.json")
        assert stats["tag_distribution"]["tag_a"] == 2
        assert stats["tag_distribution"]["tag_b"] == 1
        assert stats["tag_distribution"]["tag_c"] == 1

    def test_applies_to_distribution(self):
        stats = _stats("distributions.json")
        assert stats["applies_to_distribution"]["Game.php"] == 2
        assert stats["applies_to_distribution"]["Managers"] == 1
        assert stats["applies_to_distribution"]["Engine"] == 1

    def test_cross_reference_count(self):
        stats = _stats("multi_file_a.json")
        assert stats["cross_reference_count"] == 1

    def test_largest_file(self):
        stats = _stats_multi(["multi_file_a.json", "multi_file_b.json"])
        assert "lines)" in stats["largest_file"]
        # The file with more lines is multi_file_a (11 lines), not b (10 lines)
        assert "multi_file_a.json" in stats["largest_file"]

    def test_smallest_file(self):
        stats = _stats_multi(["multi_file_a.json", "multi_file_b.json"])
        assert "multi_file_a.json" in stats["smallest_file"]

    def test_average_rules_per_file(self):
        stats = _stats_multi(["multi_file_a.json", "multi_file_b.json"])
        assert stats["average_rules_per_file"] == 3.0

    def test_average_rules_rounded(self):
        stats = _stats_multi(["multi_file_a.json", "single.json"])
        assert stats["average_rules_per_file"] == 2.0


# ======================================================================
# Gap analysis
# ======================================================================


class TestGapAnalysis:
    def test_no_gaps(self):
        stats = _stats("multi_file_a.json")
        assert stats["gaps"]["ARCH"] == []

    def test_single_gap(self):
        stats = _stats("gaps.json")
        assert stats["gaps"]["TEST"] == ["TEST-002", "TEST-004"]

    def test_gap_at_start(self):
        """If first ID is TEST-003, TEST-001 and TEST-002 should be gaps."""
        stats = _stats("gaps.json")
        assert "TEST-002" in stats["gaps"]["TEST"]
        assert "TEST-004" in stats["gaps"]["TEST"]

    def test_gap_reports_correct_prefix(self):
        stats = _stats("gaps.json")
        for g in stats["gaps"]["TEST"]:
            assert g.startswith("TEST-")


# ======================================================================
# Empty / edge cases
# ======================================================================


class TestEmptyRuntime:
    def test_empty_collection(self):
        stats = generate_statistics(RuleCollection())
        assert stats["total_files"] == 0
        assert stats["total_rules"] == 0
        assert stats["total_lines"] == 0
        assert stats["average_rules_per_file"] == 0.0
        assert stats["files"] == []
        assert stats["gaps"] == {}
        assert stats["largest_file"] == " (0 lines)"
        assert stats["smallest_file"] == " (0 lines)"


class TestEmptyFile:
    def test_empty_json(self):
        stats = _stats("empty.json")
        assert stats["total_files"] == 1
        assert stats["total_rules"] == 0


class TestSingleRule:
    def test_single_rule(self):
        stats = _stats("single.json")
        assert stats["total_rules"] == 1
        assert stats["total_files"] == 1
        assert stats["files"][0]["rules_from"] == "CORE-001"
        assert stats["files"][0]["rules_to"] == "CORE-001"
        assert stats["priority_distribution"] == {"1": 1}
        assert stats["tag_distribution"] == {"server": 1}
        assert stats["applies_to_distribution"] == {"All components": 1}
        assert stats["cross_reference_count"] == 0
        assert stats["gaps"] == {"CORE": []}


class TestMissingOptionals:
    def test_no_tags_no_applies_to(self):
        """Rules with missing optional fields shouldn't crash stats."""
        coll = RuleCollection()
        from tooling._shared.types import Rule

        coll.rules["T-001"] = Rule(
            id="T-001", priority=3, rule="R.", violation=["V"],
            check="C.", fix="F.", tags=[],
        )
        from tooling._shared.types import FileInfo

        coll.files.append(
            FileInfo(
                path="/f.json", domain="test", version="1.0",
                last_updated="2026-01-01", source="s",
                rule_count=1, line_count=1,
            )
        )
        coll.file_index["/f.json"] = ["T-001"]
        from tooling._shared.loader import _build_indices

        _build_indices(coll)

        stats = generate_statistics(coll)
        assert stats["total_rules"] == 1
        assert stats["tag_distribution"] == {}
        assert stats["applies_to_distribution"] == {}

    def test_non_list_fields_are_skipped(self):
        """Tags or applies_to that aren't lists shouldn't crash."""
        coll = RuleCollection()
        from tooling._shared.types import Rule

        coll.rules["T-001"] = Rule(
            id="T-001", priority=3, rule="R.", violation=["V"],
            check="C.", fix="F.", tags="not a list",
            applies_to="not a list",
        )
        from tooling._shared.types import FileInfo

        coll.files.append(
            FileInfo(
                path="/f.json", domain="test", version="1.0",
                last_updated="2026-01-01", source="s",
                rule_count=1, line_count=1,
            )
        )
        coll.file_index["/f.json"] = ["T-001"]
        from tooling._shared.loader import _build_indices

        _build_indices(coll)

        stats = generate_statistics(coll)
        assert stats["total_rules"] == 1
        assert stats["tag_distribution"] == {}
        assert stats["applies_to_distribution"] == {}
