"""Unit tests for submission intake (harness §3.6)."""

import json

from tooling.harness.agent.intake import (
    DECLARATION_FILE,
    REQUIRED_DOCUMENTS,
    intake_submission,
)

DOCS = ("reasoning.md", "architecture.md", "subsystems.md",
        "testing-evidence.md", "validation-evidence.md")


def _write_submission(work, *, status="complete", extra_issue=None):
    for name in DOCS:
        (work / name).write_text(f"content of {name}\n")
    (work / "changes").mkdir()
    (work / "changes" / "patch.diff").write_text("diff\n")
    declaration = {
        "task_id": "NOT-02",
        "status": status,
        "self_reported_time": "1.5h",
        "artifacts": list(DOCS) + ["changes/"],
    }
    if extra_issue:
        declaration["status"] = extra_issue
    (work / DECLARATION_FILE).write_text(json.dumps(declaration))


def test_complete_submission(tmp_path):
    work = tmp_path / "work"
    work.mkdir()
    _write_submission(work)
    record = intake_submission(work)
    assert record["status"] == "complete"
    assert len(record["found"]) == 7
    assert record["missing"] == []
    assert record["issues"] == []
    assert record["declaration"]["task_id"] == "NOT-02"


def test_partial_submission(tmp_path):
    work = tmp_path / "work"
    work.mkdir()
    (work / "reasoning.md").write_text("x")
    (work / DECLARATION_FILE).write_text(
        json.dumps({"task_id": "NOT-02", "status": "partial",
                    "self_reported_time": "1h", "artifacts": ["reasoning.md"]})
    )
    record = intake_submission(work)
    assert record["status"] == "partial"
    assert "architecture.md" in record["missing"]
    assert "changes" in record["missing"]
    assert record["issues"] == []


def test_missing_declaration(tmp_path):
    work = tmp_path / "work"
    work.mkdir()
    (work / "reasoning.md").write_text("x")
    record = intake_submission(work)
    assert record["status"] == "partial"
    assert record["declaration"] is None
    assert "declaration.json" in record["missing"]


def test_declaration_claims_complete_but_missing_items(tmp_path):
    work = tmp_path / "work"
    work.mkdir()
    _write_submission(work)
    (work / "validation-evidence.md").unlink()
    record = intake_submission(work)
    assert "validation-evidence.md" in record["missing"]
    assert any("claims 'complete'" in issue for issue in record["issues"])


def test_malformed_declaration(tmp_path):
    work = tmp_path / "work"
    work.mkdir()
    (work / DECLARATION_FILE).write_text("{not json")
    record = intake_submission(work)
    assert record["status"] == "partial"
    assert record["declaration"] is None
    assert any("not valid JSON" in issue for issue in record["issues"])


def test_declaration_missing_fields_and_bad_status(tmp_path):
    work = tmp_path / "work"
    work.mkdir()
    (work / DECLARATION_FILE).write_text(json.dumps({"task_id": "NOT-02"}))
    record = intake_submission(work)
    assert any("missing field 'status'" in issue for issue in record["issues"])
    assert any("missing field 'self_reported_time'" in issue for issue in record["issues"])


def test_intake_records_do_not_interpret_content(tmp_path):
    work = tmp_path / "work"
    work.mkdir()
    _write_submission(work)
    record = intake_submission(work)
    # found/missing are pure presence facts; no content is read or judged
    assert set(record["found"]) == set(REQUIRED_DOCUMENTS) | {"changes", "declaration.json"}
    assert record["missing"] == []
