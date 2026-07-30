"""Tests for tooling._shared.types."""

from tooling._shared.types import (
    ExitCode,
    FileInfo,
    ReportFormat,
    Rule,
    RuleCollection,
    ValidationError,
    ValidatorResult,
)


class TestRule:
    def test_minimal_rule(self):
        r = Rule(id="TEST-001", priority=3, rule="Do the thing.",
                 violation=["bad"], check="verify", fix="fix it", tags=["a"])
        assert r.id == "TEST-001"
        assert r.priority == 3
        assert r.rule == "Do the thing."
        assert r.violation == ["bad"]
        assert r.check == "verify"
        assert r.fix == "fix it"
        assert r.tags == ["a"]
        assert r.applies_to is None
        assert r.exceptions is None
        assert r.see_also is None
        assert r.rationale is None
        assert r.source is None
        assert r.file_path == ""
        assert r.file_domain == ""

    def test_full_rule(self):
        r = Rule(
            id="ARCH-001",
            priority=2,
            rule="Game.php is orchestration only.",
            violation=["SQL in Game.php"],
            check="Count lines.",
            fix="Extract to Manager.",
            tags=["architecture"],
            applies_to=["Game.php"],
            exceptions=["None"],
            see_also=["CORE-002"],
            rationale="Keeps Game.php thin.",
            source="doc.md",
            file_path="/path/to/architecture.json",
            file_domain="architecture",
        )
        assert r.applies_to == ["Game.php"]
        assert r.exceptions == ["None"]
        assert r.see_also == ["CORE-002"]
        assert r.rationale == "Keeps Game.php thin."
        assert r.source == "doc.md"
        assert r.file_path == "/path/to/architecture.json"
        assert r.file_domain == "architecture"


class TestFileInfo:
    def test_minimal(self):
        fi = FileInfo(path="/a.json", domain="arch", version="1.0",
                       last_updated="2026-01-01", source="doc.md")
        assert fi.path == "/a.json"
        assert fi.rule_count == 0
        assert fi.line_count == 0

    def test_full(self):
        fi = FileInfo(path="/a.json", domain="arch", version="1.0",
                       last_updated="2026-01-01", source="doc.md",
                       rule_count=5, line_count=100)
        assert fi.rule_count == 5
        assert fi.line_count == 100


class TestRuleCollection:
    def test_empty(self):
        rc = RuleCollection()
        assert rc.rules == {}
        assert rc.files == []
        assert rc.crossref_index == {}
        assert rc.domain_index == {}
        assert rc.file_index == {}

    def test_with_data(self):
        r = Rule(id="T-001", priority=1, rule="x", violation=["v"],
                 check="c", fix="f", tags=["t"])
        rc = RuleCollection(
            rules={"T-001": r},
            files=[],
            crossref_index={"T-002": ["T-001"]},
            domain_index={"test": ["T-001"]},
            file_index={"/f.json": ["T-001"]},
        )
        assert rc.rules["T-001"].id == "T-001"
        assert rc.crossref_index["T-002"] == ["T-001"]
        assert rc.domain_index["test"] == ["T-001"]
        assert rc.file_index["/f.json"] == ["T-001"]


class TestValidationError:
    def test_minimal(self):
        e = ValidationError(validator="schema")
        assert e.validator == "schema"
        assert e.rule_id is None
        assert e.file is None
        assert e.reason == ""
        assert e.severity == "error"

    def test_full(self):
        e = ValidationError(
            validator="crossref",
            rule_id="ARCH-001",
            file="arch.json",
            reason="unresolved reference",
            severity="warning",
        )
        assert e.validator == "crossref"
        assert e.rule_id == "ARCH-001"
        assert e.file == "arch.json"
        assert e.reason == "unresolved reference"
        assert e.severity == "warning"


class TestValidatorResult:
    def test_default_pass(self):
        vr = ValidatorResult(name="schema")
        assert vr.name == "schema"
        assert vr.status == "pass"
        assert vr.errors == []

    def test_fail(self):
        e = ValidationError(validator="schema", reason="bad field")
        vr = ValidatorResult(name="schema", status="fail", errors=[e])
        assert vr.status == "fail"
        assert len(vr.errors) == 1


class TestReportFormat:
    def test_values(self):
        assert ReportFormat.HUMAN.value == "human"
        assert ReportFormat.JSON.value == "json"
        assert ReportFormat.CI.value == "ci"


class TestExitCode:
    def test_values(self):
        assert ExitCode.SUCCESS == 0
        assert ExitCode.FAILURE == 1
        assert ExitCode.ERROR == 2
