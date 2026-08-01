"""Unit tests for the opencode platform adapter (MS-10B G2).

The real ``opencode`` binary is never invoked: a hermetic fake
executable shadows it on ``PATH`` and drives every adapter behavior —
argv construction, the run-scoped permission environment (G1), the
``--dir`` working-directory flag (G7), session-id parsing, the
transcript export path (including after a timeout kill, G5), and
failure handling.  No model, no provider, no network.
"""

import json
import os
import stat

import pytest

from tooling.harness.agent.adapter import (
    SESSION_TIMEOUT_EXIT_CODE,
    SessionConfig,
)
from tooling.harness.agent.opencode_adapter import OpenCodeAdapter
from tooling.harness.config import default_reference_root
from tooling.harness.runtime.run_dir import create_run_dir
from tooling.harness.util.proc import CommandLog

FAKE_VERSION = "9.9.9-fake"
FAKE_SESSION_ID = "fake-session-123"

FAKE_OPENCODE = """#!/usr/bin/env python3
import json
import os
import signal
import sys
import time

def _term(signum, frame):
    sys.exit(0)

signal.signal(signal.SIGTERM, _term)

argv = sys.argv[1:]

if argv and argv[0] == "--version":
    print(%(version)r)
    sys.exit(0)

if argv and argv[0] == "export":
    sid = argv[1]
    if os.environ.get("FAKE_OPENCODE_EXPORT_EXIT") == "1":
        print("export failed", file=sys.stderr)
        sys.exit(7)
    print(json.dumps({"type": "session.export", "sessionID": sid,
                      "messages": [{"role": "assistant", "content": "done"}]}))
    sys.stdout.flush()
    sys.exit(0)

if argv and argv[0] == "run":
    print("CWD=" + os.getcwd(), file=sys.stderr)
    print("PWD=" + os.environ.get("PWD", ""), file=sys.stderr)
    print("OPENCODE_PERMISSION=" + os.environ.get("OPENCODE_PERMISSION", ""),
          file=sys.stderr)
    if os.environ.get("FAKE_OPENCODE_NO_SESSION") != "1":
        print(json.dumps({"type": "session.ready",
                          "sessionID": %(session_id)r}))
        sys.stdout.flush()
    if os.environ.get("FAKE_OPENCODE_SLEEP"):
        time.sleep(float(os.environ["FAKE_OPENCODE_SLEEP"]))
    print(json.dumps({"type": "message.done"}))
    sys.stdout.flush()
    sys.exit(int(os.environ.get("FAKE_OPENCODE_EXIT", "0")))

print("unexpected argv: " + repr(argv), file=sys.stderr)
sys.exit(9)
""" % {
    "version": FAKE_VERSION,
    "session_id": FAKE_SESSION_ID,
}


@pytest.fixture
def fake_opencode(tmp_path, monkeypatch):
    """Install the hermetic fake ``opencode`` executable first on PATH."""
    bindir = tmp_path / "fake-bin"
    bindir.mkdir()
    script = bindir / "opencode"
    script.write_text(FAKE_OPENCODE, encoding="utf-8")
    script.chmod(stat.S_IRWXU)
    monkeypatch.setenv(
        "PATH", str(bindir) + os.pathsep + os.environ.get("PATH", "")
    )
    return script


def _config(tmp_path, **overrides) -> SessionConfig:
    run = create_run_dir("NOT-02", "demo-model", tmp_path / "runs")
    bundle = run.root / "protocol" / "prompt-bundle.txt"
    bundle.parent.mkdir(parents=True, exist_ok=True)
    bundle.write_text("bundle\n")
    values = dict(
        platform="opencode",
        agent_id="opencode",
        model="demo-model",
        prompt_bundle=bundle,
        workspace_read=run.workspace_read,
        workspace_work=run.workspace_work,
        timeout_seconds=60,
        command_log=CommandLog(run.root / "protocol" / "command.log"),
    )
    values.update(overrides)
    return SessionConfig(**values)


def _records(config: SessionConfig) -> list[dict]:
    return config.command_log.records()


# ----------------------------------------------------------------------
# argv construction (G7) and version preflight
# ----------------------------------------------------------------------


def test_version_preflight_and_argv_construction(tmp_path, fake_opencode):
    config = _config(tmp_path)
    result = OpenCodeAdapter().launch(config)

    assert result.platform_version == FAKE_VERSION
    records = _records(config)
    assert len(records) == 3  # version + run + export

    version_record = records[0]
    assert "--version" in version_record["command"]

    run_record = records[1]
    assert "opencode run" in run_record["command"]
    # G7: the explicit --dir flag names the writable working directory
    assert "--dir" in run_record["command"]
    assert str(config.workspace_work) in run_record["command"]
    assert "--format" in run_record["command"]
    assert "bundle" in run_record["command"]  # the prompt bundle message

    export_record = records[2]
    assert "opencode export" in export_record["command"]
    assert FAKE_SESSION_ID in export_record["command"]


def test_model_flag_when_configured(tmp_path, fake_opencode):
    config = _config(tmp_path, launch_model="some/provider-model")
    OpenCodeAdapter().launch(config)
    run_record = _records(config)[1]
    assert "--model" in run_record["command"]
    assert "some/provider-model" in run_record["command"]


def test_model_flag_absent_when_not_configured(tmp_path, fake_opencode):
    config = _config(tmp_path)
    OpenCodeAdapter().launch(config)
    assert "--model" not in _records(config)[1]["command"]


# ----------------------------------------------------------------------
# permission environment (G1)
# ----------------------------------------------------------------------


