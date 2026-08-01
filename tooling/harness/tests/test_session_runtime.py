"""Unit tests for the session runtime (MVB-011) and capture (MVB-012)."""

import io
import json

import pytest

from tooling.harness.agent.adapter import AdapterError, AgentAdapter, SessionConfig, SessionResult
from tooling.harness.agent.runtime import (
    P2_BUDGET_SECONDS,
    SessionRuntimeError,
    p3_budget_seconds,
    run_session,
    verify_boundaries,
)
from tooling.harness.runtime.run_dir import create_run_dir
from tooling.harness.runtime.manifest import RunManifest, new_run_manifest
from tooling.harness.runtime.status import RunStatus
from tooling.harness.util.log import HarnessLog
from tooling.harness.util.proc import CommandLog

AT = "2026-07-31T12:00:00Z"


def _prepared_run(tmp_path, *, status="READY"):
    run = create_run_dir("NOT-02", "demo-model", tmp_path / "runs")
    manifest = new_run_manifest(run.run_id, "NOT-02", model_id="demo-model",
                                started_at=AT)
    manifest.update(prompt_bundle_sha256="ab" * 32)
    (run.root / "protocol").mkdir(exist_ok=True)
    (run.root / "protocol" / "prompt-bundle.txt").write_text("bundle\n")
    manifest.save(run.manifest_path)
    run_status = RunStatus(run_id=run.run_id, updated_at=AT)
    if status == "READY":
        run_status.transition("READY", checkpoint="p1", at=AT)
    elif status == "RUNNING":
        run_status.transition("READY", checkpoint="p1", at=AT)
        run_status.transition("RUNNING", checkpoint="p2", at=AT)
    run_status.save(run.status_path)
    return run, manifest, run_status


class FakeAdapter(AgentAdapter):
    """Scriptable adapter for lifecycle tests."""

    platform = "fake"

    def __init__(self, *, result=None, error=None, interrupt=False):
        self._result = result
        self._error = error
        self._interrupt = interrupt
        self.launches = 0

    def launch(self, config: SessionConfig) -> SessionResult:
        self.launches += 1
        if self._interrupt:
            raise KeyboardInterrupt
        if self._error:
            raise self._error
        # a faithful adapter executes its commands through the command log
        config.command_log.run(["true"])
        return self._result


def _ok_result(**overrides):
    defaults = dict(
        exit_code=0, started_at=AT, ended_at=AT, duration_seconds=1.0,
        stdout="out", stderr="", raw_response="raw", transcript="transcript",
        platform="fake", agent_id="fake", model="demo-model",
    )
    defaults.update(overrides)
    return SessionResult(**defaults)


def _timeout_result():
    return _ok_result(exit_code=-1)


def _run(tmp_path, adapter, **kwargs):
    run, manifest, run_status = _prepared_run(tmp_path)
    log = HarnessLog(stream=io.StringIO())
    command_log = CommandLog(run.root / "protocol" / "command.log")
    return run_session(
        run, manifest, run_status,
        adapter=adapter,
        timeout_seconds=60,
        log=log,
        command_log=command_log,
        **kwargs,
    ), run, manifest, run_status


# ----------------------------------------------------------------------
# P3 budget formula (§5.2)
# ----------------------------------------------------------------------


def test_p3_budget_from_effort():
    assert p3_budget_seconds("1-2 hours") == 3 * 3600
    assert p3_budget_seconds("3-5 hours") == 8 * 3600
    assert p3_budget_seconds("8-10 hours") == 15 * 3600
    assert p3_budget_seconds("11-12 hours") == 16 * 3600  # capped at 16h
    assert p3_budget_seconds("1 hour") == 2 * 3600  # min 2h


def test_p3_budget_override():
    assert p3_budget_seconds("1-2 hours", override_minutes=1) == 60
    assert p3_budget_seconds("1-2 hours", override_minutes=0.5) == 30


def test_p3_budget_unparseable_raises():
    with pytest.raises(SessionRuntimeError):
        p3_budget_seconds("not an estimate")


# ----------------------------------------------------------------------
# Boundary verification
# ----------------------------------------------------------------------


def test_verify_boundaries_passes_on_prepared_workspace(tmp_path):
    run, _, _ = _prepared_run(tmp_path)
    (run.workspace_read / "f.txt").write_text("x")
    run.workspace_read.chmod(0o555)
    (run.workspace_read / "f.txt").chmod(0o444)
    assert verify_boundaries(run) == []


def test_verify_boundaries_detects_writable_read(tmp_path):
    run, _, _ = _prepared_run(tmp_path)
    (run.workspace_read / "f.txt").write_text("x")  # default modes
    failures = verify_boundaries(run)
    assert any("not read-only" in f for f in failures)


# ----------------------------------------------------------------------
# Lifecycle
# ----------------------------------------------------------------------


