"""Unit tests for MVB-003: hashing, clock, logging, and the command runner."""

import hashlib
import json
import re
from datetime import datetime, timezone
from pathlib import Path

import pytest

from tooling.harness.util.clock import (
    format_iso,
    is_iso_utc,
    is_run_id_timestamp,
    run_id_timestamp,
    utc_now,
    utc_now_iso,
)
from tooling.harness.util.hash import (
    KNOWN_EMPTY_SHA256,
    sha256_bytes,
    sha256_file,
    sha256_text,
)
from tooling.harness.util.log import HarnessLog, harness_log
from tooling.harness.util.proc import TIMEOUT_EXIT_CODE, run_cmd

ISO_PATTERN = re.compile(r"^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$")

SHA256_ABC = hashlib.sha256(b"abc").hexdigest()


# ----------------------------------------------------------------------
# hash.py
# ----------------------------------------------------------------------


def test_sha256_known_vector_empty():
    assert sha256_bytes(b"") == KNOWN_EMPTY_SHA256 == (
        "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
    )


def test_sha256_known_vector_abc():
    assert sha256_bytes(b"abc") == SHA256_ABC
    assert sha256_text("abc") == SHA256_ABC


def test_sha256_file(tmp_path):
    path = tmp_path / "data.bin"
    path.write_bytes(b"file contents")
    assert sha256_file(path) == hashlib.sha256(b"file contents").hexdigest()


def test_sha256_file_matches_bytes_helper(tmp_path):
    path = tmp_path / "data.bin"
    path.write_bytes(b"same bytes")
    assert sha256_file(path) == sha256_bytes(b"same bytes")


# ----------------------------------------------------------------------
# clock.py
# ----------------------------------------------------------------------


def test_utc_now_iso_format():
    value = utc_now_iso()
    assert ISO_PATTERN.match(value)
    assert value.endswith("Z")


def test_utc_now_is_utc_aware():
    now = utc_now()
    assert now.tzinfo is not None
    assert now.utcoffset().total_seconds() == 0


def test_format_iso_fixed_datetime():
    dt = datetime(2026, 7, 31, 12, 0, 0, 123456, tzinfo=timezone.utc)
    assert format_iso(dt) == "2026-07-31T12:00:00.123456Z"


def test_format_iso_handles_naive_datetime():
    dt = datetime(2026, 7, 31, 12, 0, 0, tzinfo=timezone.utc)
    naive = datetime(2026, 7, 31, 12, 0, 0)
    assert format_iso(naive) == format_iso(dt)


def test_run_id_timestamp_format():
    dt = datetime(2026, 7, 31, 12, 0, 0, tzinfo=timezone.utc)
    assert run_id_timestamp(dt) == "20260731T120000Z"


def test_is_iso_utc_accepts_and_rejects():
    assert is_iso_utc("2026-07-31T12:00:00Z")
    assert is_iso_utc("2026-07-31T12:00:00.123456Z")
    assert not is_iso_utc("2026-07-31 12:00:00Z")
    assert not is_iso_utc("2026-07-31T12:00:00+00:00")
    assert not is_iso_utc("nope")
    assert is_run_id_timestamp("20260731T120000Z")
    assert not is_run_id_timestamp("2026-07-31T12:00:00Z")


# ----------------------------------------------------------------------
# log.py
# ----------------------------------------------------------------------


def test_log_writes_jsonl_record(tmp_path):
    log_file = tmp_path / "harness.log"
    log = HarnessLog(log_file)
    log.info("hello")
    records = [json.loads(line) for line in log_file.read_text().splitlines()]
    assert len(records) == 1
    assert records[0]["level"] == "INFO"
    assert records[0]["message"] == "hello"
    assert ISO_PATTERN.match(records[0]["timestamp"])


def test_log_appends_multiple_records(tmp_path):
    log_file = tmp_path / "harness.log"
    log = HarnessLog(log_file)
    log.warning("w1")
    log.error("e1")
    records = [json.loads(line) for line in log_file.read_text().splitlines()]
    assert [r["level"] for r in records] == ["WARNING", "ERROR"]
    assert records[0]["timestamp"] <= records[1]["timestamp"]


def test_log_mirrors_to_stdout(tmp_path, capsys):
    log = HarnessLog(tmp_path / "h.log")
    log.info("visible")
    captured = capsys.readouterr()
    assert "INFO" in captured.out
    assert "visible" in captured.out


def test_log_rejects_unknown_level(tmp_path):
    log = HarnessLog(tmp_path / "h.log")
    with pytest.raises(ValueError):
        log.log("TRACE", "x")


def test_harness_log_run_dir(tmp_path):
    run_dir = tmp_path / "run-x"
    log = harness_log(run_dir)
    log.debug("in run")
    assert (run_dir / "protocol" / "harness.log").is_file()


def test_harness_log_stdout_only():
    log = harness_log()
    log.info("no file")
    assert log._log_file is None


# ----------------------------------------------------------------------
# proc.py
# ----------------------------------------------------------------------


def test_run_cmd_success_with_output():
    result = run_cmd(["sh", "-c", "echo hello"])
    assert result.exit_code == 0
    assert result.stdout == "hello\n"
    assert result.stderr == ""
    assert result.wall_time >= 0


def test_run_cmd_string_form():
    result = run_cmd("echo hello")
    assert result.exit_code == 0
    assert result.stdout == "hello\n"


def test_run_cmd_failing_command():
    result = run_cmd(["sh", "-c", "echo oops >&2; exit 3"])
    assert result.exit_code == 3
    assert result.stdout == ""
    assert "oops" in result.stderr


def test_run_cmd_empty_output():
    result = run_cmd(["true"])
    assert result.exit_code == 0
    assert result.stdout == ""
    assert result.stderr == ""


def test_run_cmd_timestamps_are_iso_utc():
    result = run_cmd(["true"])
    assert ISO_PATTERN.match(result.started_at)
    assert ISO_PATTERN.match(result.ended_at)
    assert result.ended_at >= result.started_at


def test_run_cmd_cwd(tmp_path):
    result = run_cmd(["pwd"], cwd=tmp_path)
    assert result.exit_code == 0
    assert str(tmp_path) in result.stdout


def test_run_cmd_writes_log_record(tmp_path):
    log_file = tmp_path / "command.log"
    run_cmd(["sh", "-c", "echo hi"], log_file=log_file)
    run_cmd(["true"], log_file=log_file)
    records = [json.loads(line) for line in log_file.read_text().splitlines()]
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
        assert record["wall_time"] >= 0
        assert ISO_PATTERN.match(record["started_at"])
        assert ISO_PATTERN.match(record["ended_at"])
    assert records[0]["stdout"] == "hi\n"


def test_run_cmd_timeout():
    result = run_cmd(["sh", "-c", "sleep 5"], timeout=0.2)
    assert result.exit_code == TIMEOUT_EXIT_CODE == -1
