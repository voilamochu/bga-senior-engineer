"""Unit tests for MVB-006: environment collection (§4.4–4.5)."""

import json

import pytest

from tooling.harness.config import network_policy
from tooling.harness.environment.collect import (
    ENVIRONMENT_FIELDS,
    EnvironmentError,
    collect_environment,
    load_environment,
    mismatched_tools,
    missing_tools,
    save_environment,
    validate_environment,
)
from tooling.harness.util.proc import CommandResult
from tooling.harness.tests.conftest import git

PHP7 = CommandResult(
    command="php -v",
    exit_code=0,
    stdout="PHP 7.4.1 (cli) (built: Jan 1 2020 00:00:00) (NTS)\n",
    stderr="",
    wall_time=0.01,
    started_at="2026-07-31T12:00:00Z",
    ended_at="2026-07-31T12:00:00Z",
)


def test_environment_contains_every_section_4_5_field(git_repo):
    env = collect_environment(git_repo)
    assert list(env) == list(ENVIRONMENT_FIELDS)


def test_tools_are_captured_not_hardcoded(git_repo):
    env = collect_environment(git_repo)
    tools = {t["name"]: t for t in env["tools"]}
    assert set(tools) == {"python3", "php", "node", "git", "opencode"}
    py = tools["python3"]
    assert py["present"] is True
    assert py["path"]
    assert "Python " in py["version"] and py["version"].count(".") >= 2
    assert py["version_ok"] is True
    assert py["required_version"] == "3.10+"
    git_tool = tools["git"]
    assert git_tool["present"] is True
    assert git_tool["version_ok"] is True
    assert git_tool["required_version"] == "any"
    # the execution platform is a required tool: present and versioned
    opencode_tool = tools["opencode"]
    assert opencode_tool["present"] is True
    assert opencode_tool["path"]
    assert opencode_tool["version"] and opencode_tool["version"].count(".") >= 2
    # version_ok semantics: a present tool either satisfies or mismatches
    for tool in env["tools"]:
        if tool["present"]:
            assert isinstance(tool["version_ok"], bool)
        else:
            assert tool["version_ok"] is False


def test_missing_tool_is_recorded(git_repo, monkeypatch):
    import shutil as shutil_module

    real_which = shutil_module.which
    monkeypatch.setattr(
        "tooling.harness.environment.collect.shutil.which",
        lambda name: None if name == "php" else real_which(name),
    )
    env = collect_environment(git_repo)
    php = {t["name"]: t for t in env["tools"]}["php"]
    assert php["present"] is False
    assert php["version"] is None
    assert php["path"] is None
    assert php["version_ok"] is False
    assert missing_tools(env) == ["php"]


def test_version_mismatch_is_recorded(git_repo, monkeypatch):
    def fake_run_cmd(cmd, **kwargs):
        if any("php" in str(part) for part in cmd):
            return PHP7
        from tooling.harness.util import proc as proc_module

        return proc_module.run_cmd(cmd, **kwargs)

    monkeypatch.setattr("tooling.harness.environment.collect.run_cmd", fake_run_cmd)
    env = collect_environment(git_repo)
    php = {t["name"]: t for t in env["tools"]}["php"]
    assert php["present"] is True
    assert php["version"].startswith("PHP 7.4")
    assert php["version_ok"] is False
    assert mismatched_tools(env) == ["php"]


def test_validator_version_is_recorded(git_repo):
    env = collect_environment(git_repo)
    assert isinstance(env["validator_version"], str)
    assert "Runtime Specification Validator" in env["validator_version"]
    assert "Version:" in env["validator_version"]


def test_reference_head_and_status_captured(git_repo):
    env = collect_environment(git_repo)
    assert env["reference_head"] == git(git_repo, "rev-parse", "HEAD").strip()
    assert env["reference_status"] == ""


def test_reference_status_records_dirty_state(git_repo):
    (git_repo / "file.txt").write_text("dirty\n", encoding="utf-8")
    env = collect_environment(git_repo)
    assert "file.txt" in env["reference_status"]


def test_os_is_captured(git_repo):
    env = collect_environment(git_repo)
    assert env["os"]["platform"]
    assert env["os"]["release"]
    assert env["os"]["architecture"]


def test_network_policy_default_disabled(git_repo):
    assert network_policy() == "disabled"
    env = collect_environment(git_repo)
    assert env["network"] == "disabled"
    assert collect_environment(git_repo, network="enabled")["network"] == "enabled"


def test_network_policy_from_settings(tmp_path, monkeypatch):
    settings_file = tmp_path / "settings.json"
    settings_file.write_text(json.dumps({"network": "enabled"}))
    monkeypatch.setattr("tooling.harness.config.default_settings_file", lambda: settings_file)
    assert network_policy() == "enabled"


def test_dependencies_recorded_empty(git_repo):
    assert collect_environment(git_repo)["dependencies"] == []


def test_save_load_validate_round_trip(git_repo, tmp_path):
    env = collect_environment(git_repo)
    path = tmp_path / "environment.json"
    save_environment(env, path)
    assert load_environment(path) == env
    with pytest.raises(EnvironmentError):
        validate_environment({})
    with pytest.raises(EnvironmentError):
        validate_environment({**env, "network": "sometimes"})
