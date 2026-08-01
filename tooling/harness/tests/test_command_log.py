"""Unit tests for the E3 command log (harness §6.1/§6.2.4)."""

import json

from tooling.harness.util.proc import CommandLog, run_cmd


def test_command_log_records_all_five_fields(tmp_path):
    log = CommandLog(tmp_path / "command.log")
    log.run(["sh", "-c", "echo hello"])
    log.run(["true"])
    records = log.records()
    assert len(records) == 2
    for record in records:
        assert set(record) == {
            "command",
            "exit_code",
            "stdout",
            "stderr",
            "wall_time",
            "started_at",
            "ended_at",
        }
        assert isinstance(record["exit_code"], int)
        assert isinstance(record["wall_time"], float)
        assert isinstance(record["stdout"], str)
        assert isinstance(record["stderr"], str)
    assert records[0]["stdout"] == "hello\n"


def test_command_log_records_failures_and_empty_output(tmp_path):
    log = CommandLog(tmp_path / "command.log")
    result = log.run(["sh", "-c", "echo oops >&2; exit 3"])
    assert result.exit_code == 3
    log.run(["true"])
    records = log.records()
    assert records[0]["exit_code"] == 3
    assert "oops" in records[0]["stderr"]
    assert records[1]["exit_code"] == 0
    assert records[1]["stdout"] == ""


def test_command_log_records_manual_record(tmp_path):
    log = CommandLog(tmp_path / "command.log")
    result = run_cmd(["true"])
    log.record(result)
    assert log.records()[0]["exit_code"] == 0


def test_command_log_missing_file_returns_empty(tmp_path):
    log = CommandLog(tmp_path / "nope.log")
    assert log.records() == []


def test_command_log_jsonl_format(tmp_path):
    log = CommandLog(tmp_path / "command.log")
    log.run(["true"])
    for line in (tmp_path / "command.log").read_text().splitlines():
        json.loads(line)  # every line is valid JSON
