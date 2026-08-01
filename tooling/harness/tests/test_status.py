"""Unit tests for MVB-002: status schema and the §2.0.2 status graph."""

import json

import pytest

from tooling.harness.runtime.status import (
    INITIAL_STATUS,
    STATUS_SCHEMA,
    VALID_STATUSES,
    InvalidStatusError,
    InvalidTransitionError,
    RunStatus,
)
from tooling.harness.util.clock import is_iso_utc

RUN_ID = "run-NOT-02-demo-model-20260731T120000Z-00"
AT = "2026-07-31T12:00:00Z"


@pytest.fixture
def status():
    return RunStatus(run_id=RUN_ID)


def test_initial_status_is_initializing(status):
    assert status.status == INITIAL_STATUS == "INITIALIZING"
    assert status.to_dict()["status"] == "INITIALIZING"


def test_full_happy_path_transitions(status):
    status.transition("READY", at=AT)
    status.transition("RUNNING", at=AT)
    status.transition("COMPLETED", at=AT)
    status.transition("VERDICTED", at=AT)
    status.transition("ARCHIVED", at=AT)
    assert status.status == "ARCHIVED"
    assert status.is_terminal()


def test_timeout_path(status):
    status.transition("READY", at=AT)
    status.transition("RUNNING", at=AT)
    status.transition("TIMEOUT", at=AT)
    status.transition("VERDICTED", at=AT)


def test_rejection_path(status):
    status.transition("READY", at=AT)
    status.transition("RUNNING", at=AT)
    status.transition("COMPLETED", at=AT)
    status.transition("REJECTED", at=AT)
    status.transition("ARCHIVED", at=AT)


def test_abort_path_from_running(status):
    status.transition("READY", at=AT)
    status.transition("RUNNING", at=AT)
    status.transition("ABORTED", at=AT)
    status.transition("ARCHIVED", at=AT)


def test_blocked_retry_path(status):
    status.transition("BLOCKED", at=AT)
    status.transition("READY", at=AT)


def test_illegal_transitions_raise(status):
    with pytest.raises(InvalidTransitionError):
        status.transition("RUNNING")
    with pytest.raises(InvalidTransitionError):
        status.transition("ARCHIVED")
    with pytest.raises(InvalidTransitionError):
        status.transition("INITIALIZING")  # self-transition


def test_illegal_transition_from_completed(status):
    status.transition("READY", at=AT)
    status.transition("RUNNING", at=AT)
    status.transition("COMPLETED", at=AT)
    with pytest.raises(InvalidTransitionError):
        status.transition("READY")


def test_terminal_status_rejects_all_transitions(status):
    status.transition("READY", at=AT)
    status.transition("RUNNING", at=AT)
    status.transition("COMPLETED", at=AT)
    status.transition("VERDICTED", at=AT)
    status.transition("ARCHIVED", at=AT)
    for candidate in VALID_STATUSES:
        if candidate == "ARCHIVED":
            continue
        with pytest.raises(InvalidTransitionError):
            status.transition(candidate)


def test_unknown_status_raises(status):
    with pytest.raises(InvalidStatusError):
        status.transition("DONE")


def test_every_status_is_reachable_and_graph_is_complete():
    # every listed status appears as a key; successors are valid statuses
    assert set(TRANSITIONS_KEYS()) == set(VALID_STATUSES)
    for successors in transitions_values():
        for successor in successors:
            assert successor in VALID_STATUSES


def TRANSITIONS_KEYS():
    from tooling.harness.runtime.status import TRANSITIONS

    return TRANSITIONS.keys()


def transitions_values():
    from tooling.harness.runtime.status import TRANSITIONS

    return TRANSITIONS.values()


def test_checkpoint_recording(status):
    status.record_checkpoint("p0", at=AT)
    assert status.checkpoints == {"p0": AT}
    assert status.updated_at == AT
    assert is_iso_utc(status.checkpoints["p0"])
    with pytest.raises(InvalidStatusError):
        status.record_checkpoint("p99")


def test_transition_can_carry_checkpoint(status):
    status.transition("READY", checkpoint="p1", at=AT)
    assert status.checkpoints == {"p1": AT}


def test_round_trip(status):
    status.record_checkpoint("p0", at=AT)
    data = status.to_dict()
    assert data["schema"] == STATUS_SCHEMA
    assert data["status"] == "INITIALIZING"
    restored = RunStatus.from_dict(data)
    assert restored.to_dict() == data


def test_save_and_load(tmp_path, status):
    status.transition("READY", checkpoint="p1", at=AT)
    path = tmp_path / "status.json"
    status.save(path)
    loaded = RunStatus.load(path)
    assert loaded.to_dict() == status.to_dict()
    json.loads(path.read_text())


def test_save_is_deterministic(tmp_path, status):
    a, b = tmp_path / "a.json", tmp_path / "b.json"
    status.save(a)
    status.save(b)
    assert a.read_bytes() == b.read_bytes()


def test_load_rejects_bad_status(status):
    data = status.to_dict()
    data["status"] = "NOPE"
    with pytest.raises(InvalidStatusError):
        RunStatus.from_dict(data)


def test_load_rejects_bad_run_id(status):
    data = status.to_dict()
    data["run_id"] = "bad"
    with pytest.raises(InvalidStatusError):
        RunStatus.from_dict(data)


def test_load_rejects_bad_checkpoint(status):
    data = status.to_dict()
    data["checkpoints"] = {"p0": "not-a-timestamp"}
    with pytest.raises(InvalidStatusError):
        RunStatus.from_dict(data)
