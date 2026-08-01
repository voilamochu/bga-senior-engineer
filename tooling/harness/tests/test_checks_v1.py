"""Tests for the V1 runtime-validator check (MS-06).

V1 is exercised through the validator's public CLI interface (a real
subprocess), never through internal implementation details.
"""

import shutil
from pathlib import Path

import pytest

from tooling.harness.util.proc import CommandLog
from tooling.harness.validation.checks.v1 import V1_VALIDATORS, run_v1

VALIDATOR_FIXTURES = Path(__file__).resolve().parents[2] / "validator" / "tests" / "fixtures"


def _run_v1(rules_root, tmp_path):
    command_log = CommandLog(tmp_path / "command.log")
    return run_v1(rules_root=rules_root, command_log=command_log)


def test_v1_passes_on_frozen_skill_rules(tmp_path):
    """V1 against the committed skill rules passes (deterministic fixture)."""
    from tooling.harness.config import default_skill_root

    rules = default_skill_root() / "rules"
    result = _run_v1(rules, tmp_path)
    assert result.verdict == "PASS"
    assert result.blocking is True
    assert result.exit_code == 0
    assert result.version == "1.0.0"
    assert "tooling.validator" in result.executed_by
    assert V1_VALIDATORS in result.executed_by
    assert "Schema Validation" in result.raw_text
    # raw output is persisted as the check's record
    assert result.raw_text.strip()


def test_v1_fails_on_violating_rules(tmp_path):
    """The validator itself reports violations -> FAIL (blocking)."""
    bad_rules = tmp_path / "bad-rules"
    bad_rules.mkdir()
    (bad_rules / "bad_priority.json").write_text(
        (VALIDATOR_FIXTURES / "bad_priority.json").read_text(encoding="utf-8"),
        encoding="utf-8",
    )
    result = _run_v1(bad_rules, tmp_path)
    assert result.verdict == "FAIL"
    assert result.blocking is True
    assert result.exit_code == 1
    assert result.findings  # failing validators recorded


def test_v1_blocks_on_missing_rules_directory(tmp_path):
    result = _run_v1(tmp_path / "does-not-exist", tmp_path)
    assert result.verdict == "BLOCKED"
    assert result.blocking is True
    assert "rules directory missing" in result.detail


def test_v1_blocks_on_malformed_rules(tmp_path):
    malformed = tmp_path / "malformed-rules"
    malformed.mkdir()
    (malformed / "broken.json").write_text("{ not json", encoding="utf-8")
    result = _run_v1(malformed, tmp_path)
    assert result.verdict == "BLOCKED"
    assert result.blocking is True


def test_v1_records_command_in_command_log(tmp_path):
    from tooling.harness.config import default_skill_root

    command_log = CommandLog(tmp_path / "command.log")
    run_v1(rules_root=default_skill_root() / "rules", command_log=command_log)
    records = command_log.records()
    assert len(records) == 1
    assert "tooling.validator" in records[0]["command"]
    assert records[0]["exit_code"] == 0
