"""Unit tests for the NOT-02 task checks (MS-06, MVB-017)."""

from pathlib import Path

import pytest

from tooling.harness.validation.checks.not_02 import (
    CONSOLIDATED_PATTERNS,
    GAME_PHP_RELPATH,
    run_call_site_counts,
    run_duplication_scan,
    run_payload_parity,
    run_single_source,
    run_task_checks,
)
from tooling.harness.tests.validation_fixtures import (
    DUPLICATED_GAME_PHP,
    GAMELOG_TEXT,
    PASSING_GAME_PHP,
    PASSING_SUBSYSTEMS_MD,
)


def _check(results, check_id):
    return next(r for r in results if r.id == check_id)


class TestSingleSource:
    def test_passing_submission_has_one_sender_per_pattern(self):
        result = run_single_source(PASSING_GAME_PHP)
        assert result.verdict == "PASS"
        assert result.findings == []

    def test_duplicated_block_fails(self):
        result = run_single_source(DUPLICATED_GAME_PHP)
        assert result.verdict == "FAIL"
        assert any("labOutputActivated" in f for f in result.findings)
        assert any("2" in f for f in result.findings)

    def test_no_sender_fails(self):
        result = run_single_source("<?php class Game { public function m() {} }")
        assert result.verdict == "FAIL"
        assert any("no sending method" in f for f in result.findings)
        # all four patterns must be flagged
        assert len(result.findings) == len(CONSOLIDATED_PATTERNS)


class TestCallSiteCounts:
    def test_passing_submission_counts_match(self):
        result = run_call_site_counts(PASSING_GAME_PHP)
        assert result.verdict == "PASS"
        assert result.findings == []

    def test_wrong_call_site_count_fails(self):
        game = PASSING_GAME_PHP.replace(
            "        $this->notifyLabOutputActivated($player, $state, 4);\n"
            "        $this->notifySynergyMilestone($player, $state);",
            "        $this->notifyLabOutputActivated($player, $state, 4);",
        )
        result = run_call_site_counts(game)
        assert result.verdict == "FAIL"
        assert any("synergyMilestone" in f and "expected 2" in f for f in result.findings)

    def test_not_evaluated_when_single_source_fails(self):
        result = run_call_site_counts(DUPLICATED_GAME_PHP)
        assert result.verdict == "FAIL"
        assert any("not evaluated" in f for f in result.findings)


class TestDuplicationScan:
    def test_passing_submission_has_no_duplicates(self):
        result = run_duplication_scan(PASSING_GAME_PHP)
        assert result.verdict == "PASS"
        assert result.blocking is False

    def test_duplicated_block_is_found(self):
        result = run_duplication_scan(DUPLICATED_GAME_PHP)
        assert result.verdict == "FAIL"
        assert result.blocking is False
        assert any("labOutputActivated" in f for f in result.findings)

    def test_same_type_different_message_not_flagged(self):
        game = PASSING_GAME_PHP.replace(
            "'${player_name} gained influence from market activity'",
            "'${player_name} gained influence from market activity copy'",
        )
        result = run_duplication_scan(game)
        # market vs synergy influenceChanged payloads are identical, but
        # the messages differ, so they are distinct blocks
        assert result.verdict == "PASS"


class TestPayloadParity:
    def test_without_gamelog_is_recorded_substitution(self):
        result = run_payload_parity([])
        assert result.verdict == "PASS"
        assert result.blocking is True
        assert result.substituted is True
        assert "substitution" in result.substitution_reason
        assert result.evidence == []

    def test_with_gamelog_records_substitution_and_paths(self):
        result = run_payload_parity(["evidence/e8-diff-bundle/gamelog.json"])
        assert result.substituted is True
        assert result.evidence == ["evidence/e8-diff-bundle/gamelog.json"]
        assert "gamelog" in result.substitution_reason


class TestTaskChecks:
    def _bundle(self, tmp_path, files):
        bundle = tmp_path / "diff"
        bundle.mkdir()
        for relpath, content in files.items():
            path = bundle / relpath
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(content, encoding="utf-8")
        return bundle

    def test_missing_diff_bundle_blocks_content_checks(self, tmp_path):
        results = run_task_checks(diff_bundle=None, changed_paths=[], gamelogs=[])
        assert [r.id for r in results] == ["NOT02-A", "NOT02-B", "NOT02-C", "NOT02-D"]
        assert results[0].verdict == "BLOCKED"
        assert results[3].verdict == "PASS"  # parity still substituted

    def test_missing_game_php_fails_content_checks(self, tmp_path):
        bundle = self._bundle(tmp_path, {"other.php": "<?php\n"})
        results = run_task_checks(
            diff_bundle=bundle, changed_paths=["other.php"], gamelogs=[]
        )
        assert results[0].verdict == "FAIL"
        assert GAME_PHP_RELPATH in results[0].detail

    def test_passing_submission_passes_all(self, tmp_path):
        bundle = self._bundle(tmp_path, {GAME_PHP_RELPATH: PASSING_GAME_PHP})
        results = run_task_checks(
            diff_bundle=bundle,
            changed_paths=[GAME_PHP_RELPATH],
            gamelogs=[],
        )
        assert all(r.verdict == "PASS" for r in results)
        assert results[0].evidence == [f"evidence/e8-diff-bundle/{GAME_PHP_RELPATH}"]

    def test_duplicated_submission_rejects(self, tmp_path):
        bundle = self._bundle(tmp_path, {GAME_PHP_RELPATH: DUPLICATED_GAME_PHP})
        results = run_task_checks(
            diff_bundle=bundle,
            changed_paths=[GAME_PHP_RELPATH],
            gamelogs=[],
        )
        assert results[0].verdict == "FAIL"  # single-source
        assert results[1].verdict == "FAIL"  # call-site not evaluated
        assert results[2].verdict == "FAIL"  # duplication scan
        assert results[3].verdict == "PASS"  # parity substitution

    def test_gamelog_discovery_feeds_parity(self, tmp_path):
        bundle = self._bundle(
            tmp_path,
            {GAME_PHP_RELPATH: PASSING_GAME_PHP, "gamelog.json": GAMELOG_TEXT},
        )
        results = run_task_checks(
            diff_bundle=bundle,
            changed_paths=[GAME_PHP_RELPATH, "gamelog.json"],
            gamelogs=["evidence/e8-diff-bundle/gamelog.json"],
        )
        parity = _check(results, "NOT02-D")
        assert parity.substituted is True
        assert parity.evidence == ["evidence/e8-diff-bundle/gamelog.json"]


def test_checks_are_deterministic():
    first = run_single_source(PASSING_GAME_PHP).to_dict()
    second = run_single_source(PASSING_GAME_PHP).to_dict()
    assert first == second