def _permission_from_stderr(result) -> dict:
    for line in result.stderr.splitlines():
        if line.startswith("OPENCODE_PERMISSION="):
            return json.loads(line.split("=", 1)[1])
    raise AssertionError("OPENCODE_PERMISSION missing from session stderr")


def test_run_scoped_permission_policy(tmp_path, fake_opencode):
    config = _config(tmp_path)
    result = OpenCodeAdapter().launch(config)

    policy = _permission_from_stderr(result)
    external = policy["external_directory"]
    work = str(config.workspace_work.resolve())
    read = str(config.workspace_read.resolve())
    reference = str(default_reference_root().resolve())

    # read access: only the work and read trees of this run
    assert external[f"{work}/**"] == "allow"
    assert external[f"{read}/**"] == "allow"
    # explicit deny of the real bga-mercurio checkout
    assert external[f"{reference}/**"] == "deny"
    # every other external path is not allowed (headless ask => denied)

    # write access: the project root (work) stays writable by default;
    # every external path is explicitly denied
    edit = policy["edit"]
    assert edit[f"{read}/**"] == "deny"
    assert edit[f"{reference}/**"] == "deny"

    # network is disabled: webfetch and websearch are denied
    assert policy["webfetch"] == "deny"
    assert policy["websearch"] == "deny"


def test_session_runs_inside_working_directory(tmp_path, fake_opencode):
    config = _config(tmp_path)
    result = OpenCodeAdapter().launch(config)

    work = str(config.workspace_work.resolve())
    assert f"CWD={work}" in result.stderr
    assert f"PWD={work}" in result.stderr


def test_global_opencode_config_untouched(tmp_path, fake_opencode):
    config = _config(tmp_path)
    OpenCodeAdapter().launch(config)
    policy = _permission_from_stderr(OpenCodeAdapter().launch(config))
    # the policy is delivered inline via the environment, so no config
    # file is written anywhere (work tree contains only the bundle)
    assert list(config.workspace_work.iterdir()) == []


# ----------------------------------------------------------------------
# session-id parsing, export, transcript (G5)
# ----------------------------------------------------------------------


def test_transcript_captured_from_export(tmp_path, fake_opencode):
    config = _config(tmp_path)
    result = OpenCodeAdapter().launch(config)

    assert FAKE_SESSION_ID in result.transcript
    assert "session.export" in result.transcript
    assert "session.ready" in result.raw_response
    assert "message.done" in result.raw_response
    assert result.exit_code == 0


def test_missing_session_id_skips_export(tmp_path, fake_opencode, monkeypatch):
    monkeypatch.setenv("FAKE_OPENCODE_NO_SESSION", "1")
    config = _config(tmp_path)
    result = OpenCodeAdapter().launch(config)

    assert result.transcript == ""
    assert FAKE_SESSION_ID not in result.raw_response
    commands = [r["command"] for r in _records(config)]
    assert not any("export" in c for c in commands)


def test_export_failure_leaves_absent_transcript(tmp_path, fake_opencode, monkeypatch):
    monkeypatch.setenv("FAKE_OPENCODE_EXPORT_EXIT", "1")
    config = _config(tmp_path)
    result = OpenCodeAdapter().launch(config)

    assert result.transcript == ""
    export_record = _records(config)[2]
    assert "opencode export" in export_record["command"]
    assert export_record["exit_code"] == 7


# ----------------------------------------------------------------------
# failure handling and timeout (G5)
# ----------------------------------------------------------------------


def test_nonzero_exit_propagates_with_artifacts(tmp_path, fake_opencode, monkeypatch):
    monkeypatch.setenv("FAKE_OPENCODE_EXIT", "3")
    config = _config(tmp_path)
    result = OpenCodeAdapter().launch(config)

    assert result.exit_code == 3
    assert not result.is_timeout()
    # raw output and transcript are retained for the failed session
    assert FAKE_SESSION_ID in result.raw_response
    assert FAKE_SESSION_ID in result.transcript


def test_timeout_retains_partial_output_and_exports_transcript(
    tmp_path, fake_opencode, monkeypatch
):
    """G5: after a timeout kill the transcript export is still attempted
    whenever a session ID was produced."""
    monkeypatch.setenv("FAKE_OPENCODE_SLEEP", "60")
    config = _config(tmp_path, timeout_seconds=1)
    result = OpenCodeAdapter().launch(config)

    assert result.exit_code == SESSION_TIMEOUT_EXIT_CODE
    assert result.is_timeout()
    # partial stdout produced before the kill is retained
    assert FAKE_SESSION_ID in result.raw_response
    # the export was attempted and the recoverable transcript captured
    assert FAKE_SESSION_ID in result.transcript
    assert "session.export" in result.transcript


def test_timeout_with_unrecoverable_export(tmp_path, fake_opencode, monkeypatch):
    monkeypatch.setenv("FAKE_OPENCODE_SLEEP", "60")
    monkeypatch.setenv("FAKE_OPENCODE_EXPORT_EXIT", "1")
    config = _config(tmp_path, timeout_seconds=1)
    result = OpenCodeAdapter().launch(config)

    assert result.is_timeout()
    assert result.transcript == ""
    export_record = _records(config)[2]
    assert export_record["exit_code"] == 7


# ----------------------------------------------------------------------
# capabilities (G6)
# ----------------------------------------------------------------------


def test_temperature_capability_recorded(tmp_path, fake_opencode):
    config = _config(tmp_path)
    result = OpenCodeAdapter().launch(config)

    assert result.capabilities is not None
    temperature = result.capabilities["temperature"]
    assert temperature["supported"] is False
    assert "temperature" in temperature["note"]
    assert "5.5" in temperature["policy"]
