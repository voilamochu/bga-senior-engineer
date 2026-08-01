"""Unit tests for the deterministic stub adapter."""

from tooling.harness.agent.adapter import SessionConfig
from tooling.harness.agent.stub_adapter import (
    DECLARATION,
    STUB_RAW_RESPONSE,
    STUB_TRANSCRIPT,
    StubAdapter,
)
from tooling.harness.runtime.run_dir import create_run_dir
from tooling.harness.util.proc import CommandLog


def _config(tmp_path) -> SessionConfig:
    run = create_run_dir("NOT-02", "demo-model", tmp_path / "runs")
    bundle = run.root / "protocol" / "prompt-bundle.txt"
    bundle.parent.mkdir(parents=True, exist_ok=True)
    bundle.write_text("bundle\n")
    return SessionConfig(
        platform="stub",
        agent_id="stub",
        model="demo-model",
        prompt_bundle=bundle,
        workspace_read=run.workspace_read,
        workspace_work=run.workspace_work,
        timeout_seconds=60,
        command_log=CommandLog(run.root / "protocol" / "command.log"),
    )


def test_stub_launch_writes_deterministic_submission(tmp_path):
    adapter = StubAdapter()
    config = _config(tmp_path)
    result = adapter.launch(config)
    assert result.exit_code == 0
    assert result.platform == "stub"
    work = config.workspace_work
    for name in ("reasoning.md", "architecture.md", "subsystems.md",
                 "testing-evidence.md", "validation-evidence.md",
                 "declaration.json"):
        assert (work / name).is_file()
    assert (work / "changes" / "notifications-helpers.php").is_file()
    assert result.transcript == STUB_TRANSCRIPT
    assert result.raw_response == STUB_RAW_RESPONSE


def test_stub_declaration_matches_contract(tmp_path):
    config = _config(tmp_path)
    StubAdapter().launch(config)
    import json

    declaration = json.loads(
        (config.workspace_work / "declaration.json").read_text()
    )
    assert declaration == DECLARATION
    assert declaration["status"] == "complete"
    assert declaration["task_id"] == "NOT-02"


def test_stub_commands_are_logged_with_all_fields(tmp_path):
    config = _config(tmp_path)
    StubAdapter().launch(config)
    records = config.command_log.records()
    assert len(records) >= 8  # mkdir + 6 files + declaration
    for record in records:
        assert set(record) >= {
            "command", "exit_code", "stdout", "stderr", "wall_time"
        }


def test_stub_replay_is_deterministic(tmp_path):
    first_config = _config(tmp_path)
    second_config = _config(tmp_path)
    first = StubAdapter().launch(first_config)
    second = StubAdapter().launch(second_config)
    # transcripts, raw responses, and submissions are byte-identical
    assert first.transcript == second.transcript
    assert first.raw_response == second.raw_response
    for name in ("reasoning.md", "architecture.md", "subsystems.md",
                 "testing-evidence.md", "validation-evidence.md",
                 "declaration.json"):
        assert (first_config.workspace_work / name).read_bytes() == (
            second_config.workspace_work / name).read_bytes()
    assert first.exit_code == second.exit_code
