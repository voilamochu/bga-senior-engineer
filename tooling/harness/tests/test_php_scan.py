"""Unit tests for the basic PHP scanner (MS-06 php_scan helper)."""

import pytest

from tooling.harness.validation.checks.php_scan import (
    iter_methods,
    mask_text,
    normalize_payload,
    notify_calls,
    payload_keys,
)
from tooling.harness.tests.validation_fixtures import PASSING_GAME_PHP

SAMPLE = """<?php
class Game
{
    public function first($player)
    {
        $this->notifyAllPlayers('alpha', clienttranslate('${player_name} hit'), [
            'player_id' => $player->id,
            'value' => 1,
        ]);
    }

    public function second($player)
    {
        // a comment containing 'alpha' and [ braces {
        $this->notifyPlayer($player->id, 'beta', '', ['deck' => 3]);
    }

    private function helper()
    {
        /* multi
           line comment */
        $this->notify->all('gamma', 'msg', array('x' => 1));
    }
}
"""


def test_mask_text_preserves_lines_and_hides_string_contents():
    masked = mask_text(SAMPLE)
    assert masked.count("\n") == SAMPLE.count("\n")
    assert "alpha" not in masked.replace("'", "")
    assert "player_id" not in masked


def test_iter_methods_extracts_three_methods():
    methods = iter_methods(SAMPLE)
    assert [m["name"] for m in methods] == ["first", "second", "helper"]
    assert methods[0]["start_line"] == 4
    assert "notifyAllPlayers" in methods[0]["body"]
    assert "alpha" in methods[0]["body"]


def test_notify_calls_extracts_types_api_and_lines():
    calls = notify_calls(SAMPLE)
    assert [(c["type"], c["api"], c["line"]) for c in calls] == [
        ("alpha", "notifyAllPlayers", 6),
        ("beta", "notifyPlayer", 15),
        ("gamma", "notify->all", 22),
    ]
    assert [c["method"] for c in calls] == ["first", "second", "helper"]


def test_notify_calls_extracts_payload_and_message():
    calls = notify_calls(SAMPLE)
    alpha = calls[0]
    assert alpha["payload"].startswith("[")
    assert payload_keys(alpha["payload"]) == ["player_id", "value"]
    assert "player_name" in alpha["message"]
    beta = calls[1]
    assert payload_keys(beta["payload"]) == ["deck"]


def test_array_payload_style():
    calls = notify_calls(SAMPLE)
    gamma = calls[2]
    assert gamma["payload"].startswith("(")
    assert payload_keys(gamma["payload"]) == ["x"]


def test_payload_keys_ignore_literal_values():
    payload = "['a' => 1, 'b' => 'two', 3 => 'x', 'c' => $var]"
    assert payload_keys(payload) == ["a", "b", "c"]


def test_normalize_payload_replaces_variables_and_collapses_whitespace():
    payload = "[ 'a' => $player->name,\n    'b' => $other->value ]"
    normalized = normalize_payload(payload)
    assert "$X->name" in normalized
    assert "$other" not in normalized
    assert "\n" not in normalized


def test_normalize_payload_keeps_literals_and_keys():
    payload = "['a' => 1, 'b' => 'kept literal']"
    assert normalize_payload(payload) == "['a' => 1, 'b' => 'kept literal']"


def test_scanner_on_fixture_a_game_php():
    methods = iter_methods(PASSING_GAME_PHP)
    assert len(methods) == 10
    calls = notify_calls(PASSING_GAME_PHP)
    types = sorted({c["type"] for c in calls})
    assert types == [
        "cardKept",
        "governorRewardGranted",
        "influenceChanged",
        "labOutputActivated",
        "reputationUpdate",
        "synergyMilestoneReached",
    ]
    # every call is inside a method
    assert all(c["method"] for c in calls)


def test_scanner_ignores_comments_and_strings_with_braces():
    tricky = """<?php
class Game
{
    public function m()
    {
        // function not_a_method( {
        $s = 'function fake( {';
        $t = "function fake2( {";
        $this->notifyAllPlayers('type', 'msg with } and {', []);
    }
}
"""
    assert [m["name"] for m in iter_methods(tricky)] == ["m"]
    calls = notify_calls(tricky)
    assert len(calls) == 1
    assert calls[0]["type"] == "type"
    assert calls[0]["payload"] == "[]"


def test_notify_call_without_payload():
    text = "<?php class G { function m() { $this->notifyPlayer(1, 't', '', []); } }"
    calls = notify_calls(text)
    assert calls[0]["payload"] == "[]"


@pytest.mark.parametrize("invalid", ["", "no php here"])
def test_empty_or_non_php_text_yields_no_results(invalid):
    assert iter_methods(invalid) == []
    assert notify_calls(invalid) == []
