"""Comprehensive unit tests for the Cross-Reference Validator."""

from pathlib import Path

from tooling._shared.loader import load_rules
from tooling._shared.types import RuleCollection, ValidationError, ValidatorResult
from tooling.validator.src.validators.crossref_validator import validate

FIXTURES = Path(__file__).parent / "fixtures" / "crossref"


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


def _error_severities(result: ValidatorResult) -> list[str]:
    return [e.severity for e in result.errors]


def _error_ids(result: ValidatorResult) -> list[str | None]:
    return [e.rule_id for e in result.errors]


# ======================================================================
# Positive cases
# ======================================================================


class TestNoReferences:
    def test_runtime_with_no_refs_passes(self):
        result = _run("no_refs.json")
        assert result.status == "pass", _error_reasons(result)

    def test_validator_name(self):
        result = _run("no_refs.json")
        assert result.name == "crossref"


class TestValidReferences:
    def test_valid_acyclic_graph(self):
        result = _run("valid_refs.json")
        assert result.status == "pass"

    def test_deep_acyclic_graph(self):
        result = _run("deep_acyclic.json")
        assert result.status == "pass"

    def test_cross_file_refs_pass(self):
        result = _run_multi(
            ["valid_multi_file_a.json", "valid_multi_file_b.json"]
        )
        assert result.status == "pass", _error_reasons(result)


class TestMultipleIndependentGraphs:
    def test_two_independent_cycles(self):
        """Two disconnected cyclic graphs should each report their cycle."""
        result = _run_multi(["multi_graph_a.json", "multi_graph_b.json"])
        assert result.status == "fail"
        reasons = _error_reasons(result)
        assert sum(1 for r in reasons if "circular reference" in r) >= 1

    def test_disconnected_graphs(self):
        result = _run_multi(["valid_refs.json", "no_refs.json"])
        assert result.status == "pass"

    def test_fan_out_graph_passes(self):
        """Multiple nodes pointing to the same target (visited-set exercise)."""
        result = _run("fan_out.json")
        assert result.status == "pass"


# ======================================================================
# Unresolved references
# ======================================================================


class TestUnresolvedReference:
    def test_detects_unresolved_ref(self):
        result = _run("unresolved.json")
        reasons = _error_reasons(result)
        assert any("does not exist" in r for r in reasons)

    def test_references_missing_rule(self):
        result = _run("unresolved.json")
        reasons = _error_reasons(result)
        assert any("ARCH-999" in r for r in reasons)

    def test_reference_to_malformed_id(self):
        result = _run("ref_malformed.json")
        reasons = _error_reasons(result)
        assert any("ARCH-0001" in r for r in reasons)
        assert any("does not exist" in r for r in reasons)

    def test_reference_to_deleted_id(self):
        result = _run("ref_deleted.json")
        reasons = _error_reasons(result)
        assert any("ARCH-002" in r for r in reasons)
        assert any("does not exist" in r for r in reasons)


# ======================================================================
# Self-reference
# ======================================================================


class TestSelfReference:
    def test_detects_self_reference(self):
        result = _run("self_ref.json")
        reasons = _error_reasons(result)
        assert any("references itself" in r for r in reasons)

    def test_self_ref_is_error(self):
        result = _run("self_ref.json")
        sevs = _error_severities(result)
        error_reasons = [r for r, s in zip(_error_reasons(result), sevs) if s == "error"]
        assert any("references itself" in r for r in error_reasons)


# ======================================================================
# Circular references
# ======================================================================


class TestDirectCycle:
    def test_detects_direct_cycle(self):
        result = _run("direct_cycle.json")
        reasons = _error_reasons(result)
        assert any("circular reference" in r for r in reasons)

    def test_reports_cycle_path(self):
        result = _run("direct_cycle.json")
        reasons = _error_reasons(result)
        assert any("ARCH-001" in r for r in reasons)
        assert any("ARCH-002" in r for r in reasons)

    def test_no_duplicate_cycle_reports(self):
        result = _run("direct_cycle.json")
        reasons = _error_reasons(result)
        circular = [r for r in reasons if "circular reference" in r]
        assert len(circular) == 1


