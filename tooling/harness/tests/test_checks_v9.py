"""Tests for the V9 hidden-info payload-key scan (MS-06, MVB-017)."""

from pathlib import Path

from tooling.harness.validation.checks.v9 import (
    PRIVATE_PAYLOAD_KEYS,
    run_v9,
    run_v9_from_diff_bundle,
)
from tooling.harness.tests.validation_fixtures import (
    LEAKY_GAME_PHP,
    PASSING_GAME_PHP,
)


def _pair(text):
    return [("modules/php/Game.php", text)]


class TestRunV9:
    def test_clean_submission_passes(self):
        result = run_v9(_pair(PASSING_GAME_PHP))
        assert result.verdict == "PASS"
        assert result.blocking is True
        assert result.findings == []
        assert "0 leaks" in result.detail

    def test_private_payload_key_fails(self):
        result = run_v9(_pair(LEAKY_GAME_PHP))
        assert result.verdict == "FAIL"
        assert result.blocking is True
        assert any("'hand'" in finding for finding in result.findings)
        assert any("cardKept" in finding for finding in result.findings)
        assert any(":98" in finding for finding in result.findings)

    def test_private_key_in_non_notify_code_not_flagged(self):
        text = PASSING_GAME_PHP.replace(
            "    private function notifyCardKept($player, $state, $path)\n",
            "    private function notifyCardKept($player, $state, $path)\n"
            "    {\n"
            "        $hand = $player->hand;\n",
        ).replace(
            "        $this->notifyAllPlayers('cardKept', clienttranslate",
            "        unset($hand);\n        $this->notifyAllPlayers('cardKept', clienttranslate",
        )
        result = run_v9(_pair(text))
        assert result.verdict == "PASS"

    def test_no_changed_php_files_passes_vacuously(self):
        result = run_v9([])
        assert result.verdict == "PASS"
        assert "0 notification payload(s) inspected" in result.detail

    def test_findings_are_sorted_and_deterministic(self):
        first = run_v9(_pair(LEAKY_GAME_PHP))
        second = run_v9(_pair(LEAKY_GAME_PHP))
        assert first.to_dict() == second.to_dict()
        assert first.findings == sorted(first.findings)

    def test_blocklist_is_documented_c4_concepts(self):
        assert set(PRIVATE_PAYLOAD_KEYS) == {"hand", "hidden", "unrevealed", "draft"}


class TestRunV9FromDiffBundle:
    def test_missing_diff_bundle_blocks(self, tmp_path):
        result = run_v9_from_diff_bundle(None, [])
        assert result.verdict == "BLOCKED"
        assert "diff bundle" in result.detail

    def test_scans_changed_php_files_only(self, tmp_path):
        bundle = tmp_path / "diff"
        bundle.mkdir()
        (bundle / "modules" / "php").mkdir(parents=True)
        (bundle / "modules" / "php" / "Game.php").write_text(
            PASSING_GAME_PHP, encoding="utf-8"
        )
        (bundle / "modules" / "js" ).mkdir(parents=True)
        (bundle / "modules" / "js" / "Game.js").write_text(
            "export const x = 1;\n", encoding="utf-8"
        )
        result = run_v9_from_diff_bundle(bundle, ["modules/php/Game.php", "modules/js/Game.js"])
        assert result.verdict == "PASS"
        assert result.evidence == ["evidence/e8-diff-bundle/modules/php/Game.php"]