def test_completed_session(tmp_path):
    outcome, run, manifest, run_status = _run(
        tmp_path, FakeAdapter(result=_ok_result())
    )
    assert outcome["exit_status"] == "completed"
    assert run_status.status == "COMPLETED"
    assert run_status.checkpoints.get("p2") and run_status.checkpoints.get("p3")
    assert manifest.prompt_bundle_sha256
    assert manifest.execution["exit_status"] == "completed"
    assert manifest.execution["restarts"] == 0
    assert manifest.execution["timeouts"] == {
        "p2_budget_seconds": P2_BUDGET_SECONDS, "p3_budget_seconds": 60
    }
    assert manifest.timeouts["p3_budget_seconds"] == 60
    assert manifest.phases["p3"].started_at and manifest.phases["p3"].ended_at
    # raw artifacts captured
    session_dir = run.root / "protocol" / "session"
    assert (session_dir / "transcript.txt").read_text() == "transcript"
    assert (session_dir / "raw-response.txt").read_text() == "raw"
    assert (session_dir / "session.json").is_file()
    assert (session_dir / "intake.json").is_file()
    # command log recorded
    assert (run.root / "protocol" / "command.log").is_file()
    assert manifest.execution["session_hashes"]["transcript"]
    # status.json written
    assert RunStatus.load(run.status_path).status == "COMPLETED"


def test_timeout_session_retains_partial_work(tmp_path):
    run, manifest, run_status = _prepared_run(tmp_path)
    (run.workspace_work / "partial.txt").write_text("partial work\n")
    log = HarnessLog(stream=io.StringIO())
    outcome = run_session(
        run, manifest, run_status,
        adapter=FakeAdapter(result=_timeout_result()),
        timeout_seconds=60,
        log=log,
        command_log=CommandLog(run.root / "protocol" / "command.log"),
    )
    assert outcome["exit_status"] == "timeout"
    assert run_status.status == "TIMEOUT"
    assert (run.workspace_work / "partial.txt").is_file()
    assert manifest.execution["exit_code"] == -1
    assert manifest.submission_status == "partial"


def test_launch_failure_blocks_run(tmp_path):
    outcome, _, _, run_status = _run(
        tmp_path, FakeAdapter(error=AdapterError("platform unavailable"))
    )
    assert outcome["exit_status"] == "failed"
    assert run_status.status == "BLOCKED"
    assert "platform unavailable" in run_status.blocked_reason


def test_operator_interrupt_aborts_run(tmp_path):
    outcome, _, _, run_status = _run(
        tmp_path, FakeAdapter(interrupt=True)
    )
    assert outcome["exit_status"] == "aborted"
    assert run_status.status == "ABORTED"
    assert "interrupted" in run_status.blocked_reason


def test_nonzero_exit_aborts_run(tmp_path):
    outcome, _, _, run_status = _run(
        tmp_path, FakeAdapter(result=_ok_result(exit_code=2))
    )
    assert outcome["exit_status"] == "failed"
    assert run_status.status == "ABORTED"
    assert "exit code 2" in run_status.blocked_reason


def test_running_run_resumes_with_restart(tmp_path):
    run, manifest, run_status = _prepared_run(tmp_path, status="RUNNING")
    manifest.start_phase("p3", at=AT)
    manifest.save(run.manifest_path)
    log = HarnessLog(stream=io.StringIO())
    adapter = FakeAdapter(result=_ok_result())
    outcome = run_session(
        run, manifest, run_status,
        adapter=adapter,
        timeout_seconds=60,
        log=log,
        command_log=CommandLog(run.root / "protocol" / "command.log"),
    )
    assert outcome["exit_status"] == "completed"
    assert outcome["metadata"]["restarts"] == 1
    assert manifest.execution["restarts"] == 1
    # P0-P2 phase records untouched
    assert manifest.phases["p0"].started_at == AT
    assert "p1" not in manifest.phases
    # p3 kept its original started_at (not re-executed)
    assert manifest.phases["p3"].started_at == AT


def test_session_requires_prompt_bundle(tmp_path):
    run, manifest, run_status = _prepared_run(tmp_path)
    manifest.update(prompt_bundle_sha256=None)
    manifest.save(run.manifest_path)
    with pytest.raises(SessionRuntimeError, match="no prompt bundle"):
        run_session(
            run, manifest, run_status,
            adapter=FakeAdapter(result=_ok_result()),
            timeout_seconds=60,
            log=HarnessLog(stream=io.StringIO()),
            command_log=CommandLog(run.root / "protocol" / "command.log"),
        )


def test_session_requires_read_only_workspace(tmp_path):
    run, manifest, run_status = _prepared_run(tmp_path)
    (run.workspace_read / "w.txt").write_text("w")  # writable read dir
    with pytest.raises(SessionRuntimeError, match="boundary"):
        run_session(
            run, manifest, run_status,
            adapter=FakeAdapter(result=_ok_result()),
            timeout_seconds=60,
            log=HarnessLog(stream=io.StringIO()),
            command_log=CommandLog(run.root / "protocol" / "command.log"),
        )


def test_session_metadata_is_faithful_and_hashable(tmp_path):
    outcome, run, manifest, _ = _run(
        tmp_path, FakeAdapter(result=_ok_result())
    )
    session = json.loads(
        (run.root / "protocol" / "session" / "session.json").read_text()
    )
    assert session["exit_status"] == "completed"
    assert session["exit_code"] == 0
    assert session["environment"]["network"] == "disabled"
    assert session["artifacts"]["transcript"]["sha256"]
    assert session["artifacts"]["command_log"]["sha256"]
    # hashes recorded in the manifest match the artifact files
    from tooling.harness.util.hash import sha256_file

    assert manifest.execution["session_hashes"]["transcript"] == sha256_file(
        run.root / "protocol" / "session" / "transcript.txt"
    )
    assert manifest.execution["session_hashes"]["command_log"] == sha256_file(
        run.root / "protocol" / "command.log"
    )