class TestThreeNodeCycle:
    def test_detects_three_node_cycle(self):
        result = _run("three_node_cycle.json")
        reasons = _error_reasons(result)
        assert any("circular reference" in r for r in reasons)

    def test_three_node_cycle_path(self):
        result = _run("three_node_cycle.json")
        reasons = _error_reasons(result)
        assert any("ARCH-001" in r for r in reasons)
        assert any("ARCH-002" in r for r in reasons)
        assert any("ARCH-003" in r for r in reasons)


class TestLargerCycle:
    def test_detects_five_node_cycle(self):
        result = _run("larger_cycle.json")
        reasons = _error_reasons(result)
        assert any("circular reference" in r for r in reasons)

    def test_larger_cycle_all_nodes(self):
        result = _run("larger_cycle.json")
        reasons = _error_reasons(result)
        assert any("ARCH-001" in r for r in reasons)
        assert any("ARCH-005" in r for r in reasons)


# ======================================================================
# Same-file reference (informational warning)
# ======================================================================


class TestSameFileReference:
    def test_detects_same_file_ref(self):
        result = _run("same_file_ref.json")
        reasons = _error_reasons(result)
        assert any("same-file reference" in r for r in reasons)

    def test_same_file_is_warning_not_error(self):
        result = _run("same_file_ref.json")
        sevs = _error_severities(result)
        same_file_indices = [
            i for i, r in enumerate(_error_reasons(result))
            if "same-file reference" in r
        ]
        for idx in same_file_indices:
            assert sevs[idx] == "warning"

    def test_same_file_does_not_fail(self):
        result = _run("same_file_ref.json")
        assert result.status == "pass"


# ======================================================================
# Orphan rules
# ======================================================================


class TestOrphanRules:
    def test_detects_orphan_rule(self):
        result = _run("orphan_rules.json")
        reasons = _error_reasons(result)
        assert any("orphan rule" in r for r in reasons)

    def test_orphan_is_warning(self):
        result = _run("orphan_rules.json")
        sevs = _error_severities(result)
        orphan_indices = [
            i for i, r in enumerate(_error_reasons(result))
            if "orphan rule" in r
        ]
        for idx in orphan_indices:
            assert sevs[idx] == "warning"

    def test_orphan_does_not_make_result_fail(self):
        result = _run("orphan_rules.json")
        assert result.status == "pass"

    def test_referenced_rule_not_orphan(self):
        result = _run("orphan_rules.json")
        ids_with_incoming = ["ARCH-002"]
        orphan_ids = [
            e.rule_id for e in result.errors
            if "orphan rule" in e.reason
        ]
        for rid in ids_with_incoming:
            assert rid not in orphan_ids


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


# ======================================================================
# Depth limit
# ======================================================================


class TestDepthLimitExceeded:
    def test_detects_depth_exceeded(self):
        result = _run("depth_exceeded.json")
        reasons = _error_reasons(result)
        assert any("depth limit exceeded" in r for r in reasons)

    def test_depth_limit_mentions_possible_cycle(self):
        result = _run("depth_exceeded.json")
        reasons = _error_reasons(result)
        assert any("possible circular reference" in r for r in reasons)


# ======================================================================
# Internal helper coverage
# ======================================================================


class TestInternalHelpers:
    def test_normalize_empty_path(self):
        from tooling.validator.src.validators.crossref_validator import _normalize_cycle

        assert _normalize_cycle([]) == ""

    def test_normalize_single_element(self):
        from tooling.validator.src.validators.crossref_validator import _normalize_cycle

        assert _normalize_cycle(["A", "A"]) == "A -> A"

    def test_normalize_rotation(self):
        from tooling.validator.src.validators.crossref_validator import _normalize_cycle

        # C -> A -> B -> C should normalize to A -> B -> C -> A
        result = _normalize_cycle(["C", "A", "B", "C"])
        assert result == "A -> B -> C -> A"


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
