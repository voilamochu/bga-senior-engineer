"""Unit tests for MVB-009: prompt materialization from the pinned documents."""

import pytest

from tooling.harness.config import default_evaluation_docs
from tooling.harness.prompt.materialize import (
    TaskSectionError,
    default_attached_documents,
    extract_safety_section,
    materialize_benchmark_prompt,
    parse_corpus_task,
    parse_eval_task,
    primary_rule_files,
)

DOCS = default_evaluation_docs()
SYSTEM_PROMPT = """# System Prompt

## Role

Agent role text.

## Repository Safety Protocol (MANDATORY)

### 12.1 The Invariant

`bga-mercurio` is a STRICTLY READ-ONLY reference repository.

**Prohibited:** modifying, creating, deleting, or renaming any file.

## Determinism

Temperature 0.
"""


def test_parse_corpus_not_02():
    task = parse_corpus_task(DOCS["corpus"], "NOT-02")
    assert task["id"] == "NOT-02"
    assert task["title"] == "Consolidate Duplicated Notification Blocks"
    assert task["category"] == "Notification"
    assert task["difficulty"] == "Easy"
    assert task["effort"] == "1-2 hours"
    assert "labOutputActivated" in task["background"]
    assert "cardKept" in task["background"]
    assert "exactly ONE helper method" in task["objective"]


def test_parse_eval_not_02():
    task = parse_eval_task(DOCS["evaluation"], "NOT-02")
    assert task["id"] == "NOT-02"
    assert task["title"] == "Consolidate Duplicated Notification Blocks"
    assert "exactly one helper" in task["expected_outcomes"]
    assert "labOutputActivated" in task["success_criteria"]
    assert "| 6 |" in task["success_criteria"]
    assert "Reasoning" in task["required_evidence"]
    assert "notifications.json" in task["primary_rules"]
    assert "notification-patterns.md" in task["key_standards"]


def test_parse_unknown_corpus_task_raises():
    with pytest.raises(TaskSectionError, match="not found"):
        parse_corpus_task(DOCS["corpus"], "ZZZ-99")


def test_parse_unknown_eval_task_raises():
    with pytest.raises(TaskSectionError, match="not found"):
        parse_eval_task(DOCS["evaluation"], "ZZZ-99")


def test_primary_rule_files():
    assert primary_rule_files("NOTF-001, NOTF-003, notifications.json") == [
        "notifications.json"
    ]
    assert primary_rule_files("nothing here") == []


def test_attached_documents_default():
    eval_task = parse_eval_task(DOCS["evaluation"], "NOT-02")
    documents = default_attached_documents(
        eval_task, skill_rule_files=primary_rule_files(eval_task["primary_rules"])
    )
    assert documents == [
        "docs/evaluation/benchmark-evaluation-spec.md",
        "docs/evaluation/benchmark-task-corpus.md",
        "bga-senior-engineer-skill/rules/notifications.json",
        "docs/standards/notification-patterns.md",
    ]


def test_extract_safety_section():
    section = extract_safety_section(SYSTEM_PROMPT)
    assert section.startswith("## Repository Safety Protocol (MANDATORY)")
    assert "STRICTLY READ-ONLY" in section
    assert "## Determinism" not in section


def test_extract_safety_section_missing_raises():
    with pytest.raises(ValueError):
        extract_safety_section("no safety here")


def test_materialize_contains_all_appendix_b_sections_in_order():
    corpus = parse_corpus_task(DOCS["corpus"], "NOT-02")
    eval_task = parse_eval_task(DOCS["evaluation"], "NOT-02")
    prompt = materialize_benchmark_prompt(
        corpus,
        eval_task,
        safety_section=extract_safety_section(SYSTEM_PROMPT),
        attached_documents=["docs/evaluation/benchmark-evaluation-spec.md"],
    )
    expected_order = [
        "# BGA Senior Engineer — Benchmark Task",
        "## Repository Safety (MANDATORY)",
        "## Task",
        "## Expected Outcomes",
        "## Success Criteria",
        "## Required Evidence",
        "## Environment",
        "## Submission",
        "## Attached Documents",
    ]
    positions = [prompt.index(section) for section in expected_order]
    assert positions == sorted(positions)
    assert "### 12.1 The Invariant" in prompt
    assert "- Reference codebase: `workspace/read/` (READ ONLY)" in prompt
    assert "- Working directory: `workspace/work/` (WRITABLE)" in prompt
    assert "- Prohibited: any write to bga-mercurio or any read-only path" in prompt
    assert "- declaration.json" in prompt
    assert "- docs/evaluation/benchmark-evaluation-spec.md" in prompt


def test_materialize_is_deterministic():
    corpus = parse_corpus_task(DOCS["corpus"], "NOT-02")
    eval_task = parse_eval_task(DOCS["evaluation"], "NOT-02")
    kwargs = dict(
        safety_section=extract_safety_section(SYSTEM_PROMPT),
        attached_documents=["a.md", "b.md"],
    )
    assert materialize_benchmark_prompt(corpus, eval_task, **kwargs) == (
        materialize_benchmark_prompt(corpus, eval_task, **kwargs)
    )
